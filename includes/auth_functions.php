<?php
/**
 * ============================================
 * CineMaster - Authentication Functions
 * ============================================
 * This file contains all authentication-related functions including:
 * - User registration
 * - Login with lockout protection
 * - Session management
 * - Password reset
 * - Remember me functionality
 */

/**
 * ============================================
 * REGISTRATION FUNCTION
 * ============================================
 * Registers a new user with email verification
 * 
 * @param string $name User's full name
 * @param string $email User's email address
 * @param string $password User's password (plain text - will be hashed)
 * @return array Result with success status and message
 */
function registerUser($name, $email, $password) {
    $pdo = getDBConnection();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Email address is already registered.'];
    }
    
    // Hash the password using PHP's secure password_hash function
    // Uses bcrypt algorithm by default (COST factor of 10)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification token
    $token = bin2hex(random_bytes(32));
    $tokenExpire = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    try {
        // Insert new user with prepared statement (prevents SQL injection)
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, token, token_expire, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $email, $hashedPassword, $token, $tokenExpire]);
        
        $userId = $pdo->lastInsertId();
        
        // Send verification email (simulated for demo)
        sendVerificationEmail($email, $name, $token);
        
        // Log the registration
        logAction($userId, 'User registered', 'auth');
        
        return [
            'success' => true, 
            'message' => 'Registration successful! Please check your email to verify your account.',
            'user_id' => $userId
        ];
        
    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * ============================================
 * LOGIN FUNCTION WITH LOCKOUT PROTECTION
 * ============================================
 * Authenticates a user with brute force protection
 * 
 * @param string $email User's email
 * @param string $password User's password
 * @param bool $remember Whether to remember the user
 * @return array Result with success status and message
 */
function loginUser($email, $password, $remember = false) {
    $pdo = getDBConnection();
    $ip = getClientIP();
    
    // Check if IP is locked out
    if (isIPLockedOut($ip)) {
        $remainingTime = getRemainingLockoutTime($ip);
        return [
            'success' => false, 
            'message' => "Too many failed attempts. Please try again in {$remainingTime} minutes."
        ];
    }
    
    // Find user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Verify password using password_verify (secure comparison)
    if ($user && password_verify($password, $user['password'])) {
        
        // Check if account is verified
        if ($user['is_verified'] == 0) {
            return [
                'success' => false, 
                'message' => 'Please verify your email address before logging in.'
            ];
        }
        
        // Clear failed attempts on successful login
        clearLoginAttempts($ip);
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['logged_in'] = true;
        
        // Handle "Remember Me" functionality
        if ($remember) {
            setRememberMeCookie($user['id']);
        }
        
        // Log successful login
        logAction($user['id'], 'User logged in', 'auth');
        
        return [
            'success' => true, 
            'message' => 'Login successful!',
            'role' => $user['role'],
            'redirect' => ($user['role'] === 'admin') ? 'admin/dashboard.php' : 'user/profile.php'
        ];
        
    } else {
        // Record failed attempt
        recordLoginAttempt($ip, $email);
        
        // Get remaining attempts
        $attempts = getFailedAttempts($ip);
        $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
        
        if ($remaining <= 0) {
            return [
                'success' => false, 
                'message' => 'Account locked due to too many failed attempts. Try again in 30 minutes.'
            ];
        }
        
        return [
            'success' => false, 
            'message' => "Invalid email or password. {$remaining} attempts remaining."
        ];
    }
}

/**
 * ============================================
 * LOCKOUT FUNCTIONS
 * ============================================
 */

/**
 * Records a failed login attempt
 * 
 * @param string $ip The IP address
 * @param string $email The email attempted (optional)
 */
function recordLoginAttempt($ip, $email = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $email]);
}

/**
 * Gets the number of failed attempts for an IP in the lockout window
 * 
 * @param string $ip The IP address
 * @return int Number of failed attempts
 */
function getFailedAttempts($ip) {
    $pdo = getDBConnection();
    $lockoutWindow = date('Y-m-d H:i:s', time() - LOCKOUT_TIME);
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE ip_address = ? AND attempt_time > ?
    ");
    $stmt->execute([$ip, $lockoutWindow]);
    $result = $stmt->fetch();
    
    return (int) $result['attempts'];
}

/**
 * Checks if an IP is currently locked out
 * 
 * @param string $ip The IP address
 * @return bool True if locked out
 */
