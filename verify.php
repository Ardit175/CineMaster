<?php
/**
 * ============================================
 * CineMaster - Email Verification Page
 * ============================================
 * Verifies user email using the token from the verification link
 */

require_once 'config/config.php';

$pageTitle = 'Email Verification';

$token = $_GET['token'] ?? '';
$result = null;

if (!empty($token)) {
    $result = verifyEmail($token);
}

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            
            <div class="card bg-dark border-secondary shadow-lg">
                <div class="card-body p-5 text-center">
                    
                    <?php if (empty($token)): ?>
                        <i class="bi bi-exclamation-circle text-warning fs-1 mb-3 d-block"></i>
                        <h4>Invalid Verification Link</h4>
                        <p class="text-muted">Please check your email for the correct verification link.</p>
                        
                    <?php elseif ($result && $result['success']): ?>
                        <i class="bi bi-check-circle text-success fs-1 mb-3 d-block"></i>
                        <h4>Email Verified!</h4>
                        <p class="text-muted"><?php echo htmlspecialchars($result['message']); ?></p>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-danger btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login Now
                        </a>
                        
                    <?php else: ?>
                        <i class="bi bi-x-circle text-danger fs-1 mb-3 d-block"></i>
                        <h4>Verification Failed</h4>
                        <p class="text-muted"><?php echo htmlspecialchars($result['message'] ?? 'Unknown error'); ?></p>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-outline-danger">
                            Register Again
                        </a>
                    <?php endif; ?>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
