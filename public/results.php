<?php
// Results Display Page
// Appeal Prospect MVP - 13-Section Analysis Display

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';

// Start session and require login
start_session();
require_login();

$case_data = null;
$analysis_sections = [];
$citations = [];
$research_summary = '';
$token_usage = [];
$errors = [];

// Get case ID from URL
$case_id = (int)($_GET['case_id'] ?? 0);
$user_id = current_user_id();

if ($case_id <= 0) {
    $errors[] = 'Invalid case ID provided.';
} else {
    // Fetch case data with access control
    $case_data = db_query_single(
        "SELECT * FROM cases WHERE id = ? AND (user_id = ? OR ? = 1)",
        [$case_id, $user_id, current_is_admin() ? 1 : 0]
    );
    
    if (!$case_data) {
        $errors[] = 'Case not found or access denied.';
    } elseif ($case_data['status'] !== 'analyzed') {
        // Redirect to analyze page if not complete
        if ($case_data['status'] === 'uploaded') {
            header('Location: /analyze.php?case_id=' . $case_id);
            exit;
        } elseif ($case_data['status'] === 'analyzing') {
            $errors[] = 'Analysis is still in progress. Please wait and refresh the page.';
        } elseif ($case_data['status'] === 'failed') {
            $errors[] = 'Analysis failed: ' . ($case_data['error_message'] ?? 'Unknown error');
        }
    } else {
        // Parse analysis results
        if (!empty($case_data['structured_analysis'])) {
            $analysis_sections = json_decode($case_data['structured_analysis'], true) ?? [];
        }
        
        // Parse citations
        if (!empty($case_data['citations'])) {
            $citations = json_decode($case_data['citations'], true) ?? [];
        }
        
        // Research summary
        $research_summary = $case_data['research_summary'] ?? '';
        
        // Token usage
        $token_usage = [
            'input' => $case_data['token_in'] ?? 0,
            'output' => $case_data['token_out'] ?? 0,
            'total' => ($case_data['token_in'] ?? 0) + ($case_data['token_out'] ?? 0)
        ];
        
        // Fallback: parse from raw analysis_result if structured data missing
        if (empty($analysis_sections) && !empty($case_data['analysis_result'])) {
            $analysis_sections = parseAnalysisFromText($case_data['analysis_result']);
        }
    }
}

/**
 * Parse analysis sections from raw text (fallback)
 */