function isIPLockedOut($ip) {
    return getFailedAttempts($ip) >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Gets remaining lockout time in minutes
 * 
 * @param string $ip The IP address
 * @return int Remaining minutes
 */
function getRemainingLockoutTime($ip) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT attempt_time 
        FROM login_attempts 
        WHERE ip_address = ? 
        ORDER BY attempt_time DESC 
        LIMIT 1
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch();
    
    if ($result) {
        $lastAttempt = strtotime($result['attempt_time']);
        $unlockTime = $lastAttempt + LOCKOUT_TIME;
        $remaining = ceil(($unlockTime - time()) / 60);
        return max(0, $remaining);
    }
    
    return 0;
}

/**
 * Clears login attempts for an IP (called on successful login)
 * 
 * @param string $ip The IP address
 */
function clearLoginAttempts($ip) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

/**
 * ============================================
 * SESSION MANAGEMENT
 * ============================================
 */

/**
 * Checks if user is logged in and session is valid
 * Handles session timeout (15 minutes of inactivity)
 * 
 * @return bool True if logged in and session valid
 */
function isLoggedIn() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Check for remember me cookie
        if (checkRememberMeCookie()) {
            return true;
        }
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        
        if ($inactive > SESSION_TIMEOUT) {
            // Session expired - log out user
            logoutUser();
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Checks if the logged-in user is an admin
 * 
 * @return bool True if admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Requires user to be logged in, redirects to login if not
 * 
 * @param string $redirect URL to redirect to after login
 */
function requireLogin($redirect = null) {
    if (!isLoggedIn()) {
        if ($redirect) {
            $_SESSION['redirect_after_login'] = $redirect;
        }
        setFlashMessage('error', 'Please log in to access this page.');
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Requires user to be an admin, redirects if not
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied. Admin privileges required.');
        redirect(SITE_URL . '/index.php');
    }
}

/**
 * Logs out the current user
 */
function logoutUser() {
    // Log the logout action before destroying session
    if (isset($_SESSION['user_id'])) {
        logAction($_SESSION['user_id'], 'User logged out', 'auth');
    }
    
    // Clear remember me cookie
    clearRememberMeCookie();
    
    // Destroy session
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * ============================================
 * REMEMBER ME FUNCTIONALITY
 * ============================================
 */

/**
 * Sets a secure remember me cookie
 * 
 * @param int $userId The user ID
 */
function setRememberMeCookie($userId) {
    $pdo = getDBConnection();
    
    // Generate a secure token
    $token = bin2hex(random_bytes(32));
    $hashedToken = password_hash($token, PASSWORD_DEFAULT);
    
    // Store hashed token in database
    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt->execute([$hashedToken, $userId]);
    
    // Set cookie with user ID and token
    $cookieValue = $userId . ':' . $token;
    $expiry = time() + (REMEMBER_ME_DAYS * 24 * 60 * 60);
    
    setcookie('remember_me', $cookieValue, [
        'expires' => $expiry,
        'path' => '/',
        'httponly' => true,
        'secure' => false,  // Set to true in production with HTTPS
        'samesite' => 'Lax'
    ]);
}

/**
 * Checks and validates remember me cookie
 * 
 * @return bool True if valid cookie found and user logged in
 */
function checkRememberMeCookie() {
    if (!isset($_COOKIE['remember_me'])) {
        return false;
    }
    
    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) !== 2) {
        return false;
    }
    
    list($userId, $token) = $parts;
    $pdo = getDBConnection();
    
    // Get user with remember token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND remember_token IS NOT NULL");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($token, $user['remember_token'])) {
        // Valid token - log user in
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['logged_in'] = true;
        
        // Refresh the token
        setRememberMeCookie($user['id']);
        
        return true;
    }
    
    // Invalid token - clear cookie
    clearRememberMeCookie();
    return false;
}

/**
 * Clears the remember me cookie
 */
function clearRememberMeCookie() {
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true
        ]);
    }
    
    // Clear token from database if user is known
    if (isset($_SESSION['user_id'])) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
}

/**
 * ============================================
 * EMAIL VERIFICATION
 * ============================================
 */

/**
 * Verifies a user's email with the provided token
 * 
 * @param string $token The verification token
 * @return array Result with success status and message
 */
function verifyEmail($token) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, name, email FROM users 
        WHERE token = ? AND token_expire > NOW() AND is_verified = 0
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Mark user as verified
        $stmt = $pdo->prepare("
            UPDATE users SET is_verified = 1, token = NULL, token_expire = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        logAction($user['id'], 'Email verified', 'auth');
        
        return [
            'success' => true,
            'message' => 'Email verified successfully! You can now log in.'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Invalid or expired verification link.'
    ];
}

