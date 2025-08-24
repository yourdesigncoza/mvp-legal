-- Appeal Prospect MVP Database Schema
-- MySQL 8.0+ with UTF8MB4 support
-- Created: August 24, 2025

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create database (uncomment if needed)
-- CREATE DATABASE appeal_prospect_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE appeal_prospect_mvp;

-- ============================================================================
-- USERS TABLE
-- Stores user accounts with authentication and role information
-- ============================================================================

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_email (email),
    INDEX idx_users_created_at (created_at),
    INDEX idx_users_is_admin (is_admin)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- CASES TABLE
-- Stores judgment analysis cases with full text and results
-- ============================================================================

CREATE TABLE cases (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    case_name VARCHAR(200) NOT NULL,
    original_filename VARCHAR(255) NULL DEFAULT NULL,
    mime_type VARCHAR(100) NULL DEFAULT NULL,
    judgment_text LONGTEXT NOT NULL,
    stored_filename VARCHAR(255) NULL DEFAULT NULL,
    file_size INT UNSIGNED NULL DEFAULT NULL,
    analysis_result LONGTEXT NULL DEFAULT NULL,
    structured_analysis JSON NULL DEFAULT NULL,
    citations JSON NULL DEFAULT NULL,
    research_summary TEXT NULL DEFAULT NULL,
    status ENUM('uploaded', 'analyzing', 'analyzed', 'failed') DEFAULT 'uploaded',
    token_in INT UNSIGNED NULL DEFAULT NULL,
    token_out INT UNSIGNED NULL DEFAULT NULL,
    error_message TEXT NULL DEFAULT NULL,
    started_analysis_at TIMESTAMP NULL DEFAULT NULL,
    completed_analysis_at TIMESTAMP NULL DEFAULT NULL,
    analyzed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY fk_cases_user_id (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_cases_user_id (user_id),
    INDEX idx_cases_created_at (created_at),
    INDEX idx_cases_status (status),
    INDEX idx_cases_user_created (user_id, created_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- SETTINGS TABLE
-- Stores application settings and encrypted API keys
-- ============================================================================

CREATE TABLE settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL DEFAULT NULL,
    is_encrypted TINYINT(1) NOT NULL DEFAULT 0,
    description TEXT NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_settings_key (setting_key),
    INDEX idx_settings_updated (updated_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- INITIAL SETTINGS DATA
-- Default configuration values
-- ============================================================================

INSERT INTO settings (setting_key, setting_value, is_encrypted, description) VALUES
('openai_api_key', NULL, 1, 'OpenAI API key for GPT-4o-mini analysis'),
('perplexity_api_key', NULL, 1, 'Perplexity API key for web research (optional)'),
('max_file_size', '10485760', 0, 'Maximum upload file size in bytes (10MB)'),
('allowed_mime_types', 'application/pdf,text/plain', 0, 'Comma-separated list of allowed MIME types'),
('app_name', 'Appeal Prospect MVP', 0, 'Application display name'),
('app_version', '1.0.0', 0, 'Current application version'),
('seed_key', NULL, 0, 'Key required to run seed script (set during installation)'),
('maintenance_mode', '0', 0, 'Enable maintenance mode (1=enabled, 0=disabled)');

-- ============================================================================
-- INDEXES AND CONSTRAINTS SUMMARY
-- ============================================================================

-- Users table:
-- - Primary key: id
-- - Unique: email
-- - Indexes: created_at, is_admin

-- Cases table:
-- - Primary key: id
-- - Foreign key: user_id -> users(id) CASCADE DELETE
-- - Indexes: user_id, created_at, status, user_id+created_at

-- Settings table:
-- - Primary key: id
-- - Unique: setting_key
-- - Index: updated_at

-- ============================================================================
-- NOTES
-- ============================================================================

-- 1. All tables use utf8mb4 for proper Unicode support including emojis
-- 2. Foreign key constraints ensure data integrity
-- 3. JSON column for citations provides flexibility
-- 4. Encrypted settings for sensitive API keys
-- 5. Status tracking for async processing
-- 6. Token usage tracking for cost monitoring
-- 7. Timestamps for audit trails
-- 8. Proper indexing for performance

-- To create the database and user (run as MySQL admin):
-- CREATE DATABASE appeal_prospect_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- CREATE USER 'appeal_user'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT ALL PRIVILEGES ON appeal_prospect_mvp.* TO 'appeal_user'@'localhost';
-- FLUSH PRIVILEGES;