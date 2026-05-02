<?php
/**
 * Centralized Database Connection
 * Returns a PDO instance for database operations
 */

function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // Database credentials - consider moving to environment variables for production
    $host = getenv('DB_HOST') ?: 'localhost';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_DATABASE') ?: 'icct_forum';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch(PDOException $e) {
        // Log the error securely
        error_log("Database connection failed: " . $e->getMessage());
        // Return null instead of dying - let calling code handle the error
        return null;
    }
}

// Backward compatibility - return PDO instance directly when file is included
$pdo = getDatabaseConnection();
if ($pdo === null) {
    // Only die if this file is included expecting a direct return
    if (!function_exists('getPDO')) {
        http_response_code(503);
        die("Database service temporarily unavailable. Please try again later.");
    }
}

return $pdo;
?>
