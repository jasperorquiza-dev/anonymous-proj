<?php
$host = 'sql309.byetcluster.com';
$username = 'if0_40120574';
$password = 'lcBcaqmAsOne';
$database = 'if0_40120574_icctforumjoo';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully!";
    
    // Test if tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    echo "<br>✅ Tables found: " . count($tables);
    
    foreach($tables as $table) {
        echo "<br> - " . $table[0];
    }
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
