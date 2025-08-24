<?php
/**
 * Accessibility Enhancements
 * Appeal Prospect MVP - WCAG 2.1 AA Compliance
 */
?>

<!-- Skip Navigation Links -->
<div class="skip-links">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <a href="#navigation" class="skip-link">Skip to navigation</a>
    <a href="#footer" class="skip-link">Skip to footer</a>
</div>

<!-- Screen Reader Announcements -->
<div aria-live="polite" aria-atomic="true" class="sr-announcements">
    <div id="sr-status" class="visually-hidden"></div>
</div>

<div aria-live="assertive" aria-atomic="true" class="sr-announcements">
    <div id="sr-alert" class="visually-hidden"></div>
</div>

<style>
/* Skip Links */
.skip-links {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10000;
}

.skip-link {
    position: absolute;
    top: -40px;
    left: 6px;
    background: #000;
    color: #fff;
    padding: 8px 12px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    border-radius: 0 0 4px 4px;
    transition: top 0.3s ease;
}

.skip-link:focus {
    top: 0;
    color: #fff;
}

/* Screen Reader Only Content */
.visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

.visually-hidden-focusable:focus {
    position: static !important;
    width: auto !important;
    height: auto !important;
    padding: inherit !important;
    margin: inherit !important;
    overflow: visible !important;
    clip: auto !important;
    white-space: inherit !important;
}

/* Focus Management */
.focus-trap {
    position: relative;
}

.focus-trap::before,
.focus-trap::after {
    content: "";
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    pointer-events: none;
}

/* Enhanced Focus Indicators */
*:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.btn:focus,
.form-control:focus,
.form-select:focus,
.form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
    .btn {
        border: 2px solid;
    }
    
    .card {
        border: 2px solid;
    }
    
    .alert {
        border: 2px solid;
    }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .skip-link {
        background: #fff;
        color: #000;
    }
    
    .skip-link:focus {
        color: #000;
    }
}

/* Print Styles */
@media print {
    .skip-links,
    .sr-announcements,
    .btn,
    .navbar,
    .footer {
        display: none !important;
    }
    
    .container {
        max-width: none !important;
        padding: 0 !important;
    }
}

/* Large Text Support */
@media (min-width: 1200px) {
    .large-text {
        font-size: 1.125rem;
        line-height: 1.6;
    }
}

/* Color Blind Friendly Colors */
.color-blind-friendly {
    --primary-accessible: #0066cc;
    --success-accessible: #007700;
    --warning-accessible: #ff8800;
    --danger-accessible: #cc0000;
}

.color-blind-friendly .text-primary { color: var(--primary-accessible) !important; }
.color-blind-friendly .text-success { color: var(--success-accessible) !important; }
.color-blind-friendly .text-warning { color: var(--warning-accessible) !important; }
.color-blind-friendly .text-danger { color: var(--danger-accessible) !important; }

/* Touch Targets */
@media (max-width: 768px) {
    .btn,
    .form-control,
    .form-select,
    .form-check-input,
    .nav-link {
        min-height: 44px;
        min-width: 44px;
        padding: 12px 16px;
    }
    
    .btn-sm {
        min-height: 36px;
        padding: 8px 12px;
    }
}

/* Table Accessibility */
.table-responsive {
    overflow-x: auto;
}

.table th {
    font-weight: 600;
}

.table caption {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    color: #6c757d;
    text-align: left;
    caption-side: top;
}

/* Form Accessibility */
.required-field::after {
    content: " *";
    color: #dc3545;
    font-weight: bold;
}

.form-text {
    margin-top: 0.25rem;
}

/* Modal Accessibility */
.modal {
    --bs-modal-bg: #fff;
}

.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
}

