<?php
// Admin Dashboard
// Appeal Prospect MVP - Administrative Interface

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/settings.php';
require_once __DIR__ . '/../app/save_fetch.php';
require_once __DIR__ . '/../app/gpt_handler.php';
require_once __DIR__ . '/../app/perplexity.php';

// Start session and require admin
start_session();
require_login();
require_admin();

$errors = [];
$success_message = '';
$tab = $_GET['tab'] ?? 'dashboard';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_api_keys':
                handleApiKeyUpdate($errors, $success_message);
                break;
                
            case 'test_openai':
                handleOpenAITest($errors, $success_message);
                break;
                
            case 'test_perplexity':
                handlePerplexityTest($errors, $success_message);
                break;
                
            case 'toggle_user_admin':
                handleUserAdminToggle($errors, $success_message);
                break;
                
            case 'delete_user_case':
                handleUserCaseDelete($errors, $success_message);
                break;
                
            case 'update_settings':
                handleSettingsUpdate($errors, $success_message);
                break;
        }
    }
}

// Get dashboard data
$system_stats = get_case_statistics(0); // All users
$recent_activity = get_recent_activity(0, 7); // All users, last 7 days
$all_users = getAllUsers();
$all_cases = get_all_cases(20, 0); // Latest 20 cases

// Get current API key status
$openai_key_status = !empty(get_setting('openai_api_key'));
$perplexity_key_status = !empty(get_setting('perplexity_api_key'));

$page_title = 'Admin Dashboard - Appeal Prospect MVP';

/**
 * Handle API key updates
 */
function handleApiKeyUpdate(array &$errors, string &$success_message): void
{
    $openai_key = trim($_POST['openai_api_key'] ?? '');
    $perplexity_key = trim($_POST['perplexity_api_key'] ?? '');
    
    $updated = 0;
    
    // Validate and update OpenAI key
    if (!empty($openai_key)) {
        $key_validation = validate_api_key($openai_key, 'openai');
        if (!$key_validation['valid']) {
            $errors[] = $key_validation['error'];
        } else {
            if (set_setting('openai_api_key', $key_validation['value'], true)) {
                $updated++;
            } else {
                $errors[] = 'Failed to save OpenAI API key';
            }
        }
    }
    
    // Validate and update Perplexity key
    if (!empty($perplexity_key)) {
        $key_validation = validate_api_key($perplexity_key, 'perplexity');
        if (!$key_validation['valid']) {
            $errors[] = $key_validation['error'];
        } else {
            if (set_setting('perplexity_api_key', $key_validation['value'], true)) {
                $updated++;
            } else {
                $errors[] = 'Failed to save Perplexity API key';
            }
        }
    }
    
    if ($updated > 0 && empty($errors)) {
        $success_message = "Updated {$updated} API key(s) successfully.";
    }
}

/**
 * Handle OpenAI connection test
 */
function handleOpenAITest(array &$errors, string &$success_message): void
{
    $result = test_openai_connection();
    
    if ($result['success']) {
        $success_message = 'OpenAI connection test successful!';
    } else {
        $errors[] = 'OpenAI connection test failed: ' . $result['error'];
    }
}

/**
 * Handle Perplexity connection test
 */
function handlePerplexityTest(array &$errors, string &$success_message): void
{
    $result = test_perplexity_connection();
    
    if ($result['success']) {
        $success_message = 'Perplexity connection test successful!';
    } else {
        $errors[] = 'Perplexity connection test failed: ' . $result['error'];
    }
}

/**
 * Handle user admin status toggle
 */
function handleUserAdminToggle(array &$errors, string &$success_message): void
{
    $user_id = (int)($_POST['user_id'] ?? 0);
    $make_admin = isset($_POST['make_admin']);
    
    if ($user_id <= 0) {
        $errors[] = 'Invalid user ID';
        return;
    }
    
    // Prevent self-demotion
    if ($user_id === current_user_id() && !$make_admin) {
        $errors[] = 'You cannot remove your own admin privileges';
        return;
    }
    
    $sql = "UPDATE users SET is_admin = ? WHERE id = ?";
    if (db_execute($sql, [$make_admin ? 1 : 0, $user_id])) {
        $action = $make_admin ? 'granted' : 'removed';
        $success_message = "Admin privileges {$action} successfully.";
    } else {
        $errors[] = 'Failed to update user privileges';
    }
}

