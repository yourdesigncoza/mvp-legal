<?php
// User Logout Handler
// Appeal Prospect MVP

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';

// Start session
start_session();

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: /login.php');
    exit;
}

// CSRF protection - check if this is a POST request or has valid token
$valid_logout = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST logout with CSRF token
    if (validate_csrf()) {
        $valid_logout = true;
    }
} elseif (isset($_GET['confirm']) && $_GET['confirm'] === '1') {
    // GET logout with confirmation parameter (for navigation links)
    $valid_logout = true;
}

if ($valid_logout) {
    // Get user name for goodbye message
    $user_name = $_SESSION['user_name'] ?? 'User';
    
    // Perform logout
    logout_user();
    
    // Start new session for flash message
    start_session();
    set_flash_message("Goodbye {$user_name}! You have been logged out successfully.", 'info');
    
    // Redirect to home page
    header('Location: /index.php');
    exit;
} else {
    // Show logout confirmation page
    $page_title = 'Logout - Appeal Prospect MVP';
    $user_name = $_SESSION['user_name'] ?? 'User';
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<div class="container-fluid">
    <div class="row flex-center min-vh-100 py-5">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4 col-xxl-3">
            
            <!-- Logo/Brand -->
            <div class="text-center mb-5">
                <a class="d-flex flex-center text-decoration-none mb-4" href="/index.php">
                    <div class="d-flex align-items-center fw-bolder fs-2 d-inline-block text-primary">
                        <i class="fas fa-gavel me-2"></i>
                        Appeal Prospect
                    </div>
                </a>
                <h3 class="text-body-highlight">Logout Confirmation</h3>
                <p class="text-body-tertiary">Are you sure you want to sign out?</p>
            </div>

            <!-- User Info Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 4rem; height: 4rem;">
                            <i class="fas fa-user text-primary fs-3"></i>
                        </div>
                    </div>
                    <h5 class="card-title mb-1"><?= htmlspecialchars($user_name) ?></h5>
                    <p class="card-text text-body-tertiary mb-0">
                        <?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>
                    </p>
                    <?php if (current_is_admin()): ?>
                        <span class="badge badge-phoenix badge-phoenix-primary mt-2">
                            <i class="fas fa-shield-alt me-1"></i>
                            Administrator
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Logout Form -->
            <form method="POST" action="/logout.php" class="mb-3">
                <?= csrf_field() ?>
                <button class="btn btn-danger w-100 mb-3" type="submit">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Yes, Sign Me Out
                </button>
            </form>

            <!-- Cancel Button -->
            <div class="text-center">
                <a href="javascript:history.back()" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-arrow-left me-2"></i>
                    Cancel, Stay Logged In
                </a>
            </div>

            <!-- Additional Options -->
            <div class="mt-4">
                <hr class="bg-body-secondary">
                <div class="text-center">
                    <small class="text-body-tertiary">Quick Actions</small>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <a href="/my-cases.php" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-folder me-1"></i>
                        My Cases
                    </a>
                    <a href="/upload.php" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-upload me-1"></i>
                        Upload
                    </a>
                    <?php if (current_is_admin()): ?>
                    <a href="/admin.php" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-cog me-1"></i>
                        Admin
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-focus the logout button
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.querySelector('button[type="submit"]');
    if (logoutBtn) {
        logoutBtn.focus();
    }
});

// Handle keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Enter or Space to confirm logout
    if ((e.key === 'Enter' || e.key === ' ') && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A') {
        e.preventDefault();
        document.querySelector('form').submit();
    }
    
    // Escape to cancel
    if (e.key === 'Escape') {
        history.back();
    }
});
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>

<?php
    exit;
}
?>