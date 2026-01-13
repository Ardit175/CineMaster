<?php
/**
 * ============================================
 * CineMaster - Forgot Password Page
 * ============================================
 * Allows users to request a password reset link
 */

require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'Forgot Password';
$errors = [];
$success = '';

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $result = initiatePasswordReset($email);
            if ($result['success']) {
                // Redirect to prevent form resubmission
                $_SESSION['reset_message'] = $result['message'];
                redirect(SITE_URL . '/forgot_password.php?sent=1');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// Check for redirect message
if (isset($_GET['sent']) && isset($_SESSION['reset_message'])) {
    $success = $_SESSION['reset_message'];
    unset($_SESSION['reset_message']);
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
                        <i class="bi bi-key me-2"></i>Forgot Password
                    </h3>
                </div>
                
                <div class="card-body text-light p-4">
                    <p class="text-light text-center mb-4">
                        Enter your email address and we'll send you a link to reset your password.
                    </p>
                    
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
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php else: ?>
                    
                    <form method="POST" action="" id="forgotPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="mb-4">
                            <label for="email" class="form-label text-light">
                                <i class="bi bi-envelope me-1"></i>Email Address
                            </label>
                            <input type="email" class="form-control bg-dark text-light border-secondary" 
                                   id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning btn-lg" id="submitBtn">
                                <i class="bi bi-send me-2"></i>Send Reset Link
                            </button>
                        </div>
                    </form>
                    
                    <script>
                    document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
                        const btn = document.getElementById('submitBtn');
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
                    });
                    </script>
                    
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo SITE_URL; ?>/login.php" class="text-danger text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
