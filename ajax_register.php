<?php
// ajax_register.php - AJAX registration handler
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database_connection.php';
require_once 'rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting: 3 registrations per 10 minutes per IP
    rate_limit('register', 3, 600, true);
    
    $name = trim($_POST['name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
        exit;
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
        exit;
    }
    
    // Password strength check
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must contain both letters and numbers']);
        exit;
    }
    
    if ($age < 13 || $age > 100) {
        echo json_encode(['status' => 'error', 'message' => 'Age must be between 13 and 100']);
        exit;
    }
    
    // Username validation
    if (strlen($username) < 3 || strlen($username) > 20) {
        echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 20 characters']);
        exit;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo json_encode(['status' => 'error', 'message' => 'Username can only contain letters, numbers, and underscores']);
        exit;
    }
    
    try {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
            exit;
        }
        
        // Create new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, age, username, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $age, $username, $hashed_password]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Registration successful! You can now login.'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method'
    ]);
}
?>