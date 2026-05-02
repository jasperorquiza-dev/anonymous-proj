<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../core/config.php';
require_once '../admin/admin_functions.php';
require_once '../master/master_auth.php';
header('Content-Type: application/json');
if (!isAdmin() && !isMaster()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_ids'])) {
    $message_ids = json_decode($_POST['message_ids'], true);
    if (!is_array($message_ids)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid message IDs format'
        ]);
        exit();
    }
    $deleted_count = 0;
    $errors = [];
    try {
        $pdo = getPDO();
        if (!$pdo) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database connection failed'
            ]);
            exit();
        }
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        foreach ($message_ids as $message_id) {
            $message_id = intval($message_id);
            if ($message_id > 0) {
                if ($stmt->execute([$message_id])) {
                    if ($stmt->rowCount() > 0) {
                        $deleted_count++;
                    } else {
                        $errors[] = "Message ID $message_id not found";
                    }
                } else {
                    $errors[] = "Failed to delete message ID: $message_id";
                }
            }
        }
        if ($deleted_count > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => "Successfully deleted $deleted_count message(s)",
                'deleted' => $deleted_count
            ]);
            exit();
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No messages were deleted. Messages may not exist.',
                'errors' => $errors
            ]);
            exit();
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit();
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request - no message IDs provided'
    ]);
    exit();
}
?>