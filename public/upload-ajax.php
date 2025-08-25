<?php
// AJAX File Upload Handler for DropzoneJS
// Appeal Prospect MVP - Phoenix UI Implementation

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/settings.php';
require_once __DIR__ . '/../app/pdf_parser.php';

// Start session and require login
start_session();

// Set JSON response headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Validate CSRF token
    if (!validate_csrf()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid security token'
        ]);
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode([
            'success' => false,
            'error' => 'No file uploaded'
        ]);
        exit;
    }

    // Get case name from POST data
    $case_name = trim($_POST['case_name'] ?? '');
    if (empty($case_name)) {
        echo json_encode([
            'success' => false,
            'error' => 'Case name is required'
        ]);
        exit;
    }

    // Validate case name using security function
    $case_validation = validate_case_name($case_name);
    if (!$case_validation['valid']) {
        echo json_encode([
            'success' => false,
            'error' => $case_validation['error']
        ]);
        exit;
    }
    $case_name = $case_validation['value']; // Use sanitized value

    $file = $_FILES['file'];

    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'error' => 'File upload failed: ' . get_upload_error_message($file['error'])
        ]);
        exit;
    }

    // Validate file size (10MB max)
    $max_size = (int)get_setting('max_file_size', '10485760');
    if ($file['size'] > $max_size) {
        echo json_encode([
            'success' => false,
            'error' => 'File size must be less than ' . format_bytes($max_size)
        ]);
        exit;
    }

    // Validate MIME type
    $allowed_types = explode(',', get_setting('allowed_mime_types', 'application/pdf,text/plain'));
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode([
            'success' => false,
            'error' => 'Only PDF and text files are allowed'
        ]);
        exit;
    }

    // Process the file upload
    $user_id = current_user_id();
    $result = process_file_upload($file, $case_name, $user_id);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => "File processed successfully! Extracted " . number_format($result['text_length']) . " characters of text.",
            'case_id' => $result['case_id'],
            'text_length' => $result['text_length'],
            'redirect_url' => app_url('analyze.php?case_id=' . $result['case_id'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }

} catch (Exception $e) {
    error_log("AJAX Upload Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Upload processing failed. Please try again.'
    ]);
}

/**
 * Get human-readable upload error message
 */
function get_upload_error_message(int $error_code): string {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File is too large';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary upload directory';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload blocked by extension';
        default:
            return 'Unknown upload error';
    }
}