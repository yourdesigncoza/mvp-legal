<?php
// Database configuration
// Appeal Prospect MVP - Configuration

declare(strict_types=1);

// Database Configuration
// Update these values to match your MySQL setup
define('DB_HOST', 'localhost');
define('DB_NAME', 'appeal_prospect_mvp');
define('DB_USER', 'root');  // Update with your MySQL username
define('DB_PASS', '');      // Update with your MySQL password

// Environment Configuration
define('APP_ENV', 'development'); // 'development' or 'production'
define('APP_DEBUG', APP_ENV === 'development');

// Application Configuration
define('APP_NAME', 'Appeal Prospect MVP');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/mvp-legal'); // Update for your setup

// Security Configuration
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
define('CSRF_TOKEN_LIFETIME', 3600);   // 1 hour

// File Upload Configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads');
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'text/plain',
    'text/html'
]);

// API Configuration
define('OPENAI_MODEL', 'gpt-4o-mini');
define('OPENAI_MAX_TOKENS', 4000);
define('OPENAI_TEMPERATURE', 0.1);

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', '/var/log/appeal_prospect_errors.log');
}

// Timezone
date_default_timezone_set('Africa/Johannesburg');

// PHP Settings
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('post_max_size', '12M');
ini_set('upload_max_filesize', '10M');

?>