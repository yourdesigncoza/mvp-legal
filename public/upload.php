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
                        redirect('analyze.php?case_id=' . $result['case_id']);
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
                        redirect('analyze.php?case_id=' . $result['case_id']);
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

<div class="container px-0">
    
    <div class="content">
        <div class="container px-6 py-4">
            
            <!-- Page Header -->
            <div class="row align-items-center justify-content-between py-2 pe-0 mb-4">
                <div class="col-auto">
                    <h2 class="text-body-emphasis mb-0 fs-6 mb-2">
                        <i class="fas fa-upload me-2 text-primary"></i>
                        Upload Judgment
                    </h2>
                    <p class="text-body-tertiary mb-0">
                        Upload a PDF file or paste judgment text for AI analysis
                    </p>
                </div>
                <div class="col-auto">
                    <a href="<?= app_url('my-cases.php') ?>" class="btn btn-subtle-secondary">
                        <i class="fas fa-folder me-2"></i>
                        My Cases
                    </a>
                </div>
            </div>

            <!-- Success Message -->
            <?php if ($success_message): ?>
                <div class="alert alert-subtle-success border-0" role="alert">
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
                <div class="alert alert-subtle-danger border-0" role="alert">
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
                        <div class="card-header bg-body">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-upload me-2"></i>
                                Upload Method
                            </h5>
                        </div>
                        <div class="card-body">
                            
                            <form method="POST" action="<?= app_url('upload.php') ?>" enctype="multipart/form-data" novalidate>
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
                                            <label class="form-label" for="dropzone-upload">
                                                PDF File <span class="text-danger">*</span>
                                            </label>

                                            <!-- DropzoneJS Container -->
                                            <div class="dropzone" id="dropzone-upload">
                                                <div class="dz-message" data-dz-message>
                                                    <div class="mb-3">
                                                        <i class="fas fa-cloud-upload-alt text-primary" style="font-size: 3rem;"></i>
                                                    </div>
                                                    <h5 class="text-primary mb-2">Drop PDF file here</h5>
                                                    <p class="text-body-tertiary mb-3">or click to browse files</p>
                                                    <div class="btn btn-primary">
                                                        <i class="fas fa-folder-open me-2"></i>
                                                        Choose File
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Preview container for uploaded files -->
                                            <div class="dropzone-previews mt-3"></div>
                                            
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
                            
                            <div class="alert alert-subtle-warning border-0">
                                <h6 class="alert-heading">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Privacy Notice
                                </h6>
                                <p class="mb-0 small">
                                    This is only a demo platform & you should not upload sensitive documents.
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

<?php $upload_ajax_url = app_url('upload-ajax.php'); ?>
<script>
// Custom CSS for DropzoneJS Phoenix styling
const dropzoneStyle = document.createElement('style');
dropzoneStyle.textContent = `
    .dropzone {
        border: 2px dashed var(--phoenix-primary) !important;
        border-radius: 12px !important;
        background: rgba(var(--phoenix-primary-rgb), 0.1) !important;
        color: var(--phoenix-body-color) !important;
        transition: all 0.3s ease;
        min-height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .dropzone:hover {
        border-color: var(--phoenix-success) !important;
        background: rgba(var(--phoenix-success-rgb), 0.1) !important;
    }
    .dropzone.dz-drag-hover {
        border-color: var(--phoenix-success) !important;
        background: rgba(var(--phoenix-success-rgb), 0.15) !important;
    }
    .dropzone .dz-message {
        text-align: center;
        margin: 0;
    }
    .dropzone .dz-preview {
        margin: 20px 0 !important;
    }
    .dropzone .dz-preview .dz-image {
        width: 40px !important;
        height: 40px !important;
        border-radius: 8px !important;
    }
    .dropzone .dz-preview .dz-details {
        background: rgba(var(--phoenix-primary-rgb), 0.1) !important;
        border-radius: 8px;
        padding: 10px;
    }
    .dropzone .dz-preview .dz-progress {
        background: rgba(var(--phoenix-gray-400), 0.3) !important;
    }
    .dropzone .dz-preview .dz-progress .dz-upload {
        background: var(--phoenix-primary) !important;
    }
    .dropzone .dz-preview .dz-success-mark,
    .dropzone .dz-preview .dz-error-mark {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
    }
    .dropzone .dz-preview .dz-error-message {
        background: var(--phoenix-danger) !important;
        color: white !important;
        border-radius: 6px;
    }
    .dropzone .dz-message {
        padding: 2rem 2rem;
    }
    /* Hide message when files are present */
    .dropzone.dz-started .dz-message {
        display: none;
    }
    /* Preview container styles */
    .dropzone-previews {
        margin-top: 1rem;
    }
    .dropzone-previews .dz-preview {
        margin: 0;
        opacity: 1;
        transform: none;
    }
    /* Card styling for file preview */
    .dropzone-previews .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.2s ease-in-out;
    }
    .dropzone-previews .card:hover {
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }
    /* Progress bar styling */
    .dz-preview .progress {
        height: 2px;
        background-color: rgba(var(--phoenix-gray-300-rgb), 0.3);
        border-radius: 1px;
        overflow: hidden;
    }
    .dz-preview .progress-bar {
        transition: width 0.3s ease;
        background-color: var(--phoenix-primary);
    }
    /* Avatar styling for file icon */
    .dz-preview .avatar-name {
        background-color: rgba(var(--phoenix-gray-200-rgb), 0.3) !important;
        width: 60px;
        height: 60px;
    }
`;
document.head.appendChild(dropzoneStyle);

