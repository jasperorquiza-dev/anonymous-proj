<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../admin/admin_functions.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    if (deleteMessage($message_id)) {
        echo json_encode(['status' => 'success', 'message' => 'Message deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete message. You may not have admin privileges.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}