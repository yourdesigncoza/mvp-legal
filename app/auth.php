<?php
// Authentication and session management functions
// Appeal Prospect MVP - Authentication Layer

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Start secure session with proper configuration
 */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Configure secure session settings
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_only_cookies', '1');
        
        // Set secure flag if using HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }
        
        session_start();
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
function require_login(string $redirect_to = '/login.php'): void {
    if (!is_logged_in()) {
        // Store the intended destination
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_to);
        exit;
    }
}

/**
 * Require user to be admin, redirect if not
 */
function require_admin(string $redirect_to = '/index.php'): void {
    require_login();
    if (!current_is_admin()) {
        header('Location: ' . $redirect_to);
        exit;
    }
}

/**
 * Hash password using Argon2id
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iterations
        'threads' => 3          // 3 threads
    ]);
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
    $user = db_query_single(
        "SELECT id, email, password_hash, name, is_admin FROM users WHERE email = ?",
        [$email]
    );
    
    if (!$user || !verify_password($password, $user['password_hash'])) {
        return false;
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['is_admin'] = (bool)$user['is_admin'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    
    // Update last login timestamp
    db_execute(
        "UPDATE users SET last_login_at = NOW() WHERE id = ?",
        [$user['id']]
    );
    
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
    // Check if email already exists
    $existing = db_query_single(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    
    if ($existing) {
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
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF field for forms
 */
function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST data
 */
function validate_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return verify_csrf_token($token);
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