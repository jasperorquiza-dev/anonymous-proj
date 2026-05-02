<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../master/master_auth.php';
require_once '../admin/admin_functions.php';
header('Content-Type: application/json');
if (!isMaster()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
try {
    $users = getAllUsers();
    echo json_encode(['status' => 'success', 'users' => $users]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>