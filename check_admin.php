<?php
// check_admin.php - Check admin user status
require_once 'database_connection.php';

$username = 'theadmin'; // CHANGE THIS

try {
    $stmt = $pdo->prepare("SELECT id, name, username, password, is_admin FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found!<br>";
        echo "ID: " . $admin['id'] . "<br>";
        echo "Name: " . $admin['name'] . "<br>";
        echo "Username: " . $admin['username'] . "<br>";
        echo "Is Admin: " . ($admin['is_admin'] == 1 ? '✅ YES' : '❌ NO') . "<br>";
        
        // Test password
        if (password_verify('thepassword', $admin['password'])) {
            echo "Password 'thepassword': ✅ WORKS!<br>";
        } else {
            echo "Password 'thepassword': ❌ DOES NOT WORK.<br>";
        }
        
    } else {
        echo "❌ Admin user '$username' not found!";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>