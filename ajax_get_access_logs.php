<?php
// ajax_get_access_logs.php - Get access logs for master dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'master_auth.php';
require_once 'admin_functions.php';

if (!isMaster()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $logs = getAccessLogs(100);
    echo json_encode(['status' => 'success', 'logs' => $logs]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