/**
 * ============================================
 * PASSWORD RESET
 * ============================================
 */

/**
 * Initiates password reset process
 * 
 * @param string $email User's email
 * @return array Result with success status
 */
function initiatePasswordReset($email) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Always return success to prevent email enumeration
    if (!$user) {
        return [
            'success' => true,
            'message' => 'If an account exists with this email, you will receive a password reset link.'
        ];
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $tokenExpire = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $pdo->prepare("UPDATE users SET token = ?, token_expire = ? WHERE id = ?");
    $stmt->execute([$token, $tokenExpire, $user['id']]);
    
    // Send reset email
    sendPasswordResetEmail($email, $user['name'], $token);
    
    logAction($user['id'], 'Password reset requested', 'auth');
    
    return [
        'success' => true,
        'message' => 'If an account exists with this email, you will receive a password reset link.'
    ];
}

/**
 * Resets password with token
 * 
 * @param string $token Reset token
 * @param string $newPassword New password
 * @return array Result with success status
 */
function resetPassword($token, $newPassword) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT id FROM users 
        WHERE token = ? AND token_expire > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid or expired reset link.'
        ];
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password and clear token
    $stmt = $pdo->prepare("
        UPDATE users SET password = ?, token = NULL, token_expire = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$hashedPassword, $user['id']]);
    
    logAction($user['id'], 'Password reset completed', 'auth');
    
    return [
        'success' => true,
        'message' => 'Password reset successful! You can now log in with your new password.'
    ];
}

/**
 * ============================================
 * EMAIL FUNCTIONS (Simulated for Demo)
 * ============================================
 */

/**
 * Sends verification email (simulated)
 */
function sendVerificationEmail($email, $name, $token) {
    $verifyLink = SITE_URL . "/verify.php?token=" . $token;
    
    $subject = "Verify your CineMaster account";
    $body = "Hello {$name},\n\n";
    $body .= "Thank you for registering at CineMaster!\n\n";
    $body .= "Please click the link below to verify your email:\n";
    $body .= $verifyLink . "\n\n";
    $body .= "This link will expire in 24 hours.\n\n";
    $body .= "Best regards,\nCineMaster Team";
    
    if (EMAIL_SIMULATION) {
        // Log email to file for demo
        logEmail($email, $subject, $body);
    } else {
        // Use PHPMailer for real email sending
        // sendRealEmail($email, $name, $subject, $body);
    }
}

/**
 * Sends password reset email (simulated)
 */
function sendPasswordResetEmail($email, $name, $token) {
    $resetLink = SITE_URL . "/reset_password.php?token=" . $token;
    
    $subject = "Reset your CineMaster password";
    $body = "Hello {$name},\n\n";
    $body .= "You requested to reset your password.\n\n";
    $body .= "Click the link below to set a new password:\n";
    $body .= $resetLink . "\n\n";
    $body .= "This link will expire in 1 hour.\n";
    $body .= "If you didn't request this, please ignore this email.\n\n";
    $body .= "Best regards,\nCineMaster Team";
    
    if (EMAIL_SIMULATION) {
        logEmail($email, $subject, $body);
    }
}

/**
 * Logs email to file (for demo purposes)
 */
function logEmail($to, $subject, $body) {
    $logDir = dirname(EMAIL_LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $log = "\n========================================\n";
    $log .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $log .= "To: {$to}\n";
    $log .= "Subject: {$subject}\n";
    $log .= "Body:\n{$body}\n";
    $log .= "========================================\n";
    
    file_put_contents(EMAIL_LOG_FILE, $log, FILE_APPEND);
}

/**
 * ============================================
 * LOGGING FUNCTION
 * ============================================
 */

/**
 * Logs an action to the database
 * 
 * @param int|null $userId User ID (null for guest actions)
 * @param string $action Description of the action
 * @param string $type Type of action
 * @param string|null $details Additional details (JSON)
 */
function logAction($userId, $action, $type = 'auth', $details = null) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO logs (user_id, action, action_type, ip_address, user_agent, details, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $type,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $details
        ]);
    } catch (PDOException $e) {
        error_log("Logging Error: " . $e->getMessage());
    }
}

/**
 * Gets current logged-in user's data
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, name, email, role, profile_photo, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetch();
}
?>