document.addEventListener('DOMContentLoaded', function() {
    const textArea = document.getElementById('judgment_text');
    const charCount = document.getElementById('char-count');
    let myDropzone = null;
    
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
    
    // Initialize DropzoneJS only when file upload tab is active
    function initializeDropzone() {
        if (myDropzone) {
            myDropzone.destroy();
        }
        
        const caseNameField = document.getElementById('case_name');
        
        myDropzone = new Dropzone("#dropzone-upload", {
            url: "<?= $upload_ajax_url ?>",
            method: 'POST',
            paramName: 'file',
            maxFiles: 1,
            maxFilesize: <?= (int)get_setting('max_file_size', '10485760') / 1024 / 1024 ?>, // Convert to MB
            acceptedFiles: '.pdf,.txt,application/pdf,text/plain',
            addRemoveLinks: true,
            dictDefaultMessage: '',
            dictRemoveFile: 'Remove file',
            dictFileTooBig: 'File is too large ({{filesize}}MB). Maximum allowed: {{maxFilesize}}MB',
            dictInvalidFileType: 'Only PDF and text files are allowed',
            dictMaxFilesExceeded: 'Only one file can be uploaded at a time',
            autoProcessQueue: false,
            previewsContainer: ".dropzone-previews",
            clickable: true,
            previewTemplate: `
                <div class="dz-preview dz-file-preview mb-3">
                    <div class="card border border-translucent">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="avatar avatar-2xl">
                                        <div class="avatar-name rounded-3 bg-soft-secondary text-dark d-flex align-items-center justify-content-center">
                                            <i class="fas fa-file-pdf text-body-quaternary fs-2"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-truncate"><span data-dz-name></span></h6>
                                    <div class="mb-2">
                                        <small class="text-body-tertiary"><span data-dz-size></span></small>
                                    </div>
                                    <div class="progress mt-2" style="height: 2px;">
                                        <div class="progress-bar bg-primary" role="progressbar" data-dz-uploadprogress></div>
                                    </div>
                                    <div class="dz-error-message text-danger small mt-2" style="display: none;"></div>
                                    <div class="dz-success-mark text-success mt-2" style="display: none;">
                                        <small><i class="fas fa-check-circle me-1"></i>Upload complete</small>
                                    </div>
                                </div>
                                <div class="ms-2">
                                    <button type="button" class="btn btn-close" data-dz-remove aria-label="Remove file"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            sending: function(file, xhr, formData) {
                // Add CSRF token
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                
                // Add case name
                const caseName = caseNameField ? caseNameField.value.trim() : '';
                if (!caseName) {
                    this.cancelUpload(file);
                    showAlert('Please enter a case name first', 'danger');
                    return false;
                }
                formData.append('case_name', caseName);
                
                // Show loading state
                showLoading('uploadBtn', 'Uploading...');
            },
            success: function(file, response) {
                console.log('Upload success:', response);
                hideLoading('uploadBtn', '<i class="fas fa-magic me-2"></i>Analyze Judgment');
                
                if (response.success) {
                    // Show success message
                    showAlert(response.message + ' You can now proceed to analysis.', 'success');
                    
                    // Add proceed to analysis button to the file preview
                    const proceedBtn = document.createElement('div');
                    proceedBtn.className = 'mt-2 text-center';
                    proceedBtn.innerHTML = `
                        <a href="${response.redirect_url}" class="btn btn-sm btn-subtle-success">
                            <i class="fas fa-magic me-1"></i>
                            Proceed to Analysis
                        </a>
                    `;
                    
                    const preview = file.previewElement.querySelector('.alert');
                    if (preview) {
                        preview.appendChild(proceedBtn);
                    }
                    
                    // Show success mark
                    const successMark = file.previewElement.querySelector('.dz-success-mark');
                    if (successMark) {
                        successMark.style.display = 'block';
                    }
                    
                    // Store the redirect URL for the form submit button
                    window.analysisRedirectUrl = response.redirect_url;
                    
                    // Update the submit button to redirect to analysis
                    const submitBtn = document.getElementById('uploadBtn');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-magic me-2"></i>Proceed to Analysis';
                        submitBtn.onclick = function(e) {
                            e.preventDefault();
                            window.location.href = response.redirect_url;
                        };
                    }
                } else {
                    showAlert(response.error || 'Upload failed', 'danger');
                }
            },
            error: function(file, errorMessage) {
                console.error('Upload error:', errorMessage);
                hideLoading('uploadBtn', '<i class="fas fa-magic me-2"></i>Analyze Judgment');
                
                let message = 'Upload failed';
                if (typeof errorMessage === 'string') {
                    message = errorMessage;
                } else if (errorMessage && errorMessage.error) {
                    message = errorMessage.error;
                }
                
                showAlert(message, 'danger');
            },
            addedfile: function(file) {
                // Create the preview element using the template
                var node = document.createElement("div");
                node.innerHTML = this.options.previewTemplate.trim();
                var previewElement = node.firstChild;
                
                // Set up the preview element
                file.previewElement = previewElement;
                previewElement.querySelector("[data-dz-name]").textContent = file.name;
                
                // Format file size as plain text
                var fileSize = file.size;
                var sizeText = '';
                if (fileSize < 1024) {
                    sizeText = fileSize + ' B';
                } else if (fileSize < 1024 * 1024) {
                    sizeText = Math.round(fileSize / 1024 * 10) / 10 + ' KB';
                } else if (fileSize < 1024 * 1024 * 1024) {
                    sizeText = Math.round(fileSize / (1024 * 1024) * 10) / 10 + ' MB';
                } else {
                    sizeText = Math.round(fileSize / (1024 * 1024 * 1024) * 10) / 10 + ' GB';
                }
                previewElement.querySelector("[data-dz-size]").textContent = sizeText;
                
                // Add to preview container
                var previewContainer = document.querySelector(this.options.previewsContainer);
                if (previewContainer) {
                    previewContainer.appendChild(previewElement);
                }
                
                // Set up remove link
                var removeLink = previewElement.querySelector("[data-dz-remove]");
                if (removeLink) {
                    removeLink.addEventListener("click", (e) => {
                        e.preventDefault();
                        this.removeFile(file);
                    });
                }
                
                // Remove existing files if maxFiles is 1
                if (this.files.length > this.options.maxFiles) {
                    this.removeFile(this.files[0]);
                }
            }
        });
    }
    
    // Initialize dropzone if file tab is active
    if (document.getElementById('upload-file').classList.contains('active')) {
        initializeDropzone();
    }
    
    // Handle tab switching
    document.addEventListener('shown.bs.tab', function(e) {
        if (e.target.getAttribute('data-bs-target') === '#upload-file') {
            initializeDropzone();
        }
    });
    
    // Handle form submission for text uploads
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const uploadType = document.getElementById('upload_type').value;
        
        if (uploadType === 'file') {
            e.preventDefault();
            
            // Validate case name
            const caseName = document.getElementById('case_name').value.trim();
            if (!caseName) {
                showAlert('Please enter a case name', 'danger');
                return false;
            }
            
            // Check if file is added
            if (!myDropzone || myDropzone.files.length === 0) {
                showAlert('Please select a file to upload', 'danger');
                return false;
            }
            
            // Process the queue
            myDropzone.processQueue();
        } else {
            // Text upload - use normal form submission
            showLoading('uploadBtn', 'Processing...');
        }
    });
});

function setUploadType(type) {
    document.getElementById('upload_type').value = type;
}

function showAlert(message, type = 'info') {
    // Create alert element with Phoenix subtle styling
    const alertHtml = `
        <div class="alert alert-subtle-${type} alert-dismissible fade show border-0 mt-3" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert after the page header
    const pageHeader = document.querySelector('.row.align-items-center');
    if (pageHeader) {
        pageHeader.insertAdjacentHTML('afterend', alertHtml);
    }
}
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>