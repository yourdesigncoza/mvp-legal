<?php
// Analysis Processor
// Appeal Prospect MVP - AI Analysis Orchestration

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/gpt_handler.php';
require_once __DIR__ . '/../app/perplexity.php';

// Start session and require login
start_session();
require_login();

$errors = [];
$analysis_result = null;
$case_data = null;
$processing_status = [];

// Handle direct case analysis from upload
if (isset($_GET['case_id'])) {
    $case_id = (int)$_GET['case_id'];
    $user_id = current_user_id();
    
    // Verify case belongs to user (or user is admin)
    $case_data = db_query_single(
        "SELECT * FROM cases WHERE id = ? AND (user_id = ? OR ? = 1)", 
        [$case_id, $user_id, current_is_admin() ? 1 : 0]
    );
    
    if (!$case_data) {
        $errors['access'] = 'Case not found or access denied.';
    } elseif ($case_data['status'] === 'analyzing') {
        $errors['status'] = 'Case is currently being analyzed. Please wait.';
    } elseif (empty($case_data['judgment_text'])) {
        $errors['data'] = 'No judgment text found for this case.';
    }
}

// Handle form submission for analysis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    // Validate CSRF token
    if (!validate_csrf()) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        $case_id = (int)($_POST['case_id'] ?? 0);
        $user_id = current_user_id();
        
        // Verify case again
        $case_data = db_query_single(
            "SELECT * FROM cases WHERE id = ? AND (user_id = ? OR ? = 1)", 
            [$case_id, $user_id, current_is_admin() ? 1 : 0]
        );
        
        if (!$case_data) {
            $errors['general'] = 'Case not found or access denied.';
        } else {
            // Start analysis process
            $analysis_result = processAnalysis($case_data, $processing_status);
        }
    }
}

// Auto-start analysis if case is ready and hasn't been analyzed
if ($case_data && empty($errors) && !$analysis_result && $case_data['status'] === 'uploaded') {
    $analysis_result = processAnalysis($case_data, $processing_status);
}

$page_title = 'Analyze Judgment - Appeal Prospect MVP';

/**
 * Main analysis processing function
 */
