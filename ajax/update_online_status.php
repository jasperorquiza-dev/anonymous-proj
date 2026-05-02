<?php
// update_online_status.php - Track online users

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../core/database_connection.php';

// Set content type
header('Content-Type: application/json');

// Database connection function
function getPDO() {
    return getDatabaseConnection();
}

try {
    $pdo = getPDO();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Create online_users table if it doesn't exist
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
    
    // Clean up old entries (5 minutes inactive)
    $pdo->exec("DELETE FROM online_users WHERE last_activity < NOW() - INTERVAL 5 MINUTE");
    
    if (isset($_SESSION['user_id'])) {
        // Logged-in user
        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['user_username'] ?? 'Unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Update or insert online status
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
        // Guests are not counted in logged-in online metrics
        // Optionally, comment out to avoid populating table with guests
    }
    
    echo json_encode(['status' => 'success']);
    
} catch(Exception $e) {
    // Return success even if there's an error to prevent breaking the UI
    error_log("Online status error: " . $e->getMessage());
    echo json_encode(['status' => 'success']); // Return success to prevent UI issues
}
?>
