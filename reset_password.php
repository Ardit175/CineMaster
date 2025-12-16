<?php
/**
 * ============================================
 * CineMaster - Reset Password Page
 * ============================================
 * Allows users to set a new password using a reset token
 */

require_once 'config/config.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'Reset Password';
$errors = [];
$success = '';
$tokenValid = false;

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $errors[] = 'Invalid reset link.';
} else {
    // Verify token exists and is valid
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ? AND token_expire > NOW()");
    $stmt->execute([$token]);
    $tokenValid = $stmt->rowCount() > 0;
    
    if (!$tokenValid) {
        $errors[] = 'This reset link is invalid or has expired.';
    }
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain uppercase, lowercase, and a number.';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($errors)) {
            $result = resetPassword($token, $password);
            if ($result['success']) {
                $success = $result['message'];
                $tokenValid = false; // Prevent form from showing again
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            
            <div class="card bg-dark border-secondary shadow-lg">
                <div class="card-header bg-warning text-dark text-center py-4">
                    <h3 class="mb-0">
                        <i class="bi bi-key-fill me-2"></i>Reset Password
                    </h3>
                </div>
                
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center">
                            <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                        <div class="d-grid">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-danger btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login Now
                            </a>
                        </div>
                    <?php elseif ($tokenValid): ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control bg-dark text-light border-secondary" 
                                   id="password" name="password" required>
                            <div class="form-text text-muted">
                                Min 8 characters, with uppercase, lowercase, and number.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control bg-dark text-light border-secondary" 
                                   id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="bi bi-check-lg me-2"></i>Reset Password
                            </button>
                        </div>
                    </form>
                    
                    <?php else: ?>
                        <div class="text-center">
                            <a href="<?php echo SITE_URL; ?>/forgot_password.php" class="btn btn-outline-danger">
                                Request New Reset Link
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
