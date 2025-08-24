<?php
/**
 * PDF Parser Unit Tests
 * Appeal Prospect MVP - PDF Processing Testing
 */

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

class PDFParserTest extends TestCase
{
    private PDFParser $parser;
    
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../app/pdf_parser.php';
        $this->parser = new PDFParser();
    }
    
    /**
     * @group pdf
     * @group file-processing
     */
    public function testProcessTextInputWithValidData(): void
    {
        $userId = $this->createTestUser();
        $text = str_repeat('This is test judgment text content. ', 20); // >100 chars
        $caseName = 'Test Case for Text Input';
        
        $result = $this->parser->processTextInput($text, $caseName, $userId);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('case_id', $result);
        $this->assertArrayHasKey('text_length', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(strlen($text), $result['text_length']);
        
        // Verify case was saved to database
        $stmt = $this->db->prepare('SELECT * FROM cases WHERE id = ?');
        $stmt->execute([$result['case_id']]);
        $case = $stmt->fetch();
        
        $this->assertNotFalse($case);
        $this->assertEquals($userId, $case['user_id']);
        $this->assertEquals($caseName, $case['case_name']);
        $this->assertEquals('text/plain', $case['mime_type']);
    }
    
    /**
     * @group pdf
     * @group file-processing
     */
    public function testProcessTextInputWithInvalidData(): void
    {
        $userId = $this->createTestUser();
        
        // Test with empty text
        $result = $this->parser->processTextInput('', 'Test Case', $userId);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('100 characters', $result['error']);
        
        // Test with short text
        $result = $this->parser->processTextInput('Short', 'Test Case', $userId);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('100 characters', $result['error']);
        
        // Test with invalid case name
        $validText = str_repeat('Valid text content. ', 10);
        $result = $this->parser->processTextInput($validText, 'A', $userId); // Too short
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('case name', strtolower($result['error']));
    }
    
    /**
     * @group pdf
     * @group file-processing
     */
    public function testProcessUploadedFileWithTextFile(): void
    {
        $userId = $this->createTestUser();
        $content = str_repeat('This is test file content. ', 20);
        $file = TestUtils::mockFileUpload('test.txt', $content, 'text/plain');
        $caseName = 'Test Case for File Upload';
        
        $result = $this->parser->processUploadedFile($file, $caseName, $userId);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('case_id', $result);
        $this->assertArrayHasKey('text_length', $result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('message', $result);
        
        // Verify file was saved
        $this->assertFileExists($result['file_path']);
        
        // Verify case was saved to database
        $stmt = $this->db->prepare('SELECT * FROM cases WHERE id = ?');
        $stmt->execute([$result['case_id']]);
        $case = $stmt->fetch();
        
        $this->assertNotFalse($case);
        $this->assertEquals($userId, $case['user_id']);
        $this->assertEquals($caseName, $case['case_name']);
        $this->assertEquals('test.txt', $case['original_filename']);
        $this->assertEquals('text/plain', $case['mime_type']);
        $this->assertEquals(strlen($content), $case['file_size']);
    }
    
    /**
     * @group pdf
     * @group file-processing  
     */
    public function testProcessUploadedFileWithInvalidFile(): void
    {
        $userId = $this->createTestUser();
        $caseName = 'Test Case';
        
        // Test with no file
        $emptyFile = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0
        ];
        
        $result = $this->parser->processUploadedFile($emptyFile, $caseName, $userId);
        $this->assertFalse($result['success']);
        
        // Test with upload error
        $errorFile = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/nonexistent',
            'error' => UPLOAD_ERR_PARTIAL,
            'size' => 100
        ];
        
        $result = $this->parser->processUploadedFile($errorFile, $caseName, $userId);
        $this->assertFalse($result['success']);
        
        // Test with oversized file
        $largeFile = TestUtils::mockFileUpload('large.txt', str_repeat('A', 11 * 1024 * 1024), 'text/plain');
        $result = $this->parser->processUploadedFile($largeFile, $caseName, $userId);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('too large', strtolower($result['error']));
    }
    
    /**
     * @group pdf
     * @group file-processing
     */
    public function testProcessUploadedFileWithInvalidMimeType(): void
    {
        $userId = $this->createTestUser();
        $caseName = 'Test Case';
        
        // Create a file with disallowed MIME type
        $executableContent = 'executable content';
        $file = TestUtils::mockFileUpload('malicious.exe', $executableContent, 'application/executable');
        
        $result = $this->parser->processUploadedFile($file, $caseName, $userId);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not allowed', strtolower($result['error']));
    }
    
    /**
     * @group pdf
     * @group file-processing
     */
    public function testProcessUploadedFileWithMockPDF(): void
    {
        $this->skipIfFunctionNotExists('exec'); // PDF processing requires exec
        
        $userId = $this->createTestUser();
        $caseName = 'Test PDF Case';
        
        // Create a mock PDF file (minimal PDF structure)
        $pdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000074 00000 n \n0000000120 00000 n \ntrailer\n<<\n/Size 4\n/Root 1 0 R\n>>\nstartxref\n197\n%%EOF";
        
        $file = TestUtils::mockFileUpload('test.pdf', $pdfContent, 'application/pdf');
        
        $result = $this->parser->processUploadedFile($file, $caseName, $userId);
        
        // Result may fail if pdftotext is not available, which is acceptable in test environment
        if ($result['success']) {
            $this->assertArrayHasKey('case_id', $result);
            $this->assertArrayHasKey('text_length', $result);
        } else {
            // If PDF processing fails, error should indicate PDF processing issue
            $this->assertStringContainsString('pdf', strtolower($result['error']));
        }
    }
    
    /**
     * @group pdf
     * @group helpers
     */
    public function testHelperFunctions(): void
    {
        $userId = $this->createTestUser();
        $text = str_repeat('Test content for helper function. ', 10);
        $caseName = 'Helper Test Case';
        
        // Test process_text_input helper
        $result = process_text_input($text, $caseName, $userId);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('case_id', $result);
        
        // Test process_file_upload helper
        $file = TestUtils::mockFileUpload('helper_test.txt', $text, 'text/plain');
        $result = process_file_upload($file, 'Helper File Test', $userId);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('case_id', $result);
    }
    
    /**
     * @group pdf
     * @group text-cleaning
     */
    public function testTextCleaning(): void
    {
        // Since cleanText is private, we test it through public methods
        $userId = $this->createTestUser();
        
        $messyText = "Text    with     multiple\n\n\n\nspaces\r\nand\r\nline\nending\nissues    ";
        $caseName = 'Text Cleaning Test';
        
        $result = $this->parser->processTextInput($messyText, $caseName, $userId);
        $this->assertTrue($result['success']);
        
        // Verify the text was cleaned by checking database
        $stmt = $this->db->prepare('SELECT judgment_text FROM cases WHERE id = ?');
        $stmt->execute([$result['case_id']]);
        $cleanedText = $stmt->fetchColumn();
        
        // Should have normalized whitespace
        $this->assertStringNotContainsString('    ', $cleanedText); // No multiple spaces
        $this->assertStringNotContainsString("\r", $cleanedText); // No carriage returns
        $this->assertStringNotContainsString("\n\n\n", $cleanedText); // No excessive newlines
        $this->assertEquals(trim($cleanedText), $cleanedText); // Should be trimmed
    }
    
    /**
     * @group pdf
     * @group utf8
     */
    public function testUTF8Handling(): void
    {
        $userId = $this->createTestUser();
        
        // Text with various UTF-8 characters
        $utf8Text = "Test with UTF-8: café, naïve, résumé, Москва, 北京, العربية. " . str_repeat('Additional content. ', 10);
        $caseName = 'UTF-8 Test Case';
        
        $result = $this->parser->processTextInput($utf8Text, $caseName, $userId);
        $this->assertTrue($result['success']);
        
        // Verify UTF-8 content was preserved
        $stmt = $this->db->prepare('SELECT judgment_text FROM cases WHERE id = ?');
        $stmt->execute([$result['case_id']]);
        $savedText = $stmt->fetchColumn();
        
        $this->assertStringContainsString('café', $savedText);
        $this->assertStringContainsString('naïve', $savedText);
        $this->assertStringContainsString('Москва', $savedText);
        $this->assertStringContainsString('北京', $savedText);
        $this->assertStringContainsString('العربية', $savedText);
    }
    
    /**
     * @group pdf
     * @group security-integration
     */
    public function testSecurityIntegration(): void
    {
        $userId = $this->createTestUser();
        
        // Test with malicious case name
        $validText = str_repeat('Valid text content. ', 10);
        $maliciousCaseName = '<script>alert("xss")</script>';
        
        $result = $this->parser->processTextInput($validText, $maliciousCaseName, $userId);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalid characters', strtolower($result['error']));
        
        // Test with malicious text content (should be cleaned, not rejected)
        $maliciousText = '<script>alert("xss")</script>' . str_repeat(' Additional content to meet length requirement.', 10);
        $validCaseName = 'Security Test Case';
        
        $result = $this->parser->processTextInput($maliciousText, $validCaseName, $userId);
        $this->assertTrue($result['success']);
        
        // Verify malicious content was sanitized
        $stmt = $this->db->prepare('SELECT judgment_text FROM cases WHERE id = ?');
        $stmt->execute([$result['case_id']]);
        $savedText = $stmt->fetchColumn();
        
        $this->assertStringNotContainsString('<script>', $savedText);
        $this->assertStringNotContainsString('alert(', $savedText);
    }
    
    /**
     * @group pdf
     * @group error-handling
     */
    public function testErrorHandling(): void
    {
        $userId = $this->createTestUser();
        
        // Test with invalid user ID
        $validText = str_repeat('Valid text content. ', 10);
        $validCaseName = 'Error Test Case';
        
        $result = $this->parser->processTextInput($validText, $validCaseName, 99999);
        // Should succeed even with non-existent user ID (database constraint would catch it)
        // This tests that the method handles the case gracefully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
    
    /**
     * @group pdf
     * @group file-permissions
     */
    public function testFilePermissionsAndSecurity(): void
    {
        $userId = $this->createTestUser();
        $content = str_repeat('Test file content. ', 20);
        $file = TestUtils::mockFileUpload('permissions_test.txt', $content, 'text/plain');
        $caseName = 'Permissions Test Case';
        
        $result = $this->parser->processUploadedFile($file, $caseName, $userId);
        
        if ($result['success']) {
            // Verify upload directory structure exists
            $uploadDir = dirname($result['file_path']);
            $this->assertDirectoryExists($uploadDir);
            
            // Verify .htaccess exists in upload directory
            $htaccessPath = dirname(dirname($result['file_path'])) . '/.htaccess';
            if (file_exists($htaccessPath)) {
                $htaccessContent = file_get_contents($htaccessPath);
                $this->assertStringContainsString('Deny from all', $htaccessContent);
            }
        }
    }
    
    /**
     * @group pdf
     * @group performance
     */
    public function testPerformance(): void
    {
        $userId = $this->createTestUser();
        
        // Test processing time for large text
        $largeText = str_repeat('This is a large text content for performance testing. ', 1000);
        $caseName = 'Performance Test Case';
        
        $this->assertExecutionTime(function() use ($largeText, $caseName, $userId) {
            $this->parser->processTextInput($largeText, $caseName, $userId);
        }, 2.0); // Allow up to 2 seconds for large text processing
        
        // Test file upload performance
        $fileContent = str_repeat('File content for performance testing. ', 500);
        $file = TestUtils::mockFileUpload('performance_test.txt', $fileContent, 'text/plain');
        
        $this->assertExecutionTime(function() use ($file, $caseName, $userId) {
            $this->parser->processUploadedFile($file, $caseName . ' File', $userId);
        }, 3.0); // Allow up to 3 seconds for file processing
    }
}