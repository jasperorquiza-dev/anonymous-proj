<?php
require_once '../core/database_connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if (!function_exists('getPDO')) {
    function getPDO() {
        return getDatabaseConnection();
    }
}
try {
    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    $pdo->exec("DELETE FROM online_users WHERE last_activity < NOW() - INTERVAL 5 MINUTE");
    $stmt = $pdo->query("
        SELECT
            COUNT(DISTINCT CASE WHEN username NOT LIKE 'Guest%' THEN user_id END) AS online_count,
            GROUP_CONCAT(DISTINCT CASE WHEN username NOT LIKE 'Guest%' THEN username END) AS logged_in_users
        FROM online_users
        WHERE last_activity > NOW() - INTERVAL 2 MINUTE
    ");
    $result = $stmt->fetch();
    $online_count = $result['online_count'] ?: 1;
    $logged_in_users = $result['logged_in_users'] ? explode(',', $result['logged_in_users']) : [];
    $logged_in_users = array_filter(array_unique($logged_in_users));
    if (isset($_SESSION['user_username']) && !in_array($_SESSION['user_username'], $logged_in_users)) {
        $logged_in_users[] = $_SESSION['user_username'];
    }
    echo json_encode([
        'status' => 'success',
        'online_count' => $online_count,
        'logged_in_users' => array_values($logged_in_users)
    ]);
} catch (Throwable $e) {
    error_log('get_online_users error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'online_count' => 1,
        'logged_in_users' => []
    ]);
}
?>