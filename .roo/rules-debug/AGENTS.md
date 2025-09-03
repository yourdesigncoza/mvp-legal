# Project Debug Rules (Non-Obvious Only)

**PHP Error Logs**: Check both Apache error log AND PHP-FPM log - errors split across both locations
**Database Debugging**: Enable query logging in app/db.php by uncommenting error_log() calls - queries logged to system error log
**File Upload Issues**: Check uploads directory permissions AND .htaccess rules - both cause silent upload failures
**API Connection Tests**: Use test_openai_connection()/test_perplexity_connection() functions - direct cURL calls won't match production setup
**PDF Processing Failures**: Check if poppler-utils is installed with `pdftotext -v` - missing binary causes silent text extraction failures
**Encryption Debugging**: Settings decryption failures are logged to PHP error log with prefix "Settings decryption failed"
**Session Issues**: Session data stored in custom format - use print_r($_SESSION) instead of var_dump for readable output
**Phoenix UI Issues**: CSS classes must match exactly - Phoenix framework is case-sensitive on class names
**Database Schema**: Run `SOURCE schema.sql` from MySQL command line - PHP execution of schema will fail on large files
**Case Analysis Failures**: AI parsing errors logged to app/logs/ directory if it exists, otherwise to system error log