function parseAnalysisFromText(string $analysis_text): array
{
    $sections = [
        'review_summary' => '',
        'issues_identified' => '',
        'legal_grounds' => '',
        'strength_assessment' => '',
        'available_remedies' => '',
        'procedural_requirements' => '',
        'ethical_considerations' => '',
        'appeal_strategy' => '',
        'constitutional_aspects' => '',
        'success_probability' => '',
        'risks_challenges' => '',
        'layperson_summary' => '',
        'sources_references' => ''
    ];
    
    // Simple section parsing - look for common headers
    $section_patterns = [
        'review_summary' => '/(?:^|\n)(?:#+ )?(?:Review )?Summary[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'issues_identified' => '/(?:^|\n)(?:#+ )?Issues? (?:Identified|Found)[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'legal_grounds' => '/(?:^|\n)(?:#+ )?Legal Grounds?[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'strength_assessment' => '/(?:^|\n)(?:#+ )?Strength (?:Assessment|Analysis)[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'available_remedies' => '/(?:^|\n)(?:#+ )?(?:Available )?Remedies[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'procedural_requirements' => '/(?:^|\n)(?:#+ )?Procedural Requirements?[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'ethical_considerations' => '/(?:^|\n)(?:#+ )?Ethical Considerations?[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'appeal_strategy' => '/(?:^|\n)(?:#+ )?Appeal Strategy[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'constitutional_aspects' => '/(?:^|\n)(?:#+ )?Constitutional Aspects?[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'success_probability' => '/(?:^|\n)(?:#+ )?(?:Success )?Probability[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'risks_challenges' => '/(?:^|\n)(?:#+ )?(?:Risks?|Challenges?)[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'layperson_summary' => '/(?:^|\n)(?:#+ )?(?:Layperson|Plain (?:English|Language)) Summary[:\s]*\n(.*?)(?=\n(?:#|$))/is',
        'sources_references' => '/(?:^|\n)(?:#+ )?(?:Sources?|References?)[:\s]*\n(.*?)(?=\n(?:#|$))/is'
    ];
    
    foreach ($section_patterns as $key => $pattern) {
        if (preg_match($pattern, $analysis_text, $matches)) {
            $sections[$key] = trim($matches[1]);
        }
    }
    
    // If no sections found, put everything in review_summary
    if (array_sum(array_map('strlen', $sections)) === 0) {
        $sections['review_summary'] = trim($analysis_text);
    }
    
    return $sections;
}

/**
 * Get section display configuration
 */
function getSectionConfig(): array
{
    return [
        'review_summary' => [
            'title' => 'Review Summary',
            'icon' => 'fas fa-clipboard-list',
            'color' => 'primary',
            'description' => 'Executive summary of the judgment analysis'
        ],
        'issues_identified' => [
            'title' => 'Issues Identified',
            'icon' => 'fas fa-exclamation-triangle',
            'color' => 'warning',
            'description' => 'Key legal issues and concerns found in the judgment'
        ],
        'legal_grounds' => [
            'title' => 'Legal Grounds',
            'icon' => 'fas fa-balance-scale',
            'color' => 'info',
            'description' => 'Legal basis and grounds for potential appeal'
        ],
        'strength_assessment' => [
            'title' => 'Strength Assessment',
            'icon' => 'fas fa-chart-line',
            'color' => 'success',
            'description' => 'Assessment of the strength of potential appeal grounds'
        ],
        'available_remedies' => [
            'title' => 'Available Remedies',
            'icon' => 'fas fa-tools',
            'color' => 'secondary',
            'description' => 'Legal remedies and relief options available'
        ],
        'procedural_requirements' => [
            'title' => 'Procedural Requirements',
            'icon' => 'fas fa-list-check',
            'color' => 'dark',
            'description' => 'Procedural steps and requirements for appeal process'
        ],
        'ethical_considerations' => [
            'title' => 'Ethical Considerations',
            'icon' => 'fas fa-user-shield',
            'color' => 'primary',
            'description' => 'Ethical aspects and professional conduct considerations'
        ],
        'appeal_strategy' => [
            'title' => 'Appeal Strategy',
            'icon' => 'fas fa-chess',
            'color' => 'success',
            'description' => 'Recommended strategy and approach for appeal'
        ],
        'constitutional_aspects' => [
            'title' => 'Constitutional Aspects',
            'icon' => 'fas fa-landmark',
            'color' => 'warning',
            'description' => 'Constitutional law implications and considerations'
        ],
        'success_probability' => [
            'title' => 'Success Probability',
            'icon' => 'fas fa-percent',
            'color' => 'info',
            'description' => 'Estimated likelihood of successful appeal'
        ],
        'risks_challenges' => [
            'title' => 'Risks & Challenges',
            'icon' => 'fas fa-exclamation-circle',
            'color' => 'danger',
            'description' => 'Potential risks and challenges in pursuing appeal'
        ],
        'layperson_summary' => [
            'title' => 'Layperson Summary',
            'icon' => 'fas fa-user-friends',
            'color' => 'secondary',
            'description' => 'Plain language summary for non-legal professionals'
        ],
        'sources_references' => [
            'title' => 'Sources & References',
            'icon' => 'fas fa-book',
            'color' => 'dark',
            'description' => 'Legal sources, cases, and references cited'
        ]
    ];
}

$page_title = 'Analysis Results - Appeal Prospect MVP';
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<div class="container-fluid px-0">
    <?php include __DIR__ . '/../app/templates/navbar.php'; ?>
    
    <div class="content">
        <div class="container-fluid px-6 py-4">
            
            <?php if (!empty($errors)): ?>
                <!-- Error Display -->
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger border-0" role="alert">
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
                            <a href="/upload.php" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>
                                Upload New Case
                            </a>
                            <a href="/my-cases.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-folder me-2"></i>
                                My Cases
                            </a>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                
                <!-- Page Header -->
                <div class="row align-items-center justify-content-between py-2 pe-0 mb-4">
                    <div class="col-auto">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="/my-cases.php">My Cases</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Analysis Results</li>
                            </ol>
                        </nav>
                        <h1 class="text-body-emphasis mb-0">
                            <i class="fas fa-file-alt me-2 text-primary"></i>
                            <?= htmlspecialchars($case_data['case_name']) ?>
                        </h1>
                        <p class="text-body-tertiary mb-0">
                            Analyzed on <?= date('F j, Y \a\t g:i A', strtotime($case_data['completed_analysis_at'])) ?>
                        </p>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex gap-2">
                            <a href="/my-cases.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Cases
                            </a>
                            <a href="/analyze.php?case_id=<?= $case_id ?>" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-2"></i>
                                Re-analyze
                            </a>
                            <button class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Analysis Overview -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Analysis Overview
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                            <i class="fas fa-file-alt text-primary fs-3 mb-2"></i>
                                            <h6 class="mb-1">Document Length</h6>
                                            <span class="text-body-secondary"><?= number_format(strlen($case_data['judgment_text'])) ?> characters</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                            <i class="fas fa-brain text-success fs-3 mb-2"></i>
                                            <h6 class="mb-1">AI Analysis</h6>
                                            <span class="text-body-secondary">13 Sections Complete</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                            <i class="fas fa-search text-info fs-3 mb-2"></i>
                                            <h6 class="mb-1">Web Research</h6>
                                            <span class="text-body-secondary">
                                                <?= count($citations) ?> source<?= count($citations) !== 1 ? 's' : '' ?> found
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                            <i class="fas fa-coins text-warning fs-3 mb-2"></i>
                                            <h6 class="mb-1">Token Usage</h6>
                                            <span class="text-body-secondary"><?= number_format($token_usage['total']) ?> tokens</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 13-Section Analysis Results -->
                <div class="row">
                    <div class="col-12">
                        <?php 
                        $section_config = getSectionConfig();
                        $section_count = 0;
                        ?>
                        
                        <?php foreach ($section_config as $section_key => $config): ?>
                            <?php 
                            $content = $analysis_sections[$section_key] ?? '';
                            if (empty(trim($content))) continue;
                            $section_count++;
                            ?>
                            
                            <div class="card shadow-sm mb-4" id="section-<?= $section_key ?>">
                                <div class="card-header bg-<?= $config['color'] ?> bg-opacity-10 border-<?= $config['color'] ?> border-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h5 class="card-title mb-0 text-<?= $config['color'] ?>">
                                            <i class="<?= $config['icon'] ?> me-2"></i>
                                            <?= $section_count ?>. <?= htmlspecialchars($config['title']) ?>
                                        </h5>
                                        <button class="btn btn-sm btn-outline-<?= $config['color'] ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $section_key ?>">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                    <small class="text-body-secondary"><?= htmlspecialchars($config['description']) ?></small>
                                </div>
                                <div class="collapse show" id="collapse-<?= $section_key ?>">
                                    <div class="card-body">
                                        <div class="analysis-content">
                                            <?= nl2br(htmlspecialchars(trim($content))) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        <?php endforeach; ?>

                        <?php if ($section_count === 0): ?>
                            <!-- Fallback: Display raw analysis if no structured sections -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-file-text me-2"></i>
                                        Analysis Results
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="analysis-content">
                                        <?= nl2br(htmlspecialchars($case_data['analysis_result'] ?? 'No analysis results available.')) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Web Research Sources -->
                        <?php if (!empty($citations) || !empty($research_summary)): ?>
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-globe me-2"></i>
                                        Web Research Sources
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($research_summary)): ?>
                                        <div class="mb-4">
                                            <h6 class="text-info mb-2">Research Summary</h6>
                                            <p class="text-body-secondary"><?= nl2br(htmlspecialchars($research_summary)) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($citations)): ?>
                                        <h6 class="text-info mb-3">Sources & Citations</h6>
                                        <div class="row g-3">
                                            <?php foreach ($citations as $index => $citation): ?>
                                                <div class="col-md-6">
                                                    <div class="card h-100 border-info border-opacity-25">
                                                        <div class="card-body">
                                                            <div class="d-flex align-items-start">
                                                                <span class="badge badge-phoenix badge-phoenix-info me-2"><?= $index + 1 ?></span>
                                                                <div class="flex-grow-1">
                                                                    <h6 class="card-title mb-2">
                                                                        <a href="<?= htmlspecialchars($citation['url']) ?>" target="_blank" class="text-decoration-none">
                                                                            <?= htmlspecialchars($citation['title']) ?>
                                                                            <i class="fas fa-external-link-alt ms-1 fs-9"></i>
                                                                        </a>
                                                                    </h6>
                                                                    <?php if (!empty($citation['snippet'])): ?>
                                                                        <p class="card-text small text-body-secondary mb-2">
                                                                            <?= htmlspecialchars(substr($citation['snippet'], 0, 150)) ?>...
                                                                        </p>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($citation['domain'])): ?>
                                                                        <small class="text-body-tertiary">
                                                                            <i class="fas fa-link me-1"></i>
                                                                            <?= htmlspecialchars($citation['domain']) ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Analysis Metadata -->
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Analysis Details
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-5">Case ID:</dt>
                                            <dd class="col-sm-7"><?= $case_data['id'] ?></dd>
                                            <dt class="col-sm-5">Created:</dt>
                                            <dd class="col-sm-7"><?= date('F j, Y g:i A', strtotime($case_data['created_at'])) ?></dd>
                                            <dt class="col-sm-5">Analysis Started:</dt>
                                            <dd class="col-sm-7">
                                                <?= $case_data['started_analysis_at'] ? date('F j, Y g:i A', strtotime($case_data['started_analysis_at'])) : 'N/A' ?>
                                            </dd>
                                            <dt class="col-sm-5">Analysis Completed:</dt>
                                            <dd class="col-sm-7">
                                                <?= $case_data['completed_analysis_at'] ? date('F j, Y g:i A', strtotime($case_data['completed_analysis_at'])) : 'N/A' ?>
                                            </dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-5">Original File:</dt>
                                            <dd class="col-sm-7">
                                                <?= $case_data['original_filename'] ? htmlspecialchars($case_data['original_filename']) : 'Text Input' ?>
                                            </dd>
                                            <dt class="col-sm-5">File Type:</dt>
                                            <dd class="col-sm-7"><?= htmlspecialchars($case_data['mime_type'] ?? 'text/plain') ?></dd>
                                            <dt class="col-sm-5">Input Tokens:</dt>
                                            <dd class="col-sm-7"><?= number_format($token_usage['input']) ?></dd>
                                            <dt class="col-sm-5">Output Tokens:</dt>
                                            <dd class="col-sm-7"><?= number_format($token_usage['output']) ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.analysis-content {
    line-height: 1.6;
    font-size: 0.95rem;
}

.analysis-content p {
    margin-bottom: 1rem;
}

@media print {
    .btn, .breadcrumb, .card-header button {
        display: none !important;
    }
    
    .collapse {
        display: block !important;
    }
    
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling to sections
    const sectionLinks = document.querySelectorAll('a[href^="#section-"]');
    sectionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // Collapse/expand functionality
    const collapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');
    collapseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            setTimeout(() => {
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target && target.classList.contains('show')) {
                    icon.className = 'fas fa-chevron-up';
                } else {
                    icon.className = 'fas fa-chevron-down';
                }
            }, 100);
        });
    });
});
</script>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>