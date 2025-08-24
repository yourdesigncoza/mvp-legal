<?php
// Navigation Bar Template
// Appeal Prospect MVP - Phoenix UI Implementation

// Get current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid px-6">
        <!-- Brand -->
        <a class="navbar-brand fw-bolder fs-3 text-primary" href="/index.php">
            <i class="fas fa-gavel me-2"></i>
            Appeal Prospect
            <span class="badge badge-phoenix badge-phoenix-primary fs-10 ms-2">MVP</span>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (is_logged_in()): ?>
                    
                    <!-- Upload -->
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'upload' ? 'active' : '' ?>" href="/upload.php">
                            <i class="fas fa-upload me-1"></i>
                            Upload
                        </a>
                    </li>
                    
                    <!-- My Cases -->
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'my-cases' ? 'active' : '' ?>" href="/my-cases.php">
                            <i class="fas fa-folder me-1"></i>
                            My Cases
                        </a>
                    </li>
                    
                    <!-- Analysis -->
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'analyze' ? 'active' : '' ?>" href="/analyze.php">
                            <i class="fas fa-magic me-1"></i>
                            Analysis
                        </a>
                    </li>
                    
                <?php endif; ?>
            </ul>

            <!-- User Menu -->
            <ul class="navbar-nav">
                <?php if (is_logged_in()): ?>
                    
                    <!-- Admin Link -->
                    <?php if (current_is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'admin' ? 'active' : '' ?>" href="/admin.php">
                                <i class="fas fa-cog me-1"></i>
                                Admin
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="/my-cases.php">
                                    <i class="fas fa-folder me-2"></i>
                                    My Cases
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                <?php else: ?>
                    
                    <!-- Guest Links -->
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'login' ? 'active' : '' ?>" href="/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'register' ? 'active' : '' ?>" href="/register.php">
                            <i class="fas fa-user-plus me-1"></i>
                            Register
                        </a>
                    </li>
                    
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>