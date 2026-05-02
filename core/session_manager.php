<?php
/**
 * Session Management Utilities
 * Enhanced session handling with security features
 */

require_once '../core/logger.php';

class SessionManager {
    private static $initialized = false;
    
    /**
     * Initialize secure session
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            // Security settings already in config.php
            session_start();
        }
        
        // Check if session needs regeneration
        if (self::shouldRegenerateSession()) {
            self::regenerateSession();
        }
        
        // Validate session
        if (!self::validateSession()) {
            self::destroySession();
            log_security('Invalid session detected and destroyed', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        // Update activity timestamp
        $_SESSION['last_activity'] = time();
        
        self::$initialized = true;
    }
    
    /**
     * Check if session should be regenerated
     */
    private static function shouldRegenerateSession() {
        // Regenerate every 30 minutes for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
            return false;
        }
        
        return (time() - $_SESSION['last_regeneration']) > 1800; // 30 minutes
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
            log_debug('Session ID regenerated');
        }
    }
    
    /**
     * Validate session integrity
     */
    private static function validateSession() {
        // Initialize fingerprint on first access
        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = self::generateFingerprint();
            return true;
        }
        
        // Validate fingerprint hasn't changed
        $currentFingerprint = self::generateFingerprint();
        if ($_SESSION['fingerprint'] !== $currentFingerprint) {
            return false;
        }
        
        // Check session timeout (1 hour)
        if (isset($_SESSION['last_activity'])) {
            $timeout = 3600; // 1 hour
            if ((time() - $_SESSION['last_activity']) > $timeout) {
                log_auth('Session timeout', [
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate session fingerprint for validation
     */
    private static function generateFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            // Note: IP can change with mobile users, so we make it optional
            // substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 7) // First 3 octets only
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Set session variable
     */
    public static function set($key, $value) {
        self::init();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     */
    public static function get($key, $default = null) {
        self::init();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session variable exists
     */
    public static function has($key) {
        self::init();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     */
    public static function remove($key) {
        self::init();
        unset($_SESSION[$key]);
    }
    
    /**
     * Set flash message (one-time message)
     */
    public static function flash($key, $value) {
        self::init();
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get flash message and remove it
     */
    public static function getFlash($key, $default = null) {
        self::init();
        
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        
        return $value;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        self::init();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current username
     */
    public static function getUsername() {
        self::init();
        return $_SESSION['user_username'] ?? null;
    }
    
    /**
     * Check if user is master
     */
    public static function isMaster() {
        self::init();
        return isset($_SESSION['is_master']) && $_SESSION['is_master'] === true;
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        self::init();
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    /**
     * Login user
     */
    public static function login($userId, $username, $name, $isMaster = false, $isAdmin = false) {
        self::init();
        
        // Regenerate session ID on login to prevent fixation
        session_regenerate_id(true);
        
        // Set user data
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
    
    /**
     * Logout user
     */
    public static function logout() {
        self::init();
        
        $username = self::getUsername();
        
        // Clear all session data
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        log_auth("User logged out: {$username}");
    }
    
    /**
     * Destroy session completely
     */
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
    
    /**
     * Get session info for debugging
     */
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

// Global helper functions
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
