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
$action = $_POST['action'] ?? '';
try {
    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    switch ($action) {
        case 'promote_admin':
            $user_id = $_POST['user_id'] ?? '';
            if (setAdminStatus($user_id, 1)) {
                logAdminAction('promote_admin', $user_id, 'User promoted to admin');
                echo json_encode(['status' => 'success', 'message' => 'User promoted to admin']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to promote user']);
            }
            break;
        case 'demote_admin':
            $user_id = $_POST['user_id'] ?? '';
            if (setAdminStatus($user_id, 0)) {
                logAdminAction('demote_admin', $user_id, 'User demoted from admin');
                echo json_encode(['status' => 'success', 'message' => 'User demoted from admin']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to demote user']);
            }
            break;
        case 'mute_user':
            $user_id = $_POST['user_id'] ?? '';
            $duration = $_POST['duration'] ?? 24;
            if (muteUser($user_id, $duration)) {
                logAdminAction('mute_user', $user_id, "User muted for {$duration} hours");
                echo json_encode(['status' => 'success', 'message' => 'User muted']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to mute user']);
            }
            break;
        case 'unmute_user':
            $user_id = $_POST['user_id'] ?? '';
            if (unmuteUser($user_id)) {
                logAdminAction('unmute_user', $user_id, 'User unmuted');
                echo json_encode(['status' => 'success', 'message' => 'User unmuted']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to unmute user']);
            }
            break;
        case 'ban_user':
            $user_id = $_POST['user_id'] ?? '';
            $duration = $_POST['duration'] ?? null;
            if (banUser($user_id, $duration)) {
                logAdminAction('ban_user', $user_id, $duration ? "User banned for {$duration} hours" : 'User permanently banned');
                echo json_encode(['status' => 'success', 'message' => 'User banned']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to ban user']);
            }
            break;
        case 'unban_user':
            $user_id = $_POST['user_id'] ?? '';
            if (unbanUser($user_id)) {
                logAdminAction('unban_user', $user_id, 'User unbanned');
                echo json_encode(['status' => 'success', 'message' => 'User unbanned']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to unban user']);
            }
            break;
        case 'delete_user':
            $user_id = $_POST['user_id'] ?? '';
            if (deleteUser($user_id)) {
                logAdminAction('delete_user', $user_id, 'User deleted');
                echo json_encode(['status' => 'success', 'message' => 'User deleted']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
            }
            break;
        case 'reset_password':
            $user_id = $_POST['user_id'] ?? '';
            $new_password = generateRandomPassword();
            if (resetUserPassword($user_id, $new_password)) {
                logAdminAction('reset_password', $user_id, 'Password reset');
                echo json_encode(['status' => 'success', 'message' => 'Password reset. New password: ' . $new_password]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to reset password']);
            }
            break;
        case 'pin_message':
            $message_id = $_POST['message_id'] ?? '';
            if (pinMessage($message_id)) {
                logAdminAction('pin_message', $message_id, 'Message pinned');
                echo json_encode(['status' => 'success', 'message' => 'Message pinned']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to pin message']);
            }
            break;
        case 'unpin_message':
            $message_id = $_POST['message_id'] ?? '';
            if (unpinMessage($message_id)) {
                logAdminAction('unpin_message', $message_id, 'Message unpinned');
                echo json_encode(['status' => 'success', 'message' => 'Message unpinned']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to unpin message']);
            }
            break;
        case 'delete_message':
            $message_id = $_POST['message_id'] ?? '';
            if (softDeleteMessage($message_id)) {
                logAdminAction('delete_message', $message_id, 'Message deleted');
                echo json_encode(['status' => 'success', 'message' => 'Message deleted']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete message']);
            }
            break;
        case 'restore_message':
            $message_id = $_POST['message_id'] ?? '';
            if (restoreMessage($message_id)) {
                logAdminAction('restore_message', $message_id, 'Message restored');
                echo json_encode(['status' => 'success', 'message' => 'Message restored']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to restore message']);
            }
            break;
        case 'clear_spam':
            if (clearSpamMessages()) {
                logAdminAction('clear_spam', '', 'Spam messages cleared');
                echo json_encode(['status' => 'success', 'message' => 'Spam messages cleared']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to clear spam']);
            }
            break;
        case 'resolve_report':
            $report_id = $_POST['report_id'] ?? '';
            if (resolveReport($report_id)) {
                logAdminAction('resolve_report', $report_id, 'Report resolved');
                echo json_encode(['status' => 'success', 'message' => 'Report resolved']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to resolve report']);
            }
            break;
        case 'dismiss_report':
            $report_id = $_POST['report_id'] ?? '';
            if (dismissReport($report_id)) {
                logAdminAction('dismiss_report', $report_id, 'Report dismissed');
                echo json_encode(['status' => 'success', 'message' => 'Report dismissed']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to dismiss report']);
            }
            break;
        case 'ban_ip':
            $ip_address = $_POST['ip_address'] ?? '';
            if (banIPAddress($ip_address)) {
                logAdminAction('ban_ip', $ip_address, 'IP address banned');
                echo json_encode(['status' => 'success', 'message' => 'IP address banned']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to ban IP address']);
            }
            break;
        case 'create_backup':
            $type = $_POST['type'] ?? 'full';
            $backup_file = createDatabaseBackup($type);
            if ($backup_file) {
                logAdminAction('create_backup', $type, 'Database backup created');
                echo json_encode(['status' => 'success', 'message' => 'Backup created: ' . $backup_file]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create backup']);
            }
            break;
        case 'enable_maintenance':
            $message = $_POST['message'] ?? 'Forum is under maintenance. Please check back later.';
            if (enableMaintenanceMode($message)) {
                logAdminAction('enable_maintenance', '', 'Maintenance mode enabled');
                echo json_encode(['status' => 'success', 'message' => 'Maintenance mode enabled']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to enable maintenance mode']);
            }
            break;
        case 'disable_maintenance':
            if (disableMaintenanceMode()) {
                logAdminAction('disable_maintenance', '', 'Maintenance mode disabled');
                echo json_encode(['status' => 'success', 'message' => 'Maintenance mode disabled']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to disable maintenance mode']);
            }
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>