<?php
require_once 'config/config.php';

// If user is logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'teacher':
            redirect('teacher/dashboard.php');
            break;
        case 'student':
            redirect('student/dashboard.php');
            break;
        case 'parent':
            redirect('parent/dashboard.php');
            break;
        case 'librarian':
            redirect('librarian/dashboard.php');
            break;
        default:
            redirect('login.php');
    }
} else {
    // Show landing page for non-logged-in users
    redirect('home.php');
}
?>


