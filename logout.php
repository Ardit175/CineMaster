<?php
/**
 * ============================================
 * CineMaster - Logout
 * ============================================
 * Handles user logout and session destruction
 */

require_once 'config/config.php';

// Log out the user
logoutUser();

// Set flash message
setFlashMessage('success', 'You have been logged out successfully.');

// Redirect to home page
redirect(SITE_URL . '/index.php');
?>
