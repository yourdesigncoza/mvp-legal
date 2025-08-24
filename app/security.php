<?php
// Security Implementation
// Appeal Prospect MVP - Comprehensive Security Functions

declare(strict_types=1);

/**
 * Input Validation and Sanitization Functions
 */

/**
 * Validate and sanitize email address
 */
function validate_email(string $email): array
{
    $email = trim($email);
    
    if (empty($email)) {
        return ['valid' => false, 'error' => 'Email address is required', 'value' => ''];
    }
    
    if (strlen($email) > 255) {
        return ['valid' => false, 'error' => 'Email address is too long', 'value' => ''];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => 'Please enter a valid email address', 'value' => ''];
    }
    
    // Check for malicious patterns
    if (preg_match('/[<>"\']/', $email)) {
        return ['valid' => false, 'error' => 'Email contains invalid characters', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => strtolower($email)];
}

/**
 * Validate and sanitize password
 */
function validate_password(string $password, bool $require_strength = true): array
{
    if (empty($password)) {
        return ['valid' => false, 'error' => 'Password is required', 'value' => ''];
    }
    
    if (strlen($password) < 8) {
        return ['valid' => false, 'error' => 'Password must be at least 8 characters long', 'value' => ''];
    }
    
    if (strlen($password) > 128) {
        return ['valid' => false, 'error' => 'Password is too long (max 128 characters)', 'value' => ''];
    }
    
    if ($require_strength) {
        if (!preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one lowercase letter', 'value' => ''];
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one uppercase letter', 'value' => ''];
        }
        
        if (!preg_match('/\d/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one number', 'value' => ''];
        }
    }
    
    return ['valid' => true, 'error' => '', 'value' => $password];
}

/**
 * Validate and sanitize user name
 */
