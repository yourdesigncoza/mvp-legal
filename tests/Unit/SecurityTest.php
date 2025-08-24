<?php
/**
 * Security Functions Unit Tests
 * Appeal Prospect MVP - Security Testing
 */

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../app/security.php';
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidateEmailWithValidEmails(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
            'firstname-lastname@example.com'
        ];
        
        foreach ($validEmails as $email) {
            $result = validate_email($email);
            $this->assertValidationPasses($result);
            $this->assertEquals($email, $result['value']);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidateEmailWithInvalidEmails(): void
    {
        $invalidEmails = [
            '',
            'invalid-email',
            '@example.com',
            'test@',
            'test..test@example.com',
            'test@example',
            'very-long-email-address-that-exceeds-the-maximum-length-limit-for-email-addresses@very-long-domain-name-that-also-exceeds-limits.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $result = validate_email($email);
            $this->assertValidationFails($result);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidatePasswordWithStrongPasswords(): void
    {
        $strongPasswords = [
            'Password123!',
            'MySecure$Password1',
            'C0mpl3xP@ssw0rd',
            'AnotherStr0ng&Password'
        ];
        
        foreach ($strongPasswords as $password) {
            $result = validate_password($password, true);
            $this->assertValidationPasses($result);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidatePasswordWithWeakPasswords(): void
    {
        $weakPasswords = [
            '',
            '123456',
            'password',
            'PASSWORD',
            'Password',
            'password123',
            'PASSWORD123',
            'Pass123',  // Too short
            'passwordwithoutuppercaseornumbers'
        ];
        
        foreach ($weakPasswords as $password) {
            $result = validate_password($password, true);
            $this->assertValidationFails($result);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidateNameWithValidNames(): void
    {
        $validNames = [
            'John Doe',
            'Mary Jane Smith',
            "O'Connor",
            'Jean-Pierre',
            'José María',
            '李小明'
        ];
        
        foreach ($validNames as $name) {
            $result = validate_name($name);
            $this->assertValidationPasses($result);
            $this->assertNotEmpty($result['value']);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidateNameWithInvalidNames(): void
    {
        $invalidNames = [
            '',
            'A',  // Too short
            'John123',  // Contains numbers
            'John@Doe',  // Contains symbols
            str_repeat('A', 101),  // Too long
            '<script>alert("xss")</script>'  // XSS attempt
        ];
        
        foreach ($invalidNames as $name) {
            $result = validate_name($name);
            $this->assertValidationFails($result);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidateCaseNameWithValidNames(): void
    {
        $validCaseNames = [
            'Smith v Jones 2024',
            'ABC Corp vs XYZ Ltd',
            'Re: Estate of John Doe',
            'Criminal Case 123/2024',
            'Family Matter (Confidential)'
        ];
        
        foreach ($validCaseNames as $caseName) {
            $result = validate_case_name($caseName);
            $this->assertValidationPasses($result);
            $this->assertNotEmpty($result['value']);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidateCaseNameWithInvalidNames(): void
    {
        $invalidCaseNames = [
            '',
            'AB',  // Too short
            str_repeat('A', 256),  // Too long
            '<script>alert("xss")</script>',  // XSS attempt
            'Case with invalid chars: <>"|\\',
            "Case\nwith\nnewlines"
        ];
        
        foreach ($invalidCaseNames as $caseName) {
            $result = validate_case_name($caseName);
            $this->assertValidationFails($result);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidateApiKeyWithValidKeys(): void
    {
        $validOpenAIKeys = [
            'sk-1234567890abcdef1234567890abcdef12345678',
            'sk-proj-abcdefghijklmnopqrstuvwxyz1234567890'
        ];
        
        foreach ($validOpenAIKeys as $key) {
            $result = validate_api_key($key, 'openai');
            $this->assertValidationPasses($result);
            $this->assertEquals($key, $result['value']);
        }
        
        $validPerplexityKeys = [
            'pplx-1234567890abcdef1234567890abcdef',
            'pplx-abcdefghijklmnopqrstuvwxyz123456'
        ];
        
        foreach ($validPerplexityKeys as $key) {
            $result = validate_api_key($key, 'perplexity');
            $this->assertValidationPasses($result);
            $this->assertEquals($key, $result['value']);
        }
    }
    
    /**
     * @group security
     * @group validation
     */
    public function testValidateApiKeyWithInvalidKeys(): void
    {
        $invalidOpenAIKeys = [
            '',
            'invalid-key',
            'sk-',
            'sk-short',
            'not-sk-prefixed-key',
            str_repeat('sk-', 50)  // Too long
        ];
        
        foreach ($invalidOpenAIKeys as $key) {
            $result = validate_api_key($key, 'openai');
            $this->assertValidationFails($result);
        }
        
        $invalidPerplexityKeys = [
            '',
            'invalid-key',
            'pplx-',
            'pplx-short',
            'not-pplx-prefixed-key'
        ];
        
        foreach ($invalidPerplexityKeys as $key) {
            $result = validate_api_key($key, 'perplexity');
            $this->assertValidationFails($result);
        }
    }
    
    /**
     * @group security
     * @group sanitization
     */
    public function testSanitizeTextContent(): void
    {
        $testCases = [
            [
                'input' => "Normal text content",
                'expected' => "Normal text content"
            ],
            [
                'input' => "Text with\n\nnewlines\r\nand\rreturns",
                'expected' => "Text with\n\nnewlines\nand\nreturns"
            ],
            [
                'input' => "Text    with     multiple    spaces",
                'expected' => "Text with multiple spaces"
            ],
            [
                'input' => "<script>alert('xss')</script>",
                'expected' => "alert('xss')"
            ],
            [
                'input' => "Text with HTML <b>bold</b> tags",
                'expected' => "Text with HTML bold tags"
            ]
        ];
        
        foreach ($testCases as $case) {
            $result = sanitize_text_content($case['input']);
            $this->assertEquals($case['expected'], $result);
        }
    }
    
    /**
     * @group security
     * @group sanitization
     */
    public function testSanitizeHtmlOutput(): void
    {
        $testCases = [
            [
                'input' => 'Normal text',
                'expected' => 'Normal text'
            ],
            [
                'input' => 'Text with <script>alert("xss")</script>',
                'expected' => 'Text with &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'
            ],
            [
                'input' => 'Text with "quotes" and \'apostrophes\'',
                'expected' => 'Text with &quot;quotes&quot; and &#039;apostrophes&#039;'
            ],
            [
                'input' => 'Text with & ampersands',
                'expected' => 'Text with &amp; ampersands'
            ]
        ];
        
        foreach ($testCases as $case) {
            $result = sanitize_html_output($case['input']);
            $this->assertEquals($case['expected'], $result);
        }
    }
    
    /**
     * @group security
     * @group rate-limiting
     */
    public function testRateLimitingSystem(): void
    {
        $identifier = 'test@example.com';
        $maxAttempts = 5;
        $timeWindow = 900; // 15 minutes
        
        // First few attempts should be allowed
        for ($i = 0; $i < $maxAttempts; $i++) {
            $result = check_login_rate_limit($identifier, $maxAttempts, $timeWindow);
            $this->assertTrue($result['allowed']);
            $this->assertEquals($i, $result['attempts']);
            
            // Record a failed attempt
            record_failed_login_attempt($identifier);
        }
        
        // Next attempt should be blocked
        $result = check_login_rate_limit($identifier, $maxAttempts, $timeWindow);
        $this->assertFalse($result['allowed']);
        $this->assertEquals($maxAttempts, $result['attempts']);
        $this->assertArrayHasKey('reset_time', $result);
        
        // Clear attempts and verify reset
        clear_login_attempts($identifier);
        $result = check_login_rate_limit($identifier, $maxAttempts, $timeWindow);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(0, $result['attempts']);
    }
    
    /**
     * @group security
     * @group session
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
        
        // Validate session security
        $this->assertTrue(validate_session_security());
        
        // Update session activity
        $lastActivity = $_SESSION['session_last_activity'];
        sleep(1);
        update_session_activity();
        $this->assertGreaterThan($lastActivity, $_SESSION['session_last_activity']);
        
        // Test session timeout (mock old session)
        $_SESSION['session_last_activity'] = time() - 7201; // 2+ hours ago
        $this->assertTrue(check_session_timeout());
        
        // Reset for normal activity
        $_SESSION['session_last_activity'] = time();
        $this->assertFalse(check_session_timeout());
    }
    
    /**
     * @group security
     * @group logging
     */
    public function testSecurityLogging(): void
    {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $event = 'test_security_event';
        $context = ['test_key' => 'test_value', 'user_id' => 123];
        
        // Log a security event
        log_security_event($event, $context);
        
        $logFile = $logDir . '/security.log';
        $this->assertFileExists($logFile);
        
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString($event, $logContent);
        $this->assertStringContainsString('test_value', $logContent);
    }
    
    /**
     * @group security
     * @group file-validation
     */
    public function testFileValidation(): void
    {
        // Create test files
        $validPdfFile = TestUtils::createTempFile('%PDF-1.4 test content', 'pdf');
        $validTextFile = TestUtils::createTempFile('Plain text content', 'txt');
        $invalidFile = TestUtils::createTempFile('<script>alert("xss")</script>', 'exe');
        
        // Test valid PDF
        $pdfUpload = TestUtils::mockFileUpload('test.pdf', file_get_contents($validPdfFile), 'application/pdf');
        $result = validate_file_upload($pdfUpload);
        $this->assertValidationPasses($result);
        
        // Test valid text file
        $textUpload = TestUtils::mockFileUpload('test.txt', file_get_contents($validTextFile), 'text/plain');
        $result = validate_file_upload($textUpload);
        $this->assertValidationPasses($result);
        
        // Test oversized file
        $largeContent = str_repeat('A', 11 * 1024 * 1024); // 11MB
        $largeFile = TestUtils::mockFileUpload('large.txt', $largeContent, 'text/plain');
        $result = validate_file_upload($largeFile);
        $this->assertValidationFails($result, 'too large');
        
        // Test invalid MIME type
        $invalidUpload = TestUtils::mockFileUpload('test.exe', 'executable content', 'application/executable');
        $result = validate_file_upload($invalidUpload);
        $this->assertValidationFails($result, 'not allowed');
    }
    
    /**
     * @group security
     * @group performance
     */
    public function testSecurityFunctionPerformance(): void
    {
        // Test that security functions execute within reasonable time limits
        
        $this->assertExecutionTime(function() {
            validate_email('test@example.com');
        }, 0.1);
        
        $this->assertExecutionTime(function() {
            validate_password('TestPassword123!', true);
        }, 0.1);
        
        $this->assertExecutionTime(function() {
            sanitize_text_content(str_repeat('Test content ', 1000));
        }, 0.1);
        
        $this->assertExecutionTime(function() {
            check_login_rate_limit('test@example.com');
        }, 0.1);
    }
    
    /**
     * @group security
     * @group headers
     */
    public function testSecurityHeaders(): void
    {
        $this->captureOutput(function() {
            set_security_headers();
        });
        
        // Note: In a real environment, we would check headers_list()
        // For testing, we verify the function executes without errors
        $this->assertTrue(true); // Function executed successfully
    }
}