function processAnalysis(array $case_data, array &$status): ?array
{
    $case_id = (int)$case_data['id'];
    $judgment_text = $case_data['judgment_text'];
    
    try {
        // Update case status to analyzing
        db_execute("UPDATE cases SET status = 'analyzing', started_analysis_at = NOW() WHERE id = ?", [$case_id]);
        
        $status[] = "Starting analysis for case: " . htmlspecialchars($case_data['case_name']);
        
        // Step 1: OpenAI GPT Analysis
        $status[] = "Analyzing judgment with GPT-4o-mini...";
        $gpt_result = analyze_judgment_with_gpt($judgment_text, $case_id);
        
        if (!$gpt_result['success']) {
            db_execute("UPDATE cases SET status = 'failed', error_message = ? WHERE id = ?", [$gpt_result['error'], $case_id]);
            return [
                'success' => false,
                'error' => $gpt_result['error'],
                'status' => $status
            ];
        }
        
        $status[] = "GPT analysis completed. Tokens used: " . number_format($gpt_result['token_usage']['total_tokens']);
        
        // Step 2: Optional Perplexity Research
        $research_result = null;
        if (is_web_research_available()) {
            $status[] = "Researching legal sources...";
            
            // Extract brief summary for research
            $case_summary = substr($judgment_text, 0, 500) . "...";
            $legal_issues = []; // TODO: Extract from GPT analysis
            
            $research_result = research_legal_sources($case_summary, $legal_issues);
            
            if ($research_result['success'] && $research_result['available']) {
                $status[] = "Found " . count($research_result['citations']) . " relevant sources";
                
                // Save research results
                db_execute(
                    "UPDATE cases SET citations = ?, research_summary = ? WHERE id = ?", 
                    [
                        json_encode($research_result['citations']), 
                        $research_result['summary'] ?? null, 
                        $case_id
                    ]
                );
            } else {
                $status[] = $research_result['message'] ?? 'Web research unavailable';
            }
        } else {
            $status[] = "Web research unavailable (API key not configured)";
        }
        
        // Step 3: Finalize analysis
        $status[] = "Finalizing analysis results...";
        
        // Update case status to completed
        db_execute("UPDATE cases SET status = 'analyzed', completed_analysis_at = NOW() WHERE id = ?", [$case_id]);
        
        $status[] = "Analysis complete! Redirecting to results...";
        
        return [
            'success' => true,
            'case_id' => $case_id,
            'gpt_result' => $gpt_result,
            'research_result' => $research_result,
            'status' => $status
        ];
        
    } catch (Exception $e) {
        error_log("Analysis processing error: " . $e->getMessage());
        
        // Update case status to failed
        db_execute("UPDATE cases SET status = 'failed', error_message = ? WHERE id = ?", [$e->getMessage(), $case_id]);
        
        return [
            'success' => false,
            'error' => 'Analysis failed: ' . $e->getMessage(),
            'status' => $status
        ];
    }
}
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<div class="container-fluid px-0">
    
    <div class="content">
        <div class="container-fluid px-6 py-4">
            
            <!-- Page Header -->
            <div class="row align-items-center justify-content-between py-2 pe-0 mb-4">
                <div class="col-auto">
                    <h2 class="text-body-emphasis mb-0">
                        <i class="fas fa-magic me-2 text-primary"></i>
                        AI Analysis
                    </h2>
                    <p class="text-body-tertiary mb-0">
                        <?php if ($case_data): ?>
                            Analyzing: <?= htmlspecialchars($case_data['case_name']) ?>
                        <?php else: ?>
                            Process legal judgment for appeal prospects
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-auto">
                    <a href="<?= app_url('upload.php') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-upload me-2"></i>
                        Upload New
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <!-- Error Messages -->
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-subtle-danger border-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-circle fs-4 me-3"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Error</h6>
                                <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-4">
                    <a href="<?= app_url('upload.php') ?>" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>
                        Upload New Case
                    </a>
                    <a href="<?= app_url('my-cases.php') ?>" class="btn btn-subtle-secondary ms-2">
                        <i class="fas fa-folder me-2"></i>
                        My Cases
                    </a>
                </div>
                
            <?php elseif ($analysis_result): ?>
                <!-- Analysis Results -->
                <?php if ($analysis_result['success']): ?>
                    
                    <!-- Success Message -->
                    <div class="alert alert-subtle-success border-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-check-circle fs-4 me-3"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Analysis Complete!</h6>
                                <p class="mb-0">
                                    Your legal judgment has been successfully analyzed. 
                                    <strong>Redirecting to results...</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Processing Status -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list-check me-2"></i>
                                Processing Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($processing_status as $step): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <?= htmlspecialchars($step) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Analysis Summary -->
                    <?php if (isset($analysis_result['gpt_result']['token_usage'])): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Analysis Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                        <i class="fas fa-brain text-primary fs-3 mb-2"></i>
                                        <h6 class="mb-1">AI Analysis</h6>
                                        <span class="badge badge-phoenix badge-phoenix-success">Complete</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                        <i class="fas fa-search text-info fs-3 mb-2"></i>
                                        <h6 class="mb-1">Web Research</h6>
                                        <?php if ($analysis_result['research_result'] && $analysis_result['research_result']['available']): ?>
                                            <span class="badge badge-phoenix badge-phoenix-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge badge-phoenix badge-phoenix-warning">Limited</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                        <i class="fas fa-tokens text-success fs-3 mb-2"></i>
                                        <h6 class="mb-1">Tokens Used</h6>
                                        <small class="text-body-secondary">
                                            <?= number_format($analysis_result['gpt_result']['token_usage']['total_tokens']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Auto Redirect Script -->
                    <script>
                    setTimeout(function() {
                        window.location.href = '/results.php?case_id=<?= $analysis_result['case_id'] ?>';
                    }, 3000);
                    </script>
                    
                <?php else: ?>
                    <!-- Analysis Failed -->
                    <div class="alert alert-subtle-danger border-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Analysis Failed</h6>
                                <p class="mb-0"><?= htmlspecialchars($analysis_result['error']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Processing Status -->
                    <?php if (!empty($processing_status)): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>
                                Processing Log
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($processing_status as $step): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        <?= htmlspecialchars($step) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <button onclick="window.location.reload()" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>
                            Try Again
                        </button>
                        <a href="<?= app_url('upload.php') ?>" class="btn btn-subtle-secondary ms-2">
                            <i class="fas fa-upload me-2"></i>
                            Upload Different Case
                        </a>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($case_data): ?>
                <!-- Ready to Analyze -->
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-file-alt me-2"></i>
                                    Ready for Analysis
                                </h5>
                            </div>
                            <div class="card-body text-center p-5">
                                <div class="mb-4">
                                    <i class="fas fa-gavel text-primary" style="font-size: 4rem;"></i>
                                </div>
                                
                                <h4 class="text-body-emphasis mb-3">
                                    <?= htmlspecialchars($case_data['case_name']) ?>
                                </h4>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="bg-body-tertiary rounded p-3">
                                            <small class="text-body-secondary d-block">Upload Date</small>
                                            <strong><?= date('F j, Y', strtotime($case_data['created_at'])) ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="bg-body-tertiary rounded p-3">
                                            <small class="text-body-secondary d-block">Text Length</small>
                                            <strong><?= number_format(strlen($case_data['judgment_text'])) ?> characters</strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="text-body-secondary mb-4">
                                    This case is ready for AI-powered appeal prospect analysis. 
                                    The process typically takes 1-2 minutes.
                                </p>
                                
                                <form method="POST" action="<?= app_url('analyze.php') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="case_id" value="<?= $case_data['id'] ?>">
                                    
                                    <button type="submit" class="btn btn-primary btn-lg" id="analyzeBtn">
                                        <i class="fas fa-magic me-2"></i>
                                        Start AI Analysis
                                    </button>
                                </form>
                                
                                <div class="mt-4">
                                    <a href="<?= app_url('my-cases.php') ?>" class="btn btn-subtle-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Back to My Cases
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Analysis Info -->
                        <div class="card shadow-sm mt-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    What to Expect
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">
                                            <i class="fas fa-brain me-2"></i>
                                            AI Analysis
                                        </h6>
                                        <ul class="list-unstyled ms-3 small">
                                            <li>• 13-section comprehensive review</li>
                                            <li>• Legal grounds assessment</li>
                                            <li>• Success probability estimation</li>
                                            <li>• Strategic recommendations</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-info">
                                            <i class="fas fa-search me-2"></i>
                                            Web Research
                                        </h6>
                                        <ul class="list-unstyled ms-3 small">
                                            <li>• Recent case precedents</li>
                                            <li>• Relevant legislation</li>
                                            <li>• Legal commentary</li>
                                            <li>• Citation sources</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- No Case Selected -->
                <div class="text-center py-5">
                    <i class="fas fa-upload text-body-tertiary" style="font-size: 4rem;"></i>
                    <h4 class="text-body-secondary mt-3 mb-2">No Case Selected</h4>
                    <p class="text-body-tertiary mb-4">Upload a legal judgment to begin analysis</p>
                    
                    <a href="<?= app_url('upload.php') ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-upload me-2"></i>
                        Upload Judgment
                    </a>
                    <a href="<?= app_url('my-cases.php') ?>" class="btn btn-subtle-secondary btn-lg ms-2">
                        <i class="fas fa-folder me-2"></i>
                        Browse Cases
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle analysis form submission
    const analyzeBtn = document.getElementById('analyzeBtn');
    if (analyzeBtn) {
        const form = analyzeBtn.closest('form');
        form.addEventListener('submit', function() {
            showLoading('analyzeBtn', 'Analyzing...');
        });
    }
});
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>