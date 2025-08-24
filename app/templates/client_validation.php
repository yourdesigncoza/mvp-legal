<?php
/**
 * Client-side Validation System
 * Appeal Prospect MVP - Real-time Form Validation
 */
?>

<script>
/**
 * Client-side Form Validator
 */
class ClientValidator {
    constructor(form, options = {}) {
        this.form = typeof form === 'string' ? document.querySelector(form) : form;
        this.options = {
            validateOnBlur: true,
            validateOnInput: true,
            showSuccessState: true,
            realTimeValidation: true,
            submitValidation: true,
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorDisplayClass: 'invalid-feedback',
            successDisplayClass: 'valid-feedback',
            ...options
        };
        
        this.errors = {};
        this.validatedFields = {};
        
        if (this.form) {
            this.init();
        }
    }
    
    init() {
        this.bindEvents();
        this.setupCustomValidators();
        
        // Add validation attributes from server-side rules
        this.applyServerValidationRules();
    }
    
    bindEvents() {
        // Form submission validation
        if (this.options.submitValidation) {
            this.form.addEventListener('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                    this.showFormErrors();
                }
            });
        }
        
        // Field-level validation
        const fields = this.form.querySelectorAll('input, textarea, select');
        fields.forEach(field => {
            if (this.options.validateOnBlur) {
                field.addEventListener('blur', () => {
                    this.validateField(field);
                });
            }
            
            if (this.options.validateOnInput) {
                field.addEventListener('input', () => {
                    if (this.validatedFields[field.name]) {
                        this.validateField(field);
                    }
                });
            }
            
            // Real-time validation for specific fields
            if (this.options.realTimeValidation) {
                if (field.type === 'email') {
                    field.addEventListener('input', this.debounce(() => {
                        this.validateField(field);
                    }, 300));
                }
                
                if (field.type === 'password') {
                    field.addEventListener('input', () => {
                        this.validatePassword(field);
                        this.validatePasswordConfirmation(field);
                    });
                }
            }
        });
    }
    
    setupCustomValidators() {
        this.validators = {
            required: (value, field) => {
                if (!value.trim()) {
                    return 'This field is required.';
                }
                return true;
            },
            
            email: (value, field) => {
                if (!value) return true;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    return 'Please enter a valid email address.';
                }
                return true;
            },
            
            minlength: (value, field) => {
                if (!value) return true;
                const min = parseInt(field.getAttribute('minlength'));
                if (value.length < min) {
                    return `Must be at least ${min} characters long.`;
                }
                return true;
            },
            
            maxlength: (value, field) => {
                if (!value) return true;
                const max = parseInt(field.getAttribute('maxlength'));
                if (value.length > max) {
                    return `Cannot be longer than ${max} characters.`;
                }
                return true;
            },
            
            min: (value, field) => {
                if (!value) return true;
                const min = parseFloat(field.getAttribute('min'));
                if (parseFloat(value) < min) {
                    return `Must be at least ${min}.`;
                }
                return true;
            },
            
            max: (value, field) => {
                if (!value) return true;
                const max = parseFloat(field.getAttribute('max'));
                if (parseFloat(value) > max) {
                    return `Cannot be greater than ${max}.`;
                }
                return true;
            },
            
            pattern: (value, field) => {
                if (!value) return true;
                const pattern = field.getAttribute('pattern');
                const regex = new RegExp(pattern);
                if (!regex.test(value)) {
                    return field.getAttribute('title') || 'Invalid format.';
                }
                return true;
            },
            
            url: (value, field) => {
                if (!value) return true;
                try {
                    new URL(value);
                    return true;
                } catch {
                    return 'Please enter a valid URL.';
                }
            },
            
            number: (value, field) => {
                if (!value) return true;
                if (isNaN(value) || isNaN(parseFloat(value))) {
                    return 'Please enter a valid number.';
                }
                return true;
            },
            
            casename: (value, field) => {
                if (!value) return true;
                if (value.length < 3) {
                    return 'Case name must be at least 3 characters long.';
                }
                if (!/^[a-zA-Z0-9\s\-\(\)\.\/]+$/.test(value)) {
                    return 'Case name contains invalid characters.';
                }
                return true;
            },
            
            password: (value, field) => {
                if (!value) return true;
                
                const errors = [];
                if (value.length < 8) {
                    errors.push('at least 8 characters');
                }
                if (!/[a-z]/.test(value)) {
                    errors.push('one lowercase letter');
                }
                if (!/[A-Z]/.test(value)) {
                    errors.push('one uppercase letter');
                }
                if (!/\d/.test(value)) {
                    errors.push('one number');
                }
                
                if (errors.length > 0) {
                    return `Password must contain ${errors.join(', ')}.`;
                }
                return true;
            },
            
            confirmed: (value, field) => {
                const confirmField = this.form.querySelector(`[name="${field.name}_confirmation"]`);
                if (!confirmField) return true;
                
                if (value !== confirmField.value) {
                    return 'The confirmation does not match.';
                }
                return true;
            }
        };
    }
    
    validateField(field) {
        const value = field.value;
        const fieldName = field.name;
        let isValid = true;
        let errorMessage = '';
        
        // Clear previous validation state
        this.clearFieldValidation(field);
        
        // Run built-in HTML5 validation first
        if (!field.checkValidity()) {
            errorMessage = field.validationMessage;
            isValid = false;
        }
        
        // Run custom validators
        if (isValid) {
            const validators = this.getFieldValidators(field);
            
            for (const validator of validators) {
                const result = this.validators[validator](value, field);
                if (result !== true) {
                    errorMessage = result;
                    isValid = false;
                    break;
                }
            }
        }
        
        // Apply validation state
        if (isValid) {
            this.setFieldSuccess(field);
            delete this.errors[fieldName];
        } else {
            this.setFieldError(field, errorMessage);
            this.errors[fieldName] = errorMessage;
        }
        
        this.validatedFields[fieldName] = true;
        return isValid;
    }
    
    validateForm() {
        const fields = this.form.querySelectorAll('input, textarea, select');
        let isValid = true;
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    getFieldValidators(field) {
        const validators = [];
        
        // HTML5 attributes
        if (field.hasAttribute('required')) validators.push('required');
        if (field.type === 'email') validators.push('email');
        if (field.type === 'url') validators.push('url');
        if (field.type === 'number') validators.push('number');
        if (field.hasAttribute('minlength')) validators.push('minlength');
        if (field.hasAttribute('maxlength')) validators.push('maxlength');
        if (field.hasAttribute('min')) validators.push('min');
        if (field.hasAttribute('max')) validators.push('max');
        if (field.hasAttribute('pattern')) validators.push('pattern');
        
        // Custom validators based on field name/class
        if (field.name === 'password') validators.push('password');
        if (field.name.endsWith('_confirmation')) validators.push('confirmed');
        if (field.name === 'case_name') validators.push('casename');
        
        // Data attributes for custom validation
        const customValidators = field.getAttribute('data-validators');
        if (customValidators) {
            validators.push(...customValidators.split(','));
        }
        
        return validators;
    }
    
    setFieldError(field, message) {
        field.classList.remove(this.options.successClass);
        field.classList.add(this.options.errorClass);
        
        this.showFieldMessage(field, message, 'error');
        
        // Add ARIA attributes for accessibility
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', `${field.name}-error`);
    }
    
    setFieldSuccess(field) {
        field.classList.remove(this.options.errorClass);
        
        if (this.options.showSuccessState) {
            field.classList.add(this.options.successClass);
        }
        
        this.hideFieldMessage(field, 'error');
        
        // Remove ARIA attributes
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
    }
    
    clearFieldValidation(field) {
        field.classList.remove(this.options.errorClass, this.options.successClass);
        this.hideFieldMessage(field, 'error');
        this.hideFieldMessage(field, 'success');
    }
    
    showFieldMessage(field, message, type) {
        const messageId = `${field.name}-${type}`;
        let messageElement = document.getElementById(messageId);
        
        if (!messageElement) {
            messageElement = document.createElement('div');
            messageElement.id = messageId;
            messageElement.className = type === 'error' ? 
                this.options.errorDisplayClass : 
                this.options.successDisplayClass;
            
            // Insert after field or form group
            const formGroup = field.closest('.form-group, .mb-3, .col, .form-floating');
            const insertAfter = formGroup || field;
            insertAfter.parentNode.insertBefore(messageElement, insertAfter.nextSibling);
        }
        
        messageElement.textContent = message;
        messageElement.style.display = 'block';
        
        // Add animation
        messageElement.classList.add('fade-in');
        setTimeout(() => messageElement.classList.remove('fade-in'), 300);
    }
    
    hideFieldMessage(field, type) {
        const messageId = `${field.name}-${type}`;
        const messageElement = document.getElementById(messageId);
        
        if (messageElement) {
            messageElement.style.display = 'none';
        }
    }
    
    showFormErrors() {
        // Scroll to first error
        const firstErrorField = this.form.querySelector(`.${this.options.errorClass}`);
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorField.focus();
        }
        
        // Show summary if exists
        this.updateErrorSummary();
    }
    
    updateErrorSummary() {
        const errorSummary = this.form.querySelector('.validation-summary');
        if (!errorSummary) return;
        
        if (Object.keys(this.errors).length > 0) {
            const errorList = Object.values(this.errors)
                .map(error => `<li>${this.escapeHtml(error)}</li>`)
                .join('');
            
            errorSummary.innerHTML = `
                <div class="alert alert-subtle-danger" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Please correct the following errors:
                    </h6>
                    <ul class="mb-0">${errorList}</ul>
                </div>
            `;
            errorSummary.style.display = 'block';
        } else {
            errorSummary.style.display = 'none';
        }
    }
    
    validatePassword(field) {
        if (field.name !== 'password') return;
        
        const strengthIndicator = document.getElementById('password-strength');
        if (!strengthIndicator) return;
        
        const password = field.value;
        const strength = this.calculatePasswordStrength(password);
        
        strengthIndicator.className = `password-strength ${strength.class}`;
        strengthIndicator.innerHTML = `
            <div class="strength-bar">
                <div class="strength-fill" style="width: ${strength.percentage}%"></div>
            </div>
            <div class="strength-text">${strength.text}</div>
        `;
    }
    
    calculatePasswordStrength(password) {
        let score = 0;
        const checks = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };
        
        score = Object.values(checks).filter(Boolean).length;
        
        const strengthLevels = {
            0: { class: 'very-weak', text: 'Very Weak', percentage: 0 },
            1: { class: 'weak', text: 'Weak', percentage: 20 },
            2: { class: 'fair', text: 'Fair', percentage: 40 },
            3: { class: 'good', text: 'Good', percentage: 60 },
            4: { class: 'strong', text: 'Strong', percentage: 80 },
            5: { class: 'very-strong', text: 'Very Strong', percentage: 100 }
        };
        
        return strengthLevels[score] || strengthLevels[0];
    }
    
    validatePasswordConfirmation(field) {
        const confirmField = this.form.querySelector('[name="confirm_password"], [name="password_confirmation"]');
        if (!confirmField) return;
        
        // If this is the password field, validate the confirmation
        if (field.name === 'password' && confirmField.value) {
            this.validateField(confirmField);
        }
        
        // If this is the confirmation field, validate it
        if (field === confirmField) {
            this.validateField(confirmField);
        }
    }
    
    applyServerValidationRules() {
        // Apply validation rules passed from server
        const rulesScript = document.querySelector('#validation-rules');
        if (!rulesScript) return;
        
        try {
            const serverRules = JSON.parse(rulesScript.textContent);
            
            Object.entries(serverRules).forEach(([fieldName, rules]) => {
                const field = this.form.querySelector(`[name="${fieldName}"]`);
                if (!field) return;
                
                // Apply HTML5 validation attributes
                rules.forEach(rule => {
                    if (rule === 'required') {
                        field.setAttribute('required', '');
                    } else if (rule.startsWith('min:')) {
                        const min = rule.split(':')[1];
                        field.setAttribute('minlength', min);
                    } else if (rule.startsWith('max:')) {
                        const max = rule.split(':')[1];
                        field.setAttribute('maxlength', max);
                    }
                });
            });
        } catch (e) {
            console.warn('Failed to parse server validation rules:', e);
        }
    }
    
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Auto-initialize validators on forms with data-validate attribute
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        const options = {};
        
        // Parse options from data attributes
        if (form.dataset.validateOnBlur !== undefined) {
            options.validateOnBlur = form.dataset.validateOnBlur !== 'false';
        }
        if (form.dataset.validateOnInput !== undefined) {
            options.validateOnInput = form.dataset.validateOnInput !== 'false';
        }
        if (form.dataset.showSuccessState !== undefined) {
            options.showSuccessState = form.dataset.showSuccessState !== 'false';
        }
        
        new ClientValidator(form, options);
    });
});

