<?php
require_once '../core/database_connection.php';
require_once '../core/logger.php';
require_once '../core/rate_limiter.php';
class PasswordReset {
    private static function createTable() {
        $pdo = getDatabaseConnection();
        if (!$pdo) return false;
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    used TINYINT(1) DEFAULT 0,
                    ip_address VARCHAR(45),
                    INDEX idx_token (token),
                    INDEX idx_user_id (user_id),
                    INDEX idx_expires (expires_at)
                )
            ");
            return true;
        } catch (PDOException $e) {
            log_error("Failed to create password_resets table: " . $e->getMessage());
            return false;
        }
    }
    public static function generateToken($userId) {
        self::createTable();
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }
        $limiter = new RateLimiter();
        $result = $limiter->check('password_reset', 3, 3600);
        if (!$result['allowed']) {
            return [
                'success' => false,
                'message' => 'Too many reset requests. Please try again later.'
            ];
        }
        try {
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
            $stmt->execute([$userId]);
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, token, expires_at, ip_address)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $token, $expiresAt, $ipAddress]);
            $limiter->recordAttempt('password_reset');
            log_auth("Password reset token generated", ['user_id' => $userId]);
            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $expiresAt
            ];
        } catch (PDOException $e) {
            log_error("Failed to generate reset token: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate reset token'];
        }
    }
    public static function validateToken($token) {
        self::createTable();
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return ['valid' => false, 'message' => 'Database unavailable'];
        }
        try {
            $stmt = $pdo->prepare("
                SELECT pr.*, u.username
                FROM password_resets pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $reset = $stmt->fetch();
            if (!$reset) {
                return ['valid' => false, 'message' => 'Invalid or expired token'];
            }
            return [
                'valid' => true,
                'user_id' => $reset['user_id'],
                'username' => $reset['username']
            ];
        } catch (PDOException $e) {
            log_error("Failed to validate reset token: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Failed to validate token'];
        }
    }
    public static function resetPassword($token, $newPassword) {
        self::createTable();
        $validation = self::validateToken($token);
        if (!$validation['valid']) {
            return $validation;
        }
        $userId = $validation['user_id'];
        require_once 'input_validator.php';
        $passwordCheck = InputValidator::validatePassword($newPassword);
        if (!$passwordCheck['valid']) {
            return ['success' => false, 'message' => $passwordCheck['message']];
        }
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            log_auth("Password reset completed", ['user_id' => $userId]);
            return [
                'success' => true,
                'message' => 'Password reset successful'
            ];
        } catch (PDOException $e) {
            log_error("Failed to reset password: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
    public static function cleanExpiredTokens() {
        self::createTable();
        $pdo = getDatabaseConnection();
        if (!$pdo) return 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            log_error("Failed to clean expired tokens: " . $e->getMessage());
            return 0;
        }
    }
    public static function requestReset($username) {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }
        try {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if (!$user) {
                return [
                    'success' => true,
                    'message' => 'If the username exists, a reset link has been generated.'
                ];
            }
            $result = self::generateToken($user['id']);
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Reset token generated successfully.',
                    'token' => $result['token'],
                    'user_id' => $user['id'],
                    'username' => $user['username']
                ];
            }
            return $result;
        } catch (PDOException $e) {
            log_error("Failed to request password reset: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process request'];
        }
    }
}
?>