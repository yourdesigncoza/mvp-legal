<?php
// PDF Parser and Text Processor
// Appeal Prospect MVP - File Processing Handler

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';

/**
 * PDF Parser class for extracting text from uploaded files
 */
class PDFParser 
{
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'text/plain',
        'text/txt'
    ];
    
    private string $upload_dir;
    
    public function __construct() {
        $this->upload_dir = __DIR__ . '/../uploads/';
        $this->ensureUploadDirectory();
    }
    
    /**
     * Process uploaded file and extract text
     * 
     * @param array $file $_FILES array element
     * @param string $case_name User-provided case name
     * @param int $user_id Current user ID
     * @return array Result array with success status and data
     */
    public function processUploadedFile(array $file, string $case_name, int $user_id): array
    {
        try {
            // Validate file using security functions
            $validation = validate_file_upload($file);
            if (!$validation['valid']) {
                log_security_event('file_upload_validation_failed', [
                    'user_id' => $user_id,
                    'filename' => $file['name'] ?? 'unknown',
                    'error' => $validation['error']
                ]);
                return ['success' => false, 'error' => $validation['error']];
            }
            
            // Generate secure filename
            $file_info = pathinfo($file['name']);
            $extension = $file_info['extension'] ?? '';
            $secure_filename = generate_secure_filename($file['name']);
            $upload_path = $this->upload_dir . $user_id . '/' . $secure_filename;
            
            // Create user directory if needed
            $user_dir = dirname($upload_path);
            if (!is_dir($user_dir)) {
                if (!mkdir($user_dir, 0755, true)) {
                    return ['success' => false, 'error' => 'Failed to create upload directory'];
                }
            }
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                return ['success' => false, 'error' => 'Failed to save uploaded file'];
            }
            
            // Extract text based on file type
            $mime_type = mime_content_type($upload_path);
            $extracted_text = $this->extractText($upload_path, $mime_type);
            
            if (!$extracted_text['success']) {
                // Clean up file on extraction failure
                unlink($upload_path);
                return $extracted_text;
            }
            
            // Save to database
            $case_id = $this->saveCaseToDatabase([
                'user_id' => $user_id,
                'case_name' => $case_name,
                'original_filename' => $file['name'],
                'stored_filename' => $secure_filename,
                'mime_type' => $mime_type,
                'file_size' => $file['size'],
                'judgment_text' => $extracted_text['text']
            ]);
            
            if (!$case_id) {
                unlink($upload_path);
                return ['success' => false, 'error' => 'Failed to save case to database'];
            }
            
            return [
                'success' => true,
                'case_id' => $case_id,
                'text_length' => strlen($extracted_text['text']),
                'file_path' => $upload_path,
                'message' => 'File processed successfully'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Processing failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process pasted text input
     * 
     * @param string $text Judgment text
     * @param string $case_name User-provided case name
     * @param int $user_id Current user ID
     * @return array Result array with success status and data
     */
    public function processTextInput(string $text, string $case_name, int $user_id): array
    {
        try {
            // Validate case name using security functions
            $case_validation = validate_case_name($case_name);
            if (!$case_validation['valid']) {
                return ['success' => false, 'error' => $case_validation['error']];
            }
            
            // Clean and validate text
            $cleaned_text = sanitize_text_content($text);
            
            if (strlen($cleaned_text) < 100) {
                return ['success' => false, 'error' => 'Text must be at least 100 characters long'];
            }
            
            // Save to database
            $case_id = $this->saveCaseToDatabase([
                'user_id' => $user_id,
                'case_name' => $case_name,
                'original_filename' => null,
                'stored_filename' => null,
                'mime_type' => 'text/plain',
                'file_size' => strlen($cleaned_text),
                'judgment_text' => $cleaned_text
            ]);
            
            if (!$case_id) {
                return ['success' => false, 'error' => 'Failed to save case to database'];
            }
            
            return [
                'success' => true,
                'case_id' => $case_id,
                'text_length' => strlen($cleaned_text),
                'message' => 'Text processed successfully'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Processing failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
                UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
                UPLOAD_ERR_PARTIAL => 'File was partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload blocked by extension'
            ];
            
            return [
                'valid' => false,
                'error' => $error_messages[$file['error']] ?? 'Unknown upload error'
            ];
        }
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return [
                'valid' => false,
                'error' => 'File size must be less than ' . $this->formatBytes(self::MAX_FILE_SIZE)
            ];
        }
        
        // Check MIME type
        $mime_type = mime_content_type($file['tmp_name']);
        if (!in_array($mime_type, self::ALLOWED_MIME_TYPES)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Only PDF and text files are supported.'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Extract text from uploaded file
     */
    private function extractText(string $file_path, string $mime_type): array
    {
        try {
            switch ($mime_type) {
                case 'application/pdf':
                    return $this->extractPDFText($file_path);
                    
                case 'text/plain':
                case 'text/txt':
                    return $this->extractPlainText($file_path);
                    
                default:
                    return ['success' => false, 'error' => 'Unsupported file type'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Text extraction failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Extract text from PDF using pdftotext
     */
    private function extractPDFText(string $file_path): array
    {
        // Check if pdftotext is available
        $pdftotext_path = $this->findPDFToText();
        if (!$pdftotext_path) {
            return ['success' => false, 'error' => 'PDF text extraction not available. Please install poppler-utils.'];
        }
        
        // Create temporary output file
        $temp_output = tempnam(sys_get_temp_dir(), 'pdf_text_');
        
        // Build command with proper escaping and use system libraries
        // Use LD_LIBRARY_PATH to ensure system libraries are used instead of XAMPP's
        $command = 'LD_LIBRARY_PATH=/lib/x86_64-linux-gnu:/usr/lib/x86_64-linux-gnu ' .
                   escapeshellcmd($pdftotext_path) . ' -layout -enc UTF-8 ' . 
                   escapeshellarg($file_path) . ' ' . escapeshellarg($temp_output);
        
        // Execute command
        $return_code = 0;
        $output = [];
        exec($command . ' 2>&1', $output, $return_code);
        
        if ($return_code !== 0) {
            if (file_exists($temp_output)) {
                unlink($temp_output);
            }
            return ['success' => false, 'error' => 'PDF text extraction failed: ' . implode(' ', $output)];
        }
        
        // Read extracted text
        if (!file_exists($temp_output)) {
            return ['success' => false, 'error' => 'PDF text extraction produced no output'];
        }
        
        $text = file_get_contents($temp_output);
        unlink($temp_output);
        
        if ($text === false || empty(trim($text))) {
            return ['success' => false, 'error' => 'PDF appears to be empty or contains no extractable text'];
        }
        
        return [
            'success' => true,
            'text' => $this->cleanText($text)
        ];
    }
    
    /**
     * Extract text from plain text file
     */
    private function extractPlainText(string $file_path): array
    {
        $text = file_get_contents($file_path);
        
        if ($text === false) {
            return ['success' => false, 'error' => 'Failed to read text file'];
        }
        
        if (empty(trim($text))) {
            return ['success' => false, 'error' => 'File is empty'];
        }
        
        return [
            'success' => true,
            'text' => $this->cleanText($text)
        ];
    }
    
    /**
     * Clean and normalize extracted text
     */
    private function cleanText(string $text): string
    {
        // Convert to UTF-8 if needed
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Remove excessive whitespace but preserve paragraph structure
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Trim whitespace
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Find pdftotext executable
     */
    private function findPDFToText(): ?string
    {
        $possible_paths = [
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
            '/opt/bin/pdftotext'
        ];
        
        // Check common paths
        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Try which command
        $which_output = shell_exec('which pdftotext 2>/dev/null');
        if ($which_output && trim($which_output)) {
            $path = trim($which_output);
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Generate secure filename
     */
    private function generateSecureFilename(string $extension = ''): string
    {
        $hash = hash('sha256', uniqid('', true) . microtime());
        $filename = substr($hash, 0, 16) . '_' . time();
        
        if ($extension) {
            $filename .= '.' . ltrim($extension, '.');
        }
        
        return $filename;
    }
    
    /**
     * Save case to database
     */
    private function saveCaseToDatabase(array $data): ?int
    {
        try {
            $sql = "INSERT INTO cases (
                user_id, case_name, original_filename, stored_filename, 
                mime_type, file_size, judgment_text, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'uploaded', NOW())";
            
            $params = [
                $data['user_id'],
                $data['case_name'],
                $data['original_filename'],
                $data['stored_filename'],
                $data['mime_type'],
                $data['file_size'],
                $data['judgment_text']
            ];
            
            if (db_execute($sql, $params)) {
                return db_last_insert_id();
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Failed to save case to database: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ensure upload directory exists and is secure
     */
    private function ensureUploadDirectory(): void
    {
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
        
        // Create .htaccess to deny direct access
        $htaccess_path = $this->upload_dir . '.htaccess';
        if (!file_exists($htaccess_path)) {
            $htaccess_content = "Order Deny,Allow\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "# Prevent direct access to uploaded files\n";
            file_put_contents($htaccess_path, $htaccess_content);
        }
    }
    
    /**
     * Format bytes into human readable format
     */
    private function formatBytes(int $bytes, int $decimals = 2): string
    {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), $decimals) . ' ' . $sizes[$i];
    }
}

/**
 * Helper function to process file upload
 */
function process_file_upload(array $file, string $case_name, int $user_id): array
{
    $parser = new PDFParser();
    return $parser->processUploadedFile($file, $case_name, $user_id);
}

/**
 * Helper function to process text input
 */
function process_text_input(string $text, string $case_name, int $user_id): array
{
    $parser = new PDFParser();
    return $parser->processTextInput($text, $case_name, $user_id);
}