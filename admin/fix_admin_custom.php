<?php
// fix_admin_custom.php - Fix admin privileges for custom user
require_once '../core/database_connection.php';

$username = 'theadmin'; // CHANGE THIS

try {
    $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE username = ?");
    $stmt->execute([$username]);
    
    echo "✅ Admin privileges updated!<br>";
    
    // Verify
    $stmt = $pdo->prepare("SELECT username, is_admin FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "User: <strong>" . $result['username'] . "</strong><br>";
        echo "Admin Status: <strong>" . ($result['is_admin'] == 1 ? '✅ ADMIN' : '❌ NOT ADMIN') . "</strong>";
    } else {
        echo "❌ User '$username' not found!";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
