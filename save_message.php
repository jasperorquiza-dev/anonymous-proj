<?php
require_once 'auth.php';
require_once 'database_connection.php';
require_once 'philippines_time.php';
require_once 'admin_functions.php';
require_once 'master_auth.php';
require_once 'rate_limiter.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    // Rate limiting: 10 messages per minute for regular users
    if (!isMaster() && !isAdmin()) {
        rate_limit('post_message', 10, 60, true);
    }
    
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        // Get max message length from settings
        $max_length = getSetting('max_message_length', '500');
        
        // Validate message length
        if (strlen($message) > (int)$max_length) {
            echo json_encode(['status' => 'error', 'message' => "Message exceeds maximum length of {$max_length} characters"]);
            exit;
        }
        
        try {
            // Check if user is logged in
            if (isLoggedIn()) {
                $user = getUser();
                $user_id_session = $_SESSION['user_id'];
                
                // Check if user is muted
                if (isUserMuted($user_id_session)) {
                    echo json_encode(['status' => 'error', 'message' => 'Your account has been muted. You cannot post messages at this time.']);
                    exit;
                }
                
                // Check if user is master or admin
                $is_master = isMaster();
                $is_admin = isAdmin();
                
                // If not master/admin, check allow_guest_posts setting
                if (!$is_master && !$is_admin) {
                    $allow_guest_posts = getSetting('allow_guest_posts', '1');
                    if ($allow_guest_posts == '0') {
                        echo json_encode(['status' => 'error', 'message' => 'Posting is currently disabled by the administrator. Only admins can post at this time.']);
                        exit;
                    }
                }
                
                $pdo = getPDO();
                $stmt = $pdo->prepare("SELECT anonymous_username FROM users WHERE id = ?");
                $stmt->execute([$user_id_session]);
                $user_data = $stmt->fetch();
                
                // Helper to generate Anonymous number tag
                $generateAnon = function() {
                    return 'Anonymous' . rand(100, 999);
                };
                
                if ($user_data && !empty($user_data['anonymous_username'])) {
                    $existing = $user_data['anonymous_username'];
                    if (preg_match('/^Anonymous\d+$/', $existing)) {
                        $username = $existing;
                    } else {
                        $username = $generateAnon();
                        $stmt = $pdo->prepare("UPDATE users SET anonymous_username = ? WHERE id = ?");
                        $stmt->execute([$username, $user_id_session]);
                    }
                } else {
                    $username = $generateAnon();
                    $stmt = $pdo->prepare("UPDATE users SET anonymous_username = ? WHERE id = ?");
                    $stmt->execute([$username, $user_id_session]);
                }
                
                $user_id_db = 'user_' . $user['id'];
            } else {
                $user_id_db = bin2hex(random_bytes(5));
                $username = 'Anonymous' . rand(100, 999);
            }
            
            $pdo = getPDO();
            if (!$pdo) {
                echo json_encode(['status' => 'error', 'message' => 'Database unavailable']);
                exit;
            }
            
            $philippine_time = PhilippinesTime::getCurrentTime('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO messages (user_id, username, message, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id_db, $username, $message, $philippine_time]);
            
            // Record successful message post for rate limiting
            $limiter = new RateLimiter();
            $limiter->recordAttempt('post_message');
            
            echo json_encode(['status' => 'success', 'username' => $username, 'message' => 'Message posted anonymously!']);
            
        } catch(PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}