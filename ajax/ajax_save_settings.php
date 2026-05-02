<?php
// ajax_save_settings.php - Save forum settings
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
    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $settings = [
        'forum_name' => $_POST['forum_name'] ?? '',
        'forum_description' => $_POST['forum_description'] ?? '',
        'maintenance_mode' => $_POST['maintenance_mode'] ?? '0',
        'max_message_length' => $_POST['max_message_length'] ?? '500',
        'allow_guest_posts' => $_POST['allow_guest_posts'] ?? '1',
        'live_preview_enabled' => $_POST['live_preview_enabled'] ?? '1',
        'forum_title' => $_POST['forum_title'] ?? 'ICCT Forum',
        'forum_logo' => $_POST['forum_logo'] ?? 'assets/img/icct.jpg'
    ];

    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO forum_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$key, $value]);
    }

    logAdminAction('save_settings', '', 'Forum settings updated');
    echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
