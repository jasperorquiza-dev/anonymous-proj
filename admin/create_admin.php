<?php
require_once '../core/database_connection.php';
$name = 'Admin';
$age = 25;
$username = 'admin';
$password = 'password';
$is_admin = 1;
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo "Admin user already exists!<br>";
        echo "<a href='../login.php'>Go to Login</a>";
        exit;
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, age, username, password, is_admin, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $age, $username, $hashed_password, $is_admin]);
    echo "Admin user created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: password<br>";
    echo "<strong>Delete this file after use for security!</strong>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>