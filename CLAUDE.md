# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Appeal Prospect MVP** is a legal judgment analysis web application built with vanilla PHP 8+ and MySQL 8+ (LAMPP stack). It allows users to upload court judgments (PDF or text) and receive AI-powered appeal prospect analysis using OpenAI GPT-4o-mini with optional Perplexity web research.

**Tech Stack**: PHP 8+, MySQL 8+ with UTF8MB4, Apache, Phoenix UI (Bootstrap 5), OpenAI GPT-4o-mini, Perplexity API

## Database Setup

The application requires a MySQL database named `appeal_prospect_mvp`. Set up the database:

```sql
mysql -u root -p
CREATE DATABASE appeal_prospect_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE appeal_prospect_mvp;
SOURCE schema.sql;
```

## Initial Application Setup

1. **Database Configuration**: Update `app/config.php` with your MySQL credentials
2. **Seed Database**: Visit `/public/seed.php?key=appeal_prospect_setup_2025` to create initial admin user and settings
3. **API Keys**: Login as admin (`admin@example.com` / `admin123`) and configure OpenAI/Perplexity API keys in admin panel

## Development Commands

**Testing**:
```bash
# Run all tests
php run-tests.php

# Run specific test suite
php run-tests.php unit
php run-tests.php integration
php run-tests.php functional
php run-tests.php security

# Run specific test file
php run-tests.php SecurityTest.php

# PHPUnit directly (if installed via Composer)
vendor/bin/phpunit --configuration phpunit.xml
```

**Dependencies**:
```bash
# Install PHPUnit for testing
composer install

# Install pdftotext utility (required for PDF text extraction)
sudo apt-get install poppler-utils  # Ubuntu/Debian
```

**Permission Setup**:
```bash
# Set proper permissions for uploads directory
chmod 755 uploads/
chmod 644 uploads/.htaccess

# Ensure test coverage directory is writable
chmod 755 tests/coverage/
```

**Development Server** (if not using XAMPP):
```bash
cd /opt/lampp/htdocs/mvp-legal/public
php -S localhost:8080
```

## Architecture & Code Structure

### Core Architecture Pattern

The application follows a **3-layer architecture**:

1. **Presentation Layer** (`public/`): User-facing PHP pages with Phoenix UI templates
2. **Business Logic Layer** (`app/`): Core functionality classes and handlers  
3. **Data Layer** (`app/db.php`, `schema.sql`): Database operations with PDO singleton pattern

### Key Architectural Components

**Authentication & Session Management** (`app/auth.php`):
- Session-based authentication with Argon2id password hashing
- CSRF protection on all state-changing operations
- Role-based access control (user/admin)
- Session security hardening with HttpOnly/SameSite flags

**Database Layer** (`app/db.php`):
- PDO singleton pattern with UTF8MB4 charset
- Helper functions: `db_execute()`, `db_query()`, `db_query_single()`, `db_last_insert_id()`
- Prepared statements for all database operations
- Connection pooling and error handling

**AI Integration Layer**:
- **GPTHandler** (`app/gpt_handler.php`): OpenAI GPT-4o-mini integration with System Prompt v5.2
- **PerplexityHandler** (`app/perplexity.php`): Optional web research with graceful degradation
- Token counting, cost tracking, and error recovery mechanisms

**File Processing Layer** (`app/pdf_parser.php`):
- PDF text extraction using system `pdftotext` command
- Secure file storage with user-specific directories
- MIME type validation and file size limits
- Text normalization and UTF-8 encoding

**Settings Management** (`app/settings.php`):
- AES-256-GCM encryption for sensitive API keys stored in database
- Environment-aware configuration loading
- Centralized application settings with database persistence

### Phoenix UI Integration

The application uses **Phoenix UI framework** (Bootstrap 5.3+ based) located in `/phoenix/` directory:

- **Never modify files in `/phoenix/`** - this is reference material only
- **Use Phoenix CSS classes** from `public/assets/css/phoenix-bootstrap.css`
- **Template Structure**: `app/templates/` contains header, footer, navbar, and layout templates
- **Component Patterns**: Phoenix provides 141+ UI component examples for consistent styling

### Data Flow Architecture

