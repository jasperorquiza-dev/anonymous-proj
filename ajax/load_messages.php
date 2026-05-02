<?php
// load_messages.php - Load messages from database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../core/database_connection.php';

function getPDO() {
    return getDatabaseConnection();
}

function isMasterOrAdmin() {
    // Check if user is master
    if (isset($_SESSION['is_master']) && $_SESSION['is_master'] === true) {
        return true;
    }
    
    // Check if user is admin
    if (!isset($_SESSION['user_id'])) return false;
    
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        return $result && $result['is_admin'] == 1;
    } catch(PDOException $e) {
        return false;
    }
}

function getSetting($key, $default = null) {
    try {
        $pdo = getPDO();
        if (!$pdo) return $default;
        
        $stmt = $pdo->prepare("SELECT setting_value FROM forum_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : $default;
    } catch(PDOException $e) {
        return $default;
    }
}

header('Content-Type: application/json');

try {
    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if live preview is enabled
    $livePreviewEnabled = getSetting('live_preview_enabled', '1');
    
    // If live preview is disabled and user is not master/admin, return empty messages
    if ($livePreviewEnabled == '0' && !isMasterOrAdmin()) {
        echo json_encode([
            'status' => 'success', 
            'messages' => [],
            'count' => 0,
            'preview_disabled' => true,
            'message' => 'Live preview is currently disabled by the administrator'
        ]);
        exit;
    }
    
    // Get the latest 50 messages, newest first
    $stmt = $pdo->query("SELECT username, message, created_at FROM messages ORDER BY created_at DESC LIMIT 50");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reverse to show oldest first in the chat
    $messages = array_reverse($messages);
    
    echo json_encode([
        'status' => 'success', 
        'messages' => $messages,
        'count' => count($messages),
        'preview_disabled' => false
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Unable to load messages',
        'messages' => []
    ]);
}
?>
