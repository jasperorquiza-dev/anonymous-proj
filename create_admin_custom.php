<?php
// create_admin_custom.php - Create admin with custom credentials
require_once 'database_connection.php';

// CUSTOMIZE THESE VALUES:
$name = 'Forum Administrator';
$age = 30;
$username = 'theadmin';      // CHANGE THIS
$password = 'thepassword';   // CHANGE THIS
$is_admin = 1;

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        echo "Admin user '$username' already exists!<br>";
        echo "<a href='login.php'>Go to Login</a><br><br>";
        echo "<a href='reset_admin_password.php'>Reset Password</a>";
        exit;
    }
    
    // Create admin user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, age, username, password, is_admin, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $age, $username, $hashed_password, $is_admin]);
    
    echo "✅ Custom Admin user created successfully!<br>";
    echo "Username: <strong>$username</strong><br>";
    echo "Password: <strong>$password</strong><br>";
    echo "<br>";
    echo "<a href='login.php' style='background: #2563EB; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a>";
    echo "<br><br>";
    echo "<strong style='color: red;'>Delete this file after use for security!</strong>";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>