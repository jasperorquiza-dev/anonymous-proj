<?php
require_once '../core/database_connection.php';
class RateLimiter {
    private $pdo;
    public function __construct() {
        $this->pdo = getDatabaseConnection();
        $this->createTableIfNotExists();
    }
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
    public function check($action, $max_attempts = 5, $time_window = 300, $identifier = null) {
        if (!$this->pdo) {
            return ['allowed' => true, 'remaining' => $max_attempts, 'reset_time' => 0];
        }
        $identifier = $identifier ?? $this->getIdentifier();
        try {
            $this->cleanup($time_window);
            $stmt = $this->pdo->prepare("
                SELECT attempts, locked_until, last_attempt
                FROM rate_limits
                WHERE identifier = ? AND action = ?
            ");
            $stmt->execute([$identifier, $action]);
            $record = $stmt->fetch();
            $current_time = time();
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
                    $this->reset($identifier, $action);
                    return ['allowed' => true, 'remaining' => $max_attempts - 1, 'reset_time' => 0];
                }
            }
            if ($record) {
                $last_attempt = strtotime($record['last_attempt']);
                $time_since_last = $current_time - $last_attempt;
                if ($time_since_last > $time_window) {
                    $this->reset($identifier, $action);
                    return ['allowed' => true, 'remaining' => $max_attempts - 1, 'reset_time' => 0];
                }
                $attempts = $record['attempts'];
                if ($attempts >= $max_attempts) {
                    $lock_duration = $time_window * 2;
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
            return ['allowed' => true, 'remaining' => $max_attempts, 'reset_time' => 0];
        } catch (PDOException $e) {
            error_log("Rate limiter check failed: " . $e->getMessage());
            return ['allowed' => true, 'remaining' => $max_attempts, 'reset_time' => 0];
        }
    }
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
    private function getIdentifier() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $ip . '|' . $user_agent);
    }
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
function rate_limit($action, $max_attempts = 5, $time_window = 300, $ajax = false) {
    $limiter = new RateLimiter();
    $limiter->enforce($action, $max_attempts, $time_window, $ajax);
}
?>