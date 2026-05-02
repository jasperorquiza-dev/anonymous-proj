<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../core/config.php';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
if (!empty(MASTER_USERNAME) && $username === MASTER_USERNAME && !empty(MASTER_PASSWORD_HASH) && password_verify($password, MASTER_PASSWORD_HASH)) {
    $_SESSION['is_master'] = true;
    $_SESSION['user_id'] = 'master';
    $_SESSION['user_username'] = MASTER_USERNAME;
    $_SESSION['user_name'] = 'Master';
    header('Location: master_dashboard_enhanced.php');
    exit;
} else {
    header('Location: ../pages/welcome.php');
    exit;
}
?>