// Global function to create validator
window.createValidator = (form, options) => new ClientValidator(form, options);
</script>

<style>
/* Password strength indicator */
.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.strength-fill {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 2px;
}

.password-strength.very-weak .strength-fill { background-color: #dc3545; }
.password-strength.weak .strength-fill { background-color: #fd7e14; }
.password-strength.fair .strength-fill { background-color: #ffc107; }
.password-strength.good .strength-fill { background-color: #20c997; }
.password-strength.strong .strength-fill { background-color: #198754; }
.password-strength.very-strong .strength-fill { background-color: #0d6efd; }

.strength-text {
    font-size: 0.75rem;
    font-weight: 500;
}

.password-strength.very-weak .strength-text { color: #dc3545; }
.password-strength.weak .strength-text { color: #fd7e14; }
.password-strength.fair .strength-text { color: #ffc107; }
.password-strength.good .strength-text { color: #20c997; }
.password-strength.strong .strength-text { color: #198754; }
.password-strength.very-strong .strength-text { color: #0d6efd; }

/* Validation animations */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Enhanced form field states */
.form-control.is-invalid,
.form-select.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-control.is-valid,
.form-select.is-valid {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

/* Validation summary */
.validation-summary {
    margin-bottom: 1.5rem;
}

.validation-summary ul {
    padding-left: 1.5rem;
}

.validation-summary li {
    margin-bottom: 0.25rem;
}

/* Focus states for accessibility */
.form-control:focus.is-invalid {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-control:focus.is-valid {
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}
</style>