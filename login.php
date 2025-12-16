<?php
/**
 * ============================================
 * CineMaster - Login Page
 * ============================================
 * Handles user authentication with lockout protection
 * Includes remember me functionality and session management
 */

require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/user/profile.php');
    }
}

$pageTitle = 'Login';
$errors = [];

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get and sanitize inputs
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Basic validation
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        // Attempt login if no validation errors
        if (empty($errors)) {
            $result = loginUser($email, $password, $remember);
            
            if ($result['success']) {
                // Check for redirect URL in session
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirectUrl = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirectUrl);
                }
                
                // Redirect based on role
                redirect(SITE_URL . '/' . $result['redirect']);
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            
            <!-- Login Card -->
            <div class="card bg-dark border-secondary shadow-lg">
                <div class="card-header bg-danger text-center py-4">
                    <h3 class="mb-0">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Welcome Back
                    </h3>
                </div>
                
                <div class="card-body p-4">
                    <!-- Display Errors -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Check for IP lockout -->
                    <?php 
                    $ip = getClientIP();
                    if (isIPLockedOut($ip)): 
                        $remainingTime = getRemainingLockoutTime($ip);
                    ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Account Locked!</strong><br>
                            Too many failed login attempts. Please try again in <?php echo $remainingTime; ?> minutes.
                        </div>
                    <?php else: ?>
                    
                    <!-- Login Form -->
                    <form method="POST" action="" id="loginForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-1"></i>Email Address
                            </label>
                            <input type="email" class="form-control bg-dark text-light border-secondary" 
                                   id="email" name="email"
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   placeholder="Enter your email" required autofocus>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        
                        <!-- Password Field -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-1"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control bg-dark text-light border-secondary" 
                                       id="password" name="password"
                                       placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please enter your password.</div>
                        </div>
                        
                        <!-- Remember Me & Forgot Password -->
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label text-muted" for="remember">
                                    Remember me
                                </label>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/forgot_password.php" class="text-danger text-decoration-none">
                                Forgot password?
                            </a>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                    
                    <!-- Demo Credentials (For Testing) -->
                    <div class="alert alert-secondary mt-4 mb-0">
                        <small>
                            <strong>Demo Admin:</strong> admin@cinemaster.com / Admin@123<br>
                            <em class="text-muted">Note: Default password is "password" but needs to match the hash</em>
                        </small>
                    </div>
                    
                    <!-- Register Link -->
                    <div class="text-center mt-4">
                        <p class="text-muted mb-0">
                            Don't have an account? 
                            <a href="<?php echo SITE_URL; ?>/register.php" class="text-danger text-decoration-none fw-bold">
                                Register now
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Security Info -->
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="bi bi-shield-lock me-1"></i>
                    Your session will timeout after 15 minutes of inactivity.<br>
                    After 7 failed attempts, your IP will be blocked for 30 minutes.
                </small>
            </div>
            
        </div>
    </div>
</div>

<!-- Client-side Validation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    
    // Toggle password visibility
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }
    
    // Form validation
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Email validation
            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                email.classList.add('is-invalid');
                isValid = false;
            } else {
                email.classList.remove('is-invalid');
            }
            
            // Password validation
            if (password.value.length === 0) {
                password.classList.add('is-invalid');
                isValid = false;
            } else {
                password.classList.remove('is-invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
