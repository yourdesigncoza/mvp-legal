<?php
// User Login Page
// Appeal Prospect MVP - Phoenix UI Implementation

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';

// Start session
start_session();

// Redirect if already logged in
if (is_logged_in()) {
    $r = app_url('index.php');
    redirect($r);
    exit;
}

$errors = [];
$form_data = [
    'email' => '',
    'remember' => false
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf()) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        // Get and sanitize form data
        $form_data['email'] = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $form_data['remember'] = isset($_POST['remember']);
        
        // Validation
        if (empty($form_data['email'])) {
            $errors['email'] = 'Email address is required';
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }
        
        // If no validation errors, attempt login
        if (empty($errors)) {
            if (login_user($form_data['email'], $password)) {
                // Successful login
                $r = app_url('index.php');
                $redirect_to = $_SESSION['redirect_after_login'] ?? $r;
                unset($_SESSION['redirect_after_login']);
                
                set_flash_message('Welcome back! You have been logged in successfully.', 'success');
                header('Location: ' . $redirect_to);
                exit;
            } else {
                $errors['general'] = 'Invalid email address or password. Please try again.';
            }
        }
    }
}

$page_title = 'Login - Appeal Prospect MVP';
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<div class="container-fluid">
    <div class="row flex-center min-vh-100 py-5">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4 col-xxl-3">
            
            <!-- Logo/Brand -->
            <div class="text-center mb-7">
                <a class="d-flex flex-center text-decoration-none mb-4" href="<?= app_url('index.php') ?>">
                    <div class="d-flex align-items-center fw-bolder fs-2 d-inline-block text-primary">
                        <i class="fas fa-gavel me-2"></i>
                        Appeal Prospect
                    </div>
                </a>
                <h3 class="text-body-highlight">Sign In</h3>
                <p class="text-body-tertiary">Access your legal analysis dashboard</p>
            </div>

            <!-- Demo Users Info -->
            <div class="alert alert-subtle-info border-0 mb-4" role="alert">
                <h6 class="alert-heading mb-2">
                    <i class="fas fa-info-circle me-2"></i>
                    Demo Accounts
                </h6>
                <small>
                    <strong>Admin:</strong> admin@example.com / admin123<br>
                    <strong>User:</strong> demo@example.com / demo123
                </small>
            </div>

            <!-- Login Form -->
            <form method="POST" action="<?= app_url('login.php') ?>" novalidate>
                <?= csrf_field() ?>

                <!-- Display general errors -->
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-subtle-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

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
                            autofocus
                        />
                        <span class="fas fa-user text-body fs-9 form-icon"></span>
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
                            placeholder="Password"
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
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="row flex-between-center mb-7">
                    <div class="col-auto">
                        <div class="form-check mb-0">
                            <input 
                                class="form-check-input" 
                                id="remember" 
                                name="remember"
                                type="checkbox" 
                                <?= $form_data['remember'] ? 'checked' : '' ?>
                            />
                            <label class="form-check-label mb-0" for="remember">Remember me</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <a class="fs-9 fw-semibold text-decoration-none" href="#" onclick="alert('Demo feature - password reset not implemented')">
                            Forgot Password?
                        </a>
                    </div>
                </div>

                <!-- Submit Button -->
                <button class="btn btn-primary w-100 mb-3" type="submit" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Sign In
                </button>

                <!-- Register Link -->
                <div class="text-center">
                    <span class="fs-9">Don't have an account? </span>
                    <a class="fs-9 fw-bold text-decoration-none" href="<?= app_url('register.php') ?>">
                        Create one here
                    </a>
                </div>
            </form>

            <!-- Quick Login Buttons -->
            <div class="mt-4">
                <hr class="bg-body-secondary">
                <div class="text-center mb-3">
                    <small class="text-body-tertiary">Quick Demo Login</small>
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-subtle-primary btn-sm" onclick="quickLogin('admin@example.com', 'admin123')">
                        <i class="fas fa-user-shield me-1"></i>
                        Admin
                    </button>
                    <button class="btn btn-subtle-secondary btn-sm" onclick="quickLogin('demo@example.com', 'demo123')">
                        <i class="fas fa-user me-1"></i>
                        Demo User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation and loading state
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        showLoading('loginBtn', 'Signing In...');
    });
    
    // Password toggle functionality
    const passwordToggle = document.querySelector('[data-password-toggle]');
    if (passwordToggle) {
        passwordToggle.addEventListener('click', function() {
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
    }
});

// Quick login function for demo
function quickLogin(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    
    // Add visual feedback
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    emailInput.classList.add('is-valid');
    passwordInput.classList.add('is-valid');
    
    // Optional: Auto-submit after a brief delay
    setTimeout(() => {
        document.querySelector('form').submit();
    }, 500);
}

// Handle Enter key on quick login buttons
document.querySelectorAll('.btn-outline-primary, .btn-outline-secondary').forEach(button => {
    button.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.click();
        }
    });
});
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>