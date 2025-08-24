<?php
// Settings and API key management
// Appeal Prospect MVP - Settings Layer

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

/**
 * Encryption key for sensitive settings
 * In production, this should be stored securely
 */
function get_encryption_key(): string {
    // Use a combination of server-specific values for key generation
    $server_key = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $app_key = APP_NAME . APP_VERSION;
    return hash('sha256', $server_key . $app_key . 'appeal_prospect_encryption');
}

/**
 * Encrypt sensitive data
 */
function encrypt_value(string $value): string {
    $key = get_encryption_key();
    $cipher = 'AES-256-GCM';
    $iv = random_bytes(16);
    $tag = '';
    
    $encrypted = openssl_encrypt($value, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    // Combine IV, tag, and encrypted data
    return base64_encode($iv . $tag . $encrypted);
}

/**
 * Decrypt sensitive data
 */
function decrypt_value(string $encrypted_value): ?string {
    $key = get_encryption_key();
    $cipher = 'AES-256-GCM';
    
    $data = base64_decode($encrypted_value);
    if ($data === false) {
        return null;
    }
    
    $iv = substr($data, 0, 16);
    $tag = substr($data, 16, 16);
    $encrypted = substr($data, 32);
    
    $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    return $decrypted !== false ? $decrypted : null;
}

/**
 * Get setting value from database
 */
function get_setting(string $key, ?string $default = null): ?string {
    $setting = db_query_single(
        "SELECT setting_value, is_encrypted FROM settings WHERE setting_key = ?",
        [$key]
    );
    
    if (!$setting) {
        return $default;
    }
    
    $value = $setting['setting_value'];
    
    // Decrypt if encrypted
    if ($setting['is_encrypted'] && $value !== null) {
        $decrypted = decrypt_value($value);
        return $decrypted ?? $default;
    }
    
    return $value ?? $default;
}

/**
 * Set setting value in database
 */
function set_setting(string $key, ?string $value, bool $is_encrypted = false, ?string $description = null): bool {
    $encrypted_value = $value;
    
    // Encrypt if requested and value is not null
    if ($is_encrypted && $value !== null) {
        $encrypted_value = encrypt_value($value);
    }
    
    // Check if setting exists
    $existing = db_query_single(
        "SELECT id FROM settings WHERE setting_key = ?",
        [$key]
    );
    
    if ($existing) {
        // Update existing setting
        $affected = db_execute(
            "UPDATE settings SET setting_value = ?, is_encrypted = ?, updated_at = NOW() WHERE setting_key = ?",
            [$encrypted_value, $is_encrypted ? 1 : 0, $key]
        );
    } else {
        // Insert new setting
        $affected = db_insert(
            "INSERT INTO settings (setting_key, setting_value, is_encrypted, description, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
            [$key, $encrypted_value, $is_encrypted ? 1 : 0, $description]
        ) > 0 ? 1 : 0;
    }
    
    return $affected > 0;
}

/**
 * Get OpenAI API key
 */
function get_openai_key(): ?string {
    return get_setting('openai_api_key');
}

/**
 * Set OpenAI API key
 */
function set_openai_key(string $key): bool {
    return set_setting('openai_api_key', $key, true, 'OpenAI API key for GPT-4o-mini analysis');
}

/**
 * Get Perplexity API key
 */
function get_perplexity_key(): ?string {
    return get_setting('perplexity_api_key');
}

/**
 * Set Perplexity API key
 */
function set_perplexity_key(string $key): bool {
    return set_setting('perplexity_api_key', $key, true, 'Perplexity API key for web research');
}

/**
 * Check if OpenAI is configured
 */
function is_openai_configured(): bool {
    $key = get_openai_key();
    return $key !== null && strlen(trim($key)) > 0;
}

/**
 * Check if Perplexity is configured
 */
function is_perplexity_configured(): bool {
    $key = get_perplexity_key();
    return $key !== null && strlen(trim($key)) > 0;
}

/**
 * Get all non-encrypted settings for admin display
 */
function get_all_settings(): array {
    return db_query(
        "SELECT setting_key, setting_value, is_encrypted, description, updated_at 
         FROM settings 
         ORDER BY setting_key"
    );
}

/**
 * Get application configuration summary
 */
function get_app_config(): array {
    return [
        'app_name' => get_setting('app_name', APP_NAME),
        'app_version' => get_setting('app_version', APP_VERSION),
        'max_file_size' => get_setting('max_file_size', (string)MAX_FILE_SIZE),
        'allowed_mime_types' => get_setting('allowed_mime_types', implode(',', ALLOWED_MIME_TYPES)),
        'maintenance_mode' => get_setting('maintenance_mode', '0') === '1',
        'openai_configured' => is_openai_configured(),
        'perplexity_configured' => is_perplexity_configured()
    ];
}

/**
 * Check if application is in maintenance mode
 */
function is_maintenance_mode(): bool {
    return get_setting('maintenance_mode', '0') === '1';
}

/**
 * Set maintenance mode
 */
function set_maintenance_mode(bool $enabled): bool {
    return set_setting('maintenance_mode', $enabled ? '1' : '0', false, 'Enable maintenance mode');
}

/**
 * Test API key functionality
 */
function test_openai_key(?string $key = null): array {
    if ($key === null) {
        $key = get_openai_key();
    }
    
    if (!$key) {
        return ['success' => false, 'message' => 'No API key provided'];
    }
    
    // Simple test request to OpenAI
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.openai.com/v1/models',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code === 200) {
        return ['success' => true, 'message' => 'OpenAI API key is valid'];
    } else {
        return ['success' => false, 'message' => 'Invalid OpenAI API key or connection failed'];
    }
}

/**
 * Test Perplexity API key
 */
function test_perplexity_key(?string $key = null): array {
    if ($key === null) {
        $key = get_perplexity_key();
    }
    
    if (!$key) {
        return ['success' => false, 'message' => 'No Perplexity API key provided'];
    }
    
    // Test request to Perplexity
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.perplexity.ai/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'llama-3.1-sonar-small-online',
            'messages' => [
                ['role' => 'user', 'content' => 'Test connection']
            ],
            'max_tokens' => 1
        ])
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code === 200) {
        return ['success' => true, 'message' => 'Perplexity API key is valid'];
    } else {
        return ['success' => false, 'message' => 'Invalid Perplexity API key or connection failed'];
    }
}

?>