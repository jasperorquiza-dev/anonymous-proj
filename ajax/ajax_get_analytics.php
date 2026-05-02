<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../master/master_auth.php';
require_once '../admin/admin_functions.php';
if (!isMaster()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
try {
    $analytics = getAnalytics();
    echo json_encode(['status' => 'success', 'analytics' => $analytics]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>