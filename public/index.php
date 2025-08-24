<?php
// Landing page for Appeal Prospect MVP
// Phoenix UI Implementation - Phase 3 Complete

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/helpers.php';

// Start session for authentication
start_session();

// Page variables
$page_title = 'Appeal Prospect MVP - Legal Judgment Analysis';
?>
<?php include __DIR__ . '/../app/templates/header.php'; ?>

<!-- Hero Section -->
<div class="bg-primary bg-gradient">
    <div class="container py-5">
        <div class="row justify-content-center text-center text-white py-4">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-gavel me-3"></i>
                    Appeal Prospect
                </h1>
                <p class="lead mb-4">
                    AI-Powered Legal Judgment Analysis for South African Courts
                </p>
                <p class="fs-5 mb-4">
                    Upload a court judgment and receive a comprehensive 13-section analysis 
                    powered by GPT-4o-mini and System Prompt v5.2
                </p>
                
                <?php if (is_logged_in()): ?>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="<?= app_url('upload.php') ?>" class="btn btn-light btn-lg">
                            <i class="fas fa-upload me-2"></i>
                            Upload Judgment
                        </a>
                        <a href="<?= app_url('my-cases.php') ?>" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-folder me-2"></i>
                            View My Cases
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="<?= app_url('register.php') ?>" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Get Started
                        </a>
                        <a href="<?= app_url('login.php') ?>" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container py-5">
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h2 class="display-6 mb-3">How It Works</h2>
            <p class="text-body-tertiary lead">
                Our AI-powered analysis system provides comprehensive legal insights in minutes
            </p>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <!-- Step 1 -->
        <div class="col-md-4">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 4rem; height: 4rem;">
                        <i class="fas fa-upload text-primary fs-3"></i>
                    </div>
                    <h5 class="card-title">1. Upload Document</h5>
                    <p class="card-text">
                        Upload a PDF judgment or paste the text directly into our secure platform
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Step 2 -->
        <div class="col-md-4">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 4rem; height: 4rem;">
                        <i class="fas fa-brain text-success fs-3"></i>
                    </div>
                    <h5 class="card-title">2. AI Analysis</h5>
                    <p class="card-text">
                        Our AI model analyzes the judgment.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Step 3 -->
        <div class="col-md-4">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 4rem; height: 4rem;">
                        <i class="fas fa-file-alt text-info fs-3"></i>
                    </div>
                    <h5 class="card-title">3. Get Results</h5>
                    <p class="card-text">
                        Receive a structured 13-section analysis including appeal prospects and strategy
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Analysis Sections Preview -->
<div class="bg-light py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8 mx-auto text-center">
                <h3 class="display-6 mb-3">Comprehensive Analysis</h3>
                <p class="text-body-tertiary">
                    Each analysis includes these detailed sections based on South African law
                </p>
            </div>
        </div>
        
        <div class="row g-3">
            <?php
            $sections = [
                ['Review of the Judgment', 'fas fa-search', 'Facts, procedural history, and case law references'],
                ['Issues Identification', 'fas fa-list', 'Key legal questions and recent developments'],
                ['Legal Grounds', 'fas fa-balance-scale', 'Constitutional and statutory analysis'],
                ['Appeal Strength', 'fas fa-chart-line', 'Evidence evaluation and precedent analysis'],
                ['Available Remedies', 'fas fa-tools', 'Potential legal remedies and outcomes'],
                ['Procedural Requirements', 'fas fa-clipboard-list', 'Court filing requirements and deadlines'],
                ['Ethical Considerations', 'fas fa-handshake', 'Professional conduct obligations'],
                ['Appeal Strategy', 'fas fa-chess', 'Recommended legal approach'],
                ['Constitutional Implications', 'fas fa-landmark', 'Bill of Rights considerations'],
                ['Success Probability', 'fas fa-percentage', 'Statistical likelihood assessment'],
                ['Risk Analysis', 'fas fa-exclamation-triangle', 'Potential challenges and obstacles'],
                ['Layperson Summary', 'fas fa-user', 'Plain language explanation'],
                ['Sources & Citations', 'fas fa-book', 'Referenced cases and statutes']
            ];
            
            foreach (array_chunk($sections, 4) as $chunk):
            ?>
                <div class="col-lg-3 col-md-6">
                    <?php foreach ($chunk as $section): ?>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3">
                                <i class="<?= $section[1] ?> text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-1"><?= $section[0] ?></h6>
                                <small class="text-body-tertiary"><?= $section[2] ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- System Features -->
<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="card-title">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        Secure & Private
                    </h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Argon2id password hashing</li>
                        <li><i class="fas fa-check text-success me-2"></i>CSRF protection on all forms</li>
                        <li><i class="fas fa-check text-success me-2"></i>Session security hardening</li>
                        <li><i class="fas fa-check text-success me-2"></i>Encrypted API key storage</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="card-title">
                        <i class="fas fa-cogs text-primary me-2"></i>
                        Advanced Features
                    </h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>PDF text extraction</li>
                        <li><i class="fas fa-check text-success me-2"></i>Optional web research via Perplexity</li>
                        <li><i class="fas fa-check text-success me-2"></i>Case history management</li>
                        <li><i class="fas fa-check text-success me-2"></i>Admin dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demo Notice -->
<div class="bg-warning bg-opacity-10 border-top border-warning">
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-10 mx-auto text-center">
                <h5 class="text-warning mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Important Notice
                </h5>
                <p class="mb-0 text-dark">
                    This is a <strong>demonstration application</strong> for educational purposes only. 
                    The analysis provided is not legal advice and should not be used as a substitute 
                    for consultation with a qualified legal professional.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/templates/footer.php'; ?>