# Project Documentation Rules (Non-Obvious Only)

**Phoenix UI Reference**: "/phoenix/" mentioned in docs refers to external framework docs, not a project directory
**Settings Table Structure**: API keys stored as encrypted JSON in `value` column, not as separate rows per setting
**File Upload Flow**: Two separate paths (PDF upload vs text input) both end up in same database table with different source flags
**Testing Architecture**: Uses in-memory SQLite for tests, not MySQL - completely different database engine for testing
**Case Analysis Workflow**: 3-stage process (upload → analyze → results) with database status tracking, not single-page flow
**Template System**: Uses PHP includes, not a template engine - all templates are raw PHP files with mixed HTML/PHP
**Authentication Model**: Session-based auth with Argon2id hashing, not JWT or other token systems
**AI Integration**: Two separate APIs (OpenAI required, Perplexity optional) with graceful degradation when Perplexity unavailable
**File Security**: User files segregated by user_id subdirectories, with .htaccess preventing direct web access
**Database Design**: Uses JSON columns for structured_analysis and citations - not normalized relational structure