<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../core/database_connection.php';
require_once '../admin/admin_functions.php';
require_once '../core/rate_limiter.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rate_limit('login', 5, 300, true);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
        exit;
    }
    $master_username = getenv('MASTER_USERNAME') ?: '';
    $master_password_hash = getenv('MASTER_PASSWORD_HASH') ?: password_hash('', PASSWORD_DEFAULT);
    if ($username === $master_username && password_verify($password, $master_password_hash)) {
        $_SESSION['is_master'] = true;
        $_SESSION['user_id'] = 0;
        $_SESSION['user_name'] = 'Master';
        $_SESSION['user_username'] = $master_username;
        echo json_encode(['status' => 'success', 'message' => 'Login successful! Redirecting...']);
        exit;
    }
    try {
        $pdo = getPDO();
        if (!$pdo) {
            echo json_encode(['status' => 'error', 'message' => 'Database unavailable']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            if (isUserBanned($user['id'])) {
                $limiter = new RateLimiter();
                $limiter->recordAttempt('login');
                echo json_encode(['status' => 'error', 'message' => 'Your account has been banned. You cannot login.']);
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_username'] = $user['username'];
                session_regenerate_id(true);
                echo json_encode(['status' => 'success', 'message' => 'Login successful! Redirecting...']);
            }
        } else {
            $limiter = new RateLimiter();
            $limiter->recordAttempt('login');
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
        }
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}