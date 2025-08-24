<?php
// File Upload Interface
// Appeal Prospect MVP - Phoenix UI Implementation

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/settings.php';
require_once __DIR__ . '/../app/pdf_parser.php';

// Start session and require login
start_session();
require_login();

$errors = [];
$success_message = '';
$form_data = [
    'case_name' => '',
    'judgment_text' => '',
    'upload_type' => 'file'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf()) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        $form_data['upload_type'] = $_POST['upload_type'] ?? 'file';
        $form_data['case_name'] = trim($_POST['case_name'] ?? '');
        $form_data['judgment_text'] = trim($_POST['judgment_text'] ?? '');
        
        // Validate case name using security function
        $case_validation = validate_case_name($form_data['case_name']);
        if (!$case_validation['valid']) {
            $errors['case_name'] = $case_validation['error'];
        } else {
            $form_data['case_name'] = $case_validation['value']; // Use sanitized value
        }
        
        if ($form_data['upload_type'] === 'file') {
            // Handle file upload
            if (!isset($_FILES['judgment_file']) || $_FILES['judgment_file']['error'] === UPLOAD_ERR_NO_FILE) {
                $errors['file'] = 'Please select a PDF file to upload';
            } else {
                $file = $_FILES['judgment_file'];
                
                // Validate file upload
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors['file'] = 'File upload failed. Please try again.';
                } else {
                    // Validate file size (10MB max)
                    $max_size = (int)get_setting('max_file_size', '10485760');
                    if ($file['size'] > $max_size) {
                        $errors['file'] = 'File size must be less than ' . format_bytes($max_size);
                    }
                    
                    // Validate MIME type
                    $allowed_types = explode(',', get_setting('allowed_mime_types', 'application/pdf,text/plain'));
                    $file_type = mime_content_type($file['tmp_name']);
                    if (!in_array($file_type, $allowed_types)) {
                        $errors['file'] = 'Only PDF and text files are allowed';
                    }
                }
            }
        } else {
            // Handle text paste
            if (empty($form_data['judgment_text'])) {
                $errors['text'] = 'Please paste the judgment text';
            } elseif (strlen($form_data['judgment_text']) < 100) {
                $errors['text'] = 'Judgment text must be at least 100 characters';
            }
        }
        
        // If no errors, process the upload
        if (empty($errors)) {
            try {
                $user_id = current_user_id();
                
                if ($form_data['upload_type'] === 'file') {
                    // Process PDF file with PDF parser
                    $result = process_file_upload($_FILES['judgment_file'], $form_data['case_name'], $user_id);
                    
                    if ($result['success']) {
                        $success_message = "File processed successfully! Extracted " . number_format($result['text_length']) . " characters of text.";
                        
                        // Redirect to analysis page
                        header('Location: /analyze.php?case_id=' . $result['case_id']);
                        exit;
                    } else {
                        $errors['general'] = $result['error'];
                    }
                } else {
                    // Process text input
                    $result = process_text_input($form_data['judgment_text'], $form_data['case_name'], $user_id);
                    
                    if ($result['success']) {
                        $success_message = "Text processed successfully! Ready for analysis (" . number_format($result['text_length']) . " characters).";
                        
                        // Redirect to analysis page
                        header('Location: /analyze.php?case_id=' . $result['case_id']);
                        exit;
                    } else {
                        $errors['general'] = $result['error'];
                    }
                }
            } catch (Exception $e) {
                $errors['general'] = 'Upload processing failed: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Upload Judgment - Appeal Prospect MVP';
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<div class="container-fluid px-0">
    <?php include __DIR__ . '/../app/templates/navbar.php'; ?>
    
    <div class="content">
        <div class="container-fluid px-6 py-4">
            
            <!-- Page Header -->
            <div class="row align-items-center justify-content-between py-2 pe-0 mb-4">
                <div class="col-auto">
                    <h2 class="text-body-emphasis mb-0">
                        <i class="fas fa-upload me-2 text-primary"></i>
                        Upload Judgment
                    </h2>
                    <p class="text-body-tertiary mb-0">
                        Upload a PDF file or paste judgment text for AI analysis
                    </p>
                </div>
                <div class="col-auto">
                    <a href="/my-cases.php" class="btn btn-outline-secondary">
                        <i class="fas fa-folder me-2"></i>
                        My Cases
                    </a>
                </div>
            </div>

            <!-- Success Message -->
            <?php if ($success_message): ?>
                <div class="alert alert-success border-0" role="alert">
                    <div class="d-flex">
                        <i class="fas fa-check-circle fs-4 me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Success!</h6>
                            <p class="mb-0"><?= htmlspecialchars($success_message) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- General Error -->
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger border-0" role="alert">
                    <div class="d-flex">
                        <i class="fas fa-exclamation-circle fs-4 me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Error</h6>
                            <p class="mb-0"><?= htmlspecialchars($errors['general']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Upload Form -->
                <div class="col-12 col-xl-8">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-body-tertiary">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-upload me-2"></i>
                                Upload Method
                            </h5>
                        </div>
                        <div class="card-body">
                            
                            <form method="POST" action="/upload.php" enctype="multipart/form-data" novalidate>
                                <?= csrf_field() ?>
                                
                                <!-- Upload Type Toggle -->
                                <div class="mb-4">
                                    <div class="nav nav-underline" role="tablist">
                                        <button 
                                            class="nav-link <?= $form_data['upload_type'] === 'file' ? 'active' : '' ?>" 
                                            type="button" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#upload-file" 
                                            onclick="setUploadType('file')"
                                        >
                                            <i class="fas fa-file-pdf me-2"></i>
                                            Upload PDF
                                        </button>
                                        <button 
                                            class="nav-link <?= $form_data['upload_type'] === 'text' ? 'active' : '' ?>" 
                                            type="button" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#upload-text"
                                            onclick="setUploadType('text')"
                                        >
                                            <i class="fas fa-keyboard me-2"></i>
                                            Paste Text
                                        </button>
                                    </div>
                                </div>

                                <input type="hidden" name="upload_type" id="upload_type" value="<?= htmlspecialchars($form_data['upload_type']) ?>">

                                <!-- Case Name -->
                                <div class="mb-4">
                                    <label class="form-label" for="case_name">
                                        Case Name <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        class="form-control <?= isset($errors['case_name']) ? 'is-invalid' : '' ?>" 
                                        id="case_name" 
                                        name="case_name"
                                        type="text" 
                                        placeholder="e.g., Smith v Jones 2024"
                                        value="<?= htmlspecialchars($form_data['case_name']) ?>"
                                        required
                                    />
                                    <?php if (isset($errors['case_name'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['case_name']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Give this case a memorable name for easy reference
                                    </div>
                                </div>

                                <!-- Tab Content -->
                                <div class="tab-content">
                                    
                                    <!-- File Upload Tab -->
                                    <div class="tab-pane fade <?= $form_data['upload_type'] === 'file' ? 'show active' : '' ?>" id="upload-file">
                                        <div class="mb-4">
                                            <label class="form-label" for="judgment_file">
                                                PDF File <span class="text-danger">*</span>
                                            </label>
                                            
                                            <!-- Drag and Drop Area -->
                                            <div class="border border-2 border-dashed border-primary rounded-3 p-5 text-center bg-primary bg-opacity-10" 
                                                 id="dropzone" 
                                                 onclick="document.getElementById('judgment_file').click()">
                                                <div class="mb-3">
                                                    <i class="fas fa-cloud-upload-alt text-primary" style="font-size: 3rem;"></i>
                                                </div>
                                                <h5 class="text-primary mb-2">Drop PDF file here</h5>
                                                <p class="text-body-tertiary mb-3">or click to browse files</p>
                                                <button type="button" class="btn btn-primary">
                                                    <i class="fas fa-folder-open me-2"></i>
                                                    Choose File
                                                </button>
                                            </div>
                                            
                                            <input 
                                                type="file" 
                                                id="judgment_file" 
                                                name="judgment_file" 
                                                accept=".pdf,.txt" 
                                                class="d-none <?= isset($errors['file']) ? 'is-invalid' : '' ?>"
                                            />
                                            
                                            <?php if (isset($errors['file'])): ?>
                                                <div class="text-danger mt-2">
                                                    <small><?= htmlspecialchars($errors['file']) ?></small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="form-text mt-2">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Maximum file size: <?= format_bytes((int)get_setting('max_file_size', '10485760')) ?>
                                                • Supported formats: PDF, TXT
                                            </div>
                                            
                                            <!-- File Preview -->
                                            <div id="file-preview" class="mt-3 d-none">
                                                <div class="alert alert-success border-0">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-file-pdf text-danger fs-4 me-3"></i>
                                                        <div>
                                                            <h6 class="alert-heading mb-1" id="file-name"></h6>
                                                            <p class="mb-0" id="file-info"></p>
                                                        </div>
                                                        <button type="button" class="btn-close ms-auto" onclick="clearFileUpload()"></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Text Input Tab -->
                                    <div class="tab-pane fade <?= $form_data['upload_type'] === 'text' ? 'show active' : '' ?>" id="upload-text">
                                        <div class="mb-4">
                                            <label class="form-label" for="judgment_text">
                                                Judgment Text <span class="text-danger">*</span>
                                            </label>
                                            <textarea 
                                                class="form-control <?= isset($errors['text']) ? 'is-invalid' : '' ?>" 
                                                id="judgment_text" 
                                                name="judgment_text"
                                                rows="12" 
                                                placeholder="Paste the full judgment text here..."
                                                style="font-family: 'Courier New', monospace; font-size: 0.9rem;"
                                            ><?= htmlspecialchars($form_data['judgment_text']) ?></textarea>
                                            
                                            <?php if (isset($errors['text'])): ?>
                                                <div class="invalid-feedback"><?= htmlspecialchars($errors['text']) ?></div>
                                            <?php endif; ?>
                                            
                                            <div class="form-text d-flex justify-content-between">
                                                <span>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Minimum 100 characters required
                                                </span>
                                                <span id="char-count">0 characters</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid">
                                    <button class="btn btn-primary btn-lg" type="submit" id="uploadBtn">
                                        <i class="fas fa-magic me-2"></i>
                                        Analyze Judgment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Help Sidebar -->
                <div class="col-12 col-xl-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info bg-opacity-10">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-lightbulb me-2 text-info"></i>
                                Upload Tips
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-info">
                                    <i class="fas fa-file-pdf me-2"></i>
                                    PDF Files
                                </h6>
                                <ul class="list-unstyled ms-3 mb-0">
                                    <li class="mb-2">• Court judgments in PDF format</li>
                                    <li class="mb-2">• Maximum size: 10MB</li>
                                    <li class="mb-2">• Text must be selectable (not scanned images)</li>
                                    <li class="mb-0">• OCR PDFs are supported</li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="text-info">
                                    <i class="fas fa-keyboard me-2"></i>
                                    Text Input
                                </h6>
                                <ul class="list-unstyled ms-3 mb-0">
                                    <li class="mb-2">• Copy and paste judgment text</li>
                                    <li class="mb-2">• Preserves original formatting</li>
                                    <li class="mb-2">• Minimum 100 characters</li>
                                    <li class="mb-0">• Include case details and reasoning</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning border-0">
                                <h6 class="alert-heading">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Privacy Notice
                                </h6>
                                <p class="mb-0 small">
                                    Your uploaded documents are processed securely and stored privately. 
                                    Only you can access your case analyses.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Cases -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>
                                Recent Cases
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="text-body-tertiary small mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                Your recent cases will appear here once you start analyzing judgments.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload functionality
    const fileInput = document.getElementById('judgment_file');
    const dropzone = document.getElementById('dropzone');
    const filePreview = document.getElementById('file-preview');
    const textArea = document.getElementById('judgment_text');
    const charCount = document.getElementById('char-count');
    
    // Character count for text input
    if (textArea) {
        textArea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count + ' characters';
            
            if (count >= 100) {
                charCount.classList.remove('text-danger');
                charCount.classList.add('text-success');
            } else {
                charCount.classList.remove('text-success');
                charCount.classList.add('text-danger');
            }
        });
        
        // Initial count
        textArea.dispatchEvent(new Event('input'));
    }
    
    // File upload handling
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            showFilePreview(file);
        }
    });
    
    // Drag and drop functionality
    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-success');
        this.style.backgroundColor = 'var(--bs-success-bg-subtle)';
    });
    
    dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-success');
        this.style.backgroundColor = '';
    });
    
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-success');
        this.style.backgroundColor = '';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFilePreview(files[0]);
        }
    });
    
    // Form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        showLoading('uploadBtn', 'Analyzing...');
    });
});

function setUploadType(type) {
    document.getElementById('upload_type').value = type;
}

function showFilePreview(file) {
    const preview = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');
    const fileInfo = document.getElementById('file-info');
    
    fileName.textContent = file.name;
    fileInfo.textContent = `${formatBytes(file.size)} • ${file.type}`;
    
    preview.classList.remove('d-none');
}

function clearFileUpload() {
    document.getElementById('judgment_file').value = '';
    document.getElementById('file-preview').classList.add('d-none');
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>