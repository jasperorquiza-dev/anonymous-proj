<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../admin/admin_functions.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : null;
    if (banUser($user_id, $duration)) {
        echo json_encode(['status' => 'success', 'message' => 'User banned successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to ban user']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}