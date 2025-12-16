<?php
/**
 * ============================================
 * CineMaster - Registration Page
 * ============================================
 * Allows new users to create an account
 * Includes both front-end and back-end validation
 */

require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'Register';
$errors = [];
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation rules
        if (empty($name)) {
            $errors[] = 'Name is required.';
        } elseif (strlen($name) < 2 || strlen($name) > 100) {
            $errors[] = 'Name must be between 2 and 100 characters.';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        // If no errors, attempt registration
        if (empty($errors)) {
            $result = registerUser($name, $email, $password);
            
            if ($result['success']) {
                $success = $result['message'];
                // Clear form values on success
                $name = $email = '';
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
            
            <!-- Registration Card -->
            <div class="card bg-dark border-secondary shadow-lg">
                <div class="card-header bg-danger text-center py-4">
                    <h3 class="mb-0">
                        <i class="bi bi-person-plus me-2"></i>Create Account
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
                    
                    <!-- Display Success -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <br><small>Check the <code>logs/email_log.txt</code> file for the verification link (demo mode).</small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Registration Form -->
                    <form method="POST" action="" id="registerForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <!-- Name Field -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="bi bi-person me-1"></i>Full Name
                            </label>
                            <input type="text" class="form-control bg-dark text-light border-secondary" 
                                   id="name" name="name" 
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                   placeholder="Enter your full name" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        
                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-1"></i>Email Address
                            </label>
                            <input type="email" class="form-control bg-dark text-light border-secondary" 
                                   id="email" name="email"
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   placeholder="Enter your email" required>
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
                                       placeholder="Create a password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-muted">
                                <small>Min 8 characters, with uppercase, lowercase, and number.</small>
                            </div>
                        </div>
                        
                        <!-- Confirm Password Field -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="bi bi-lock-fill me-1"></i>Confirm Password
                            </label>
                            <input type="password" class="form-control bg-dark text-light border-secondary" 
                                   id="confirm_password" name="confirm_password"
                                   placeholder="Confirm your password" required>
                            <div class="invalid-feedback">Passwords do not match.</div>
                        </div>
                        
                        <!-- Password Strength Indicator -->
                        <div class="mb-3">
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="passwordStrengthText"></small>
                        </div>
                        
                        <!-- Terms Checkbox -->
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label text-muted" for="terms">
                                I agree to the <a href="#" class="text-danger">Terms of Service</a> 
                                and <a href="#" class="text-danger">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-person-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>
                    
                    <!-- Login Link -->
                    <div class="text-center mt-4">
                        <p class="text-muted mb-0">
                            Already have an account? 
                            <a href="<?php echo SITE_URL; ?>/login.php" class="text-danger text-decoration-none fw-bold">
                                Login here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Client-side Validation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const togglePassword = document.getElementById('togglePassword');
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = password.type === 'password' ? 'text' : 'password';
        password.type = type;
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
    
    // Password strength checker
    password.addEventListener('input', function() {
        const value = this.value;
        let strength = 0;
        let text = '';
        let color = '';
        
        if (value.length >= 8) strength += 25;
        if (/[a-z]/.test(value)) strength += 25;
        if (/[A-Z]/.test(value)) strength += 25;
        if (/[0-9]/.test(value)) strength += 25;
        
        if (strength <= 25) {
            text = 'Weak';
            color = 'bg-danger';
        } else if (strength <= 50) {
            text = 'Fair';
            color = 'bg-warning';
        } else if (strength <= 75) {
            text = 'Good';
            color = 'bg-info';
        } else {
            text = 'Strong';
            color = 'bg-success';
        }
        
        strengthBar.style.width = strength + '%';
        strengthBar.className = 'progress-bar ' + color;
        strengthText.textContent = value.length > 0 ? 'Password strength: ' + text : '';
    });
    
    // Confirm password validation
    confirmPassword.addEventListener('input', function() {
        if (this.value !== password.value) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Name validation
        const name = document.getElementById('name');
        if (name.value.trim().length < 2) {
            name.classList.add('is-invalid');
            isValid = false;
        } else {
            name.classList.remove('is-invalid');
        }
        
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
        if (password.value.length < 8 || 
            !/[A-Z]/.test(password.value) || 
            !/[a-z]/.test(password.value) || 
            !/[0-9]/.test(password.value)) {
            password.classList.add('is-invalid');
            isValid = false;
        } else {
            password.classList.remove('is-invalid');
        }
        
        // Confirm password validation
        if (confirmPassword.value !== password.value) {
            confirmPassword.classList.add('is-invalid');
            isValid = false;
        }
        
        // Terms checkbox
        const terms = document.getElementById('terms');
        if (!terms.checked) {
            terms.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
