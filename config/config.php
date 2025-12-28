<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Site Configuration
define('SITE_NAME', 'LearnX');
define('SITE_URL', 'http://localhost/LMS/');

// Include database connection
require_once __DIR__ . '/database.php';

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Helper function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

// Helper function to sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Helper function to format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Helper function to format datetime
function formatDateTime($datetime) {
    return date('d M Y h:i A', strtotime($datetime));
}

/**
 * Generate random secure password
 * @param int $length Password length (default 8)
 * @return string Generated password
 */
function generateRandomPassword($length = 8) {
    return bin2hex(random_bytes($length / 2));
}

?>



