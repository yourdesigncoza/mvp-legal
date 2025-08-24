<?php
/**
 * Base Test Case
 * Appeal Prospect MVP - Testing Framework Base Class
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base Test Case Class
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected PDO $db;
    protected array $originalServer;
    protected array $originalPost;
    protected array $originalGet;
    protected array $originalFiles;
    protected array $originalSession;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Get database connection
        $this->db = TestDatabase::getConnection();
        
        // Backup superglobals
        $this->originalServer = $_SERVER ?? [];
        $this->originalPost = $_POST ?? [];
        $this->originalGet = $_GET ?? [];
        $this->originalFiles = $_FILES ?? [];
        $this->originalSession = $_SESSION ?? [];
        
        // Reset database
        TestDatabase::reset();
        
        // Clear session
        TestUtils::clearSession();
        
        // Start output buffering
        ob_start();
    }
    
    protected function tearDown(): void
    {
        // Clean output buffer
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Restore superglobals
        $_SERVER = $this->originalServer;
        $_POST = $this->originalPost;
        $_GET = $this->originalGet;
        $_FILES = $this->originalFiles;
        $_SESSION = $this->originalSession;
        
        // Cleanup temp files
        TestUtils::cleanupTempFiles();
        
        parent::tearDown();
    }
    
    /**
     * Assert that a string contains valid HTML
     */
    protected function assertValidHTML(string $html): void
    {
        $this->assertTrue(
            TestUtils::assertValidHTML($html),
            'HTML is not valid'
        );
    }
    
    /**
     * Assert that an array has the expected structure
     */
    protected function assertArrayStructure(array $expected, array $actual, string $message = ''): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual, $message . " - Missing key: $key");
            
            if (is_array($value)) {
                $this->assertIsArray($actual[$key], $message . " - $key should be array");
                $this->assertArrayStructure($value, $actual[$key], $message . "[$key]");
            } else {
                $this->assertEquals($value, $actual[$key], $message . " - Value mismatch for $key");
            }
        }
    }
    
    /**
     * Assert that a response contains expected HTTP status
     */
    protected function assertResponseCode(int $expectedCode): void
    {
        $this->assertEquals($expectedCode, http_response_code());
    }
    
    /**
     * Assert that a validation result is successful
     */
    protected function assertValidationPasses(array $result): void
    {
        $this->assertTrue($result['valid'] ?? false, 'Validation should pass');
    }
    
    /**
     * Assert that a validation result fails
     */
    protected function assertValidationFails(array $result, string $expectedError = null): void
    {
        $this->assertFalse($result['valid'] ?? true, 'Validation should fail');
        
        if ($expectedError !== null) {
            $this->assertStringContainsString(
                $expectedError, 
                $result['error'] ?? '',
                'Expected error message not found'
            );
        }
    }
    
    /**
     * Assert that a function execution time is within limits
     */
    protected function assertExecutionTime(callable $callback, float $maxSeconds = 1.0): void
    {
        $start = microtime(true);
        $callback();
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(
            $maxSeconds,
            $duration,
            "Execution time ({$duration}s) exceeded maximum ({$maxSeconds}s)"
        );
    }
    
    /**
     * Create a test user
     */
    protected function createTestUser(array $userData = []): int
    {
        $defaultData = [
            'email' => 'test' . time() . '@example.com',
            'password_hash' => password_hash('testpass', PASSWORD_DEFAULT),
            'name' => 'Test User',
            'is_admin' => 0
        ];
        
        $userData = array_merge($defaultData, $userData);
        
        $stmt = $this->db->prepare('
            INSERT INTO users (email, password_hash, name, is_admin) 
            VALUES (?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $userData['email'],
            $userData['password_hash'],
            $userData['name'],
            $userData['is_admin']
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Create a test case
     */
    protected function createTestCase(int $userId, array $caseData = []): int
    {
        $defaultData = [
            'case_name' => 'Test Case ' . time(),
            'judgment_text' => 'This is test judgment text with sufficient length for validation.',
            'status' => 'uploaded'
        ];
        
        $caseData = array_merge($defaultData, $caseData);
        
        $stmt = $this->db->prepare('
            INSERT INTO cases (user_id, case_name, judgment_text, status) 
            VALUES (?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $userId,
            $caseData['case_name'],
            $caseData['judgment_text'],
            $caseData['status']
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Login a test user
     */
    protected function loginUser(int $userId, bool $isAdmin = false): void
    {
        TestUtils::loginTestUser($userId, $isAdmin);
    }
    
    /**
     * Generate and set CSRF token
     */
    protected function setCSRFToken(): string
    {
        return TestUtils::generateTestCSRFToken();
    }
    
    /**
     * Mock a file upload
     */
    protected function mockFileUpload(string $filename, string $content, string $mimeType = 'text/plain'): array
    {
        return TestUtils::mockFileUpload($filename, $content, $mimeType);
    }
    
    /**
     * Mock HTTP request
     */
    protected function mockRequest(string $method = 'GET', string $uri = '/', array $data = []): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
        
        if ($method === 'POST') {
            $_POST = $data;
        } elseif ($method === 'GET') {
            $_GET = $data;
        }
    }
    
    /**
     * Capture output from a function
     */
    protected function captureOutput(callable $callback): string
    {
        return TestUtils::captureOutput($callback);
    }
    
    /**
     * Assert that a log entry was created
     */
    protected function assertLogEntryExists(string $logFile, string $searchString): void
    {
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $this->assertStringContainsString(
                $searchString,
                $logContent,
                "Log entry not found in $logFile"
            );
        } else {
            $this->fail("Log file does not exist: $logFile");
        }
    }
    
    /**
     * Assert that an exception is thrown with specific message
     */
    protected function assertExceptionMessage(callable $callback, string $expectedMessage, string $exceptionClass = Exception::class): void
    {
        try {
            $callback();
            $this->fail("Expected exception $exceptionClass was not thrown");
        } catch (Exception $e) {
            $this->assertInstanceOf($exceptionClass, $e);
            $this->assertStringContainsString($expectedMessage, $e->getMessage());
        }
    }
    
    /**
     * Create a mock PDO statement for testing
     */
    protected function createMockStatement(array $returnData = [], bool $executeResult = true): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        
        $stmt->method('execute')
             ->willReturn($executeResult);
        
        $stmt->method('fetch')
             ->willReturn($returnData[0] ?? false);
        
        $stmt->method('fetchAll')
             ->willReturn($returnData);
        
        $stmt->method('rowCount')
             ->willReturn(count($returnData));
        
        return $stmt;
    }
    
    /**
     * Skip test if extension is not loaded
     */
    protected function skipIfExtensionNotLoaded(string $extension): void
    {
        if (!extension_loaded($extension)) {
            $this->markTestSkipped("Extension $extension is not loaded");
        }
    }
    
    /**
     * Skip test if function is not available
     */
    protected function skipIfFunctionNotExists(string $function): void
    {
        if (!function_exists($function)) {
            $this->markTestSkipped("Function $function does not exist");
        }
    }
}