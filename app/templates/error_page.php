<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Error') ?> - Appeal Prospect MVP</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Phoenix UI Theme -->
    <style>
        :root {
            --phoenix-primary: #0D47A1;
            --phoenix-secondary: #424242;
            --phoenix-success: #2E7D32;
            --phoenix-danger: #C62828;
            --phoenix-warning: #F57C00;
            --phoenix-info: #0277BD;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .error-icon {
            font-size: 6rem;
            opacity: 0.8;
        }
        
        .btn-primary {
            background: var(--phoenix-primary);
            border-color: var(--phoenix-primary);
        }
        
        .btn-primary:hover {
            background: #1565C0;
            border-color: #1565C0;
        }
        
        .text-primary {
            color: var(--phoenix-primary) !important;
        }
        
        .animate-bounce {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translateY(0);
            }
            40%, 43% {
                transform: translateY(-30px);
            }
            70% {
                transform: translateY(-15px);
            }
            90% {
                transform: translateY(-4px);
            }
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="error-container p-5 text-center fade-in">
                    
                    <!-- Error Icon -->
                    <div class="mb-4">
                        <?php
                        $icon_class = match($status_code ?? 500) {
                            404 => 'fas fa-search text-warning animate-bounce',
                            403 => 'fas fa-lock text-danger',
                            500 => 'fas fa-exclamation-triangle text-danger',
                            default => 'fas fa-bug text-secondary'
                        };
                        ?>
                        <i class="<?= $icon_class ?> error-icon"></i>
                    </div>
                    
                    <!-- Status Code -->
                    <h1 class="display-4 fw-bold text-primary mb-3">
                        <?= $status_code ?? 500 ?>
                    </h1>
                    
                    <!-- Page Title -->
                    <h2 class="h3 text-body-emphasis mb-3">
                        <?= htmlspecialchars($page_title ?? 'Error') ?>
                    </h2>
                    
                    <!-- Error Message -->
                    <p class="text-body-secondary mb-4 fs-5">
                        <?= htmlspecialchars($message ?? 'An unexpected error occurred') ?>
                    </p>
                    
                    <!-- Additional Info for Specific Errors -->
                    <?php if (($status_code ?? 500) === 404): ?>
                        <p class="text-body-tertiary mb-4">
                            The page you're looking for might have been moved, deleted, or doesn't exist.
                        </p>
                    <?php elseif (($status_code ?? 500) === 403): ?>
                        <p class="text-body-tertiary mb-4">
                            You don't have permission to access this resource. Please contact an administrator if you believe this is an error.
                        </p>
                    <?php elseif (($status_code ?? 500) === 500): ?>
                        <p class="text-body-tertiary mb-4">
                            We're experiencing technical difficulties. Our team has been notified and is working to resolve the issue.
                        </p>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
                        <!-- Home Button -->
                        <a href="/" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>
                            Go Home
                        </a>
                        
                        <!-- Back Button -->
                        <button onclick="goBack()" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Go Back
                        </button>
                        
                        <!-- Retry Button (for 500 errors) -->
                        <?php if (($status_code ?? 500) === 500): ?>
                            <button onclick="retryPage()" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-redo me-2"></i>
                                Try Again
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Support Info -->
                    <div class="mt-5 pt-4 border-top">
                        <p class="text-body-tertiary small mb-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Need help? Contact support or try these options:
                        </p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                                <a href="/my-cases.php" class="text-decoration-none small">
                                    <i class="fas fa-folder me-1"></i>
                                    My Cases
                                </a>
                            <?php else: ?>
                                <a href="/login.php" class="text-decoration-none small">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    Login
                                </a>
                            <?php endif; ?>
                            <a href="/upload.php" class="text-decoration-none small">
                                <i class="fas fa-upload me-1"></i>
                                Upload New Case
                            </a>
                            <a href="mailto:support@appealprospect.demo" class="text-decoration-none small">
                                <i class="fas fa-envelope me-1"></i>
                                Email Support
                            </a>
                        </div>
                    </div>
                    
                    <!-- Error Reference (for debugging) -->
                    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
                        <div class="mt-4 pt-3 border-top">
                            <p class="text-body-tertiary small mb-1">
                                <strong>Error Reference:</strong> ERR-<?= date('YmdHis') ?>-<?= substr(md5($_SERVER['REQUEST_URI'] ?? ''), 0, 6) ?>
                            </p>
                            <p class="text-body-tertiary small mb-0">
                                <strong>Request:</strong> <?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'GET') ?> <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Go back functionality
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        }
        
        // Retry page functionality
        function retryPage() {
            window.location.reload();
        }
        
        // Auto-hide loading state after retry
        document.addEventListener('DOMContentLoaded', function() {
            const retryBtn = document.querySelector('[onclick="retryPage()"]');
            if (retryBtn) {
                retryBtn.addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Retrying...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        retryPage();
                    }, 1000);
                });
            }
        });
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Backspace') {
                e.preventDefault();
                goBack();
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                window.location.href = '/';
            } else if (e.key === 'r' || e.key === 'R') {
                if (e.ctrlKey) {
                    e.preventDefault();
                    retryPage();
                }
            }
        });
        
        // Show keyboard shortcuts hint
        setTimeout(() => {
            if (!localStorage.getItem('error_shortcuts_shown')) {
                const tooltip = document.createElement('div');
                tooltip.className = 'position-fixed top-0 end-0 m-3 alert alert-info alert-dismissible fade show';
                tooltip.style.zIndex = '9999';
                tooltip.innerHTML = `
                    <small>
                        <strong>Keyboard shortcuts:</strong><br>
                        <kbd>Esc</kbd> Go back | <kbd>Enter</kbd> Home | <kbd>Ctrl+R</kbd> Retry
                    </small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(tooltip);
                
                setTimeout(() => {
                    if (tooltip.parentNode) {
                        tooltip.remove();
                    }
                }, 5000);
                
                localStorage.setItem('error_shortcuts_shown', 'true');
            }
        }, 2000);
    </script>
</body>
</html>