<?php
/**
 * Flash Messages Template Component
 * Appeal Prospect MVP - Enhanced Flash Messages
 */

$flash_messages = get_flash_messages();
if (empty($flash_messages)) {
    return;
}
?>

<!-- Flash Messages Container -->
<div id="flash-messages-container" class="flash-messages-container">
    <?php foreach ($flash_messages as $flash): ?>
        <?php
        // Determine alert class
        $alert_classes = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'danger' => 'alert-danger', 
            'warning' => 'alert-warning',
            'info' => 'alert-info',
            'primary' => 'alert-primary'
        ];
        
        $alert_class = $alert_classes[$flash['type']] ?? 'alert-info';
        $dismissible_class = $flash['dismissible'] ? ' alert-dismissible fade show' : '';
        
        // Determine icon
        $default_icons = [
            'success' => 'fas fa-check-circle',
            'error' => 'fas fa-exclamation-circle',
            'danger' => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'info' => 'fas fa-info-circle',
            'primary' => 'fas fa-star'
        ];
        
        $icon = $flash['icon'] ?? $default_icons[$flash['type']] ?? 'fas fa-info-circle';
        ?>
        
        <div 
            class="alert <?= $alert_class . $dismissible_class ?>" 
            role="alert"
            id="flash-<?= e($flash['id']) ?>"
            data-flash-timeout="<?= $flash['timeout'] ?? '' ?>"
            data-flash-persistent="<?= $flash['persistent'] ? 'true' : 'false' ?>"
        >
            <div class="d-flex align-items-start">
                <!-- Icon -->
                <div class="me-3 mt-1">
                    <i class="<?= $icon ?> fs-5"></i>
                </div>
                
                <!-- Content -->
                <div class="flex-grow-1">
                    <div class="flash-message-content">
                        <?= e($flash['message']) ?>
                    </div>
                    
                    <!-- Actions -->
                    <?php if (!empty($flash['actions'])): ?>
                        <div class="flash-message-actions mt-2">
                            <?php foreach ($flash['actions'] as $action): ?>
                                <a 
                                    href="<?= attr($action['url'] ?? '#') ?>" 
                                    class="btn btn-sm <?= attr($action['class'] ?? 'btn-outline-primary') ?> me-2"
                                    <?php if (isset($action['onclick'])): ?>
                                        onclick="<?= attr($action['onclick']) ?>"
                                    <?php endif; ?>
                                >
                                    <?php if (isset($action['icon'])): ?>
                                        <i class="<?= attr($action['icon']) ?> me-1"></i>
                                    <?php endif; ?>
                                    <?= e($action['text']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Dismiss Button -->
                <?php if ($flash['dismissible']): ?>
                    <button 
                        type="button" 
                        class="btn-close" 
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.flash-messages-container {
    position: relative;
    margin-bottom: 1rem;
}

.flash-messages-container .alert {
    border: 0;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 1rem;
    animation: slideInDown 0.5s ease-out;
}

.flash-messages-container .alert:last-child {
    margin-bottom: 0;
}

.flash-message-content {
    font-weight: 500;
    line-height: 1.4;
}

.flash-message-actions .btn {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
}

/* Animations */
@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideOutUp {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-20px);
    }
}

.flash-message-dismissing {
    animation: slideOutUp 0.3s ease-out forwards;
}

/* Mobile responsiveness */
@media (max-width: 576px) {
    .flash-messages-container .alert {
        margin-left: -15px;
        margin-right: -15px;
        border-radius: 0;
    }
    
    .flash-message-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .flash-message-actions .btn {
        flex: 1;
        min-width: auto;
        font-size: 0.8rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .flash-messages-container .alert {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle automatic dismissal based on timeout
    const flashMessages = document.querySelectorAll('[data-flash-timeout]');
    
    flashMessages.forEach(function(alert) {
        const timeout = parseInt(alert.dataset.flashTimeout);
        if (timeout && timeout > 0) {
            setTimeout(function() {
                dismissFlashMessage(alert);
            }, timeout * 1000);
        }
    });
    
    // Enhanced dismiss functionality
    const dismissButtons = document.querySelectorAll('.flash-messages-container .btn-close');
    dismissButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const alert = this.closest('.alert');
            dismissFlashMessage(alert);
        });
    });
    
    // Keyboard accessibility
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const visibleAlerts = document.querySelectorAll('.flash-messages-container .alert.show');
            if (visibleAlerts.length > 0) {
                dismissFlashMessage(visibleAlerts[visibleAlerts.length - 1]);
            }
        }
    });
});

function dismissFlashMessage(alert) {
    if (!alert) return;
    
    // Add dismissing animation
    alert.classList.add('flash-message-dismissing');
    
    // Remove after animation
    setTimeout(function() {
        if (alert.parentNode) {
            alert.remove();
        }
        
        // Remove container if no messages left
        const container = document.getElementById('flash-messages-container');
        if (container && container.children.length === 0) {
            container.remove();
        }
    }, 300);
}

// Utility function to add flash messages dynamically
function addFlashMessage(message, type = 'info', options = {}) {
    const container = document.getElementById('flash-messages-container') || createFlashContainer();
    const id = 'flash_' + Date.now();
    
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'danger': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle',
        'primary': 'fas fa-star'
    };
    
    const alertClasses = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'danger': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info',
        'primary': 'alert-primary'
    };
    
    const alertHTML = `
        <div class="alert ${alertClasses[type] || 'alert-info'} alert-dismissible fade show" 
             role="alert" id="${id}"
             data-flash-timeout="${options.timeout || ''}"
             data-flash-persistent="${options.persistent ? 'true' : 'false'}">
            <div class="d-flex align-items-start">
                <div class="me-3 mt-1">
                    <i class="${options.icon || icons[type] || icons.info} fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="flash-message-content">${escapeHtml(message)}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', alertHTML);
    
    // Auto-dismiss if timeout is set
    if (options.timeout) {
        setTimeout(function() {
            const alert = document.getElementById(id);
            if (alert) {
                dismissFlashMessage(alert);
            }
        }, options.timeout * 1000);
    }
}

function createFlashContainer() {
    const container = document.createElement('div');
    container.id = 'flash-messages-container';
    container.className = 'flash-messages-container';
    
    // Insert at the beginning of main content
    const main = document.querySelector('main, .content, .container');
    if (main) {
        main.insertBefore(container, main.firstChild);
    } else {
        document.body.insertBefore(container, document.body.firstChild);
    }
    
    return container;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>