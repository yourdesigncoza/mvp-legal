-- Fix missing columns in cases table
-- Run this SQL in phpMyAdmin or MySQL command line

USE appeal_prospect_mvp;

-- Add stored_filename column if it doesn't exist
ALTER TABLE cases 
ADD COLUMN IF NOT EXISTS stored_filename VARCHAR(255) NULL DEFAULT NULL AFTER original_filename;

-- Add analyzed_at column if it doesn't exist  
ALTER TABLE cases
ADD COLUMN IF NOT EXISTS analyzed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

-- Show the updated table structure
DESCRIBE cases;