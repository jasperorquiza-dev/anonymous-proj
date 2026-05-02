<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 0);
date_default_timezone_set('Asia/Manila');
define('APP_NAME', 'ICCT Forum');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'icct_forum');
define('DB_CHARSET', 'utf8mb4');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300);
define('MAX_REGISTER_ATTEMPTS', 3);
define('REGISTER_LOCKOUT_TIME', 600);
define('MAX_MESSAGE_POSTS', 10);
define('MESSAGE_POST_WINDOW', 60);
define('PASSWORD_MIN_LENGTH', 8);
define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 20);
define('MAX_MESSAGE_LENGTH', 500);
define('MESSAGE_LOAD_LIMIT', 50);
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('ALLOWED_UPLOAD_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('MASTER_USERNAME', getenv('MASTER_USERNAME') ?: '');
define('MASTER_PASSWORD_HASH', getenv('MASTER_PASSWORD_HASH') ?: '');
define('BASE_PATH', __DIR__);
define('LOGS_PATH', BASE_PATH . '/logs');
define('BACKUP_PATH', BASE_PATH . '/backups');
if (!is_dir(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0755, true);
}
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
function isMaintenanceMode() {
    return file_exists(BASE_PATH . '/.maintenance');
}
?>