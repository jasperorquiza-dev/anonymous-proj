<?php
// ajax_dismiss_system_message.php - Dismiss a system message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../admin/admin_functions.php';

$message_id = $_POST['message_id'] ?? '';

if (empty($message_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Message ID required']);
    exit;
}

try {
    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Create system_messages table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
            duration_hours INT DEFAULT 24,
            is_active TINYINT(1) DEFAULT 1,
            created_by VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL
        )
    ");

    $stmt = $pdo->prepare("UPDATE system_messages SET is_active = 0 WHERE id = ?");
    $result = $stmt->execute([$message_id]);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Message dismissed']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to dismiss message']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
