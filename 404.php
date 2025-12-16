<?php
/**
 * ============================================
 * CineMaster - 404 Error Page
 * ============================================
 */

require_once 'config/config.php';
$pageTitle = 'Page Not Found';
include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="text-center">
        <div class="mb-4">
            <i class="bi bi-film text-danger" style="font-size: 6rem;"></i>
        </div>
        <h1 class="display-1 fw-bold text-danger">404</h1>
        <h2 class="mb-4">Page Not Found</h2>
        <p class="lead text-muted mb-4">
            Oops! The page you're looking for seems to have disappeared into the cinema darkness.
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="<?php echo SITE_URL; ?>" class="btn btn-danger btn-lg">
                <i class="bi bi-house me-2"></i>Back to Home
            </a>
            <a href="<?php echo SITE_URL; ?>/movies.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-film me-2"></i>Browse Movies
            </a>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
