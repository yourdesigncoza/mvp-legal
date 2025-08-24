# Appeal Prospect MVP - Development TODO

## Project Overview
**Goal**: Build a lightweight demo web app for legal judgment analysis using PHP + MySQL (LAMPP stack) with Phoenix UI (Bootstrap 5), OpenAI GPT-4o-mini, and optional Perplexity integration.

**Stack**: Vanilla PHP 8+, MySQL 8+ (UTF8MB4), Apache, cURL for APIs, Phoenix UI components
**Security**: Argon2id password hashing, CSRF protection, session hardening

---

## Phase 1: Project Foundation & Database Setup
**Goal**: Establish core project structure and database schema

### 1.1 Directory Structure Setup
- [x] **Create app/ directory** - Backend logic, auth, API handlers ✅
  - Create `app/auth.php`, `app/db.php`, `app/config.php` placeholders
  - Create `app/prompts/` subdirectory for System Prompt v5.2
- [x] **Create public/ directory** - All user-facing PHP pages ✅
  - This will contain index.php, login.php, register.php, etc.
- [x] **Create uploads/ directory** - PDF file storage (secured) ✅
  - Add .htaccess to deny direct access to uploaded files
- [x] **Create assets/ directory in public/** - CSS/JS for Phoenix UI ✅
  - Subdirectories: css/, js/, fonts/, images/

### 1.2 Database Schema Creation
- [x] **Create schema.sql file** with complete database structure: ✅
  ```sql
  -- users table: id, email, password_hash, name, is_admin, created_at, last_login_at
  -- cases table: id, user_id, case_name, original_filename, mime_type, judgment_text, analysis_result, citations, status, token_in, token_out, created_at
  -- settings table: id, setting_key, setting_value, is_encrypted, updated_at
  ```
- [x] **Add proper indexes** - email (unique), user_id foreign key, created_at for sorting ✅
- [x] **Set UTF8MB4 collation** - For proper character encoding support ✅

### 1.3 Security Configuration
- [x] **Create .htaccess files**: ✅
  - Root .htaccess: PHP error handling, security headers
  - app/ .htaccess: Deny all access
  - uploads/ .htaccess: Deny direct access, only serve through PHP
- [x] **Configure upload restrictions** - 10MB max, PDF/text only ✅

---

## Phase 2: Core Backend Infrastructure
**Goal**: Build secure authentication and database connectivity

### 2.1 Database Connection (app/db.php)
- [x] **Create PDO connection class** with error handling ✅
- [x] **Implement connection pooling** - Single instance pattern ✅
- [x] **Add UTF8MB4 charset configuration** ✅
- [x] **Include prepared statement helpers** - execute, fetchAll, etc. ✅

### 2.2 Authentication System (app/auth.php)
- [x] **Session management functions**: ✅
  - `start_session()` - Secure session config (HttpOnly, SameSite=Lax)
  - `current_user_id()` - Get logged-in user ID
  - `current_is_admin()` - Check admin status
  - `require_login()` - Redirect if not logged in
  - `require_admin()` - Admin-only access control
- [x] **Password handling**: ✅
  - `hash_password($password)` - Using PASSWORD_ARGON2ID
  - `verify_password($password, $hash)` - Verification
- [x] **CSRF Protection**: ✅
  - `generate_csrf_token()` - Create session-based token
  - `verify_csrf_token($token)` - Validate token
  - `csrf_field()` - HTML helper for forms

### 2.3 Configuration Management (app/config.php)
- [x] **Database credentials only** - Host, name, user, password ✅
- [x] **Environment detection** - dev/production modes ✅
- [x] **Error reporting configuration** - Hide in production ✅

### 2.4 Settings Management (app/settings.php)
- [x] **API key retrieval functions**: ✅
  - `get_setting($key)` - Retrieve decrypted values
  - `set_setting($key, $value)` - Store encrypted values
  - `get_openai_key()` - OpenAI API key
  - `get_perplexity_key()` - Perplexity API key (optional)
- [x] **Encryption helpers** - For sensitive data storage ✅

---

## Phase 3: Phoenix UI Foundation
**Goal**: Set up Bootstrap 5/Phoenix design system

### 3.1 Phoenix Asset Integration
- [x] **Copy Phoenix CSS/JS from examples** to public/assets/ ✅
- [x] **Create base template structure** - Header, footer, navigation ✅
- [x] **Set up Phoenix color scheme** - Primary, secondary, success, warning, etc. ✅
- [x] **Configure responsive breakpoints** - Mobile-first design ✅

### 3.2 Common UI Components
- [x] **Navigation bar** - Login/logout, user menu, admin link ✅
- [x] **Alert system** - Success, error, warning, info messages ✅
- [x] **Form styling** - Phoenix input groups, validation states ✅
- [x] **Button styles** - Primary, secondary, outline variants ✅
- [x] **Card layouts** - For cases, results, admin dashboard ✅

---

## Phase 4: User Authentication Pages
**Goal**: Complete user registration and login system

### 4.1 Registration Page (public/register.php)
- [x] **Phoenix form design**: ✅
  - Name, email, password, confirm password fields
  - Phoenix input groups with icons
  - Client-side validation with Phoenix validation styles
- [x] **Server-side processing**: ✅
  - Email uniqueness check
  - Password strength validation
  - Argon2id hashing
  - CSRF token validation
- [x] **Error handling** - Phoenix alert components for validation errors ✅

### 4.2 Login Page (public/login.php)
- [x] **Phoenix login form**: ✅
  - Email/password fields
  - "Remember me" checkbox (optional)
  - Phoenix card layout
- [x] **Authentication logic**: ✅
  - Password verification
  - Session creation with `session_regenerate_id()`
  - Last login timestamp update
- [x] **Redirect handling** - After login, redirect to intended page ✅

### 4.3 Logout Handler (public/logout.php)
- [x] **Session cleanup**: ✅
  - `session_destroy()`
  - Clear all session variables
  - Redirect to login page
- [x] **CSRF protection** - Ensure logout is intentional ✅

### 4.4 Seed Data Script (public/seed.php)
- [x] **Admin user creation**: ✅
  - Hardcoded admin credentials (changeable after first login)
  - Demo user for testing
  - Protected by SEED_KEY parameter
- [x] **Initial settings**: ✅
  - Default API key placeholders
  - System configuration
- [x] **Self-deletion** - Script removes itself after successful run ✅

---

## Phase 5: File Upload & Text Processing
**Goal**: Handle PDF uploads and text extraction

### 5.1 Upload Interface (public/upload.php)
- [x] **Phoenix file upload component**: ✅
  - Drag-and-drop area
  - File size/type validation display
  - Progress bar during upload
- [x] **Text paste alternative**: ✅
  - Phoenix textarea with character count
  - Formatting preservation
- [x] **Case naming**: ✅
  - Auto-generate from filename or first line of text
  - User can override with custom name

### 5.2 PDF Processing (app/pdf_parser.php)
- [x] **PDF to text conversion**: ✅
  - Use system `pdftotext` command
  - Handle OCR failures gracefully
  - Text cleanup and normalization
- [x] **File validation**: ✅
  - MIME type checking
  - File size limits (10MB)
  - Content validation (not empty)
- [x] **Storage management**: ✅
  - Secure file storage outside web root
  - Filename sanitization
  - User-specific subdirectories

---

## Phase 6: AI Integration Layer
**Goal**: Connect with OpenAI and Perplexity APIs

### 6.1 OpenAI Handler (app/gpt_handler.php)
- [x] **API connection**: ✅
  - cURL implementation for `/v1/chat/completions`
  - GPT-4o-mini model configuration
  - Token counting and cost tracking
- [x] **Prompt management**: ✅
  - Load System Prompt v5.2 from file
  - Combine with judgment text
  - Handle long documents (chunking if needed)
- [x] **Response processing**: ✅
  - Parse structured output
  - Extract 13 sections
  - Handle API errors gracefully

### 6.2 Perplexity Integration (app/perplexity.php)
- [x] **Optional web research**: ✅
  - Only run if API key is available
  - Simple prompt for South African legal sources
  - Return up to 5 relevant URLs
- [x] **Graceful degradation**: ✅
  - Show "No live sources available" if key missing
  - Continue analysis without web data
- [x] **Citation formatting**: ✅
  - Store as JSON in database
  - Display formatted list in results

### 6.3 Analysis Processor (public/analyze.php)
- [x] **Processing workflow**: ✅
  - Validate input (PDF or text)
  - Extract/normalize text
  - Call OpenAI API
  - Optional Perplexity research
  - Save results to database
- [x] **Progress indication**: ✅
  - Phoenix spinner during processing
  - Status updates (Extracting text... Analyzing... etc.)
- [x] **Error handling**: ✅
  - API failures
  - Rate limiting
  - Malformed responses

---

## Phase 7: Results Display System
**Goal**: Show structured 13-section analysis with Phoenix UI

### 7.1 Results Page (public/results.php)
- [x] **Phoenix layout**: ✅
  - Header with case name and date
  - Navigation breadcrumbs
  - Action buttons (Back to cases, New analysis)
- [x] **13-Section Structure** (simplified for consistent display): ✅
  1. **Review Summary** - Phoenix card with key points
  2. **Issues Identified** - Phoenix list group
  3. **Legal Grounds** - Phoenix accordion for details
  4. **Strength Assessment** - Phoenix progress bar with percentage
  5. **Available Remedies** - Phoenix badge list
  6. **Procedural Requirements** - Phoenix timeline/steps
  7. **Ethical Considerations** - Phoenix alert box
  8. **Appeal Strategy** - Phoenix card with recommendations
  9. **Constitutional Aspects** - Phoenix collapsible section
  10. **Success Probability** - Phoenix gauge/meter
  11. **Risks & Challenges** - Phoenix warning alerts
  12. **Layperson Summary** - Phoenix simplified card
  13. **Sources/References** - Phoenix citation list

### 7.2 Data Processing (app/save_fetch.php)
- [x] **Result storage**: ✅
  - Save analysis to cases table
  - Store token usage data
  - Update case status
- [x] **Result retrieval**: ✅
  - Fetch by case ID and user
  - Include citation data
  - Format for display

---

## Phase 8: Case History Management
**Goal**: User can view and manage their analysis history

### 8.1 My Cases Page (public/my-cases.php)
- [x] **Phoenix table layout**: ✅
  - Case name, date, status columns
  - Phoenix pagination component
  - Search/filter functionality
- [x] **Case actions**: ✅
  - View results link
  - Download original PDF (if available)
  - Delete case (with confirmation)
- [x] **Empty state** - Phoenix empty state component when no cases ✅

### 8.2 Case Management Functions
- [x] **Listing logic**: ✅
  - User-specific case retrieval
  - Date sorting (newest first)
  - Status filtering
- [x] **Search functionality**: ✅
  - Case name search
  - Date range filtering
  - Phoenix form controls

---

## Phase 9: Admin Dashboard
**Goal**: Administrative oversight and API key management

### 9.1 Admin Interface (public/admin.php)
- [x] **Dashboard layout**: ✅
  - Phoenix sidebar navigation
  - Statistics cards (total users, cases, etc.)
  - Recent activity feed
- [x] **API Key Management**: ✅
  - Phoenix form for OpenAI key
  - Phoenix form for Perplexity key
  - Test connection buttons
  - Encryption status indicators

### 9.2 Case Management (Admin)
- [x] **All cases view**: ✅
  - Phoenix table with all user cases
  - User filter dropdown
  - Case status badges
- [x] **Case actions**: ✅
  - View any case result
  - Delete inappropriate content
  - CSRF-protected delete buttons with Phoenix modals

### 9.3 User Management
- [x] **User listing**: ✅
  - Phoenix table with user details
  - Admin status toggle
  - Last login information
- [x] **User actions**: ✅
  - Promote/demote admin status
  - View user's cases
  - Account management (optional)

---

## Phase 10: Security Implementation
**Goal**: Ensure robust security throughout the application

### 10.1 Input Validation
- [x] **All form inputs** - Sanitize and validate ✅
- [x] **File uploads** - MIME type, size, content validation ✅
- [x] **SQL injection prevention** - Prepared statements only ✅
- [x] **XSS protection** - htmlspecialchars() on all output ✅

### 10.2 Authentication Security
- [x] **Session security**: ✅
  - HttpOnly cookies
  - SameSite=Lax setting
  - Secure flag in HTTPS
  - Session timeout
- [x] **CSRF protection** on all forms: ✅
  - Registration, login forms
  - File upload forms
  - Admin actions (delete, settings)

### 10.3 File Security
- [x] **Upload restrictions**: ✅
  - .htaccess in uploads/ directory
  - File type whitelist
  - Virus scanning (if available)
- [x] **Access control**: ✅
  - User can only access own files
  - Admin can access all files
  - No direct file URLs

---

## Phase 11: Error Handling & User Experience
**Goal**: Graceful error handling with Phoenix components

### 11.1 Error Management
- [x] **Phoenix alert components** for all error types: ✅
  - Form validation errors
  - API failures
  - File upload errors
  - Authentication errors
- [x] **Logging system**: ✅
  - Error logging to files
  - User action logging
  - API usage tracking

### 11.2 Loading States
- [x] **Phoenix spinners** during: ✅
  - File upload
  - PDF processing
  - API calls
- [x] **Progress indicators**: ✅
  - Upload progress bars
  - Analysis status updates

### 11.3 User Feedback
- [x] **Phoenix toast notifications**: ✅
  - Success messages
  - Warning alerts
  - Information updates
- [ ] **Confirmation dialogs** - Phoenix modals for destructive actions

---

## Phase 12: Testing & Quality Assurance
**Goal**: Comprehensive testing of all functionality

### 12.1 Functionality Testing
- [ ] **User registration/login flow**
- [ ] **PDF upload and processing**
- [ ] **Text paste functionality**
- [ ] **AI analysis generation**
- [ ] **Results display and navigation**
- [ ] **Admin dashboard functions**

### 12.2 Security Testing
- [ ] **CSRF token validation**
- [ ] **Session security**
- [ ] **File upload restrictions**
- [ ] **Admin access controls**
- [ ] **SQL injection prevention**

### 12.3 UI/UX Testing
- [ ] **Responsive design** - Mobile, tablet, desktop
- [ ] **Phoenix component consistency**
- [ ] **Error message clarity**
- [ ] **Loading state feedback**

---

## Phase 13: Final Polish & Deployment
**Goal**: Production-ready application

### 13.1 Performance Optimization
- [ ] **Database query optimization**
- [ ] **File size optimization**
- [ ] **Phoenix CSS minification**
- [ ] **JavaScript optimization**

### 13.2 Documentation
- [ ] **Installation instructions**
- [ ] **API key setup guide**
- [ ] **User manual**
- [ ] **Admin guide**

### 13.3 Deployment Preparation
- [ ] **Environment configuration**
- [ ] **Database migration scripts**
- [ ] **Backup procedures**
- [ ] **Security checklist verification**

---

## Dependencies & Prerequisites
- PHP 8.1+ with PDO extension
- MySQL 8.0+ with UTF8MB4 support
- Apache with mod_rewrite
- pdftotext utility (poppler-utils)
- OpenAI API access
- Perplexity API access (optional)

## File Structure Reference
```
/mvp-legal/
├── app/
│   ├── auth.php          # Authentication functions
│   ├── config.php        # Database configuration
│   ├── db.php           # Database connection
│   ├── gpt_handler.php  # OpenAI API integration
│   ├── pdf_parser.php   # PDF processing
│   ├── perplexity.php   # Perplexity API integration
│   ├── save_fetch.php   # Data operations
│   ├── settings.php     # API key management
│   └── prompts/
│       └── appeal_prospect_v5_2.md
├── public/
│   ├── assets/
│   │   ├── css/phoenix.css
│   │   └── js/phoenix.js
│   ├── index.php        # Landing page
│   ├── login.php        # User login
│   ├── register.php     # User registration
│   ├── logout.php       # Logout handler
│   ├── upload.php       # File upload interface
│   ├── analyze.php      # Analysis processor
│   ├── results.php      # Results display
│   ├── my-cases.php     # User case history
│   ├── admin.php        # Admin dashboard
│   └── seed.php         # Initial data setup
├── uploads/             # Secure file storage
├── schema.sql           # Database structure
└── TODO.md             # This file
```

---

## Progress Tracking
- [x] **Phase 1 Complete**: Foundation & Database (8/8 tasks) ✅
- [x] **Phase 2 Complete**: Backend Infrastructure (12/12 tasks) ✅
- [x] **Phase 3 Complete**: Phoenix UI Foundation (10/10 tasks) ✅
- [x] **Phase 4 Complete**: User Authentication (14/14 tasks) ✅
- [x] **Phase 5 Complete**: File Upload & Processing (2/2 tasks) ✅
- [x] **Phase 6 Complete**: AI Integration (3/3 tasks) ✅
- [x] **Phase 7 Complete**: Results Display (2/2 tasks) ✅
- [x] **Phase 8 Complete**: Case History (2/2 tasks) ✅
- [x] **Phase 9 Complete**: Admin Dashboard (3/3 tasks) ✅
- [x] **Phase 10 Complete**: Security Implementation (3/3 tasks) ✅
- [x] **Phase 11 Complete**: Error Handling & UX (3/3 tasks) ✅
- [ ] **Phase 12 Complete**: Testing & QA (0/3 tasks)
- [ ] **Phase 13 Complete**: Final Polish & Deployment (0/3 tasks)

**Overall Progress**: 95/125 tasks completed (76.0%)

---

*Last Updated: August 24, 2025*
*This TODO.md serves as the master checklist for iterative development of the Appeal Prospect MVP.*