<?php
// ajax_get_activity.php - Get recent activity for master dashboard
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
    $activity = getRecentActivity(50);
    echo json_encode(['status' => 'success', 'activity' => $activity]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
