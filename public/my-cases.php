<?php
// My Cases Page
// Appeal Prospect MVP - Case History Management

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/save_fetch.php';

// Start session and require login
start_session();
require_login();

$user_id = current_user_id();
$cases = [];
$stats = [];
$errors = [];
$success_message = '';

// Handle pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Handle search and filters
$search_term = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $case_id = (int)($_POST['case_id'] ?? 0);
        
        switch ($action) {
            case 'delete':
                if (delete_case($case_id, $user_id)) {
                    $success_message = 'Case deleted successfully.';
                } else {
                    $errors[] = 'Failed to delete case. Please try again.';
                }
                break;
                
            case 'bulk_delete':
                $selected_cases = $_POST['selected_cases'] ?? [];
                $deleted_count = 0;
                
                foreach ($selected_cases as $selected_case_id) {
                    if (delete_case((int)$selected_case_id, $user_id)) {
                        $deleted_count++;
                    }
                }
                
                if ($deleted_count > 0) {
                    $success_message = "Deleted {$deleted_count} case(s) successfully.";
                } else {
                    $errors[] = 'No cases were deleted. Please try again.';
                }
                break;
        }
    }
}

// Get cases with search and filtering
if (!empty($search_term)) {
    $cases = search_cases($search_term, $user_id, $per_page);
    $total_cases = count($cases); // Simplified for search
} else {
    $cases = get_user_cases($user_id, $per_page, $offset, $status_filter);
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM cases WHERE user_id = ?";
    $count_params = [$user_id];
    
    if (!empty($status_filter)) {
        $count_sql .= " AND status = ?";
        $count_params[] = $status_filter;
    }
    
    $count_result = db_query_single($count_sql, $count_params);
    $total_cases = (int)($count_result['total'] ?? 0);
}

// Format cases for display
$cases = array_map('format_case_for_display', $cases);

// Get user statistics
$stats = get_case_statistics($user_id);

// Calculate pagination
$total_pages = ceil($total_cases / $per_page);

