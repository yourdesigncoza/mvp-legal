<?php
/**
 * User Workflow Functional Tests
 * Appeal Prospect MVP - End-to-End User Journey Testing
 */

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

class UserWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Include all necessary components
        require_once __DIR__ . '/../../app/auth.php';
        require_once __DIR__ . '/../../app/pdf_parser.php';
        require_once __DIR__ . '/../../app/save_fetch.php';
        require_once __DIR__ . '/../../app/settings.php';
        require_once __DIR__ . '/../../app/helpers.php';
        
        // Set up test environment
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Functional Test';
        
        // Set test API keys
        set_setting('openai_api_key', 'test-openai-key', true);
        set_setting('perplexity_api_key', 'test-perplexity-key', true);
    }
    
    /**
     * @group functional
     * @group user-journey
     */
    public function testCompleteUserRegistrationToAnalysisWorkflow(): void
    {
        // Step 1: User Registration
        $email = 'workflow@example.com';
        $password = 'SecureWorkflow123!';
        $name = 'Workflow Test User';
        
        $registrationResult = register_user($email, $password, $name);
        $this->assertTrue($registrationResult, 'User registration should succeed');
        
        // Step 2: User Login
        TestUtils::startTestSession();
        $this->captureOutput(function() { start_session(); });
        
        $loginResult = login_user($email, $password);
        $this->assertTrue($loginResult, 'User login should succeed');
        $this->assertTrue(is_logged_in(), 'User should be logged in');
        
        $userId = current_user_id();
        $this->assertNotNull($userId, 'User ID should be available');
        
        // Step 3: File Upload (Text Input)
        $judgmentText = 'This is a comprehensive legal judgment for workflow testing. ' . 
                       str_repeat('The case involves complex legal issues and requires detailed analysis. ', 30);
        $caseName = 'Workflow Test Case v Testing Ltd';
        
        $parser = new PDFParser();
        $uploadResult = $parser->processTextInput($judgmentText, $caseName, $userId);
        
        $this->assertTrue($uploadResult['success'], 'Text processing should succeed');
        $this->assertArrayHasKey('case_id', $uploadResult);
        
        $caseId = $uploadResult['case_id'];
        
        // Step 4: Verify Case Storage
        $stmt = $this->db->prepare('SELECT * FROM cases WHERE id = ?');
        $stmt->execute([$caseId]);
        $case = $stmt->fetch();
        
        $this->assertNotFalse($case, 'Case should exist in database');
        $this->assertEquals($userId, $case['user_id']);
        $this->assertEquals($caseName, $case['case_name']);
        $this->assertEquals('uploaded', $case['status']);
        
        // Step 5: Simulate AI Analysis
        $mockAnalysisResult = [
            'review_summary' => 'Comprehensive review of the workflow test case reveals multiple areas for potential appeal.',
            'issues_identified' => 'Several procedural and substantive issues identified that could form basis for appeal.',
            'legal_grounds' => 'Strong legal grounds exist based on precedent and statutory interpretation.',
            'appeal_strength' => 'The case demonstrates moderate to strong appeal prospects.',
            'available_remedies' => 'Various remedies including damages and declaratory relief are available.',
            'procedural_requirements' => 'Standard appeal procedures and deadlines must be strictly followed.',
            'ethical_considerations' => 'No significant ethical concerns identified in pursuing this appeal.',
            'appeal_strategy' => 'Recommended multi-pronged strategy focusing on procedural errors and substantive issues.',
            'constitutional_implications' => 'No direct constitutional issues, but administrative law principles apply.',
            'success_probability' => 'Estimated 65-75% probability of success based on similar cases.',
            'risk_analysis' => 'Moderate risk profile with manageable downside and significant upside potential.',
            'layperson_summary' => 'This case has good prospects for a successful appeal with reasonable costs and timeline.',
            'sources_citations' => 'Analysis based on Smith v Jones [2023], Contract Law Review Act, and established precedents.'
        ];
        
        $tokenUsage = [
            'prompt_tokens' => 1800,
            'completion_tokens' => 1200,
            'total_tokens' => 3000
        ];
        
        $saveResult = save_analysis_result($caseId, json_encode($mockAnalysisResult), $mockAnalysisResult, $tokenUsage);
        $this->assertTrue($saveResult, 'Analysis result should be saved');
        
        // Step 6: Verify Analysis Storage and Case Status Update
        $stmt = $this->db->prepare('SELECT status FROM cases WHERE id = ?');
        $stmt->execute([$caseId]);
        $status = $stmt->fetchColumn();
        $this->assertEquals('analyzed', $status, 'Case status should be updated to analyzed');
        
        // Step 7: Retrieve Analysis Results
        $retrievedAnalysis = get_case_results($caseId, $userId);
        $this->assertNotNull($retrievedAnalysis, 'Analysis results should be retrievable');
        $this->assertArrayHasKey('structured_analysis', $retrievedAnalysis);
        $this->assertArrayHasKey('token_usage', $retrievedAnalysis);
        
        // Step 8: Verify Analysis Content
        $structuredAnalysis = $retrievedAnalysis['structured_analysis'];
        $this->assertEquals($mockAnalysisResult['review_summary'], $structuredAnalysis['review_summary']);
        $this->assertEquals($mockAnalysisResult['success_probability'], $structuredAnalysis['success_probability']);
        $this->assertEquals(3000, $retrievedAnalysis['token_usage']['total_tokens']);
        
        // Step 9: User Statistics
        $userStats = get_case_statistics($userId);
        $this->assertEquals(1, $userStats['total_cases']);
        $this->assertEquals(1, $userStats['completed_analyses']);
        $this->assertEquals(0, $userStats['pending_analyses']);
        $this->assertEquals(3000, $userStats['total_tokens_used']);
        
        // Step 10: User Logout
        logout_user();
        $this->assertFalse(is_logged_in(), 'User should be logged out');
        $this->assertEmpty($_SESSION, 'Session should be cleared');
    }
    
    /**
     * @group functional
     * @group file-upload
     */
    public function testFileUploadWorkflow(): void
    {
        // Setup user
        $userId = $this->createTestUser();
        $this->loginUser($userId);
        
        // Create test file
        $fileContent = '%PDF-1.4 Test PDF Content ' . str_repeat('Legal judgment content for testing. ', 50);
        $file = TestUtils::mockFileUpload('test-judgment.pdf', $fileContent, 'application/pdf');
        
        $caseName = 'File Upload Test Case';
        
        // Process file upload
        $parser = new PDFParser();
        $result = $parser->processUploadedFile($file, $caseName, $userId);
        
        if ($result['success']) {
            // Verify file was processed
            $this->assertArrayHasKey('case_id', $result);
            $this->assertArrayHasKey('file_path', $result);
            $this->assertFileExists($result['file_path']);
            
            // Verify case in database
            $stmt = $this->db->prepare('SELECT * FROM cases WHERE id = ?');
            $stmt->execute([$result['case_id']]);
            $case = $stmt->fetch();
            
            $this->assertEquals('test-judgment.pdf', $case['original_filename']);
            $this->assertEquals(strlen($fileContent), $case['file_size']);
            
            // Verify secure file storage
            $uploadDir = dirname($result['file_path']);
            $this->assertDirectoryExists($uploadDir);
            
            // Check for .htaccess security
            $htaccessPath = dirname($uploadDir) . '/.htaccess';
            if (file_exists($htaccessPath)) {
                $htaccessContent = file_get_contents($htaccessPath);
                $this->assertStringContainsString('Deny from all', $htaccessContent);
            }
        } else {
            // If PDF processing fails (no pdftotext), verify error handling
            $this->assertArrayHasKey('error', $result);
            $this->assertStringContainsString('PDF', $result['error']);
        }
    }
    
    /**
     * @group functional
     * @group multi-user
     */
    public function testMultiUserWorkflow(): void
    {
        // Create multiple users
        $user1Id = $this->createTestUser(['email' => 'user1@test.com', 'name' => 'User One']);
        $user2Id = $this->createTestUser(['email' => 'user2@test.com', 'name' => 'User Two']);
        $adminId = $this->createTestUser(['email' => 'admin@test.com', 'name' => 'Admin User', 'is_admin' => 1]);
        
        // User 1 creates a case
        $this->loginUser($user1Id);
        $case1Id = $this->createTestCase($user1Id, ['case_name' => 'User 1 Case']);
        
        // User 2 creates a case
        $this->loginUser($user2Id);
        $case2Id = $this->createTestCase($user2Id, ['case_name' => 'User 2 Case']);
        
        // Test access control: User 1 cannot access User 2's case
        $this->loginUser($user1Id);
        $this->assertFalse(user_can_access_case($case2Id), 'User 1 should not access User 2 case');
        $this->assertTrue(user_can_access_case($case1Id), 'User 1 should access own case');
        
        // Test access control: User 2 cannot access User 1's case
        $this->loginUser($user2Id);
        $this->assertFalse(user_can_access_case($case1Id), 'User 2 should not access User 1 case');
        $this->assertTrue(user_can_access_case($case2Id), 'User 2 should access own case');
        
        // Test admin access: Admin can access all cases
        $this->loginUser($adminId, true);
        $this->assertTrue(user_can_access_case($case1Id), 'Admin should access all cases');
        $this->assertTrue(user_can_access_case($case2Id), 'Admin should access all cases');
        
        // Test statistics segregation
        $user1Stats = get_case_statistics($user1Id);
        $user2Stats = get_case_statistics($user2Id);
        $globalStats = get_case_statistics(0);
        
        $this->assertEquals(1, $user1Stats['total_cases']);
        $this->assertEquals(1, $user2Stats['total_cases']);
        $this->assertGreaterThanOrEqual(2, $globalStats['total_cases']);
    }
    
    /**
     * @group functional
     * @group error-scenarios
     */
    public function testErrorScenarioWorkflows(): void
    {
        $userId = $this->createTestUser();
        $this->loginUser($userId);
        
        // Test 1: Invalid file upload
        $invalidFile = TestUtils::mockFileUpload('malicious.exe', 'executable content', 'application/executable');
        $parser = new PDFParser();
        $result = $parser->processUploadedFile($invalidFile, 'Invalid File Test', $userId);
        
        $this->assertFalse($result['success'], 'Invalid file should be rejected');
        $this->assertArrayHasKey('error', $result);
        
        // Test 2: Oversized file
        $largeContent = str_repeat('A', 11 * 1024 * 1024); // 11MB
        $largeFile = TestUtils::mockFileUpload('large.txt', $largeContent, 'text/plain');
        $result = $parser->processUploadedFile($largeFile, 'Large File Test', $userId);
        
        $this->assertFalse($result['success'], 'Oversized file should be rejected');
        $this->assertStringContainsString('too large', strtolower($result['error']));
        
        // Test 3: Invalid text input
        $shortText = 'Too short';
        $result = $parser->processTextInput($shortText, 'Short Text Test', $userId);
        
        $this->assertFalse($result['success'], 'Short text should be rejected');
        $this->assertStringContainsString('100 characters', $result['error']);
        
        // Test 4: Invalid case name
        $validText = str_repeat('Valid judgment text content. ', 20);
        $invalidCaseName = '<script>alert("xss")</script>';
        $result = $parser->processTextInput($validText, $invalidCaseName, $userId);
        
        $this->assertFalse($result['success'], 'Invalid case name should be rejected');
        
        // Test 5: Unauthorized access
        $otherUserId = $this->createTestUser(['email' => 'other@test.com']);
        $caseId = $this->createTestCase($userId);
        
        $this->loginUser($otherUserId);
        $result = get_case_results($caseId, $otherUserId);
        $this->assertNull($result, 'Unauthorized access should return null');
    }
    
    /**
     * @group functional
     * @group session-management
     */
    public function testSessionManagementWorkflow(): void
    {
        $userId = $this->createTestUser();
        
        // Test session creation and security
        TestUtils::startTestSession();
        $this->captureOutput(function() { start_session(); });
        
        // Login user
        $email = 'session@test.com';
        $password = 'SessionTest123!';
        register_user($email, $password, 'Session Test');
        
        $this->assertTrue(login_user($email, $password));
        
        // Verify session security initialization
        $this->assertArrayHasKey('session_token', $_SESSION);
        $this->assertArrayHasKey('session_fingerprint', $_SESSION);
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        
        // Test CSRF protection
        $csrfToken = $_SESSION['csrf_token'];
        $_POST['csrf_token'] = $csrfToken;
        $this->assertTrue(validate_csrf());
        
        // Test invalid CSRF
        $_POST['csrf_token'] = 'invalid_token';
        $this->assertFalse(validate_csrf());
        
        // Test session timeout
        $this->assertFalse(check_session_timeout());
        
        // Simulate expired session
        $_SESSION['session_last_activity'] = time() - 7201; // 2+ hours
        $this->assertTrue(check_session_timeout());
        
        // Test logout
        logout_user();
        $this->assertFalse(is_logged_in());
    }
    
    /**
     * @group functional
     * @group admin-workflow
     */
    public function testAdminWorkflow(): void
    {
        // Create admin user
        $adminId = $this->createTestUser(['email' => 'admin@test.com', 'is_admin' => 1]);
        $this->loginUser($adminId, true);
        
        // Verify admin status
        $this->assertTrue(current_is_admin());
        
        // Create regular users and cases for admin to manage
        $user1Id = $this->createTestUser(['email' => 'user1@test.com']);
        $user2Id = $this->createTestUser(['email' => 'user2@test.com']);
        
        $case1Id = $this->createTestCase($user1Id, ['case_name' => 'User 1 Admin Test']);
        $case2Id = $this->createTestCase($user2Id, ['case_name' => 'User 2 Admin Test']);
        
        // Test admin access to all cases
        $this->assertTrue(user_can_access_case($case1Id));
        $this->assertTrue(user_can_access_case($case2Id));
        
        // Test admin statistics (global view)
        $globalStats = get_case_statistics(0); // Admin gets global stats
        $this->assertGreaterThanOrEqual(2, $globalStats['total_cases']);
        
        // Test admin can access any user's analysis results
        save_analysis_result($case1Id, '{"admin_test": "data"}', ['admin_test' => 'data'], ['total_tokens' => 1000]);
        
        $results = get_case_results($case1Id, $user1Id, true); // Admin access
        $this->assertNotNull($results);
        $this->assertEquals('data', $results['structured_analysis']['admin_test']);
    }
    
    /**
     * @group functional
     * @group data-integrity
     */
    public function testDataIntegrityWorkflow(): void
    {
        $userId = $this->createTestUser();
        $this->loginUser($userId);
        
        // Create case with specific content
        $originalText = 'Original judgment text with specific content for integrity testing. ' . 
                       str_repeat('This content should remain unchanged throughout the process. ', 20);
        $caseName = 'Data Integrity Test Case';
        
        $parser = new PDFParser();
        $result = $parser->processTextInput($originalText, $caseName, $userId);
        $this->assertTrue($result['success']);
        
        $caseId = $result['case_id'];
        
        // Verify stored text matches original
        $stmt = $this->db->prepare('SELECT judgment_text, case_name FROM cases WHERE id = ?');
        $stmt->execute([$caseId]);
        $stored = $stmt->fetch();
        
        $this->assertStringContainsString('specific content for integrity testing', $stored['judgment_text']);
        $this->assertEquals($caseName, $stored['case_name']);
        
        // Add analysis result
        $analysisData = [
            'test_section' => 'Test analysis content that should be preserved',
            'integrity_check' => 'This data should remain intact'
        ];
        
        $tokenData = ['total_tokens' => 1500, 'prompt_tokens' => 900, 'completion_tokens' => 600];
        
        $this->assertTrue(save_analysis_result($caseId, json_encode($analysisData), $analysisData, $tokenData));
        
        // Retrieve and verify integrity
        $retrieved = get_case_results($caseId, $userId);
        $this->assertEquals($analysisData['test_section'], $retrieved['structured_analysis']['test_section']);
        $this->assertEquals($analysisData['integrity_check'], $retrieved['structured_analysis']['integrity_check']);
        $this->assertEquals(1500, $retrieved['token_usage']['total_tokens']);
        
        // Test that other users cannot access this data
        $otherUserId = $this->createTestUser(['email' => 'other@test.com']);
        $this->loginUser($otherUserId);
        
        $unauthorized = get_case_results($caseId, $otherUserId);
        $this->assertNull($unauthorized, 'Data should be protected from unauthorized access');
    }
    
    /**
     * @group functional
     * @group performance
     */
    public function testWorkflowPerformance(): void
    {
        $userId = $this->createTestUser();
        $this->loginUser($userId);
        
        // Test complete workflow performance
        $this->assertExecutionTime(function() use ($userId) {
            // Create case
            $text = str_repeat('Performance test content. ', 100);
            $caseName = 'Performance Test Case';
            
            $parser = new PDFParser();
            $result = $parser->processTextInput($text, $caseName, $userId);
            $this->assertTrue($result['success']);
            
            $caseId = $result['case_id'];
            
            // Save analysis
            $analysis = ['performance_test' => 'Test data'];
            $tokens = ['total_tokens' => 2000];
            save_analysis_result($caseId, json_encode($analysis), $analysis, $tokens);
            
            // Retrieve results
            get_case_results($caseId, $userId);
            
        }, 5.0); // Allow 5 seconds for complete workflow
        
        // Test batch operations performance
        $this->assertExecutionTime(function() use ($userId) {
            // Create multiple cases
            for ($i = 1; $i <= 10; $i++) {
                $this->createTestCase($userId, ['case_name' => "Batch Test Case $i"]);
            }
            
            // Get statistics
            get_case_statistics($userId);
            
        }, 2.0); // Allow 2 seconds for batch operations
    }
}