/* Keyboard Navigation Indicators */
.keyboard-user *:focus {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

.mouse-user *:focus {
    outline: none;
}

/* Status and Error Messages */
.status-message {
    padding: 0.75rem 1rem;
    margin: 1rem 0;
    border-radius: 0.375rem;
    border-left: 4px solid;
}

.status-message.success {
    background-color: #d1e7dd;
    border-color: #198754;
    color: #0f5132;
}

.status-message.error {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

.status-message.warning {
    background-color: #fff3cd;
    border-color: #ffc107;
    color: #664d03;
}

.status-message.info {
    background-color: #cff4fc;
    border-color: #0dcaf0;
    color: #055160;
}
</style>

<script>
/**
 * Accessibility Enhancement Manager
 */
class AccessibilityManager {
    constructor() {
        this.isKeyboardUser = false;
        this.announcer = document.getElementById('sr-status');
        this.alerter = document.getElementById('sr-alert');
        this.focusableElements = [
            'a[href]',
            'button:not([disabled])',
            'textarea:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ];
        
        this.init();
    }
    
    init() {
        this.detectKeyboardUsers();
        this.enhanceFormAccessibility();
        this.setupKeyboardNavigation();
        this.setupFocusManagement();
        this.setupARIALiveRegions();
        this.enhanceModals();
        this.setupTableAccessibility();
    }
    
    // Detect keyboard users for appropriate focus styles
    detectKeyboardUsers() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                this.isKeyboardUser = true;
                document.body.classList.add('keyboard-user');
                document.body.classList.remove('mouse-user');
            }
        });
        
        document.addEventListener('mousedown', () => {
            this.isKeyboardUser = false;
            document.body.classList.add('mouse-user');
            document.body.classList.remove('keyboard-user');
        });
    }
    
    // Enhance form accessibility
    enhanceFormAccessibility() {
        // Add required indicators
        const requiredFields = document.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            const label = document.querySelector(`label[for="${field.id}"]`);
            if (label && !label.classList.contains('required-field')) {
                label.classList.add('required-field');
            }
            
            // Add ARIA attributes
            field.setAttribute('aria-required', 'true');
        });
        
        // Enhance error states
        const invalidFields = document.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => {
            field.setAttribute('aria-invalid', 'true');
            
            const errorElement = field.parentNode.querySelector('.invalid-feedback');
            if (errorElement && !errorElement.id) {
                errorElement.id = `${field.name || field.id}-error`;
                field.setAttribute('aria-describedby', errorElement.id);
            }
        });
        
        // Enhance fieldsets and legends
        const fieldsets = document.querySelectorAll('fieldset');
        fieldsets.forEach(fieldset => {
            const legend = fieldset.querySelector('legend');
            if (legend) {
                legend.setAttribute('role', 'group');
            }
        });
    }
    
    // Setup keyboard navigation
    setupKeyboardNavigation() {
        // Escape key handling
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.handleEscapeKey(e);
            }
        });
        
        // Arrow key navigation for menus
        const navMenus = document.querySelectorAll('[role="menu"], .nav');
        navMenus.forEach(menu => {
            this.setupArrowNavigation(menu);
        });
        
        // Enter and Space key handling for custom elements
        document.addEventListener('keydown', (e) => {
            if ((e.key === 'Enter' || e.key === ' ') && e.target.hasAttribute('role')) {
                this.handleActivationKeys(e);
            }
        });
    }
    
    // Handle Escape key for dismissing modals, dropdowns, etc.
    handleEscapeKey(e) {
        // Close modals
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const closeBtn = openModal.querySelector('[data-bs-dismiss="modal"]');
            if (closeBtn) closeBtn.click();
            return;
        }
        
        // Close dropdowns
        const openDropdown = document.querySelector('.dropdown-menu.show');
        if (openDropdown) {
            const toggle = document.querySelector('[data-bs-toggle="dropdown"][aria-expanded="true"]');
            if (toggle) toggle.click();
            return;
        }
        
        // Clear focus from active element
        if (document.activeElement && document.activeElement !== document.body) {
            document.activeElement.blur();
        }
    }
    
    // Setup arrow key navigation for menus
    setupArrowNavigation(menu) {
        const items = menu.querySelectorAll('a, button, [role="menuitem"]');
        
        menu.addEventListener('keydown', (e) => {
            if (!['ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key)) return;
            
            e.preventDefault();
            const currentIndex = Array.from(items).indexOf(document.activeElement);
            let newIndex;
            
            switch (e.key) {
                case 'ArrowDown':
                    newIndex = (currentIndex + 1) % items.length;
                    break;
                case 'ArrowUp':
                    newIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                    break;
                case 'Home':
                    newIndex = 0;
                    break;
                case 'End':
                    newIndex = items.length - 1;
                    break;
            }
            
            items[newIndex].focus();
        });
    }
    
    // Handle Enter and Space for custom interactive elements
    handleActivationKeys(e) {
        const element = e.target;
        const role = element.getAttribute('role');
        
        if (['button', 'tab', 'menuitem'].includes(role)) {
            e.preventDefault();
            element.click();
        }
    }
    
    // Setup focus management
    setupFocusManagement() {
        // Focus trap for modals
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', () => {
                this.trapFocus(modal);
            });
        });
        
        // Return focus after modal closes
        modals.forEach(modal => {
            let previousFocus = null;
            
            modal.addEventListener('show.bs.modal', () => {
                previousFocus = document.activeElement;
            });
            
            modal.addEventListener('hidden.bs.modal', () => {
                if (previousFocus) {
                    previousFocus.focus();
                }
            });
        });
    }
    
    // Trap focus within an element
    trapFocus(element) {
        const focusableElements = element.querySelectorAll(this.focusableElements.join(', '));
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        // Focus the first element
        if (firstElement) {
            firstElement.focus();
        }
        
        element.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;
            
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }
    
    // Setup ARIA live regions
    setupARIALiveRegions() {
        // Announce page changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    this.handleContentChanges(mutation.addedNodes);
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Handle dynamic content changes
    handleContentChanges(addedNodes) {
        addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                // Announce new alerts
                if (node.classList && node.classList.contains('alert')) {
                    this.announceToScreenReader(node.textContent, 'assertive');
                }
                
                // Enhance new form elements
                const newForms = node.querySelectorAll ? node.querySelectorAll('form') : [];
                newForms.forEach(form => {
                    this.enhanceFormAccessibility();
                });
            }
        });
    }
    
    // Enhance modal accessibility
    enhanceModals() {
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            // Add ARIA attributes
            if (!modal.getAttribute('aria-labelledby')) {
                const title = modal.querySelector('.modal-title');
                if (title) {
                    if (!title.id) title.id = 'modal-title-' + Date.now();
                    modal.setAttribute('aria-labelledby', title.id);
                }
            }
            
            if (!modal.getAttribute('aria-describedby')) {
                const body = modal.querySelector('.modal-body');
                if (body) {
                    if (!body.id) body.id = 'modal-body-' + Date.now();
                    modal.setAttribute('aria-describedby', body.id);
                }
            }
            
            // Ensure proper role
            if (!modal.getAttribute('role')) {
                modal.setAttribute('role', 'dialog');
            }
            
            modal.setAttribute('aria-modal', 'true');
        });
    }
    
    // Setup table accessibility
    setupTableAccessibility() {
        const tables = document.querySelectorAll('table:not([role])');
        
        tables.forEach(table => {
            table.setAttribute('role', 'table');
            
            // Add caption if missing
            if (!table.querySelector('caption')) {
                const caption = document.createElement('caption');
                caption.className = 'visually-hidden';
                caption.textContent = 'Data table';
                table.insertBefore(caption, table.firstChild);
            }
            
            // Enhance headers
            const headers = table.querySelectorAll('th');
            headers.forEach(header => {
                if (!header.getAttribute('scope')) {
                    header.setAttribute('scope', 'col');
                }
            });
            
            // Add table-responsive wrapper if missing
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }
    
    // Announce messages to screen readers
    announceToScreenReader(message, priority = 'polite') {
        const announcer = priority === 'assertive' ? this.alerter : this.announcer;
        if (!announcer) return;
        
        // Clear previous message
        announcer.textContent = '';
        
        // Add new message after a brief delay to ensure it's announced
        setTimeout(() => {
            announcer.textContent = message;
        }, 100);
        
        // Clear message after 5 seconds
        setTimeout(() => {
            announcer.textContent = '';
        }, 5000);
    }
    
    // Update page title and announce navigation
    updatePageTitle(title, announce = true) {
        document.title = title;
        
        if (announce) {
            this.announceToScreenReader(`Navigated to ${title}`, 'polite');
        }
    }
    
    // Manage focus after AJAX content loads
    manageFocusAfterAjax(container, focusTarget = null) {
        if (focusTarget) {
            const element = container.querySelector(focusTarget);
            if (element) {
                element.focus();
                return;
            }
        }
        
        // Focus first heading or focusable element
        const firstHeading = container.querySelector('h1, h2, h3, h4, h5, h6');
        if (firstHeading) {
            firstHeading.setAttribute('tabindex', '-1');
            firstHeading.focus();
            return;
        }
        
        const firstFocusable = container.querySelector(this.focusableElements.join(', '));
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }
}

// Initialize accessibility manager
document.addEventListener('DOMContentLoaded', () => {
    window.accessibilityManager = new AccessibilityManager();
});

// Global helper functions
window.announceToScreenReader = (message, priority) => {
    if (window.accessibilityManager) {
        window.accessibilityManager.announceToScreenReader(message, priority);
    }
};

window.updatePageTitle = (title, announce) => {
    if (window.accessibilityManager) {
        window.accessibilityManager.updatePageTitle(title, announce);
    }
};

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Alt + M for main menu
    if (e.altKey && e.key === 'm') {
        e.preventDefault();
        const mainNav = document.querySelector('#navigation, .navbar-nav, nav');
        if (mainNav) {
            const firstLink = mainNav.querySelector('a, button');
            if (firstLink) firstLink.focus();
        }
    }
    
    // Alt + S for search (if exists)
    if (e.altKey && e.key === 's') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"], #search, [role="searchbox"]');
        if (searchInput) searchInput.focus();
    }
    
    // Alt + H for home
    if (e.altKey && e.key === 'h') {
        e.preventDefault();
        const homeLink = document.querySelector('a[href="/"], a[href="/index.php"], .navbar-brand');
        if (homeLink) homeLink.click();
    }
});
</script>