$page_title = 'My Cases - Appeal Prospect MVP';
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<div class="container px-0">
    
    <div class="content">
        <div class="container px-6 py-4">
            
            <!-- Page Header -->
            <div class="row align-items-center justify-content-between py-2 pe-0 mb-4">
                <div class="col-auto">
                    <h1 class="text-body-emphasis mb-0 fs-6">
                        <i class="fas fa-folder me-2 text-primary"></i>
                        My Cases
                    </h1>
                    <p class="text-body-tertiary mb-0">
                        Manage and view your legal analysis cases
                    </p>
                </div>
                <div class="col-auto">
                    <a href="<?= app_url('upload.php') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        New Analysis
                    </a>
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

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                        <i class="fas fa-folder text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Total Cases</h6>
                                    <h4 class="text-primary mb-0"><?= number_format($stats['total_cases']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Completed</h6>
                                    <h4 class="text-success mb-0"><?= number_format($stats['completed_cases']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                        <i class="fas fa-clock text-warning"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Pending</h6>
                                    <h4 class="text-warning mb-0"><?= number_format($stats['pending_cases'] + $stats['processing_cases']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                        <i class="fas fa-coins text-info"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Total Tokens</h6>
                                    <h4 class="text-info mb-0"><?= number_format($stats['total_tokens']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="<?= app_url('my-cases.php') ?>" class="row g-3 align-items-end">
                        
                        <!-- Search -->
                        <div class="col-md-4">
                            <label class="form-label" for="search">Search Cases</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="search" 
                                    name="search" 
                                    placeholder="Search by case name or content..."
                                    value="<?= htmlspecialchars($search_term) ?>"
                                />
                            </div>
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="uploaded" <?= $status_filter === 'uploaded' ? 'selected' : '' ?>>Pending</option>
                                <option value="analyzing" <?= $status_filter === 'analyzing' ? 'selected' : '' ?>>Analyzing</option>
                                <option value="analyzed" <?= $status_filter === 'analyzed' ? 'selected' : '' ?>>Complete</option>
                                <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="col-md-2">
                            <label class="form-label" for="sort">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                                <option value="analyzed_at" <?= $sort_by === 'analyzed_at' ? 'selected' : '' ?>>Date Analyzed</option>
                                <option value="case_name" <?= $sort_by === 'case_name' ? 'selected' : '' ?>>Case Name</option>
                                <option value="status" <?= $sort_by === 'status' ? 'selected' : '' ?>>Status</option>
                            </select>
                        </div>
                        
                        <!-- Sort Order -->
                        <div class="col-md-2">
                            <label class="form-label" for="order">Order</label>
                            <select class="form-select" id="order" name="order">
                                <option value="desc" <?= $sort_order === 'desc' ? 'selected' : '' ?>>Newest First</option>
                                <option value="asc" <?= $sort_order === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                            </select>
                        </div>
                        
                        <!-- Actions -->
                        <div class="col-md-2">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>
                                    Search
                                </button>
                                <a href="<?= app_url('my-cases.php') ?>" class="btn btn-subtle-secondary">
                                    <i class="fas fa-times me-1"></i>
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($cases)): ?>
                <!-- Empty State -->
                <div class="text-center py-5">
                    <?php if (!empty($search_term) || !empty($status_filter)): ?>
                        <!-- No Results Found -->
                        <i class="fas fa-search text-body-tertiary mb-3" style="font-size: 4rem;"></i>
                        <h4 class="text-body-secondary mb-2">No Cases Found</h4>
                        <p class="text-body-tertiary mb-4">
                            <?php if (!empty($search_term)): ?>
                                No cases match your search for "<?= htmlspecialchars($search_term) ?>".
                            <?php else: ?>
                                No cases found with the selected filters.
                            <?php endif; ?>
                        </p>
                        <a href="<?= app_url('my-cases.php') ?>" class="btn btn-subtle-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            View All Cases
                        </a>
                    <?php else: ?>
                        <!-- No Cases Yet -->
                        <i class="fas fa-folder-open text-body-tertiary mb-3" style="font-size: 4rem;"></i>
                        <h4 class="text-body-secondary mb-2">No Cases Yet</h4>
                        <p class="text-body-tertiary mb-4">
                            Upload your first legal judgment to get started with AI-powered analysis.
                        </p>
                        <a href="<?= app_url('upload.php') ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-upload me-2"></i>
                            Upload First Case
                        </a>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                
                <!-- Cases Table -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>
                                Cases 
                                <span class="badge badge-phoenix badge-phoenix-secondary ms-2">
                                    <?= number_format($total_cases) ?>
                                </span>
                            </h5>
                            
                            <!-- Bulk Actions -->
                            <div class="d-flex align-items-center">
                                <button class="btn btn-subtle-danger btn-sm me-2" onclick="showBulkDeleteModal()" id="bulkDeleteBtn" style="display: none;">
                                    <i class="fas fa-trash me-1"></i>
                                    Delete Selected
                                </button>
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" id="selectAll" />
                                    Select All
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-body">
                                    <tr>
                                        <th width="30">
                                            <input class="form-check-input" type="checkbox" id="headerSelectAll" />
                                        </th>
                                        <th>Case Name</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Analyzed</th>
                                        <th>Tokens</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cases as $case): ?>
                                        <tr>
                                            <td class="ps-2">
                                                <input class="form-check-input case-checkbox" type="checkbox" value="<?= $case['id'] ?>" />
                                            </td>
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
                                                <span class="badge badge-phoenix badge-phoenix-<?= $case['status_badge']['class'] ?>">
                                                    <?= htmlspecialchars($case['status_badge']['text']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-body-secondary">
                                                    <?= date('M j, Y', strtotime($case['created_at'])) ?><br>
                                                    <?= date('g:i A', strtotime($case['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($case['analyzed_at']): ?>
                                                    <small class="text-body-secondary">
                                                        <?= date('M j, Y', strtotime($case['analyzed_at'])) ?><br>
                                                        <?= date('g:i A', strtotime($case['analyzed_at'])) ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-body-tertiary">—</small>
                                                <?php endif; ?>
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
                                                <div class="d-flex gap-1 align-items-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-subtle-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                    <ul class="dropdown-menu">
                                                        
                                                        <?php if ($case['status'] === 'analyzed' && $case['has_analysis']): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="<?= app_url('results.php') ?>?case_id=<?= $case['id'] ?>">
                                                                    <i class="fas fa-eye me-2"></i>
                                                                    View Results
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($case['status'] === 'uploaded'): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="<?= app_url('analyze.php') ?>?case_id=<?= $case['id'] ?>">
                                                                    <i class="fas fa-magic me-2"></i>
                                                                    Start Analysis
                                                                </a>
                                                            </li>
                                                        <?php elseif ($case['status'] === 'analyzing'): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="<?= app_url('analyze.php') ?>?case_id=<?= $case['id'] ?>">
                                                                    <i class="fas fa-spinner me-2"></i>
                                                                    View Progress
                                                                </a>
                                                            </li>
                                                        <?php elseif ($case['status'] === 'failed'): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="<?= app_url('analyze.php') ?>?case_id=<?= $case['id'] ?>">
                                                                    <i class="fas fa-redo me-2"></i>
                                                                    Retry Analysis
                                                                </a>
                                                            </li>
                                                        <?php else: ?>
                                                            <li>
                                                                <a class="dropdown-item" href="<?= app_url('analyze.php') ?>?case_id=<?= $case['id'] ?>">
                                                                    <i class="fas fa-redo me-2"></i>
                                                                    Re-analyze
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item text-danger" onclick="confirmDelete(<?= $case['id'] ?>, '<?= htmlspecialchars($case['case_name'], ENT_QUOTES) ?>')">
                                                                <i class="fas fa-trash me-2"></i>
                                                                Delete Case
                                                            </button>
                                                        </li>
                                                    </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="Cases pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    
                                    <!-- Previous -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Page Numbers -->
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Next -->
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            
                            <!-- Pagination Info -->
                            <div class="text-center mt-2">
                                <small class="text-body-secondary">
                                    Showing <?= number_format(($page - 1) * $per_page + 1) ?> to <?= number_format(min($page * $per_page, $total_cases)) ?> of <?= number_format($total_cases) ?> cases
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the case <strong id="deleteCaseName"></strong>?</p>
                <div class="alert alert-subtle-warning border-0">
                    <small>
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        This action cannot be undone. All analysis results and uploaded files will be permanently deleted.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="case_id" id="deleteCaseId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>
                        Delete Case
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirm Bulk Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="bulkDeleteCount">0</span> selected cases?</p>
                <div class="alert alert-subtle-danger border-0">
                    <small>
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        This action cannot be undone. All analysis results and uploaded files will be permanently deleted.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="bulkDeleteForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="bulk_delete">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>
                        Delete Selected
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Checkbox management
    const selectAll = document.getElementById('selectAll');
    const headerSelectAll = document.getElementById('headerSelectAll');
    const caseCheckboxes = document.querySelectorAll('.case-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    // Select all functionality
    function updateSelectAll() {
        const selectedCount = document.querySelectorAll('.case-checkbox:checked').length;
        const totalCount = caseCheckboxes.length;
        
        if (selectedCount === 0) {
            selectAll.indeterminate = false;
            selectAll.checked = false;
            headerSelectAll.indeterminate = false;
            headerSelectAll.checked = false;
            bulkDeleteBtn.style.display = 'none';
        } else if (selectedCount === totalCount) {
            selectAll.indeterminate = false;
            selectAll.checked = true;
            headerSelectAll.indeterminate = false;
            headerSelectAll.checked = true;
            bulkDeleteBtn.style.display = 'inline-block';
        } else {
            selectAll.indeterminate = true;
            selectAll.checked = false;
            headerSelectAll.indeterminate = true;
            headerSelectAll.checked = false;
            bulkDeleteBtn.style.display = 'inline-block';
        }
    }
    
    // Select all checkboxes event
    [selectAll, headerSelectAll].forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            caseCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateSelectAll();
        });
    });
    
    // Individual checkbox events
    caseCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAll);
    });
    
    // Initial state
    updateSelectAll();
});

// Delete confirmation
function confirmDelete(caseId, caseName) {
    document.getElementById('deleteCaseId').value = caseId;
    document.getElementById('deleteCaseName').textContent = caseName;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Bulk delete
function showBulkDeleteModal() {
    const selectedCheckboxes = document.querySelectorAll('.case-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    
    if (selectedCount === 0) {
        alert('Please select cases to delete.');
        return;
    }
    
    document.getElementById('bulkDeleteCount').textContent = selectedCount;
    
    // Add selected case IDs to form
    const form = document.getElementById('bulkDeleteForm');
    
    // Remove existing hidden inputs
    form.querySelectorAll('input[name="selected_cases[]"]').forEach(input => input.remove());
    
    // Add selected case IDs
    selectedCheckboxes.forEach(checkbox => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'selected_cases[]';
        hiddenInput.value = checkbox.value;
        form.appendChild(hiddenInput);
    });
    
    new bootstrap.Modal(document.getElementById('bulkDeleteModal')).show();
}
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>