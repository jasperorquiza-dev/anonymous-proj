<?php
// reset_admin_password.php - Reset admin password
require_once '../core/database_connection.php';

$new_password = 'password';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashed_password]);
    
    echo "Admin password reset to: password<br>";
    echo "Password hash updated successfully!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
