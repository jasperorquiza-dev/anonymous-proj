<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../master/master_auth.php';
require_once '../core/database_connection.php';
function getPDO() {
    return getDatabaseConnection();
}
function isAdmin() {
    if (isMaster()) return true;
    if (!isset($_SESSION['user_id'])) return false;
    $user_id = $_SESSION['user_id'];
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result && $result['is_admin'] == 1;
    } catch(PDOException $e) {
        return false;
    }
}
function isPrivileged() {
    return isMaster() || isAdmin();
}
function deleteMessage($message_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        return $stmt->execute([$message_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function getAllUsers() {
    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->query("
            SELECT u.id, u.name, u.username, u.age, u.is_admin, u.is_banned, u.is_muted,
                   u.banned_until, u.muted_until, u.created_at,
                   MAX(ou.last_activity) as last_activity,
                   CASE WHEN MAX(ou.last_activity) > NOW() - INTERVAL 2 MINUTE THEN 1 ELSE 0 END as is_online
            FROM users u
            LEFT JOIN online_users ou ON u.id = ou.user_id
            GROUP BY u.id, u.name, u.username, u.age, u.is_admin, u.is_banned, u.is_muted,
                     u.banned_until, u.muted_until, u.created_at
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
function getForumStats() {
    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stats = [];
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
        $stats['total_users'] = $stmt->fetch()['total_users'];
        $stmt = $pdo->query("SELECT COUNT(*) as total_messages FROM messages");
        $stats['total_messages'] = $stmt->fetch()['total_messages'];
        $stmt = $pdo->query("SELECT COUNT(*) as banned_users FROM users WHERE is_banned = 1");
        $stats['banned_users'] = $stmt->fetch()['banned_users'];
        $stmt = $pdo->query("SELECT COUNT(*) as muted_users FROM users WHERE is_muted = 1 OR (muted_until IS NOT NULL AND muted_until > NOW())");
        $stats['muted_users'] = $stmt->fetch()['muted_users'];
        return $stats;
    } catch(PDOException $e) {
        return [];
    }
}
function banUser($user_id, $duration_hours = null) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        if ($duration_hours) {
            $banned_until = date('Y-m-d H:i:s', strtotime("+$duration_hours hours"));
            $stmt = $pdo->prepare("UPDATE users SET is_banned = 1, banned_until = ? WHERE id = ?");
            return $stmt->execute([$banned_until, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_banned = 1, banned_until = NULL WHERE id = ?");
            return $stmt->execute([$user_id]);
        }
    } catch(PDOException $e) {
        return false;
    }
}
function unbanUser($user_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("UPDATE users SET is_banned = 0, banned_until = NULL WHERE id = ?");
        return $stmt->execute([$user_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function muteUser($user_id, $duration_hours = 24) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $muted_until = date('Y-m-d H:i:s', strtotime("+$duration_hours hours"));
        $stmt = $pdo->prepare("UPDATE users SET is_muted = 1, muted_until = ? WHERE id = ?");
        return $stmt->execute([$muted_until, $user_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function unmuteUser($user_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->prepare("UPDATE users SET is_muted = 0, muted_until = NULL WHERE id = ?");
        return $stmt->execute([$user_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function deleteUser($user_id) {
    if (!isPrivileged()) return false;
    if (!isMaster() && $_SESSION['user_id'] == $user_id) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        if (!isMaster()) {
            $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $target = $stmt->fetch();
            if ($target && (int)$target['is_admin'] === 1) {
                return false;
            }
        }
        $stmt = $pdo->prepare("DELETE FROM messages WHERE user_id LIKE ?");
        $stmt->execute(["user_" . $user_id . "%"]);
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$user_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function setAdminStatus($user_id, $is_admin) {
    if (!isMaster()) return false;
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id && $is_admin == 0) {
        return false;
    }
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        return $stmt->execute([(int)$is_admin, $user_id]);
    } catch(PDOException $e) {
        return false;
    }
}

function saveSetting($key, $value) {
    if (!isMaster()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("
            INSERT INTO forum_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$key, $value]);
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
function logAdminAction($action, $target_id, $details) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $username = $_SESSION['user_username'] ?? 'Master';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (action, target_id, username, details, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$action, $target_id, $username, $details, $ip_address]);
    } catch(PDOException $e) {
        return false;
    }
}
function resetUserPassword($user_id, $new_password = null) {
    if (!isMaster()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        if (!$new_password) {
            $new_password = generateRandomPassword();
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashed_password, $user_id]);
        if ($result) {
            $_SESSION['temp_password_' . $user_id] = $new_password;
        }
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}
function generateRandomPassword($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}
function pinMessage($message_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_pinned TINYINT(1) DEFAULT 0");
        $stmt = $pdo->prepare("UPDATE messages SET is_pinned = 1 WHERE id = ?");
        return $stmt->execute([$message_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function unpinMessage($message_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("UPDATE messages SET is_pinned = 0 WHERE id = ?");
        return $stmt->execute([$message_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function softDeleteMessage($message_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0");
        $stmt = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$message_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function restoreMessage($message_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("UPDATE messages SET is_deleted = 0 WHERE id = ?");
        return $stmt->execute([$message_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function clearSpamMessages() {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_spam TINYINT(1) DEFAULT 0");
        $stmt = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE is_spam = 1");
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}
function resolveReport($report_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $resolved_by = $_SESSION['user_id'] ?? 'master';
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_at = NOW(), resolved_by = ? WHERE id = ?");
        return $stmt->execute([$resolved_by, $report_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function dismissReport($report_id) {
    if (!isPrivileged()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $resolved_by = $_SESSION['user_id'] ?? 'master';
        $stmt = $pdo->prepare("UPDATE reports SET status = 'dismissed', resolved_at = NOW(), resolved_by = ? WHERE id = ?");
        return $stmt->execute([$resolved_by, $report_id]);
    } catch(PDOException $e) {
        return false;
    }
}
function banIPAddress($ip_address) {
    if (!isMaster()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS banned_ips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) UNIQUE NOT NULL,
                reason TEXT,
                banned_by VARCHAR(50),
                banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $banned_by = $_SESSION['user_id'] ?? 'master';
        $stmt = $pdo->prepare("INSERT INTO banned_ips (ip_address, banned_by) VALUES (?, ?) ON DUPLICATE KEY UPDATE banned_at = NOW()");
        return $stmt->execute([$ip_address, $banned_by]);
    } catch(PDOException $e) {
        return false;
    }
}
function createDatabaseBackup($type = 'full') {
    if (!isMaster()) return false;
    try {
        $backup_dir = 'backups/';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        $filename = 'backup_' . $type . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backup_dir . $filename;
        $pdo = getPDO();
        if (!$pdo) return false;
        $backup_content = "-- Database Backup\n";
        $backup_content .= "-- Type: " . $type . "\n";
        $backup_content .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        if ($type === 'full' || $type === 'users') {
            $stmt = $pdo->query("SELECT * FROM users");
            $backup_content .= "-- Users table\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $backup_content .= "INSERT INTO users VALUES (" . implode(', ', array_map(function($v) { return "'" . addslashes($v) . "'"; }, $row)) . ");\n";
            }
        }
        if ($type === 'full' || $type === 'messages') {
            $stmt = $pdo->query("SELECT * FROM messages");
            $backup_content .= "-- Messages table\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $backup_content .= "INSERT INTO messages VALUES (" . implode(', ', array_map(function($v) { return "'" . addslashes($v) . "'"; }, $row)) . ");\n";
            }
        }
        file_put_contents($filepath, $backup_content);
        return $filename;
    } catch(Exception $e) {
        return false;
    }
}
function enableMaintenanceMode($message = 'Forum is under maintenance. Please check back later.') {
    if (!isMaster()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("
            INSERT INTO forum_settings (setting_key, setting_value)
            VALUES ('maintenance_mode', '1'), ('maintenance_message', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $result = $stmt->execute([$message]);
        try {
            $pdo->exec("DELETE FROM online_users");
        } catch (Throwable $t) {
        }
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}
function disableMaintenanceMode() {
    if (!isMaster()) return false;
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("UPDATE forum_settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode'");
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}
function getAnalytics() {
    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $analytics = [];
        $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as daily_users FROM online_users WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $analytics['daily_users'] = $stmt->fetch()['daily_users'] ?? 0;
        $stmt = $pdo->query("SELECT COUNT(*) as daily_messages FROM messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $analytics['daily_messages'] = $stmt->fetch()['daily_messages'] ?? 0;
        $analytics['avg_session_time'] = '15m';
        $analytics['bounce_rate'] = 25;
        return $analytics;
    } catch(PDOException $e) {
        return [];
    }
}
function getAccessLogs($limit = 100) {
    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->prepare("
            SELECT DISTINCT ip_address, user_agent, last_activity,
                   CASE WHEN ip_address IN (SELECT ip_address FROM banned_ips) THEN 1 ELSE 0 END as is_banned
            FROM online_users
            ORDER BY last_activity DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
function getAllMessages($limit = 100) {
    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->prepare("SELECT * FROM messages ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function isUserBanned($user_id) {
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $stmt = $pdo->prepare("SELECT is_banned, banned_until FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) return false;
        if ($user['is_banned'] == 1 && empty($user['banned_until'])) return true;
        if ($user['is_banned'] == 1 && $user['banned_until'] && strtotime($user['banned_until']) > time()) return true;
        if ($user['is_banned'] == 1 && $user['banned_until'] && strtotime($user['banned_until']) <= time()) {
            unbanUser($user_id);
            return false;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

function getRecentActivity($limit = 20) {
    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->prepare("
            SELECT 'Post' as action, username, message as details, created_at
            FROM messages
            UNION ALL
            SELECT 'Join' as action, username, name as details, created_at
            FROM users
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getAdminActions($limit = 20) {
    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE action LIKE '%admin%' OR action LIKE '%ban%' OR action LIKE '%mute%' ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getReports($limit = 20) {
    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->prepare("SELECT * FROM reports ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getForumSettings() {

    if (!isPrivileged()) return [];
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM forum_settings");
        $settings = [];
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return array_merge([
            'forum_title' => 'ICCT Anonymous Forum',
            'forum_logo' => '../assets/img/icct.jpg',
            'max_message_length' => 500,
            'maintenance_mode' => 0
        ], $settings);
    } catch(PDOException $e) {
        return [
            'forum_title' => 'ICCT Anonymous Forum',
            'forum_logo' => '../assets/img/icct.jpg',
            'max_message_length' => 500,
            'maintenance_mode' => 0
        ];
    }
}
function initializeUserProfiles() {
    try {
        $pdo = getPDO();
        if (!$pdo) return;
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_profiles (
                user_id INT PRIMARY KEY,
                display_name VARCHAR(100),
                bio TEXT,
                avatar VARCHAR(255),
                profile_visibility ENUM('public', 'private') DEFAULT 'public',
                show_online_status TINYINT(1) DEFAULT 1,
                allow_messages TINYINT(1) DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    } catch(PDOException $e) {
        error_log("Failed to initialize user profiles: " . $e->getMessage());
    }
}
function getUserProfile($user_id) {
    try {
        $pdo = getPDO();
        if (!$pdo) return null;
        $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$profile) {
            $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)")->execute([$user_id]);
            return getUserProfile($user_id);
        }
        return $profile;
    } catch(PDOException $e) {
        return null;
    }
}
function getUserActivityStats($user_id) {
    try {
        $pdo = getPDO();
        if (!$pdo) return ['posts' => 0, 'likes' => 0, 'join_date' => 'N/A'];
        $stmt = $pdo->prepare("SELECT COUNT(*) as posts FROM messages WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $posts = $stmt->fetch()['posts'];
        $stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return [
            'posts' => $posts,
            'likes' => 0,
            'join_date' => $user ? date('M Y', strtotime($user['created_at'])) : 'N/A'
        ];
    } catch(PDOException $e) {
        return ['posts' => 0, 'likes' => 0, 'join_date' => 'N/A'];
    }
}
function getRecentUserMessages($user_id, $limit = 10) {
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
function updateUserProfile($user_id, $data) {
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        $fields = [];
        $params = [];
        $allowed_fields = ['display_name', 'bio', 'avatar', 'profile_visibility', 'show_online_status', 'allow_messages'];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        if (empty($fields)) return false;
        $params[] = $user_id;
        $sql = "UPDATE user_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        return false;
    }
}