<?php
// User Registration Page
// Appeal Prospect MVP - Phoenix UI Implementation

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';

// Start session
start_session();

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$form_data = [
    'name' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf()) {
        $errors['csrf'] = 'Invalid security token. Please try again.';
    } else {
        // Get and sanitize form data
        $form_data['name'] = trim($_POST['name'] ?? '');
        $form_data['email'] = trim($_POST['email'] ?? '');
        $form_data['password'] = $_POST['password'] ?? '';
        $form_data['confirm_password'] = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($form_data['name'])) {
            $errors['name'] = 'Full name is required';
        } elseif (strlen($form_data['name']) < 2) {
            $errors['name'] = 'Full name must be at least 2 characters';
        }
        
        if (empty($form_data['email'])) {
            $errors['email'] = 'Email address is required';
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        if (empty($form_data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($form_data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $form_data['password'])) {
            $errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
        }
        
        if (empty($form_data['confirm_password'])) {
            $errors['confirm_password'] = 'Please confirm your password';
        } elseif ($form_data['password'] !== $form_data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        // If no errors, attempt registration
        if (empty($errors)) {
            if (register_user($form_data['email'], $form_data['password'], $form_data['name'])) {
                set_flash_message('Registration successful! Please log in to continue.', 'success');
                header('Location: /login.php');
                exit;
            } else {
                $errors['email'] = 'An account with this email address already exists';
            }
        }
    }
}

$page_title = 'Register - Appeal Prospect MVP';
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<div class="container-fluid">
    <div class="row flex-center min-vh-100 py-5">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4 col-xxl-3">
            
            <!-- Logo/Brand -->
            <div class="text-center mb-7">
                <a class="d-flex flex-center text-decoration-none mb-4" href="/index.php">
                    <div class="d-flex align-items-center fw-bolder fs-2 d-inline-block text-primary">
                        <i class="fas fa-gavel me-2"></i>
                        Appeal Prospect
                    </div>
                </a>
                <h3 class="text-body-highlight">Create Account</h3>
                <p class="text-body-tertiary">Get started with legal judgment analysis</p>
            </div>

            <!-- Registration Form -->
            <form method="POST" action="/register.php" novalidate>
                <?= csrf_field() ?>

                <!-- Full Name -->
                <div class="mb-3">
                    <label class="form-label" for="name">Full Name</label>
                    <div class="form-icon-container">
                        <input 
                            class="form-control form-icon-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                            id="name" 
                            name="name"
                            type="text" 
                            placeholder="Enter your full name"
                            value="<?= htmlspecialchars($form_data['name']) ?>"
                            required
                        />
                        <span class="fas fa-user text-body fs-9 form-icon"></span>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="form-icon-container">
                        <input 
                            class="form-control form-icon-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                            id="email" 
                            name="email"
                            type="email" 
                            placeholder="name@example.com"
                            value="<?= htmlspecialchars($form_data['email']) ?>"
                            required
                        />
                        <span class="fas fa-envelope text-body fs-9 form-icon"></span>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="form-icon-container" data-password="data-password">
                        <input 
                            class="form-control form-icon-input pe-6 <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                            id="password" 
                            name="password"
                            type="password" 
                            placeholder="Create password"
                            data-password-input="data-password-input"
                            required
                        />
                        <span class="fas fa-key text-body fs-9 form-icon"></span>
                        <button 
                            class="btn px-3 py-0 h-100 position-absolute top-0 end-0 fs-7 text-body-tertiary" 
                            type="button"
                            data-password-toggle="data-password-toggle"
                        >
                            <span class="uil uil-eye show"></span>
                            <span class="uil uil-eye-slash hide"></span>
                        </button>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">
                        <small class="text-body-tertiary">
                            Password must be at least 8 characters with uppercase, lowercase, and number
                        </small>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div class="form-icon-container" data-password="data-password">
                        <input 
                            class="form-control form-icon-input pe-6 <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                            id="confirm_password" 
                            name="confirm_password"
                            type="password" 
                            placeholder="Confirm password"
                            data-password-input="data-password-input"
                            required
                        />
                        <span class="fas fa-lock text-body fs-9 form-icon"></span>
                        <button 
                            class="btn px-3 py-0 h-100 position-absolute top-0 end-0 fs-7 text-body-tertiary" 
                            type="button"
                            data-password-toggle="data-password-toggle"
                        >
                            <span class="uil uil-eye show"></span>
                            <span class="uil uil-eye-slash hide"></span>
                        </button>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Terms Acceptance -->
                <div class="row flex-between-center mb-7">
                    <div class="col-auto">
                        <div class="form-check mb-0">
                            <input class="form-check-input" id="terms" type="checkbox" required />
                            <label class="form-check-label mb-0" for="terms">
                                I agree to the 
                                <a href="#" class="text-decoration-none">Terms of Service</a>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Display general errors -->
                <?php if (isset($errors['csrf'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($errors['csrf']) ?>
                    </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <button class="btn btn-primary w-100 mb-3" type="submit" id="registerBtn">
                    <i class="fas fa-user-plus me-2"></i>
                    Create Account
                </button>

                <!-- Login Link -->
                <div class="text-center">
                    <span class="fs-9">Already have an account? </span>
                    <a class="fs-9 fw-bold text-decoration-none" href="/login.php">
                        Sign in here
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    // Real-time password matching
    confirmPasswordInput.addEventListener('input', function() {
        if (passwordInput.value !== this.value && this.value.length > 0) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else if (this.value.length > 0) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
    
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const termsCheck = document.getElementById('terms');
        if (!termsCheck.checked) {
            e.preventDefault();
            alert('Please accept the Terms of Service to continue.');
            return false;
        }
        
        // Show loading state
        showLoading('registerBtn', 'Creating Account...');
    });
    
    // Password toggle functionality
    document.querySelectorAll('[data-password-toggle]').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const container = this.closest('[data-password]');
            const input = container.querySelector('[data-password-input]');
            const showIcon = this.querySelector('.show');
            const hideIcon = this.querySelector('.hide');
            
            if (input.type === 'password') {
                input.type = 'text';
                showIcon.style.display = 'none';
                hideIcon.style.display = 'inline';
            } else {
                input.type = 'password';
                showIcon.style.display = 'inline';
                hideIcon.style.display = 'none';
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>