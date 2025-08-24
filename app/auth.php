<?php
// Authentication and session management functions
// Appeal Prospect MVP - Authentication Layer

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

/**
 * Start secure session with proper configuration
 */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Set comprehensive security headers
        set_security_headers();
        
        // Configure secure session settings
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', '86400'); // 24 hours
        
        // Set secure flag if using HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }
        
        session_start();
        
        // Check session security and timeout
        if (is_logged_in()) {
            if (!validate_session_security() || check_session_timeout()) {
                log_security_event('session_security_violation', [
                    'user_id' => current_user_id(),
                    'reason' => !validate_session_security() ? 'security_validation_failed' : 'session_timeout'
                ]);
                logout_user();
                return;
            }
            
            // Update session activity
            update_session_activity();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration']) || 
                time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
                regenerate_secure_session_id();
                $_SESSION['last_regeneration'] = time();
            }
        }
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        // Regenerate CSRF token periodically
        if (isset($_SESSION['csrf_token_time']) && 
            time() - $_SESSION['csrf_token_time'] > 3600) { // Every hour
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
    }
}

/**
 * Get current user ID from session
 */
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Check if current user is logged in
 */
function is_logged_in(): bool {
    return current_user_id() !== null;
}

/**
 * Check if current user is admin
 */
function current_is_admin(): bool {
    return (bool)($_SESSION['is_admin'] ?? false);
}

/**
 * Get current user data
 */
function current_user(): ?array {
    $user_id = current_user_id();
    if (!$user_id) {
        return null;
    }
    
    return db_query_single(
        "SELECT id, email, name, is_admin, created_at, last_login_at FROM users WHERE id = ?",
        [$user_id]
    );
}

/**
 * Require user to be logged in, redirect if not
 */
function require_login(string $redirect_to = ''): void {
    if (!is_logged_in()) {
        // Store the intended destination
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        $redirect_url = $redirect_to ?: app_url('login.php');
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Require user to be admin, redirect if not
 */
function require_admin(string $redirect_to = ''): void {
    require_login();
    if (!current_is_admin()) {
        $redirect_url = $redirect_to ?: app_url('index.php');
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Hash password using Argon2id
 */
function hash_password(string $password): string {
    // Use Argon2ID if available, otherwise fall back to bcrypt
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    } else {
        // Fallback to bcrypt with high cost
        return password_hash($password, PASSWORD_DEFAULT, [
            'cost' => 12
        ]);
    }
}

/**
 * Verify password against hash
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Login user with email and password
 */
function login_user(string $email, string $password): bool {
    // Validate inputs
    $email_validation = validate_email($email);
    if (!$email_validation['valid']) {
        log_security_event('login_attempt_invalid_email', ['email' => $email]);
        return false;
    }
    
    $email = $email_validation['value'];
    
    // Check rate limiting
    $rate_limit_check = check_login_rate_limit($email);
    if (!$rate_limit_check['allowed']) {
        log_security_event('login_rate_limit_exceeded', ['email' => $email]);
        return false;
    }
    
    // Validate request origin
    if (!validate_request_origin()) {
        log_security_event('login_invalid_origin', ['email' => $email]);
        record_failed_login_attempt($email);
        return false;
    }
    
    $user = db_query_single(
        "SELECT id, email, password_hash, name, is_admin FROM users WHERE email = ?",
        [$email]
    );
    
    if (!$user || !verify_password($password, $user['password_hash'])) {
        record_failed_login_attempt($email);
        log_security_event('login_failed', [
            'email' => $email,
            'user_exists' => $user ? true : false
        ]);
        return false;
    }
    
    // Clear failed login attempts on successful login
    clear_login_attempts($email);
    
    // Regenerate session ID for security
    regenerate_secure_session_id();
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['is_admin'] = (bool)$user['is_admin'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    
    // Initialize session security
    initialize_session_security();
    
    // Update last login timestamp
    db_execute(
        "UPDATE users SET last_login_at = NOW() WHERE id = ?",
        [$user['id']]
    );
    
    // Log successful login
    log_security_event('login_successful', [
        'user_id' => $user['id'],
        'email' => $email
    ]);
    
    return true;
}

/**
 * Logout current user
 */
function logout_user(): void {
    // Clear all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Register new user
 */
function register_user(string $email, string $password, string $name): bool {
    // Validate inputs
    $email_validation = validate_email($email);
    if (!$email_validation['valid']) {
        log_security_event('registration_invalid_email', ['email' => $email]);
        return false;
    }
    
    $password_validation = validate_password($password, true);
    if (!$password_validation['valid']) {
        log_security_event('registration_weak_password', ['email' => $email_validation['value']]);
        return false;
    }
    
    $name_validation = validate_name($name);
    if (!$name_validation['valid']) {
        log_security_event('registration_invalid_name', ['email' => $email_validation['value']]);
        return false;
    }
    
    // Use validated values
    $email = $email_validation['value'];
    $name = $name_validation['value'];
    
    // Check if email already exists
    $existing = db_query_single(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    
    if ($existing) {
        log_security_event('registration_duplicate_email', ['email' => $email]);
        return false; // Email already exists
    }
    
    // Insert new user
    $user_id = db_insert(
        "INSERT INTO users (email, password_hash, name, is_admin, created_at) VALUES (?, ?, ?, 0, NOW())",
        [$email, hash_password($password), $name]
    );
    
    return $user_id > 0;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token(string $token): bool {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Check token age (1 hour max)
    if (isset($_SESSION['csrf_token_time']) && 
        time() - $_SESSION['csrf_token_time'] > 3600) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF field for forms
 */
function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . sanitize_html_output($token) . '">';
}

/**
 * Validate CSRF token from POST data
 */
function validate_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($token)) {
        log_security_event('csrf_token_invalid', [
            'user_id' => current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        return false;
    }
    
    return true;
}

/**
 * Check if user has permission to access case
 */
function user_can_access_case(int $case_id, ?int $user_id = null): bool {
    if ($user_id === null) {
        $user_id = current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Admin can access all cases
    if (current_is_admin()) {
        return true;
    }
    
    // User can access their own cases
    $case = db_query_single(
        "SELECT user_id FROM cases WHERE id = ?",
        [$case_id]
    );
    
    return $case && (int)$case['user_id'] === $user_id;
}

?>