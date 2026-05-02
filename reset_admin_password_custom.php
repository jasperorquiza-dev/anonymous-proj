<?php
// reset_admin_password_custom.php - Reset admin password to custom
require_once 'database_connection.php';

$username = 'theadmin';      // CHANGE THIS
$new_password = 'thepassword'; // CHANGE THIS
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashed_password, $username]);
    
    echo "✅ Admin password reset successfully!<br>";
    echo "Username: <strong>$username</strong><br>";
    echo "New Password: <strong>$new_password</strong><br>";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>