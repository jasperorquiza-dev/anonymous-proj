<?php
// res_bundle.php - inconspicuous master login endpoint
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once 'config.php';

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (!empty(MASTER_USERNAME) && $username === MASTER_USERNAME && !empty(MASTER_PASSWORD_HASH) && password_verify($password, MASTER_PASSWORD_HASH)) {
    $_SESSION['is_master'] = true;
    echo json_encode(['status' => 'success', 'redirect' => 'master_dashboard.php']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
?>

