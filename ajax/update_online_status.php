<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../core/database_connection.php';
header('Content-Type: application/json');
function getPDO() {
    return getDatabaseConnection();
}
try {
    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS online_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            username VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            last_activity DATETIME NOT NULL,
            UNIQUE KEY unique_user (user_id)
        )
    ");
    $pdo->exec("DELETE FROM online_users WHERE last_activity < NOW() - INTERVAL 5 MINUTE");
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['user_username'] ?? 'Unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $stmt = $pdo->prepare("
            INSERT INTO online_users (user_id, username, ip_address, user_agent, last_activity)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                last_activity = NOW(),
                username = VALUES(username),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
        ");
        $stmt->execute([$user_id, $username, $ip, $user_agent]);
    } else {
    }
    echo json_encode(['status' => 'success']);
} catch(Exception $e) {
    error_log("Online status error: " . $e->getMessage());
    echo json_encode(['status' => 'success']);
}
?>