/**
 * Handle user case deletion
 */
function handleUserCaseDelete(array &$errors, string &$success_message): void
{
    $case_id = (int)($_POST['case_id'] ?? 0);
    
    if ($case_id <= 0) {
        $errors[] = 'Invalid case ID';
        return;
    }
    
    if (delete_case($case_id, 0, true)) { // Admin delete
        $success_message = 'Case deleted successfully.';
    } else {
        $errors[] = 'Failed to delete case';
    }
}

/**
 * Handle application settings update
 */
function handleSettingsUpdate(array &$errors, string &$success_message): void
{
    $app_name = trim($_POST['app_name'] ?? '');
    $max_file_size = (int)($_POST['max_file_size'] ?? 0);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    
    $updated = 0;
    
    if (!empty($app_name)) {
        if (set_setting('app_name', $app_name)) {
            $updated++;
        }
    }
    
    if ($max_file_size > 0) {
        if (set_setting('max_file_size', (string)$max_file_size)) {
            $updated++;
        }
    }
    
    if (set_setting('maintenance_mode', $maintenance_mode)) {
        $updated++;
    }
    
    if ($updated > 0) {
        $success_message = 'Application settings updated successfully.';
    }
}

/**
 * Get all users for admin management
 */
function getAllUsers(): array
{
    try {
        $sql = "SELECT 
                    id, name, email, is_admin, created_at, last_login_at,
                    (SELECT COUNT(*) FROM cases WHERE user_id = users.id) as case_count
                FROM users 
                ORDER BY created_at DESC";
        
        return db_query($sql);
    } catch (Exception $e) {
        error_log("Failed to get all users: " . $e->getMessage());
        return [];
    }
}
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<div class="container px-0">
    
    <div class="content">
        <div class="container px-6 py-4">
            
            <!-- Page Header -->
            <div class="row align-items-center justify-content-between py-2 pe-0 mb-4">
                <div class="col-auto">
                    <h1 class="text-body-emphasis mb-0 fs-6">
                        <i class="fas fa-cog me-2 text-danger"></i>
                        Admin Dashboard
                    </h1>
                    <p class="text-body-tertiary mb-0">
                        System administration and configuration
                    </p>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <a href="<?= app_url('my-cases.php') ?>" class="btn btn-subtle-secondary">
                            <i class="fas fa-folder me-2"></i>
                            My Cases
                        </a>
                        <a href="<?= app_url('upload.php') ?>" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>
                            New Analysis
                        </a>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-subtle-success border-0" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-subtle-danger border-0" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>

            <!-- Admin Tabs -->
            <div class="card shadow-sm">
                <div class="card-header border-bottom-0">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'dashboard' ? 'active' : '' ?>" href="?tab=dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'api-keys' ? 'active' : '' ?>" href="?tab=api-keys">
                                <i class="fas fa-key me-2"></i>
                                API Keys
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'users' ? 'active' : '' ?>" href="?tab=users">
                                <i class="fas fa-users me-2"></i>
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'cases' ? 'active' : '' ?>" href="?tab=cases">
                                <i class="fas fa-folder-open me-2"></i>
                                All Cases
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'settings' ? 'active' : '' ?>" href="?tab=settings">
                                <i class="fas fa-sliders-h me-2"></i>
                                Settings
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content">
                        
                        <!-- Dashboard Tab -->
                        <div class="tab-pane fade <?= $tab === 'dashboard' ? 'show active' : '' ?>">
                            
                            <!-- System Statistics -->
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card h-100 border-primary border-opacity-25">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                                        <i class="fas fa-users text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0">Total Users</h6>
                                                    <h4 class="text-primary mb-0"><?= count($all_users) ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card h-100 border-success border-opacity-25">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                                        <i class="fas fa-folder text-success"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0">Total Cases</h6>
                                                    <h4 class="text-success mb-0"><?= number_format($system_stats['total_cases']) ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card h-100 border-info border-opacity-25">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                                        <i class="fas fa-check-circle text-info"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0">Analyzed</h6>
                                                    <h4 class="text-info mb-0"><?= number_format($system_stats['completed_cases']) ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card h-100 border-warning border-opacity-25">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                                        <i class="fas fa-coins text-warning"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0">Total Tokens</h6>
                                                    <h4 class="text-warning mb-0"><?= number_format($system_stats['total_tokens']) ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- API Status -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-plug me-2"></i>
                                                API Status
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-brain me-2"></i>
                                                    <span>OpenAI GPT-4o-mini</span>
                                                </div>
                                                <span class="badge badge-phoenix badge-phoenix-<?= $openai_key_status ? 'success' : 'danger' ?>">
                                                    <?= $openai_key_status ? 'Connected' : 'Not Configured' ?>
                                                </span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-search me-2"></i>
                                                    <span>Perplexity Research</span>
                                                </div>
                                                <span class="badge badge-phoenix badge-phoenix-<?= $perplexity_key_status ? 'success' : 'warning' ?>">
                                                    <?= $perplexity_key_status ? 'Connected' : 'Optional' ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-chart-pie me-2"></i>
                                                Case Status Breakdown
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <small class="text-body-secondary">Completed</small>
                                                <small class="fw-semibold"><?= number_format($system_stats['completed_cases']) ?></small>
                                            </div>
                                            <div class="progress mb-3" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: <?= $system_stats['total_cases'] > 0 ? round(($system_stats['completed_cases'] / $system_stats['total_cases']) * 100) : 0 ?>%"></div>
                                            </div>
                                            
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <small class="text-body-secondary">Processing</small>
                                                <small class="fw-semibold"><?= number_format($system_stats['processing_cases']) ?></small>
                                            </div>
                                            <div class="progress mb-3" style="height: 8px;">
                                                <div class="progress-bar bg-warning" style="width: <?= $system_stats['total_cases'] > 0 ? round(($system_stats['processing_cases'] / $system_stats['total_cases']) * 100) : 0 ?>%"></div>
                                            </div>
                                            
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <small class="text-body-secondary">Failed</small>
                                                <small class="fw-semibold"><?= number_format($system_stats['failed_cases']) ?></small>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-danger" style="width: <?= $system_stats['total_cases'] > 0 ? round(($system_stats['failed_cases'] / $system_stats['total_cases']) * 100) : 0 ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        Recent Activity (Last 7 Days)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_activity)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-info-circle text-body-tertiary me-2"></i>
                                            <span class="text-body-secondary">No recent activity</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="timeline">
                                            <?php foreach ($recent_activity as $activity): ?>
                                                <div class="d-flex mb-3">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;">
                                                            <i class="fas fa-file-alt text-primary fs-8"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <?= htmlspecialchars($activity['case_name']) ?>
                                                        </h6>
                                                        <p class="mb-1 small text-body-secondary">
                                                            by <?= htmlspecialchars($activity['user_name']) ?>
                                                        </p>
                                                        <small class="text-body-tertiary">
                                                            <?= date('M j, Y \a\t g:i A', strtotime($activity['created_at'])) ?>
                                                        </small>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <?php 
                                                        $status_config = [
                                                            'uploaded' => ['class' => 'secondary', 'text' => 'Uploaded'],
                                                            'analyzing' => ['class' => 'warning', 'text' => 'Analyzing'],
                                                            'analyzed' => ['class' => 'success', 'text' => 'Complete'],
                                                            'failed' => ['class' => 'danger', 'text' => 'Failed']
                                                        ];
                                                        $status = $status_config[$activity['status']] ?? ['class' => 'secondary', 'text' => 'Unknown'];
                                                        ?>
                                                        <span class="badge badge-phoenix badge-phoenix-<?= $status['class'] ?>">
                                                            <?= $status['text'] ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- API Keys Tab -->
                        <div class="tab-pane fade <?= $tab === 'api-keys' ? 'show active' : '' ?>">
                            <div class="row">
                                <div class="col-lg-8">
                                    <form method="POST" action="?tab=api-keys">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_api_keys">
                                        
                                        <!-- OpenAI API Key -->
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">
                                                    <i class="fas fa-brain me-2"></i>
                                                    OpenAI API Configuration
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label" for="openai_api_key">
                                                        OpenAI API Key <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-key"></i>
                                                        </span>
                                                        <input 
                                                            type="password" 
                                                            class="form-control" 
                                                            id="openai_api_key" 
                                                            name="openai_api_key"
                                                            placeholder="sk-..."
                                                            value="<?= $openai_key_status ? '••••••••••••••••••••' : '' ?>"
                                                        />
                                                        <button class="btn btn-subtle-secondary" type="button" onclick="togglePassword('openai_api_key')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-text">
                                                        Required for AI analysis. Get your key from 
                                                        <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>.
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>
                                                        Save Key
                                                    </button>
                                                    
                                                    <?php if ($openai_key_status): ?>
                                                        <button type="submit" name="action" value="test_openai" class="btn btn-subtle-success">
                                                            <i class="fas fa-plug me-2"></i>
                                                            Test Connection
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Perplexity API Key -->
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">
                                                    <i class="fas fa-search me-2"></i>
                                                    Perplexity API Configuration
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label" for="perplexity_api_key">
                                                        Perplexity API Key 
                                                        <span class="badge badge-phoenix badge-phoenix-warning ms-1">Optional</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-key"></i>
                                                        </span>
                                                        <input 
                                                            type="password" 
                                                            class="form-control" 
                                                            id="perplexity_api_key" 
                                                            name="perplexity_api_key"
                                                            placeholder="pplx-..."
                                                            value="<?= $perplexity_key_status ? '••••••••••••••••••••' : '' ?>"
                                                        />
                                                        <button class="btn btn-subtle-secondary" type="button" onclick="togglePassword('perplexity_api_key')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-text">
                                                        Optional for web research enhancement. Get your key from 
                                                        <a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity AI</a>.
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>
                                                        Save Key
                                                    </button>
                                                    
                                                    <?php if ($perplexity_key_status): ?>
                                                        <button type="submit" name="action" value="test_perplexity" class="btn btn-subtle-success">
                                                            <i class="fas fa-plug me-2"></i>
                                                            Test Connection
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <div class="col-lg-4">
                                    <!-- API Status -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-info-circle me-2"></i>
                                                API Status
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="fw-semibold">OpenAI</span>
                                                    <span class="badge badge-phoenix badge-phoenix-<?= $openai_key_status ? 'success' : 'danger' ?>">
                                                        <?= $openai_key_status ? 'Configured' : 'Missing' ?>
                                                    </span>
                                                </div>
                                                <small class="text-body-secondary">Required for AI analysis</small>
                                            </div>
                                            
                                            <div class="mb-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="fw-semibold">Perplexity</span>
                                                    <span class="badge badge-phoenix badge-phoenix-<?= $perplexity_key_status ? 'success' : 'secondary' ?>">
                                                        <?= $perplexity_key_status ? 'Configured' : 'Not Set' ?>
                                                    </span>
                                                </div>
                                                <small class="text-body-secondary">Optional web research</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Security Notice -->
                                    <div class="alert alert-info border-0">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-shield-alt me-2"></i>
                                            Security Notice
                                        </h6>
                                        <p class="mb-0 small">
                                            API keys are encrypted using AES-256-GCM before being stored in the database. 
                                            They are never stored in plain text or logged.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Tab -->
                        <div class="tab-pane fade <?= $tab === 'users' ? 'show active' : '' ?>">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-users me-2"></i>
                                        User Management
                                        <span class="badge badge-phoenix badge-phoenix-secondary ms-2">
                                            <?= count($all_users) ?>
                                        </span>
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="bg-body">
                                                <tr>
                                                    <th>User</th>
                                                    <th>Role</th>
                                                    <th>Cases</th>
                                                    <th>Joined</th>
                                                    <th>Last Login</th>
                                                    <th width="120">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($all_users as $user): ?>
                                                    <tr>
                                                        <td>
                                                            <div>
                                                                <h6 class="mb-1"><?= htmlspecialchars($user['name']) ?></h6>
                                                                <small class="text-body-secondary"><?= htmlspecialchars($user['email']) ?></small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ($user['is_admin']): ?>
                                                                <span class="badge badge-phoenix badge-phoenix-danger">
                                                                    <i class="fas fa-shield-alt me-1"></i>
                                                                    Admin
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge badge-phoenix badge-phoenix-secondary">
                                                                    <i class="fas fa-user me-1"></i>
                                                                    User
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-phoenix badge-phoenix-info">
                                                                <?= number_format($user['case_count']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-body-secondary">
                                                                <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if ($user['last_login_at']): ?>
                                                                <small class="text-body-secondary">
                                                                    <?= date('M j, Y', strtotime($user['last_login_at'])) ?>
                                                                </small>
                                                            <?php else: ?>
                                                                <small class="text-body-tertiary">Never</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($user['id'] !== current_user_id()): ?>
                                                                <form method="POST" action="?tab=users" class="d-inline">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="action" value="toggle_user_admin">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    
                                                                    <?php if ($user['is_admin']): ?>
                                                                        <button type="submit" class="btn btn-sm btn-subtle-warning" 
                                                                                onclick="return confirm('Remove admin privileges from <?= htmlspecialchars($user['name'], ENT_QUOTES) ?>?')">
                                                                            <i class="fas fa-user me-1"></i>
                                                                            Remove Admin
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <input type="hidden" name="make_admin" value="1">
                                                                        <button type="submit" class="btn btn-sm btn-subtle-danger"
                                                                                onclick="return confirm('Grant admin privileges to <?= htmlspecialchars($user['name'], ENT_QUOTES) ?>?')">
                                                                            <i class="fas fa-shield-alt me-1"></i>
                                                                            Make Admin
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </form>
                                                            <?php else: ?>
                                                                <small class="text-body-tertiary">Current User</small>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- All Cases Tab -->
                        <div class="tab-pane fade <?= $tab === 'cases' ? 'show active' : '' ?>">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-folder-open me-2"></i>
                                        All Cases
                                        <span class="badge badge-phoenix badge-phoenix-secondary ms-2">
                                            <?= count($all_cases) ?>
                                        </span>
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="bg-body">
                                                <tr>
                                                    <th>Case Name</th>
                                                    <th>User</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Tokens</th>
                                                    <th width="120">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($all_cases as $case): ?>
                                                    <?php $case = format_case_for_display($case); ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-2">
                                                                    <?php if ($case['original_filename']): ?>
                                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                                    <?php else: ?>
                                                                        <i class="fas fa-file-text text-info"></i>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-1">
                                                                        <?php if ($case['has_analysis']): ?>
                                                                            <a href="<?= app_url('results.php') ?>?case_id=<?= $case['id'] ?>" class="text-decoration-none">
                                                                                <?= htmlspecialchars($case['case_name']) ?>
                                                                            </a>
                                                                        <?php else: ?>
                                                                            <?= htmlspecialchars($case['case_name']) ?>
                                                                        <?php endif; ?>
                                                                    </h6>
                                                                    <?php if ($case['original_filename']): ?>
                                                                        <small class="text-body-secondary">
                                                                            <?= htmlspecialchars($case['original_filename']) ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div>
                                                                <small class="fw-semibold"><?= htmlspecialchars($case['user_name']) ?></small><br>
                                                                <small class="text-body-secondary"><?= htmlspecialchars($case['user_email']) ?></small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-phoenix badge-phoenix-<?= $case['status_badge']['class'] ?>">
                                                                <?= htmlspecialchars($case['status_badge']['text']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-body-secondary">
                                                                <?= date('M j, Y g:i A', strtotime($case['created_at'])) ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if ($case['total_tokens'] > 0): ?>
                                                                <span class="badge badge-phoenix badge-phoenix-info">
                                                                    <?= number_format($case['total_tokens']) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <small class="text-body-tertiary">—</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-subtle-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <?php if ($case['has_analysis']): ?>
                                                                        <li>
                                                                            <a class="dropdown-item" href="<?= app_url('results.php') ?>?case_id=<?= $case['id'] ?>">
                                                                                <i class="fas fa-eye me-2"></i>
                                                                                View Results
                                                                            </a>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <li>
                                                                        <form method="POST" action="?tab=cases" class="d-inline">
                                                                            <?= csrf_field() ?>
                                                                            <input type="hidden" name="action" value="delete_user_case">
                                                                            <input type="hidden" name="case_id" value="<?= $case['id'] ?>">
                                                                            <button type="submit" class="dropdown-item text-danger" 
                                                                                    onclick="return confirm('Delete case: <?= htmlspecialchars($case['case_name'], ENT_QUOTES) ?>?')">
                                                                                <i class="fas fa-trash me-2"></i>
                                                                                Delete Case
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Tab -->
                        <div class="tab-pane fade <?= $tab === 'settings' ? 'show active' : '' ?>">
                            <div class="row">
                                <div class="col-lg-8">
                                    <form method="POST" action="?tab=settings">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_settings">
                                        
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">
                                                    <i class="fas fa-sliders-h me-2"></i>
                                                    Application Settings
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                
                                                <!-- App Name -->
                                                <div class="mb-3">
                                                    <label class="form-label" for="app_name">Application Name</label>
                                                    <input 
                                                        type="text" 
                                                        class="form-control" 
                                                        id="app_name" 
                                                        name="app_name"
                                                        value="<?= htmlspecialchars(get_setting('app_name', 'Appeal Prospect MVP')) ?>"
                                                    />
                                                </div>
                                                
                                                <!-- Max File Size -->
                                                <div class="mb-3">
                                                    <label class="form-label" for="max_file_size">Maximum File Size (bytes)</label>
                                                    <div class="input-group">
                                                        <input 
                                                            type="number" 
                                                            class="form-control" 
                                                            id="max_file_size" 
                                                            name="max_file_size"
                                                            value="<?= (int)get_setting('max_file_size', '10485760') ?>"
                                                            min="1048576"
                                                            max="104857600"
                                                        />
                                                        <span class="input-group-text">bytes</span>
                                                    </div>
                                                    <div class="form-text">
                                                        Current: <?= format_bytes((int)get_setting('max_file_size', '10485760')) ?> 
                                                        (Range: 1MB - 100MB)
                                                    </div>
                                                </div>
                                                
                                                <!-- Maintenance Mode -->
                                                <div class="mb-4">
                                                    <div class="form-check form-switch">
                                                        <input 
                                                            class="form-check-input" 
                                                            type="checkbox" 
                                                            id="maintenance_mode" 
                                                            name="maintenance_mode"
                                                            <?= get_setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>
                                                        />
                                                        <label class="form-check-label" for="maintenance_mode">
                                                            Enable Maintenance Mode
                                                        </label>
                                                    </div>
                                                    <div class="form-text">
                                                        When enabled, only administrators can access the application.
                                                    </div>
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>
                                                    Save Settings
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <div class="col-lg-4">
                                    <!-- System Info -->
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-info-circle me-2"></i>
                                                System Information
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <dl class="row mb-0 small">
                                                <dt class="col-6">App Version:</dt>
                                                <dd class="col-6"><?= htmlspecialchars(get_setting('app_version', '1.0.0')) ?></dd>
                                                
                                                <dt class="col-6">PHP Version:</dt>
                                                <dd class="col-6"><?= phpversion() ?></dd>
                                                
                                                <dt class="col-6">Database:</dt>
                                                <dd class="col-6">MySQL</dd>
                                                
                                                <dt class="col-6">Timezone:</dt>
                                                <dd class="col-6"><?= date_default_timezone_get() ?></dd>
                                                
                                                <dt class="col-6">Upload Limit:</dt>
                                                <dd class="col-6"><?= ini_get('upload_max_filesize') ?></dd>
                                                
                                                <dt class="col-6">Memory Limit:</dt>
                                                <dd class="col-6"><?= ini_get('memory_limit') ?></dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password toggle functionality
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Clear password fields on focus (for existing keys)
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        if (input.value.includes('•')) {
            input.addEventListener('focus', function() {
                if (this.value.includes('•')) {
                    this.value = '';
                }
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>