<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../core/database_connection.php';
$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare("DELETE FROM online_users WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
    } catch (Throwable $e) {
        error_log("Logout cleanup error: " . $e->getMessage());
    }
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header('Location: /');
exit;
?>