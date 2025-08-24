<?php
/**
 * Centralized Error Handling System
 * Appeal Prospect MVP - Error Management
 */

declare(strict_types=1);

require_once __DIR__ . '/security.php';

/**
 * Error Handler Class
 */
class ErrorHandler
{
    private const ERROR_LOG_FILE = __DIR__ . '/../logs/app_errors.log';
    private const MAX_LOG_SIZE = 10485760; // 10MB
    
    /**
     * Initialize error handling
     */
    public static function initialize(): void
    {
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set custom exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Register shutdown function for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
        
        // Ensure error log directory exists
        self::ensureErrorLogDirectory();
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error_data = [
            'type' => 'PHP Error',
            'severity' => self::getSeverityName($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'user_id' => current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error_data);
        
        // For fatal errors, show error page
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            self::showErrorPage('An internal error occurred', 500);
            return true;
        }
        
        return false; // Let PHP handle the error normally
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $exception): void
    {
        $error_data = [
            'type' => 'Uncaught Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error_data);
        self::showErrorPage('An unexpected error occurred', 500);
    }
    
    /**
     * Handle fatal errors on shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $error_data = [
                'type' => 'Fatal Error',
                'severity' => self::getSeverityName($error['type']),
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'user_id' => current_user_id(),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            self::logError($error_data);
            
            // If output hasn't started, show error page
            if (!headers_sent()) {
                self::showErrorPage('A fatal error occurred', 500);
            }
        }
    }
    
    /**
     * Log application errors
     */
    public static function logError(array $error_data): void
    {
        try {
            // Rotate log if it's too large
            self::rotateLogIfNeeded();
            
            // Format error entry
            $log_entry = json_encode($error_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            
            // Write to log file
            file_put_contents(self::ERROR_LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
            
            // Also log security events for certain error types
            if (in_array($error_data['type'] ?? '', ['Uncaught Exception', 'Fatal Error'])) {
                log_security_event('application_error', [
                    'type' => $error_data['type'],
                    'message' => $error_data['message'],
                    'file' => basename($error_data['file'] ?? 'unknown')
                ]);
            }
        } catch (Exception $e) {
            // Fallback to system error log if our logging fails
            error_log("Error logging failed: " . $e->getMessage());
            error_log("Original error: " . json_encode($error_data));
        }
    }
    
    /**
     * Show user-friendly error page
     */
    public static function showErrorPage(string $message = 'An error occurred', int $status_code = 500): void
    {
        if (headers_sent()) {
            return; // Can't send headers after output has started
        }
        
        http_response_code($status_code);
        
        // Clean any output buffer
        if (ob_get_level()) {
            ob_clean();
        }
        
        $page_title = match($status_code) {
            404 => 'Page Not Found',
            403 => 'Access Forbidden',
            500 => 'Internal Server Error',
            default => 'Error'
        };
        
        // Include error page template
        include __DIR__ . '/templates/error_page.php';
        exit;
    }
    
    /**
     * Handle 404 errors
     */
    public static function handle404(): void
    {
        self::logError([
            'type' => '404 Error',
            'message' => 'Page not found',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'direct',
            'user_id' => current_user_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        self::showErrorPage('The requested page was not found', 404);
    }
    
    /**
     * Get severity name from error constant
     */
    private static function getSeverityName(int $severity): string
    {
        return match($severity) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => 'UNKNOWN'
        };
    }
    
    /**
     * Ensure error log directory exists
     */
    private static function ensureErrorLogDirectory(): void
    {
        $log_dir = dirname(self::ERROR_LOG_FILE);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Ensure log file is writable
        if (!file_exists(self::ERROR_LOG_FILE)) {
            touch(self::ERROR_LOG_FILE);
            chmod(self::ERROR_LOG_FILE, 0644);
        }
    }
    
    /**
     * Rotate log file if it exceeds maximum size
     */
    private static function rotateLogIfNeeded(): void
    {
        if (!file_exists(self::ERROR_LOG_FILE)) {
            return;
        }
        
        if (filesize(self::ERROR_LOG_FILE) > self::MAX_LOG_SIZE) {
            $backup_file = self::ERROR_LOG_FILE . '.old';
            
            // Remove old backup if exists
            if (file_exists($backup_file)) {
                unlink($backup_file);
            }
            
            // Move current log to backup
            rename(self::ERROR_LOG_FILE, $backup_file);
            
            // Create new log file
            touch(self::ERROR_LOG_FILE);
            chmod(self::ERROR_LOG_FILE, 0644);
        }
    }
}

/**
 * Application-specific error functions
 */

/**
 * Log application-level errors with context
 */
function log_app_error(string $message, array $context = [], ?Throwable $exception = null): void
{
    $error_data = [
        'type' => 'Application Error',
        'message' => $message,
        'context' => $context,
        'user_id' => current_user_id(),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($exception) {
        $error_data['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
    }
    
    ErrorHandler::logError($error_data);
}

/**
 * Handle API errors with proper JSON response
 */
function handle_api_error(string $message, int $code = 500, array $details = []): void
{
    log_app_error("API Error: $message", ['code' => $code, 'details' => $details]);
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($code);
    }
    
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code,
        'details' => $details
    ]);
    exit;
}

/**
 * Validate and handle form submission errors
 */
function handle_form_errors(array $errors, string $form_name): bool
{
    if (!empty($errors)) {
        log_app_error("Form validation failed: $form_name", [
            'form' => $form_name,
            'errors' => $errors,
            'post_data' => array_keys($_POST)
        ]);
        return false;
    }
    return true;
}

/**
 * Handle database operation errors
 */
function handle_db_error(string $operation, ?string $error_message = null): void
{
    $message = "Database operation failed: $operation";
    $context = ['operation' => $operation];
    
    if ($error_message) {
        $context['db_error'] = $error_message;
    }
    
    log_app_error($message, $context);
}