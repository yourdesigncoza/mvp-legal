# Project Coding Rules (Non-Obvious Only)

**Database Connection**: Always use singleton pattern from app/db.php - creating new PDO connections will fail due to charset/timezone setup
**Settings Encryption**: API keys MUST use encrypt_setting()/decrypt_setting() functions - direct database storage will break production
**PDF Parser**: Must call PDFParser::processUploadedFile() or PDFParser::processTextInput() - direct file handling bypasses security checks
**Template Inclusion**: require_once for all app/templates/ files - include/require will cause duplicate definition errors
**Case Status Updates**: Use db_execute() with prepared statements for status changes - enum validation happens at database level
**File Upload Validation**: MIME type checking happens in PDFParser class, not in upload.php - bypassing it breaks security
**GPT Handler**: parseStructuredAnalysis() expects exactly 13 sections - different formats will cause parsing failures
**Flash Messages**: Use set_flash_message()/get_flash_messages() functions - session handling is customized for security
**Authentication**: check_auth() function redirects automatically - don't add manual redirect code after calling it
**CSRF Protection**: All POST forms must include csrf_field() hidden input - missing token causes silent failures