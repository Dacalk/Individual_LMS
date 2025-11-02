<?php
/**
 * Password Generation Helper Functions
 * Simple password generator without email sending
 */

/**
 * Generate random secure password
 * @param int $length Password length (default 8)
 * @return string Generated password
 */
function generateRandomPassword($length = 8) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Dummy function for compatibility (no email sent)
 * Password will be shown on screen instead
 */
function sendCredentialEmail($to_email, $subject, $body) {
    // Email functionality removed
    // Password will be displayed on screen after user creation
    return true;
}

/**
 * Generate printable credential card (no email)
 */
function generateCredentialEmailHTML($name, $username, $password, $role) {
    // Dummy function for compatibility - no email sent
    return '';
}
?>

