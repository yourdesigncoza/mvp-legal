<?php
/**
 * PHPUnit Test Bootstrap
 * Appeal Prospect MVP - Testing Framework Setup
 */

declare(strict_types=1);

// Set test environment
define('APP_ENV', 'testing');
define('APP_DEBUG', true);

// Start output buffering to prevent headers already sent errors
ob_start();

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

// Include autoloader (if using Composer)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Include core application files
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/error_handler.php';
require_once __DIR__ . '/../app/form_validator.php';
require_once __DIR__ . '/../app/helpers.php';

// Initialize error handling for tests
ErrorHandler::initialize();

/**
 * Test Database Setup
 */
class TestDatabase
{
    private static ?PDO $pdo = null;
    
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            self::createTables();
            self::seedTestData();
        }
        
        return self::$pdo;
    }
    
    private static function createTables(): void
    {
        $sql = '
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                is_admin BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login_at DATETIME NULL
            );
            
            CREATE TABLE cases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                case_name VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) NULL,
                stored_filename VARCHAR(255) NULL,
                mime_type VARCHAR(100) NULL,
                file_size INTEGER NULL,
                judgment_text TEXT NOT NULL,
                status VARCHAR(50) DEFAULT "uploaded",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            );
            
            CREATE TABLE case_results (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                case_id INTEGER NOT NULL,
                analysis_result TEXT NOT NULL,
                structured_analysis JSON NULL,
                web_research JSON NULL,
                token_usage JSON NULL,
                processing_time REAL NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (case_id) REFERENCES cases (id) ON DELETE CASCADE
            );
            
            CREATE TABLE settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(255) UNIQUE NOT NULL,
                setting_value TEXT NULL,
                is_encrypted BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE INDEX idx_users_email ON users (email);
            CREATE INDEX idx_cases_user_id ON cases (user_id);
            CREATE INDEX idx_cases_status ON cases (status);
            CREATE INDEX idx_case_results_case_id ON case_results (case_id);
            CREATE INDEX idx_settings_key ON settings (setting_key);
        ';
        
        self::$pdo->exec($sql);
    }
    
    private static function seedTestData(): void
    {
        // Create test users
        $users = [
            [
                'email' => 'test@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'name' => 'Test User',
                'is_admin' => 0
            ],
            [
                'email' => 'admin@example.com', 
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'name' => 'Admin User',
                'is_admin' => 1
            ]
        ];
        
        $stmt = self::$pdo->prepare('
            INSERT INTO users (email, password_hash, name, is_admin) 
            VALUES (?, ?, ?, ?)
        ');
        
        foreach ($users as $user) {
            $stmt->execute(array_values($user));
        }
        
        // Create test cases
        $cases = [
            [
                'user_id' => 1,
                'case_name' => 'Test Case 1',
                'judgment_text' => 'This is a test judgment text for testing purposes. It contains enough content to pass validation and test various features of the system.',
                'status' => 'uploaded'
            ],
            [
                'user_id' => 1,
                'case_name' => 'Test Case 2', 
                'judgment_text' => 'Another test judgment with different content to ensure our testing covers various scenarios and edge cases.',
                'status' => 'analyzed'
            ]
        ];
        
        $stmt = self::$pdo->prepare('
            INSERT INTO cases (user_id, case_name, judgment_text, status) 
            VALUES (?, ?, ?, ?)
        ');
        
        foreach ($cases as $case) {
            $stmt->execute(array_values($case));
        }
        
        // Create test settings
        $settings = [
            ['openai_api_key', 'test-openai-key', 1],
            ['perplexity_api_key', 'test-perplexity-key', 1],
            ['max_file_size', '10485760', 0],
            ['allowed_mime_types', 'application/pdf,text/plain', 0]
        ];
        
        $stmt = self::$pdo->prepare('
            INSERT INTO settings (setting_key, setting_value, is_encrypted) 
            VALUES (?, ?, ?)
        ');
        
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }
    
    public static function reset(): void
    {
        if (self::$pdo) {
            self::$pdo->exec('DELETE FROM case_results');
            self::$pdo->exec('DELETE FROM cases');  
            self::$pdo->exec('DELETE FROM users');
            self::$pdo->exec('DELETE FROM settings');
            self::seedTestData();
        }
    }
    
    public static function close(): void
    {
        self::$pdo = null;
    }
}

/**
 * Test Utilities
 */
class TestUtils
{
    /**
     * Create a temporary file for testing
     */
    public static function createTempFile(string $content = '', string $extension = 'txt'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.' . $extension;
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
    
    /**
     * Clean up temporary files
     */
    public static function cleanupTempFiles(): void
    {
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/test_*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Mock $_FILES array for upload testing
     */
    public static function mockFileUpload(string $filename, string $content, string $mimeType = 'text/plain'): array
    {
        $tempFile = self::createTempFile($content);
        
        return [
            'name' => $filename,
            'type' => $mimeType,
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($content)
        ];
    }
    
    /**
     * Start session for testing
     */
    public static function startTestSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_cookies', '0');
            session_start();
        }
    }
    
    /**
     * Clear session data
     */
    public static function clearSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }
    }
    
    /**
     * Mock user login for testing
     */
    public static function loginTestUser(int $userId = 1, bool $isAdmin = false): void
    {
        self::startTestSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['is_admin'] = $isAdmin;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    /**
     * Generate test CSRF token
     */
    public static function generateTestCSRFToken(): string
    {
        self::startTestSession();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Capture output for testing
     */
    public static function captureOutput(callable $callback): string
    {
        ob_start();
        try {
            $callback();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }
    
    /**
     * Assert that a string contains HTML
     */
    public static function assertValidHTML(string $html): bool
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $result = $dom->loadHTML($html);
        libxml_clear_errors();
        return $result;
    }
    
    /**
     * Create mock PDO connection
     */
    public static function mockDatabase(): PDO
    {
        return TestDatabase::getConnection();
    }
}

// Override database connection for tests
function get_db_connection(): PDO
{
    return TestDatabase::getConnection();
}

// Register cleanup function
register_shutdown_function(function() {
    TestUtils::cleanupTempFiles();
    TestDatabase::close();
});

// Initialize test database
TestDatabase::getConnection();