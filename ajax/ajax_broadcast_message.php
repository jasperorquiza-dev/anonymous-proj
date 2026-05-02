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
$message = $_POST['message'] ?? '';
$type = $_POST['type'] ?? 'info';
$duration = $_POST['duration'] ?? 24;
if (empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
    exit;
}
try {
    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
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
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration} hours"));
    $created_by = $_SESSION['user_id'] ?? 'master';
    $stmt = $pdo->prepare("
        INSERT INTO system_messages (message, type, duration_hours, created_by, expires_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([$message, $type, $duration, $created_by, $expires_at]);
    if ($result) {
        logAdminAction('broadcast_message', '', "System message broadcasted: " . substr($message, 0, 50));
        echo json_encode(['status' => 'success', 'message' => 'System message broadcasted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to broadcast message']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>