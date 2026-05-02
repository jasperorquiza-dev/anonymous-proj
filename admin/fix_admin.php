<?php
require_once '../core/database_connection.php';
try {
    $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE username = 'admin'");
    $stmt->execute();
    echo "Admin privileges updated!<br>";
    $stmt = $pdo->prepare("SELECT username, is_admin FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Admin status: " . ($result['is_admin'] == 1 ? 'ADMIN' : 'NOT ADMIN');
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>