**File Upload → Analysis → Results**:
1. `upload.php` → `pdf_parser.php` → Database (cases table, status='uploaded')
2. `analyze.php` → `gpt_handler.php` + `perplexity.php` → Database (status='analyzed')
3. `results.php` → Display structured 13-section analysis with citations

**User Case Management**:
- All cases are user-scoped (foreign key to users table)
- Admin users can view/manage all cases across users
- File storage uses user-specific subdirectories in `/uploads/`

### Security Architecture

**Multi-Layer Security Approach**:
- **Authentication**: Argon2id hashing, session regeneration, login throttling
- **Authorization**: Role-based access control with admin privilege escalation
- **CSRF Protection**: Token validation on all POST/PUT/DELETE operations  
- **File Security**: .htaccess restrictions, MIME validation, secure filename generation
- **Database Security**: Prepared statements, input sanitization, SQL injection prevention
- **API Security**: Rate limiting, error handling, key validation

### Critical Integration Points

**Database Schema Dependencies**:
- The `cases` table status enum must match analysis workflow: `uploaded` → `analyzing` → `analyzed` → `failed`
- `structured_analysis` JSON column stores parsed 13-section analysis results
- `citations` JSON column stores Perplexity research results with source URLs

**API Key Management**:
- OpenAI API key required for core functionality
- Perplexity API key optional (graceful degradation if missing)
- Keys stored encrypted in `settings` table, never in config files
- Test connection functions available: `test_openai_connection()`, `test_perplexity_connection()`

**Template Inheritance**:
- All pages include `app/templates/header.php` and `app/templates/footer.php`
- Navigation managed through `app/templates/navbar.php`
- Flash messages handled via `set_flash_message()` / `get_flash_messages()`

**System Prompt Integration**:
- Legal analysis prompt stored in `app/prompts/appeal_prospect_v5_2.md`
- Fallback prompt embedded in `GPTHandler` class if file missing
- 13-section analysis structure: Review Summary, Issues Identified, Legal Grounds, etc.

## File Upload & Processing Workflow

The application handles two upload paths:
1. **PDF Upload**: `upload.php` → `PDFParser::processUploadedFile()` → `pdftotext` extraction → Database
2. **Text Input**: `upload.php` → `PDFParser::processTextInput()` → Text validation → Database

Both paths create case records with `status='uploaded'`, then redirect to `analyze.php` for AI processing.

## Common Development Patterns

**Adding New Analysis Features**:
1. Extend `GPTHandler::parseStructuredAnalysis()` for new analysis sections
2. Update database schema if new fields needed for results storage
3. Modify `results.php` display logic for new analysis components

**Phoenix UI Component Usage**:
1. Reference `/phoenix/` directory for component HTML structure
2. Copy HTML patterns, adapting data binding for PHP variables
3. Maintain Phoenix CSS class naming conventions for consistency

**API Integration Extensions**:
1. Follow pattern in `gpt_handler.php` and `perplexity.php` for new AI services  
2. Implement graceful degradation and error recovery
3. Store API configurations in encrypted settings table

## Testing Framework

The application includes a comprehensive testing suite with 4 test categories:

**Test Execution**:
- **Unit Tests** (`tests/Unit/`): Individual component testing (SecurityTest, PDFParserTest, FormValidatorTest)  
- **Integration Tests** (`tests/Integration/`): Cross-component workflows (AuthenticationTest, AIAnalysisTest)
- **Functional Tests** (`tests/Functional/`): End-to-end user journeys (UserWorkflowTest)
- **Security Tests**: CSRF, input validation, file upload security, session management

**Test Database**: Tests use in-memory SQLite, created fresh per run with automatic cleanup

**Coverage Reports**: Generated in `tests/coverage/index.html` with targets of 90%+ for security functions, 80%+ for business logic

## Configuration Management

**Environment Settings** (`app/config.php`):
- Database credentials and connection settings
- File upload limits (10MB max, PDF/text allowed)
- OpenAI API configuration (GPT-4o-mini, 4000 max tokens)
- Security settings (session lifetime, CSRF tokens)
- South African timezone (Africa/Johannesburg)

**Runtime Environment Variables**:
- `APP_ENV`: 'development' or 'production' 
- `APP_DEBUG`: Controls error display and logging
- PHP settings: 256M memory, 300s execution time

This MVP demonstrates a complete legal tech workflow suitable for production scaling with proper framework integration and infrastructure.