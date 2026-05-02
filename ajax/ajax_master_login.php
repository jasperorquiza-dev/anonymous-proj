<?php
// ajax_master_login.php - Handle master login during maintenance mode
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../core/config.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Check if it's the master account
if (!empty(MASTER_USERNAME) && $username === MASTER_USERNAME && !empty(MASTER_PASSWORD_HASH) && password_verify($password, MASTER_PASSWORD_HASH)) {
    // Set master session
    $_SESSION['is_master'] = true;
    $_SESSION['user_id'] = 'master';
    $_SESSION['user_username'] = MASTER_USERNAME;
    $_SESSION['user_name'] = 'Master';
    
    // Redirect to master dashboard
    header('Location: master_dashboard_enhanced.php');
    exit;
} else {
    // Invalid credentials - redirect back to maintenance page
    header('Location: ../pages/welcome.php');
    exit;
}
?>
