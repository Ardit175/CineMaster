<?php
/**
 * ============================================
 * CineMaster - Configuration File
 * ============================================
 * This file contains all configuration settings for the application
 * including database credentials, site settings, and security configs.
 * 
 * IMPORTANT: In production, move sensitive data to environment variables
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings BEFORE starting session
    ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1);     // Only use cookies for sessions
    ini_set('session.cookie_secure', 0);        // Set to 1 in production with HTTPS
    
    session_start();
}

// ============================================
// ERROR REPORTING (Disable in Production)
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// TIMEZONE SETTING
// ============================================
date_default_timezone_set('America/New_York');  // Change to your timezone

// ============================================
// DATABASE CONFIGURATION
// ============================================
// Docker configuration (use 'db' as host when running in Docker)
// For XAMPP, change DB_HOST to 'localhost' and DB_PASS to ''
define('DB_HOST', 'db');                 // Database host ('db' for Docker, 'localhost' for XAMPP)
define('DB_NAME', 'cinemaster');         // Database name
define('DB_USER', 'root');               // Database username
define('DB_PASS', 'root');               // Database password ('root' for Docker, '' for XAMPP)
define('DB_CHARSET', 'utf8mb4');         // Character set for proper Unicode support

// ============================================
// SITE CONFIGURATION
// ============================================
define('SITE_NAME', 'CineMaster');
define('SITE_URL', 'http://localhost:8888');  // Base URL (port 8888 for Docker)
define('SITE_EMAIL', 'noreply@cinemaster.com');

// ============================================
// PATH CONSTANTS
// ============================================
define('ROOT_PATH', dirname(__DIR__) . '/');           // Root directory
define('CONFIG_PATH', ROOT_PATH . 'config/');          // Config directory
define('INCLUDES_PATH', ROOT_PATH . 'includes/');      // Includes directory
define('ASSETS_PATH', ROOT_PATH . 'assets/');          // Assets directory
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');        // Uploads directory
define('ADMIN_PATH', ROOT_PATH . 'admin/');            // Admin directory

// URL paths for frontend use
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', SITE_URL . '/uploads');

// ============================================
// SECURITY CONFIGURATION
// ============================================
define('SESSION_TIMEOUT', 900);          // Session timeout in seconds (15 minutes)
define('MAX_LOGIN_ATTEMPTS', 7);         // Maximum failed login attempts
define('LOCKOUT_TIME', 1800);            // Lockout duration in seconds (30 minutes)
define('REMEMBER_ME_DAYS', 30);          // Remember me cookie duration in days

// CSRF Token name
define('CSRF_TOKEN_NAME', 'csrf_token');

// ============================================
// STRIPE CONFIGURATION (TEST MODE)
// ============================================
// Get your test keys from: https://dashboard.stripe.com/test/apikeys
define('STRIPE_PUBLIC_KEY', 'pk_test_your_public_key_here');
define('STRIPE_SECRET_KEY', 'sk_test_your_secret_key_here');
define('STRIPE_CURRENCY', 'usd');

// ============================================
// EMAIL CONFIGURATION (PHPMailer)
// ============================================
define('MAIL_HOST', 'smtp.gmail.com');   // SMTP server
define('MAIL_PORT', 587);                // SMTP port (587 for TLS)
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM_NAME', 'CineMaster');

// For demo purposes, we'll simulate emails by logging to a file
define('EMAIL_SIMULATION', true);        // Set to false in production with real SMTP
define('EMAIL_LOG_FILE', ROOT_PATH . 'logs/email_log.txt');

// ============================================
// UPLOAD CONFIGURATION
// ============================================
define('MAX_FILE_SIZE', 5 * 1024 * 1024);  // 5MB max file size
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ============================================
// PAGINATION
// ============================================
define('ITEMS_PER_PAGE', 10);

// ============================================
// DATABASE CONNECTION (PDO)
// ============================================
/**
 * Creates and returns a PDO database connection
 * Uses prepared statements to prevent SQL injection
 * 
 * @return PDO Database connection object
 */
function getDBConnection() {
    static $pdo = null;  // Static variable to reuse connection
    
    if ($pdo === null) {
        try {
            // Create DSN (Data Source Name)
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            // PDO options for security and performance
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Return associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                     // Use real prepared statements
                PDO::ATTR_PERSISTENT         => false                       // Don't use persistent connections
            ];
            
            // Create PDO instance
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log error and display user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

// ============================================
// HELPER FUNCTION: Generate CSRF Token
// ============================================
/**
 * Generates a CSRF token and stores it in the session
 * 
 * @return string The generated CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validates the CSRF token from a form submission
 * 
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// ============================================
// HELPER FUNCTION: Sanitize Input
// ============================================
/**
 * Sanitizes user input to prevent XSS attacks
 * 
 * @param string $data The input data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);                    // Remove whitespace
    $data = stripslashes($data);            // Remove backslashes
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');  // Convert special characters
    return $data;
}

// ============================================
// HELPER FUNCTION: Redirect
// ============================================
/**
 * Redirects to a specified URL
 * 
 * @param string $url The URL to redirect to
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// ============================================
// HELPER FUNCTION: Set Flash Message
// ============================================
/**
 * Sets a flash message to display on the next page load
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message The message to display
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Gets and clears the flash message
 * 
 * @return array|null The flash message or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// ============================================
// HELPER FUNCTION: Format Price
// ============================================
/**
 * Formats a price for display
 * 
 * @param float $price The price to format
 * @return string Formatted price string
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// ============================================
// HELPER FUNCTION: Format Date
// ============================================
/**
 * Formats a date string for display
 * 
 * @param string $date The date to format
 * @param string $format The desired format
 * @return string Formatted date string
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Formats a datetime for display
 * 
 * @param string $datetime The datetime to format
 * @return string Formatted datetime string
 */
function formatDateTime($datetime) {
    return date('M d, Y - h:i A', strtotime($datetime));
}

// ============================================
// HELPER FUNCTION: Get Client IP
// ============================================
/**
 * Gets the client's IP address
 * 
 * @return string The client's IP address
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    return $ip;
}

// ============================================
// Include Required Files
// ============================================
require_once INCLUDES_PATH . 'auth_functions.php';
require_once INCLUDES_PATH . 'helper_functions.php';
?>
