<?php
/**
 * Rate Limiting Utility
 * Prevents brute force attacks and spam by limiting request frequency
 */

require_once '../core/database_connection.php';

class RateLimiter {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
        $this->createTableIfNotExists();
    }
    
    /**
     * Create rate_limit table if it doesn't exist
     */
    private function createTableIfNotExists() {
        if (!$this->pdo) return;
        
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    identifier VARCHAR(255) NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    attempts INT DEFAULT 0,
                    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    locked_until TIMESTAMP NULL,
                    INDEX idx_identifier_action (identifier, action),
                    INDEX idx_locked_until (locked_until)
                )
            ");
        } catch (PDOException $e) {
            error_log("Rate limiter table creation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check if the action is allowed
     * @param string $action The action being performed (e.g., 'login', 'register', 'post_message')
     * @param int $max_attempts Maximum attempts allowed
     * @param int $time_window Time window in seconds
     * @param string|null $identifier Custom identifier (defaults to IP address)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     */
    public function check($action, $max_attempts = 5, $time_window = 300, $identifier = null) {
        if (!$this->pdo) {
            // If database is down, allow the request
            return ['allowed' => true, 'remaining' => $max_attempts, 'reset_time' => 0];
        }
        
        $identifier = $identifier ?? $this->getIdentifier();
        
        try {
            // Clean up old entries
            $this->cleanup($time_window);
            
            // Get current attempt count
            $stmt = $this->pdo->prepare("
                SELECT attempts, locked_until, last_attempt 
                FROM rate_limits 
                WHERE identifier = ? AND action = ?
            ");
            $stmt->execute([$identifier, $action]);
            $record = $stmt->fetch();
            
            $current_time = time();
            
            // Check if locked
            if ($record && $record['locked_until']) {
                $locked_until = strtotime($record['locked_until']);
                if ($locked_until > $current_time) {
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'reset_time' => $locked_until,
                        'message' => 'Too many attempts. Please try again in ' . ($locked_until - $current_time) . ' seconds.'
                    ];
                } else {
                    // Lock expired, reset
                    $this->reset($identifier, $action);
                    return ['allowed' => true, 'remaining' => $max_attempts - 1, 'reset_time' => 0];
                }
            }
            
            // Check attempt count
            if ($record) {
                $last_attempt = strtotime($record['last_attempt']);
                $time_since_last = $current_time - $last_attempt;
                
                // If outside time window, reset
                if ($time_since_last > $time_window) {
                    $this->reset($identifier, $action);
                    return ['allowed' => true, 'remaining' => $max_attempts - 1, 'reset_time' => 0];
                }
                
                $attempts = $record['attempts'];
                if ($attempts >= $max_attempts) {
                    // Lock the account
                    $lock_duration = $time_window * 2; // Lock for double the time window
                    $this->lock($identifier, $action, $lock_duration);
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'reset_time' => $current_time + $lock_duration,
                        'message' => 'Too many attempts. Locked for ' . ($lock_duration / 60) . ' minutes.'
                    ];
                }
                
                return [
                    'allowed' => true,
                    'remaining' => $max_attempts - $attempts,
                    'reset_time' => $last_attempt + $time_window
                ];
            }
            
            // No record found, allow
            return ['allowed' => true, 'remaining' => $max_attempts, 'reset_time' => 0];
            
        } catch (PDOException $e) {
            error_log("Rate limiter check failed: " . $e->getMessage());
            // On error, allow the request
            return ['allowed' => true, 'remaining' => $max_attempts, 'reset_time' => 0];
        }
    }
    
    /**
     * Record an attempt
     * @param string $action The action being performed
     * @param string|null $identifier Custom identifier
     */
    public function recordAttempt($action, $identifier = null) {
        if (!$this->pdo) return;
        
        $identifier = $identifier ?? $this->getIdentifier();
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (identifier, action, attempts, last_attempt) 
                VALUES (?, ?, 1, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    attempts = attempts + 1, 
                    last_attempt = NOW()
            ");
            $stmt->execute([$identifier, $action]);
        } catch (PDOException $e) {
            error_log("Rate limiter record attempt failed: " . $e->getMessage());
        }
    }
    
    /**
     * Reset attempts for an identifier/action
     */
    private function reset($identifier, $action) {
        if (!$this->pdo) return;
        
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM rate_limits 
                WHERE identifier = ? AND action = ?
            ");
            $stmt->execute([$identifier, $action]);
        } catch (PDOException $e) {
            error_log("Rate limiter reset failed: " . $e->getMessage());
        }
    }
    
    /**
     * Lock an identifier/action for a duration
     */
    private function lock($identifier, $action, $duration) {
        if (!$this->pdo) return;
        
        try {
            $locked_until = date('Y-m-d H:i:s', time() + $duration);
            $stmt = $this->pdo->prepare("
                UPDATE rate_limits 
                SET locked_until = ? 
                WHERE identifier = ? AND action = ?
            ");
            $stmt->execute([$locked_until, $identifier, $action]);
        } catch (PDOException $e) {
            error_log("Rate limiter lock failed: " . $e->getMessage());
        }
    }
    
    /**
     * Clean up old entries
     */
    private function cleanup($time_window) {
        if (!$this->pdo) return;
        
        try {
            $cutoff = date('Y-m-d H:i:s', time() - ($time_window * 3));
            $stmt = $this->pdo->prepare("
                DELETE FROM rate_limits 
                WHERE last_attempt < ? AND locked_until IS NULL
            ");
            $stmt->execute([$cutoff]);
        } catch (PDOException $e) {
            error_log("Rate limiter cleanup failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get identifier for the current request
     */
    private function getIdentifier() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $ip . '|' . $user_agent);
    }
    
    /**
     * Check and enforce rate limit
     * Dies with error message if limit exceeded
     */
    public function enforce($action, $max_attempts = 5, $time_window = 300, $ajax = false) {
        $result = $this->check($action, $max_attempts, $time_window);
        
        if (!$result['allowed']) {
            $this->recordAttempt($action);
            
            if ($ajax) {
                header('Content-Type: application/json');
                http_response_code(429);
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Too many attempts. Please try again later.',
                    'retry_after' => $result['reset_time'] - time()
                ]);
            } else {
                http_response_code(429);
                die($result['message'] ?? 'Too many attempts. Please try again later.');
            }
            exit;
        }
    }
}

// Global helper function
function rate_limit($action, $max_attempts = 5, $time_window = 300, $ajax = false) {
    $limiter = new RateLimiter();
    $limiter->enforce($action, $max_attempts, $time_window, $ajax);
}
?>
