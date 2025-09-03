# Project Architecture Rules (Non-Obvious Only)

**3-Layer Architecture**: Presentation (public/) → Business Logic (app/) → Data (schema.sql) - strict separation enforced by .htaccess rules
**Phoenix UI Constraint**: CSS framework exists only as compiled bootstrap file - no component customization allowed, only class usage
**Database Singleton**: PDO connection shared across entire request lifecycle - creating multiple connections breaks transaction handling
**File Processing Pipeline**: Upload → PDF text extraction → AI analysis → structured JSON storage - cannot skip or reorder steps
**Security Layer**: Multiple .htaccess files create nested security zones - removing any .htaccess breaks entire security model
**AI Handler Coupling**: GPTHandler depends on specific 13-section prompt format - changing prompt structure breaks parseStructuredAnalysis()
**User Scope Enforcement**: All file operations and database queries must be user-scoped - no global admin access to user data
**Settings Encryption Layer**: All sensitive data (API keys) encrypted at storage level - application code never sees plain text
**Template Inheritance**: Header/footer inclusion required on ALL public pages - missing either breaks navigation and styling
**Status State Machine**: Case status follows strict state machine - invalid transitions are blocked at database constraint level
**Testing Isolation**: Test database completely separate from application database - no shared schema or data