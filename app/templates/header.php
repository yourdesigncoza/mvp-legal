<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Appeal Prospect MVP</title>
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset_url('assets/img/favicons/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset_url('assets/img/favicons/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset_url('assets/img/favicons/favicon-16x16.png') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= asset_url('assets/img/favicons/favicon.ico') ?>">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Phoenix Bootstrap CSS -->
    <link href="<?= asset_url('assets/css/phoenix-bootstrap.css') ?>" type="text/css" rel="stylesheet">
    
    <!-- DropzoneJS CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" type="text/css" />
    
    <!-- Phoenix Bootstrap CSS -->
    <link href="<?= asset_url('assets/css/app.css') ?>" type="text/css" rel="stylesheet">

</head>

<body>
    <!-- Global Disclaimer -->
    <div class="alert alert-subtle-warning alert-dismissible fade show border-0 rounded-0 mb-0 text-center p-1 fs-9" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Demo Only — Not Legal Advice:</strong> This is a demonstration application for educational purposes only.
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand fw-bolder fs-6 text-primary" href="<?= app_url('index.php') ?>">
                <i class="fas fa-gavel me-2"></i>
                Appeal Prospect
                <span class="badge badge-phoenix badge-phoenix-primary fs-10 ms-2">MVP</span>
            </a>

            <!-- Mobile toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto fs-9">
                    <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                        <!-- Logged in navigation -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= app_url('upload.php') ?>">
                                <i class="fas fa-upload me-1"></i>
                                Upload Judgment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= app_url('my-cases.php') ?>">
                                <i class="fas fa-folder me-1"></i>
                                My Cases
                            </a>
                        </li>
                        
                        <?php if (function_exists('current_is_admin') && current_is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= app_url('admin.php') ?>">
                                <i class="fas fa-cog me-1"></i>
                                Admin
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- User dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User' ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= app_url('logout.php') ?>">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest navigation -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= app_url('login.php') ?>">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-phoenix-primary ms-2" href="<?= app_url('register.php') ?>">
                                <i class="fas fa-user-plus me-1"></i>
                                Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main" id="top">
        <div class="container px-0">
            <?php
            // Display flash messages
            if (isset($_SESSION['flash_message'])): 
                $flash = $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                $alert_class = 'alert-info';
                switch($flash['type']) {
                    case 'success': $alert_class = 'alert-success'; break;
                    case 'error': $alert_class = 'alert-danger'; break;
                    case 'warning': $alert_class = 'alert-warning'; break;
                }
            ?>
            <div class="alert <?= $alert_class ?> alert-dismissible fade show mt-3" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>