<?php
// logout.php - Logout script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database_connection.php';

// Capture user id before destroying session
$userId = $_SESSION['user_id'] ?? null;

// Remove from online users immediately for real-time accuracy
if ($userId) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare("DELETE FROM online_users WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
    } catch (Throwable $e) {
        // Ignore errors on logout cleanup
        error_log("Logout cleanup error: " . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: /');
exit;
?>