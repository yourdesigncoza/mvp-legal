# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Critical Non-Obvious Patterns

**Database Setup**: Database MUST be named `appeal_prospect_mvp` with UTF8MB4 collation - other names will break the application
**Initial Setup**: Visit `/public/seed.php?key=appeal_prospect_setup_2025` (exact key required) to initialize admin user and settings
**Phoenix UI**: References `/phoenix/` directory for component patterns but this directory doesn't exist in repo - it's reference material only
**API Keys**: Stored encrypted in database `settings` table using AES-256-GCM, never in config files
**File Processing**: PDF extraction uses system `pdftotext` command (poppler-utils package required), not PHP libraries
**Case Status Flow**: Database enum must follow exact sequence: `uploaded` → `analyzing` → `analyzed` → `failed`
**Template Pattern**: All public pages must include both header.php AND footer.php from app/templates/ - missing either breaks layout
**Security Files**: .htaccess files in app/ and uploads/ directories are critical - these prevent direct web access to sensitive files
**Settings Encryption**: Use `encrypt_setting()` / `decrypt_setting()` functions from app/settings.php for API keys, never store plain text
**Database Helpers**: Use `db_execute()`, `db_query()`, `db_query_single()` from app/db.php instead of raw PDO for proper error handling
**AI Analysis Structure**: Must return exactly 13 sections matching the format in app/prompts/appeal_prospect_v5_2.md
**File Storage**: User files stored in uploads/{user_id}/ subdirectories, never in root uploads folder
**PDFToText Command**: Uses LD_LIBRARY_PATH override to avoid XAMPP library conflicts with system libraries
**Test Database**: Uses SQLite in-memory for testing, not MySQL - completely different engine
**Authentication Flow**: check_auth() function automatically redirects - don't add manual redirects after calling it
**Secure Cookie Flag**: `session.cookie_secure` is intentionally NOT forced in root .htaccess so local HTTP logins work - app/auth.php auto-enables it under HTTPS. Do not re-add php_value session.cookie_secure 1 for HTTP environments (breaks logins in-progress/curl)
**Error Handler Self-Contained**: error_handler.php must never depend on auth.php functions - use its own ErrorHandler::currentUserId() guard. 404.php only loads error_handler.php
**Tests Removed**: The tests/ directory was deleted in commit a427030. phpunit.xml and run-tests.php still reference it. Restore from git (git checkout a427030^ -- tests/) or update references before running the suite
**PHP CLI Path**: XAMPP's PHP is at /opt/lampp/bin/php (not in PATH)

## Test Commands
```bash
# NOTE: tests/ directory was removed from the repo (commit a427030).
# Restore first: git checkout a427030^ -- tests/
/opt/lampp/bin/php run-tests.php                    # Run all tests
/opt/lampp/bin/php run-tests.php SecurityTest.php   # Run specific test file
/opt/lampp/bin/php vendor/bin/phpunit --configuration phpunit.xml  # PHPUnit directly
```

## Required System Dependencies
- poppler-utils (for pdftotext command)
- PHP 8+ with PDO, OpenSSL extensions
- MySQL 8+ with UTF8MB4 support