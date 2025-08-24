UPLOADS DIRECTORY
================

This directory stores uploaded PDF files for the Appeal Prospect MVP.

SECURITY NOTES:
- Direct web access is denied via .htaccess
- Files can only be accessed through authenticated PHP scripts
- User-specific subdirectories will be created as needed
- Files are validated for type and size before storage

STRUCTURE:
uploads/
├── .htaccess (security protection)
├── README.txt (this file)
└── user_[id]/ (created dynamically for each user)
    ├── case_[id]_original.pdf
    └── case_[id]_processed.txt

Only authorized users can access their own files.
Administrators can access all files through the admin interface.