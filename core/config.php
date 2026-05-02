<?php
/**
 * Application Configuration
 * Central configuration file for the ICCT Forum
 */

// Error Reporting
ini_set('display_errors', 0); // Never display errors to users in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log'); // Custom error log location
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Session Configuration
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.cookie_secure', 1); // Only send cookie over HTTPS (set to 0 for local dev)
ini_set('session.use_strict_mode', 1); // Prevent session fixation attacks
ini_set('session.cookie_samesite', 'Lax'); // CSRF protection
ini_set('session.gc_maxlifetime', 3600); // Session timeout: 1 hour
ini_set('session.cookie_lifetime', 0); // Cookie expires when browser closes

// Timezone
date_default_timezone_set('Asia/Manila');

// Application Settings
define('APP_NAME', 'ICCT Forum');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production'); // development, staging, or production

// Database Configuration (use environment variables in production)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'icct_forum');
define('DB_CHARSET', 'utf8mb4');

// Security Settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300); // 5 minutes
define('MAX_REGISTER_ATTEMPTS', 3);
define('REGISTER_LOCKOUT_TIME', 600); // 10 minutes
define('MAX_MESSAGE_POSTS', 10);
define('MESSAGE_POST_WINDOW', 60); // 1 minute
define('PASSWORD_MIN_LENGTH', 8);
define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 20);

// Message Settings
define('MAX_MESSAGE_LENGTH', 500);
define('MESSAGE_LOAD_LIMIT', 50);

// File Upload Settings (if needed in future)
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_UPLOAD_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Master Account (disabled for security in public repo)
define('MASTER_USERNAME', getenv('MASTER_USERNAME') ?: '');
define('MASTER_PASSWORD_HASH', getenv('MASTER_PASSWORD_HASH') ?: '');

// Paths
define('BASE_PATH', __DIR__);
define('LOGS_PATH', BASE_PATH . '/logs');
define('BACKUP_PATH', BASE_PATH . '/backups');

// Create necessary directories
if (!is_dir(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0755, true);
}

// Development mode settings
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Helper function to check if we're in maintenance mode
function isMaintenanceMode() {
    return file_exists(BASE_PATH . '/.maintenance');
}
?>
