<?php
/**
 * Loading States and Progress Indicators
 * Appeal Prospect MVP - UX Enhancement Components
 */
?>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay d-none">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div class="loading-message mt-3">
            <h5 id="loading-title">Processing...</h5>
            <p id="loading-text" class="text-body-secondary mb-0">Please wait while we process your request.</p>
        </div>
    </div>
</div>

<!-- Progress Bar Component -->
<div id="progress-container" class="progress-container d-none">
    <div class="progress-header mb-2">
        <div class="d-flex justify-content-between align-items-center">
            <span id="progress-label" class="fw-medium">Processing</span>
            <span id="progress-percentage" class="text-body-secondary">0%</span>
        </div>
    </div>
    <div class="progress" style="height: 8px;">
        <div 
            id="progress-bar" 
            class="progress-bar progress-bar-striped progress-bar-animated" 
            role="progressbar" 
            style="width: 0%"
            aria-valuenow="0" 
            aria-valuemin="0" 
            aria-valuemax="100"
        ></div>
    </div>
    <div id="progress-message" class="mt-2 text-body-secondary small"></div>
</div>

<!-- Skeleton Loaders -->
<div class="skeleton-loader d-none">
    <div class="row">
        <div class="col-md-8">
            <div class="skeleton-item skeleton-title mb-3"></div>
            <div class="skeleton-item skeleton-text mb-2"></div>
            <div class="skeleton-item skeleton-text mb-2" style="width: 80%;"></div>
            <div class="skeleton-item skeleton-text mb-3" style="width: 60%;"></div>
            
            <div class="skeleton-item skeleton-subtitle mb-2"></div>
            <div class="skeleton-item skeleton-text mb-2"></div>
            <div class="skeleton-item skeleton-text mb-2" style="width: 90%;"></div>
        </div>
        <div class="col-md-4">
            <div class="skeleton-item skeleton-card">
                <div class="skeleton-item skeleton-subtitle mb-2"></div>
                <div class="skeleton-item skeleton-text mb-2"></div>
                <div class="skeleton-item skeleton-text" style="width: 70%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Inline Spinner Component -->
<span class="inline-spinner d-none">
    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
    <span class="spinner-text">Loading...</span>
</span>

<!-- Button Loading States -->
<style>
.btn.loading {
    pointer-events: none;
    position: relative;
}

.btn.loading .btn-text {
    opacity: 0;
}

.btn.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: button-loading-spinner 1s ease infinite;
}

@keyframes button-loading-spinner {
    from {
        transform: rotate(0turn);
    }
    to {
        transform: rotate(1turn);
    }
}
</style>

<style>
/* Loading Overlay Styles */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(5px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.3s ease;
}

.loading-content {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.05);
    max-width: 300px;
    animation: loadingPulse 2s ease-in-out infinite;
}

@keyframes loadingPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.loading-spinner .spinner-border {
    animation-duration: 1.5s;
}

/* Progress Container */
.progress-container {
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin: 1rem 0;
}

.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
    overflow: hidden;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
    background: linear-gradient(45deg, #007bff, #0056b3);
}

/* Skeleton Loader Styles */
.skeleton-loader {
    padding: 1.5rem;
}

.skeleton-item {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 8px;
    height: 1rem;
    margin-bottom: 0.75rem;
}

.skeleton-title {
    height: 2rem;
    width: 70%;
}

.skeleton-subtitle {
    height: 1.5rem;
    width: 50%;
}

.skeleton-text {
    height: 1rem;
    width: 100%;
}

.skeleton-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
}

