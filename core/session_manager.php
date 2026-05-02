<?php
require_once '../core/logger.php';
class SessionManager {
    private static $initialized = false;
    public static function init() {
        if (self::$initialized) {
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (self::shouldRegenerateSession()) {
            self::regenerateSession();
        }
        if (!self::validateSession()) {
            self::destroySession();
            log_security('Invalid session detected and destroyed', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        $_SESSION['last_activity'] = time();
        self::$initialized = true;
    }
    private static function shouldRegenerateSession() {
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
            return false;
        }
        return (time() - $_SESSION['last_regeneration']) > 1800;
    }
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
            log_debug('Session ID regenerated');
        }
    }
    private static function validateSession() {
        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = self::generateFingerprint();
            return true;
        }
        $currentFingerprint = self::generateFingerprint();
        if ($_SESSION['fingerprint'] !== $currentFingerprint) {
            return false;
        }
        if (isset($_SESSION['last_activity'])) {
            $timeout = 3600;
            if ((time() - $_SESSION['last_activity']) > $timeout) {
                log_auth('Session timeout', [
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return false;
            }
        }
        return true;
    }
    private static function generateFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        return hash('sha256', implode('|', $components));
    }
    public static function set($key, $value) {
        self::init();
        $_SESSION[$key] = $value;
    }
    public static function get($key, $default = null) {
        self::init();
        return $_SESSION[$key] ?? $default;
    }
    public static function has($key) {
        self::init();
        return isset($_SESSION[$key]);
    }
    public static function remove($key) {
        self::init();
        unset($_SESSION[$key]);
    }
    public static function flash($key, $value) {
        self::init();
        $_SESSION['_flash'][$key] = $value;
    }
    public static function getFlash($key, $default = null) {
        self::init();
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
    public static function isLoggedIn() {
        self::init();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    public static function getUserId() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }
    public static function getUsername() {
        self::init();
        return $_SESSION['user_username'] ?? null;
    }
    public static function isMaster() {
        self::init();
        return isset($_SESSION['is_master']) && $_SESSION['is_master'] === true;
    }
    public static function isAdmin() {
        self::init();
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    public static function login($userId, $username, $name, $isMaster = false, $isAdmin = false) {
        self::init();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_username'] = $username;
        $_SESSION['user_name'] = $name;
        $_SESSION['is_master'] = $isMaster;
        $_SESSION['is_admin'] = $isAdmin;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['fingerprint'] = self::generateFingerprint();
        log_auth("User logged in: {$username}", [
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    public static function logout() {
        self::init();
        $username = self::getUsername();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        log_auth("User logged out: {$username}");
    }
    public static function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
        }
    }
    public static function getSessionInfo() {
        self::init();
        return [
            'session_id' => session_id(),
            'user_id' => self::getUserId(),
            'username' => self::getUsername(),
            'is_master' => self::isMaster(),
            'is_admin' => self::isAdmin(),
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'time_since_login' => isset($_SESSION['login_time']) ? (time() - $_SESSION['login_time']) : null,
            'time_since_activity' => isset($_SESSION['last_activity']) ? (time() - $_SESSION['last_activity']) : null
        ];
    }
}
function session_init() {
    SessionManager::init();
}
function session_set($key, $value) {
    SessionManager::set($key, $value);
}
function session_get($key, $default = null) {
    return SessionManager::get($key, $default);
}
function session_flash($key, $value) {
    SessionManager::flash($key, $value);
}
function session_get_flash($key, $default = null) {
    return SessionManager::getFlash($key, $default);
}
function is_logged_in() {
    return SessionManager::isLoggedIn();
}
?>