function validate_name(string $name): array
{
    $name = trim($name);
    
    if (empty($name)) {
        return ['valid' => false, 'error' => 'Name is required', 'value' => ''];
    }
    
    if (strlen($name) < 2) {
        return ['valid' => false, 'error' => 'Name must be at least 2 characters long', 'value' => ''];
    }
    
    if (strlen($name) > 100) {
        return ['valid' => false, 'error' => 'Name is too long (max 100 characters)', 'value' => ''];
    }
    
    // Remove potentially harmful characters
    $name = preg_replace('/[<>"\']/', '', $name);
    
    // Check for remaining malicious patterns
    if (preg_match('/<script|javascript:|data:|vbscript:/i', $name)) {
        return ['valid' => false, 'error' => 'Name contains invalid content', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $name];
}

/**
 * Validate case name
 */
function validate_case_name(string $case_name): array
{
    $case_name = trim($case_name);
    
    if (empty($case_name)) {
        return ['valid' => false, 'error' => 'Case name is required', 'value' => ''];
    }
    
    if (strlen($case_name) < 3) {
        return ['valid' => false, 'error' => 'Case name must be at least 3 characters long', 'value' => ''];
    }
    
    if (strlen($case_name) > 200) {
        return ['valid' => false, 'error' => 'Case name is too long (max 200 characters)', 'value' => ''];
    }
    
    // Remove HTML tags and potentially harmful characters
    $case_name = strip_tags($case_name);
    $case_name = preg_replace('/[<>"\']/', '', $case_name);
    
    return ['valid' => true, 'error' => '', 'value' => $case_name];
}

/**
 * Validate file upload
 */
function validate_file_upload(array $file): array
{
    // Check for upload errors
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload blocked by extension'
        ];
        
        $error = $error_messages[$file['error']] ?? 'Unknown upload error';
        return ['valid' => false, 'error' => $error];
    }
    
    // Validate file size
    $max_size = (int)get_setting('max_file_size', '10485760'); // 10MB default
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size exceeds maximum allowed size of ' . format_bytes($max_size)];
    }
    
    // Validate MIME type
    $allowed_types = ['application/pdf', 'text/plain'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'File type not allowed. Only PDF and text files are supported.'];
    }
    
    // Additional security checks
    $filename = $file['name'];
    
    // Check for dangerous extensions
    $dangerous_extensions = ['.php', '.php3', '.php4', '.php5', '.phtml', '.asp', '.aspx', '.jsp', '.js', '.exe', '.bat', '.cmd', '.scr'];
    foreach ($dangerous_extensions as $ext) {
        if (stripos($filename, $ext) !== false) {
            return ['valid' => false, 'error' => 'File type not allowed for security reasons'];
        }
    }
    
    // Check for double extensions
    if (preg_match('/\.[a-zA-Z0-9]{2,4}\.[a-zA-Z0-9]{2,4}$/', $filename)) {
        return ['valid' => false, 'error' => 'Files with double extensions are not allowed'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Sanitize text content for database storage
 */
function sanitize_text_content(string $text): string
{
    // Convert to UTF-8 if needed
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    }
    
    // Remove null bytes
    $text = str_replace("\0", '', $text);
    
    // Normalize line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Remove excessive whitespace but preserve structure
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{4,}/', "\n\n\n", $text);
    
    return trim($text);
}

/**
 * Sanitize HTML output
 */
function sanitize_html_output(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Security Headers and Protection Functions
 */

/**
 * Set comprehensive security headers
 */
function set_security_headers(): void
{
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
           "img-src 'self' data:; " .
           "connect-src 'self'";
    
    header("Content-Security-Policy: $csp");
    
    // HTTPS enforcement in production
    if (defined('APP_ENV') && APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Rate Limiting Functions
 */

/**
 * Check rate limiting for login attempts
 */
function check_login_rate_limit(string $identifier): array
{
    $cache_key = "login_attempts_" . hash('sha256', $identifier);
    $attempts = get_cache_value($cache_key, 0);
    
    if ($attempts >= 5) {
        $lockout_time = get_cache_value($cache_key . "_lockout", 0);
        $remaining_time = max(0, $lockout_time - time());
        
        if ($remaining_time > 0) {
            return [
                'allowed' => false,
                'error' => "Too many login attempts. Try again in " . ceil($remaining_time / 60) . " minute(s).",
                'retry_after' => $remaining_time
            ];
        } else {
            // Reset attempts after lockout period
            clear_cache_value($cache_key);
            clear_cache_value($cache_key . "_lockout");
        }
    }
    
    return ['allowed' => true, 'error' => '', 'retry_after' => 0];
}

/**
 * Record failed login attempt
 */
function record_failed_login_attempt(string $identifier): void
{
    $cache_key = "login_attempts_" . hash('sha256', $identifier);
    $attempts = get_cache_value($cache_key, 0) + 1;
    
    // Store attempts for 1 hour
    set_cache_value($cache_key, $attempts, 3600);
    
    // Set lockout after 5 attempts
    if ($attempts >= 5) {
        // Lock out for 15 minutes
        set_cache_value($cache_key . "_lockout", time() + (15 * 60), 900);
    }
}

/**
 * Clear login attempts on successful login
 */
function clear_login_attempts(string $identifier): void
{
    $cache_key = "login_attempts_" . hash('sha256', $identifier);
    clear_cache_value($cache_key);
    clear_cache_value($cache_key . "_lockout");
}

/**
 * Simple file-based cache for rate limiting
 */
function get_cache_value(string $key, int $default = 0): int
{
    $cache_file = sys_get_temp_dir() . '/appeal_prospect_cache_' . hash('sha256', $key);
    
    if (!file_exists($cache_file)) {
        return $default;
    }
    
    $data = file_get_contents($cache_file);
    if ($data === false) {
        return $default;
    }
    
    $cache_data = json_decode($data, true);
    if (!$cache_data || !isset($cache_data['expires']) || $cache_data['expires'] < time()) {
        unlink($cache_file);
        return $default;
    }
    
    return (int)($cache_data['value'] ?? $default);
}

function set_cache_value(string $key, int $value, int $ttl = 3600): void
{
    $cache_file = sys_get_temp_dir() . '/appeal_prospect_cache_' . hash('sha256', $key);
    $cache_data = [
        'value' => $value,
        'expires' => time() + $ttl
    ];
    
    file_put_contents($cache_file, json_encode($cache_data), LOCK_EX);
}

function clear_cache_value(string $key): void
{
    $cache_file = sys_get_temp_dir() . '/appeal_prospect_cache_' . hash('sha256', $key);
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }
}

/**
 * Session Security Functions
 */

/**
 * Generate secure session ID
 */
function regenerate_secure_session_id(): void
{
    // Only regenerate if session is active
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Check session hijacking attempts
 */
function validate_session_security(): bool
{
    if (!isset($_SESSION['security'])) {
        return false;
    }
    
    $security = $_SESSION['security'];
    
    // Check user agent
    $current_user_agent = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($security['user_agent'] !== $current_user_agent) {
        return false;
    }
    
    // Check IP address (allow for proxy changes)
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($security['ip_address']) && $security['ip_address'] !== $current_ip) {
        // Allow subnet changes (for mobile/proxy users)
        $security_ip_parts = explode('.', $security['ip_address']);
        $current_ip_parts = explode('.', $current_ip);
        
        if (count($security_ip_parts) === 4 && count($current_ip_parts) === 4) {
            // Check if first 3 octets match (same subnet)
            if ($security_ip_parts[0] !== $current_ip_parts[0] || 
                $security_ip_parts[1] !== $current_ip_parts[1] || 
                $security_ip_parts[2] !== $current_ip_parts[2]) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Initialize session security data
 */
function initialize_session_security(): void
{
    $_SESSION['security'] = [
        'user_agent' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'created_at' => time(),
        'last_activity' => time()
    ];
}

/**
 * Update session activity
 */
function update_session_activity(): void
{
    if (isset($_SESSION['security'])) {
        $_SESSION['security']['last_activity'] = time();
    }
}

/**
 * Check session timeout
 */
function check_session_timeout(): bool
{
    if (!isset($_SESSION['security']['last_activity'])) {
        return false;
    }
    
    $timeout = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 86400; // 24 hours default
    return (time() - $_SESSION['security']['last_activity']) > $timeout;
}

/**
 * API Key Security Functions
 */

/**
 * Validate API key format
 */
function validate_api_key(string $key, string $type): array
{
    $key = trim($key);
    
    if (empty($key)) {
        return ['valid' => false, 'error' => 'API key is required'];
    }
    
    switch ($type) {
        case 'openai':
            if (!str_starts_with($key, 'sk-')) {
                return ['valid' => false, 'error' => 'OpenAI API key must start with "sk-"'];
            }
            if (strlen($key) < 20) {
                return ['valid' => false, 'error' => 'OpenAI API key appears to be invalid'];
            }
            break;
            
        case 'perplexity':
            if (strlen($key) < 10) {
                return ['valid' => false, 'error' => 'Perplexity API key appears to be invalid'];
            }
            break;
    }
    
    // Check for suspicious patterns
    if (preg_match('/[<>"\'\0]/', $key)) {
        return ['valid' => false, 'error' => 'API key contains invalid characters'];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $key];
}

/**
 * Security Logging Functions
 */

/**
 * Log security event
 */
function log_security_event(string $event, array $context = []): void
{
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'context' => $context
    ];
    
    $log_line = json_encode($log_entry) . "\n";
    
    // Log to secure file
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0750, true);
    }
    
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}

/**
 * Miscellaneous Security Functions
 */

/**
 * Generate secure filename
 */
function generate_secure_filename(string $original_filename = ''): string
{
    $extension = '';
    if (!empty($original_filename)) {
        $extension = '.' . pathinfo($original_filename, PATHINFO_EXTENSION);
    }
    
    return hash('sha256', uniqid('', true) . microtime() . random_bytes(16)) . $extension;
}

/**
 * Check if request is from allowed origin
 */
function validate_request_origin(): bool
{
    // Allow direct access (no referrer)
    if (!isset($_SERVER['HTTP_REFERER'])) {
        return true;
    }
    
    $referer = $_SERVER['HTTP_REFERER'];
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    // Check if referrer matches current host
    return strpos($referer, $host) !== false;
}

/**
 * Sanitize file path to prevent directory traversal
 */
function sanitize_file_path(string $path): string
{
    // Remove null bytes
    $path = str_replace("\0", '', $path);
    
    // Remove directory traversal attempts
    $path = str_replace(['../', '.\\', '..\\'], '', $path);
    
    // Remove leading slashes
    $path = ltrim($path, '/\\');
    
    return $path;
}