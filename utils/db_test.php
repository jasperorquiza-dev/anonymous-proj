<?php
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_DATABASE') ?: 'icct_forum';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully!";
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    echo "<br>✅ Tables found: " . count($tables);
    foreach($tables as $table) {
        echo "<br> - " . $table[0];
    }
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>