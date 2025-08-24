<?php
/**
 * Authentication Integration Tests
 * Appeal Prospect MVP - Authentication System Testing
 */

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../app/auth.php';
        
        // Mock server environment
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testCompleteRegistrationFlow(): void
    {
        $email = 'newuser@example.com';
        $password = 'SecurePass123!';
        $name = 'New User';
        
        // Test registration
        $result = register_user($email, $password, $name);
        $this->assertTrue($result, 'User registration should succeed');
        
        // Verify user exists in database
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        $this->assertNotFalse($user, 'User should exist in database');
        $this->assertEquals($email, $user['email']);
        $this->assertEquals($name, $user['name']);
        $this->assertEquals(0, $user['is_admin']);
        $this->assertTrue(password_verify($password, $user['password_hash']));
        
        // Test duplicate registration
        $duplicateResult = register_user($email, $password, $name);
        $this->assertFalse($duplicateResult, 'Duplicate registration should fail');
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testCompleteLoginFlow(): void
    {
        // Create test user
        $email = 'logintest@example.com';
        $password = 'TestLogin123!';
        $name = 'Login Test User';
        
        $this->assertTrue(register_user($email, $password, $name));
        
        // Test successful login
        TestUtils::startTestSession();
        $this->setCSRFToken();
        
        $loginResult = login_user($email, $password);
        $this->assertTrue($loginResult, 'Login should succeed with valid credentials');
        
        // Check session variables
        $this->assertEquals($email, $_SESSION['user_email']);
        $this->assertEquals($name, $_SESSION['user_name']);
        $this->assertFalse($_SESSION['is_admin']);
        $this->assertArrayHasKey('user_id', $_SESSION);
        
        // Test login state functions
        $this->assertTrue(is_logged_in());
        $this->assertFalse(current_is_admin());
        $this->assertNotNull(current_user_id());
        $this->assertNotNull(current_user());
        
        // Verify last_login_at was updated
        $stmt = $this->db->prepare('SELECT last_login_at FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $lastLogin = $stmt->fetchColumn();
        $this->assertNotNull($lastLogin);
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testLoginWithInvalidCredentials(): void
    {
        $email = 'invalid@example.com';
        $password = 'WrongPassword123!';
        
        TestUtils::startTestSession();
        $this->setCSRFToken();
        
        $result = login_user($email, $password);
        $this->assertFalse($result, 'Login should fail with invalid credentials');
        
        // Verify session is not set
        $this->assertFalse(is_logged_in());
        $this->assertNull(current_user_id());
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testLoginRateLimiting(): void
    {
        $email = 'ratelimit@example.com';
        $password = 'TestPassword123!';
        $name = 'Rate Limit Test';
        
        // Register user
        $this->assertTrue(register_user($email, $password, $name));
        
        TestUtils::startTestSession();
        $this->setCSRFToken();
        
        // Attempt multiple failed logins
        for ($i = 0; $i < 5; $i++) {
            $result = login_user($email, 'WrongPassword');
            $this->assertFalse($result);
        }
        
        // Next attempt should be rate limited
        $result = login_user($email, $password);
        $this->assertFalse($result, 'Login should be rate limited after too many attempts');
        
        // Clear rate limit and try again
        clear_login_attempts($email);
        $result = login_user($email, $password);
        $this->assertTrue($result, 'Login should succeed after clearing rate limit');
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testLogoutFlow(): void
    {
        // Create and login user
        $userId = $this->createTestUser();
        $this->loginUser($userId);
        
        $this->assertTrue(is_logged_in());
        
        // Test logout
        logout_user();
        
        $this->assertFalse(is_logged_in());
        $this->assertNull(current_user_id());
        $this->assertEmpty($_SESSION);
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testSessionSecurity(): void
    {
        TestUtils::startTestSession();
        
        // Initialize session security
        initialize_session_security();
        
        $this->assertArrayHasKey('session_token', $_SESSION);
        $this->assertArrayHasKey('session_fingerprint', $_SESSION);
        $this->assertArrayHasKey('session_created', $_SESSION);
        $this->assertArrayHasKey('session_last_activity', $_SESSION);
        
        // Test session validation
        $this->assertTrue(validate_session_security());
        
        // Test session timeout
        $this->assertFalse(check_session_timeout());
        
        // Simulate old session
        $_SESSION['session_last_activity'] = time() - 7201; // 2+ hours ago
        $this->assertTrue(check_session_timeout());
        
        // Test activity update
        $oldActivity = $_SESSION['session_last_activity'];
        update_session_activity();
        $this->assertGreaterThan($oldActivity, $_SESSION['session_last_activity']);
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testCSRFProtection(): void
    {
        TestUtils::startTestSession();
        
        // Generate CSRF token
        $token = generate_csrf_token();
        $this->assertNotEmpty($token);
        $this->assertEquals($token, $_SESSION['csrf_token']);
        
        // Test valid token
        $_POST['csrf_token'] = $token;
        $this->assertTrue(validate_csrf());
        
        // Test invalid token
        $_POST['csrf_token'] = 'invalid_token';
        $this->assertFalse(validate_csrf());
        
        // Test missing token
        unset($_POST['csrf_token']);
        $this->assertFalse(validate_csrf());
        
        // Test token verification
        $this->assertTrue(verify_csrf_token($token));
        $this->assertFalse(verify_csrf_token('invalid'));
        
        // Test CSRF field generation
        $field = csrf_field();
        $this->assertStringContainsString('csrf_token', $field);
        $this->assertStringContainsString($token, $field);
        $this->assertStringContainsString('type="hidden"', $field);
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testPasswordHashing(): void
    {
        $password = 'TestPassword123!';
        
        // Test hashing
        $hash = hash_password($password);
        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        
        // Test verification
        $this->assertTrue(verify_password($password, $hash));
        $this->assertFalse(verify_password('WrongPassword', $hash));
        
        // Test that each hash is unique (salt)
        $hash2 = hash_password($password);
        $this->assertNotEquals($hash, $hash2);
        $this->assertTrue(verify_password($password, $hash2));
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testUserAccessControl(): void
    {
        // Create regular user and admin user
        $userId = $this->createTestUser(['is_admin' => 0]);
        $adminId = $this->createTestUser(['is_admin' => 1, 'email' => 'admin@test.com']);
        
        // Create test case
        $caseId = $this->createTestCase($userId);
        
        // Test regular user access
        $this->loginUser($userId, false);
        $this->assertTrue(user_can_access_case($caseId));
        $this->assertFalse(current_is_admin());
        
        // Test admin access
        $this->loginUser($adminId, true);
        $this->assertTrue(user_can_access_case($caseId));
        $this->assertTrue(current_is_admin());
        
        // Test unauthorized access
        $otherUserId = $this->createTestUser(['email' => 'other@test.com']);
        $this->loginUser($otherUserId, false);
        $this->assertFalse(user_can_access_case($caseId));
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testRequireLoginRedirection(): void
    {
        // Test redirect when not logged in
        $_SERVER['REQUEST_URI'] = '/protected-page';
        
        $this->captureOutput(function() {
            try {
                require_login('/login.php');
                $this->fail('Should have redirected');
            } catch (Exception $e) {
                // Expected - header() calls exit
            }
        });
        
        // Check that redirect destination was stored
        $this->assertEquals('/protected-page', $_SESSION['redirect_after_login']);
        
        // Test no redirect when logged in
        $this->loginUser($this->createTestUser());
        
        // Should not throw exception or redirect
        require_login('/login.php');
        $this->assertTrue(true); // If we get here, no redirect occurred
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testRequireAdminAccess(): void
    {
        // Test redirect for regular user
        $userId = $this->createTestUser(['is_admin' => 0]);
        $this->loginUser($userId, false);
        
        $this->captureOutput(function() {
            try {
                require_admin('/index.php');
                $this->fail('Should have redirected');
            } catch (Exception $e) {
                // Expected - header() calls exit
            }
        });
        
        // Test no redirect for admin
        $adminId = $this->createTestUser(['is_admin' => 1, 'email' => 'admin@test.com']);
        $this->loginUser($adminId, true);
        
        // Should not throw exception or redirect
        require_admin('/index.php');
        $this->assertTrue(true); // If we get here, no redirect occurred
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testCurrentUserFunction(): void
    {
        $userData = [
            'email' => 'currentuser@test.com',
            'name' => 'Current User Test',
            'is_admin' => 0
        ];
        
        $userId = $this->createTestUser($userData);
        $this->loginUser($userId, false);
        
        $currentUser = current_user();
        $this->assertNotNull($currentUser);
        $this->assertEquals($userId, $currentUser['id']);
        $this->assertEquals($userData['email'], $currentUser['email']);
        $this->assertEquals($userData['name'], $currentUser['name']);
        $this->assertEquals($userData['is_admin'], $currentUser['is_admin']);
        $this->assertArrayHasKey('created_at', $currentUser);
        
        // Test when not logged in
        TestUtils::clearSession();
        $this->assertNull(current_user());
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testStartSecureSession(): void
    {
        // Mock HTTPS environment
        $_SERVER['HTTPS'] = 'on';
        
        $this->captureOutput(function() {
            start_session();
        });
        
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        
        // Verify session configuration
        $this->assertEquals('1', ini_get('session.cookie_httponly'));
        $this->assertEquals('Lax', ini_get('session.cookie_samesite'));
        $this->assertEquals('1', ini_get('session.use_only_cookies'));
        
        // CSRF token should be generated
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertArrayHasKey('csrf_token_time', $_SESSION);
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testSessionRegeneration(): void
    {
        TestUtils::startTestSession();
        $originalId = session_id();
        
        regenerate_secure_session_id();
        
        $newId = session_id();
        $this->assertNotEquals($originalId, $newId);
    }
    
    /**
     * @group integration
     * @group authentication
     */
    public function testCompleteAuthenticationWorkflow(): void
    {
        // 1. User registration
        $email = 'workflow@test.com';
        $password = 'WorkflowTest123!';
        $name = 'Workflow Test User';
        
        $this->assertTrue(register_user($email, $password, $name));
        
        // 2. Session start
        TestUtils::startTestSession();
        $this->captureOutput(function() {
            start_session();
        });
        
        // 3. Login
        $this->assertTrue(login_user($email, $password));
        $this->assertTrue(is_logged_in());
        
        // 4. Access protected resource
        $this->assertNotNull(current_user());
        
        // 5. Create case (user owns resource)
        $caseId = $this->createTestCase(current_user_id());
        $this->assertTrue(user_can_access_case($caseId));
        
        // 6. CSRF protection
        $token = generate_csrf_token();
        $_POST['csrf_token'] = $token;
        $this->assertTrue(validate_csrf());
        
        // 7. Session security validation
        $this->assertTrue(validate_session_security());
        $this->assertFalse(check_session_timeout());
        
        // 8. Logout
        logout_user();
        $this->assertFalse(is_logged_in());
    }
    
    /**
     * @group integration
     * @group authentication
     * @group performance
     */
    public function testAuthenticationPerformance(): void
    {
        // Registration performance
        $this->assertExecutionTime(function() {
            register_user('perf@test.com', 'PerfTest123!', 'Performance Test');
        }, 2.0); // Allow 2 seconds for password hashing
        
        // Login performance
        TestUtils::startTestSession();
        $this->setCSRFToken();
        
        $this->assertExecutionTime(function() {
            login_user('perf@test.com', 'PerfTest123!');
        }, 2.0); // Allow 2 seconds for password verification
        
        // Session validation performance
        $this->assertExecutionTime(function() {
            validate_session_security();
        }, 0.1);
        
        // CSRF validation performance
        $_POST['csrf_token'] = $_SESSION['csrf_token'];
        $this->assertExecutionTime(function() {
            validate_csrf();
        }, 0.1);
    }
}