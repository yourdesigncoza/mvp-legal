<?php
// Database Seed Script
// Appeal Prospect MVP - Initial Data Setup

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/settings.php';

// Security check - require SEED_KEY parameter
$required_seed_key = 'appeal_prospect_setup_2025';
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $required_seed_key) {
    http_response_code(403);
    die('<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .error { background: white; padding: 30px; border-radius: 10px; display: inline-block; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error h1 { color: #dc3545; margin: 0 0 20px 0; }
        .error p { color: #6c757d; margin: 0; }
    </style>
</head>
<body>
    <div class="error">
        <h1>🔒 Access Denied</h1>
        <p>Invalid or missing seed key. Contact administrator for setup.</p>
        <p><small>Usage: <code>seed.php?key=YOUR_SEED_KEY</code></small></p>
    </div>
</body>
</html>');
}

// Start session
start_session();

$messages = [];
$errors = [];

try {
    // Check if database connection works
    $pdo = Database::getConnection();
    $messages[] = "✅ Database connection successful";
    
    // Check if users table exists and is empty
    $userCount = db_query_single("SELECT COUNT(*) as count FROM users");
    if ($userCount && $userCount['count'] > 0) {
        $errors[] = "⚠️  Users table already contains data. Skipping user creation.";
        $skip_users = true;
    } else {
        $skip_users = false;
        $messages[] = "✅ Users table is ready for seeding";
    }
    
    if (!$skip_users) {
        // Create Admin User
        $admin_created = register_user(
            'admin@example.com',
            'admin123',
            'System Administrator'
        );
        
        if ($admin_created) {
            // Make user admin
            db_execute("UPDATE users SET is_admin = 1 WHERE email = 'admin@example.com'");
            $messages[] = "✅ Admin user created: admin@example.com / admin123";
        } else {
            $errors[] = "❌ Failed to create admin user";
        }
        
        // Create Demo User
        $demo_created = register_user(
            'demo@example.com',
            'demo123',
            'Demo User'
        );
        
        if ($demo_created) {
            $messages[] = "✅ Demo user created: demo@example.com / demo123";
        } else {
            $errors[] = "❌ Failed to create demo user";
        }
    }
    
    // Set up initial settings
    $settings_created = 0;
    
    // Default API key placeholders
    if (set_setting('openai_api_key', null, true, 'OpenAI API key for GPT-4o-mini analysis')) {
        $settings_created++;
    }
    
    if (set_setting('perplexity_api_key', null, true, 'Perplexity API key for web research (optional)')) {
        $settings_created++;
    }
    
    // App configuration
    if (set_setting('app_name', 'Appeal Prospect MVP', false, 'Application display name')) {
        $settings_created++;
    }
    
    if (set_setting('app_version', '1.0.0', false, 'Current application version')) {
        $settings_created++;
    }
    
    if (set_setting('max_file_size', '10485760', false, 'Maximum upload file size in bytes (10MB)')) {
        $settings_created++;
    }
    
    if (set_setting('allowed_mime_types', 'application/pdf,text/plain', false, 'Comma-separated list of allowed MIME types')) {
        $settings_created++;
    }
    
    if (set_setting('maintenance_mode', '0', false, 'Enable maintenance mode (1=enabled, 0=disabled)')) {
        $settings_created++;
    }
    
    $messages[] = "✅ Created {$settings_created} application settings";
    
    // Test settings retrieval
    $app_name = get_setting('app_name');
    if ($app_name === 'Appeal Prospect MVP') {
        $messages[] = "✅ Settings retrieval test passed";
    } else {
        $errors[] = "❌ Settings retrieval test failed";
    }
    
    $success = count($errors) === 0;
    
} catch (Exception $e) {
    $errors[] = "❌ Database error: " . $e->getMessage();
    $success = false;
}

$page_title = 'Database Setup - Appeal Prospect MVP';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="/assets/css/phoenix-bootstrap.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                
                <!-- Header -->
                <div class="text-center mb-5">
                    <div class="d-inline-flex align-items-center fw-bolder fs-2 text-primary mb-3">
                        <i class="fas fa-gavel me-2"></i>
                        Appeal Prospect
                    </div>
                    <h1 class="h3 text-body-emphasis mb-2">Database Setup Complete</h1>
                    <p class="text-body-tertiary">Initial data has been configured</p>
                </div>

                <!-- Setup Results -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header <?= $success ? 'bg-success' : 'bg-danger' ?> text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                            Setup <?= $success ? 'Successful' : 'Failed' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Success Messages -->
                        <?php if (!empty($messages)): ?>
                            <div class="mb-3">
                                <h6 class="text-success mb-2">
                                    <i class="fas fa-check me-2"></i>
                                    Completed Steps
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($messages as $message): ?>
                                        <li class="mb-1">
                                            <code class="text-success"><?= htmlspecialchars($message) ?></code>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="mb-3">
                                <h6 class="text-danger mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Issues Found
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li class="mb-1">
                                            <code class="text-danger"><?= htmlspecialchars($error) ?></code>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Accounts -->
                <?php if (!$skip_users): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>
                            Created User Accounts
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="text-danger mb-2">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        Administrator
                                    </h6>
                                    <p class="mb-1"><strong>Email:</strong> <code>admin@example.com</code></p>
                                    <p class="mb-0"><strong>Password:</strong> <code>admin123</code></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="text-info mb-2">
                                        <i class="fas fa-user me-2"></i>
                                        Demo User
                                    </h6>
                                    <p class="mb-1"><strong>Email:</strong> <code>demo@example.com</code></p>
                                    <p class="mb-0"><strong>Password:</strong> <code>demo123</code></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Next Steps -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list-check me-2"></i>
                            Next Steps
                        </h5>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <li class="mb-2">
                                <strong>Configure API Keys:</strong> 
                                <a href="/admin.php" class="text-decoration-none">
                                    Login as admin and set your OpenAI/Perplexity API keys
                                </a>
                            </li>
                            <li class="mb-2">
                                <strong>Security:</strong> 
                                Delete this seed script file for production use
                            </li>
                            <li class="mb-2">
                                <strong>Test Application:</strong> 
                                Try uploading a sample legal document
                            </li>
                            <li class="mb-0">
                                <strong>Customize:</strong> 
                                Update app settings and user interface as needed
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-3 justify-content-center">
                    <a href="/index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        Go to Application
                    </a>
                    <a href="/login.php" class="btn btn-success">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Login Now
                    </a>
                    <?php if ($success): ?>
                    <button class="btn btn-danger" onclick="deleteScript()">
                        <i class="fas fa-trash me-2"></i>
                        Delete This Script
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Warning -->
                <div class="alert alert-warning border-0 mt-4" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Security Warning
                    </h6>
                    <p class="mb-0">
                        For security reasons, delete this seed script file after setup is complete. 
                        The script contains default passwords and should not remain accessible.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    function deleteScript() {
        if (confirm('Are you sure you want to delete this seed script? This action cannot be undone.')) {
            alert('Script deletion is not implemented in this demo. Please manually delete /public/seed.php');
        }
    }
    
    // Auto-redirect after 10 seconds if successful
    <?php if ($success): ?>
    let countdown = 10;
    const timer = setInterval(() => {
        countdown--;
        if (countdown <= 0) {
            clearInterval(timer);
            window.location.href = '/index.php';
        }
    }, 1000);
    <?php endif; ?>
    </script>
</body>
</html>