@keyframes skeleton-loading {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Inline Spinner */
.inline-spinner {
    display: inline-flex;
    align-items: center;
    color: #6c757d;
    font-size: 0.875rem;
}

.inline-spinner .spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 1px;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .loading-content {
        margin: 1rem;
        padding: 1.5rem;
        max-width: 280px;
    }
    
    .progress-container {
        margin: 1rem;
        padding: 1rem;
    }
    
    .skeleton-loader {
        padding: 1rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .loading-overlay {
        background: rgba(33, 37, 41, 0.95);
    }
    
    .loading-content {
        background: #212529;
        color: white;
        border-color: #495057;
    }
    
    .progress-container {
        background: #212529;
        border-color: #495057;
        color: white;
    }
    
    .skeleton-item {
        background: linear-gradient(90deg, #343a40 25%, #495057 50%, #343a40 75%);
        background-size: 200% 100%;
    }
    
    .skeleton-card {
        background: #343a40;
        border-color: #495057;
    }
}

/* Animation classes for show/hide */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

.fade-out {
    animation: fadeOut 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}

/* Focus states for accessibility */
.loading-overlay:focus-within .loading-content {
    box-shadow: 0 10px 30px rgba(0, 123, 255, 0.2);
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .loading-overlay {
        background: rgba(255, 255, 255, 1);
    }
    
    .loading-content {
        border: 2px solid #000;
    }
    
    .skeleton-item {
        background: #000;
        opacity: 0.1;
    }
}
</style>

<script>
/**
 * Loading States Manager
 */
class LoadingManager {
    constructor() {
        this.overlay = document.getElementById('loading-overlay');
        this.progressContainer = document.getElementById('progress-container');
        this.progressBar = document.getElementById('progress-bar');
        this.isLoading = false;
        this.currentProgress = 0;
    }
    
    // Show loading overlay
    show(title = 'Processing...', message = 'Please wait while we process your request.') {
        if (!this.overlay) return;
        
        document.getElementById('loading-title').textContent = title;
        document.getElementById('loading-text').textContent = message;
        
        this.overlay.classList.remove('d-none');
        this.overlay.classList.add('fade-in');
        this.isLoading = true;
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Focus trap
        this.overlay.focus();
    }
    
    // Hide loading overlay
    hide() {
        if (!this.overlay) return;
        
        this.overlay.classList.add('fade-out');
        
        setTimeout(() => {
            this.overlay.classList.add('d-none');
            this.overlay.classList.remove('fade-in', 'fade-out');
            this.isLoading = false;
            
            // Restore body scroll
            document.body.style.overflow = '';
        }, 300);
    }
    
    // Show progress bar
    showProgress(label = 'Processing', percentage = 0, message = '') {
        if (!this.progressContainer) return;
        
        document.getElementById('progress-label').textContent = label;
        this.updateProgress(percentage, message);
        
        this.progressContainer.classList.remove('d-none');
        this.progressContainer.classList.add('fade-in');
    }
    
    // Update progress
    updateProgress(percentage, message = '') {
        if (!this.progressBar) return;
        
        this.currentProgress = Math.max(0, Math.min(100, percentage));
        
        this.progressBar.style.width = this.currentProgress + '%';
        this.progressBar.setAttribute('aria-valuenow', this.currentProgress.toString());
        
        document.getElementById('progress-percentage').textContent = Math.round(this.currentProgress) + '%';
        
        if (message) {
            document.getElementById('progress-message').textContent = message;
        }
        
        // Auto-hide when complete
        if (this.currentProgress >= 100) {
            setTimeout(() => {
                this.hideProgress();
            }, 1000);
        }
    }
    
    // Hide progress bar
    hideProgress() {
        if (!this.progressContainer) return;
        
        this.progressContainer.classList.add('fade-out');
        
        setTimeout(() => {
            this.progressContainer.classList.add('d-none');
            this.progressContainer.classList.remove('fade-in', 'fade-out');
            this.currentProgress = 0;
        }, 300);
    }
    
    // Set button loading state
    setButtonLoading(button, loadingText = 'Loading...', originalText = '') {
        if (!button) return;
        
        if (!originalText) {
            originalText = button.innerHTML;
        }
        
        button.disabled = true;
        button.classList.add('loading');
        button.setAttribute('data-original-text', originalText);
        
        // Store original text in button element
        const textSpan = button.querySelector('.btn-text') || button;
        textSpan.innerHTML = loadingText;
    }
    
    // Remove button loading state
    removeButtonLoading(button) {
        if (!button) return;
        
        const originalText = button.getAttribute('data-original-text') || 'Submit';
        
        button.disabled = false;
        button.classList.remove('loading');
        button.innerHTML = originalText;
        button.removeAttribute('data-original-text');
    }
    
    // Show skeleton loader
    showSkeleton(container) {
        if (!container) return;
        
        const skeleton = document.querySelector('.skeleton-loader').cloneNode(true);
        skeleton.classList.remove('d-none');
        
        container.innerHTML = '';
        container.appendChild(skeleton);
    }
    
    // Show inline spinner
    showInlineSpinner(container, text = 'Loading...') {
        if (!container) return;
        
        const spinner = document.querySelector('.inline-spinner').cloneNode(true);
        spinner.classList.remove('d-none');
        spinner.querySelector('.spinner-text').textContent = text;
        
        container.appendChild(spinner);
    }
    
    // Remove inline spinner
    removeInlineSpinner(container) {
        if (!container) return;
        
        const spinner = container.querySelector('.inline-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
}

// Global loading manager instance
window.loadingManager = new LoadingManager();

// Convenience functions
window.showLoading = (title, message) => window.loadingManager.show(title, message);
window.hideLoading = () => window.loadingManager.hide();
window.showProgress = (label, percentage, message) => window.loadingManager.showProgress(label, percentage, message);
window.updateProgress = (percentage, message) => window.loadingManager.updateProgress(percentage, message);
window.hideProgress = () => window.loadingManager.hideProgress();
window.setButtonLoading = (button, text, original) => window.loadingManager.setButtonLoading(button, text, original);
window.removeButtonLoading = (button) => window.loadingManager.removeButtonLoading(button);

// Auto-handle form submissions
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions with loading states
    const forms = document.querySelectorAll('form[data-loading]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            const loadingTitle = form.getAttribute('data-loading-title') || 'Processing...';
            const loadingMessage = form.getAttribute('data-loading-message') || 'Please wait while we process your request.';
            
            if (submitBtn) {
                setButtonLoading(submitBtn, 'Processing...');
            }
            
            // Show overlay for complex operations
            if (form.classList.contains('complex-form')) {
                showLoading(loadingTitle, loadingMessage);
            }
        });
    });
    
    // Handle AJAX requests with loading states
    const ajaxButtons = document.querySelectorAll('[data-ajax]');
    ajaxButtons.forEach(button => {
        button.addEventListener('click', function() {
            setButtonLoading(this, 'Loading...');
            
            // Remove loading state after 10 seconds as fallback
            setTimeout(() => {
                removeButtonLoading(this);
            }, 10000);
        });
    });
    
    // Keyboard accessibility for loading overlay
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.loadingManager.isLoading) {
            // Allow escape to cancel loading (if implemented by app)
            if (window.cancelLoading && typeof window.cancelLoading === 'function') {
                window.cancelLoading();
            }
        }
    });
});
</script>