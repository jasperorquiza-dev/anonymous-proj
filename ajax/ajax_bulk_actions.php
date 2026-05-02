<?php
// ajax_bulk_actions.php - Handle bulk actions for users
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
$user_ids = json_decode($_POST['user_ids'] ?? '[]', true);
$message_ids = json_decode($_POST['message_ids'] ?? '[]', true);

// Check if this is a message action or user action
if (in_array($action, ['pin_messages', 'clear_spam'])) {
    // Handle message actions
    if ($action === 'pin_messages' && !empty($message_ids)) {
        handleMessageActions($action, $message_ids);
        exit;
    } elseif ($action === 'clear_spam') {
        handleClearSpam();
        exit;
    }
}

if (empty($user_ids) || !is_array($user_ids)) {
    echo json_encode(['status' => 'error', 'message' => 'No users selected']);
    exit;
}

try {
    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $success_count = 0;
    $total_count = count($user_ids);

    switch ($action) {
        case 'mute':
            foreach ($user_ids as $user_id) {
                if (muteUser($user_id, 24)) {
                    $success_count++;
                    logAdminAction('bulk_mute', $user_id, 'User muted in bulk action');
                }
            }
            break;

        case 'ban':
            foreach ($user_ids as $user_id) {
                if (banUser($user_id)) {
                    $success_count++;
                    logAdminAction('bulk_ban', $user_id, 'User banned in bulk action');
                }
            }
            break;

        case 'unmute':
            foreach ($user_ids as $user_id) {
                if (unmuteUser($user_id)) {
                    $success_count++;
                    logAdminAction('bulk_unmute', $user_id, 'User unmuted in bulk action');
                }
            }
            break;

        case 'unban':
            foreach ($user_ids as $user_id) {
                if (unbanUser($user_id)) {
                    $success_count++;
                    logAdminAction('bulk_unban', $user_id, 'User unbanned in bulk action');
                }
            }
            break;

        case 'delete':
            foreach ($user_ids as $user_id) {
                if (deleteUser($user_id)) {
                    $success_count++;
                    logAdminAction('bulk_delete', $user_id, 'User deleted in bulk action');
                }
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            exit;
    }

    echo json_encode([
        'status' => 'success', 
        'message' => "Bulk action completed: {$success_count}/{$total_count} users processed"
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Function to handle message actions
function handleMessageActions($action, $message_ids) {
    try {
        require_once '../core/config.php';
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $success_count = 0;
        
        if ($action === 'pin_messages') {
            $stmt = $pdo->prepare("UPDATE messages SET is_pinned = 1 WHERE id = ?");
            foreach ($message_ids as $message_id) {
                if ($stmt->execute([$message_id])) {
                    if ($stmt->rowCount() > 0) {
                        $success_count++;
                    }
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => "Successfully pinned $success_count message(s)"
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to handle clear spam
function handleClearSpam() {
    try {
        require_once '../core/config.php';
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Delete messages marked as spam (you might need to adjust this based on your spam marking system)
        $stmt = $pdo->prepare("DELETE FROM messages WHERE is_spam = 1 OR message LIKE '%spam%' OR message LIKE '%viagra%' OR message LIKE '%casino%'");
        $stmt->execute();
        $deleted_count = $stmt->rowCount();
        
        echo json_encode([
            'status' => 'success',
            'message' => "Successfully cleared $deleted_count spam message(s)"
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
