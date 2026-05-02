<?php
// test_admin_status.php - Test admin status functionality
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../master/master_auth.php';
require_once '../admin/admin_functions.php';

if (!isMaster()) {
    echo "Access denied. Master account required.";
    exit;
}

echo "<h2>Admin Status Test</h2>";

try {
    $pdo = getPDO();
    if (!$pdo) {
        echo "Database connection failed.";
        exit;
    }
    
    // Test 1: Check if is_admin column exists
    echo "<h3>Test 1: Database Structure</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_is_admin = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'is_admin') {
            $has_is_admin = true;
            echo "✅ is_admin column exists (Type: " . $column['Type'] . ")<br>";
            break;
        }
    }
    
    if (!$has_is_admin) {
        echo "❌ is_admin column does not exist. Creating it...<br>";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        echo "✅ is_admin column created.<br>";
    }
    
    // Test 2: Get all users with admin status
    echo "<h3>Test 2: User Admin Status</h3>";
    $stmt = $pdo->query("SELECT id, username, is_admin FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>is_admin</th><th>Status</th></tr>";
    
    foreach ($users as $user) {
        $status = $user['is_admin'] == 1 ? 'Admin' : 'User';
        $color = $user['is_admin'] == 1 ? 'green' : 'black';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['is_admin']}</td>";
        echo "<td style='color: $color;'><strong>$status</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 3: Test setAdminStatus function
    echo "<h3>Test 3: Admin Status Functions</h3>";
    
    // Find a non-admin user to test with
    $test_user = null;
    foreach ($users as $user) {
        if ($user['is_admin'] == 0 && $user['username'] !== 'jasperlei') {
            $test_user = $user;
            break;
        }
    }
    
    if ($test_user) {
        echo "Testing with user: {$test_user['username']} (ID: {$test_user['id']})<br>";
        
        // Test promote
        echo "Testing promote to admin...<br>";
        $result = setAdminStatus($test_user['id'], 1);
        echo $result ? "✅ Promote successful<br>" : "❌ Promote failed<br>";
        
        // Verify
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$test_user['id']]);
        $new_status = $stmt->fetch()['is_admin'];
        echo "New status: " . ($new_status == 1 ? 'Admin' : 'User') . "<br>";
        
        // Test demote
        echo "Testing demote from admin...<br>";
        $result = setAdminStatus($test_user['id'], 0);
        echo $result ? "✅ Demote successful<br>" : "❌ Demote failed<br>";
        
        // Verify
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$test_user['id']]);
        $final_status = $stmt->fetch()['is_admin'];
        echo "Final status: " . ($final_status == 1 ? 'Admin' : 'User') . "<br>";
        
    } else {
        echo "No suitable test user found.<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
