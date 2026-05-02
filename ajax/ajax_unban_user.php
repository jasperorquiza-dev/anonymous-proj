<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ... rest of your existing AJAX code
session_start();
require_once '../admin/admin_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    if (unbanUser($user_id)) {
        echo json_encode(['status' => 'success', 'message' => 'User unbanned successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to unban user']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
