<?php
/**
 * Phoenix Layout Template
 * Main layout wrapper for all pages
 */

// Set default page title if not provided
if (!isset($page_title)) {
    $page_title = 'Appeal Prospect MVP';
}

// Include header
include __DIR__ . '/header.php';
?>

<!-- Page Content -->
<div class="content">
    <?php if (isset($content)): ?>
        <?= $content ?>
    <?php else: ?>
        <!-- Default content structure -->
        <div class="container-fluid px-4 py-4">
            <?php if (isset($page_header)): ?>
                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-1"><?= htmlspecialchars($page_header['title'] ?? 'Page Title') ?></h2>
                                <?php if (isset($page_header['subtitle'])): ?>
                                    <p class="text-body-tertiary mb-0"><?= htmlspecialchars($page_header['subtitle']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($page_header['actions'])): ?>
                                <div class="d-flex gap-2">
                                    <?= $page_header['actions'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Content Area -->
            <?php if (isset($main_content)): ?>
                <?= $main_content ?>
            <?php else: ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <p class="text-muted">Content will be loaded here...</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include __DIR__ . '/footer.php';
?>