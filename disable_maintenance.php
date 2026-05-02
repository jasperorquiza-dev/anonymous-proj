<?php
// disable_maintenance.php - Emergency script to disable maintenance mode
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'admin_functions.php';

// Simple password check for security
$emergency_password = $_GET['password'] ?? '';

if ($emergency_password === 'emergency123') {
    try {
        $pdo = getPDO();
        if ($pdo) {
            // Disable maintenance mode
            $stmt = $pdo->prepare("
                INSERT INTO forum_settings (setting_key, setting_value) 
                VALUES ('maintenance_mode', '0') 
                ON DUPLICATE KEY UPDATE setting_value = '0'
            ");
            $stmt->execute();
            
            echo "<h2>✅ Maintenance Mode Disabled</h2>";
            echo "<p>Maintenance mode has been turned off. You can now access the forum normally.</p>";
            echo "<a href='index.php'>Go to Forum</a>";
        } else {
            echo "<h2>❌ Database Error</h2>";
            echo "<p>Could not connect to database.</p>";
        }
    } catch (Exception $e) {
        echo "<h2>❌ Error</h2>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h2>Emergency Maintenance Disable</h2>";
    echo "<p>Add ?password=emergency123 to the URL to disable maintenance mode.</p>";
    echo "<p>Example: disable_maintenance.php?password=emergency123</p>";
}
?>
