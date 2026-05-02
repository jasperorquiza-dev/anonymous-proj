<?php
/**
 * Input Validation Utilities
 * Centralized input validation and sanitization functions
 */

class InputValidator {
    
    /**
     * Validate and sanitize username
     * @param string $username
     * @return array ['valid' => bool, 'message' => string, 'sanitized' => string]
     */
    public static function validateUsername($username) {
        $username = trim($username);
        
        if (empty($username)) {
            return ['valid' => false, 'message' => 'Username is required', 'sanitized' => ''];
        }
        
        if (strlen($username) < USERNAME_MIN_LENGTH) {
            return ['valid' => false, 'message' => 'Username must be at least ' . USERNAME_MIN_LENGTH . ' characters', 'sanitized' => $username];
        }
        
        if (strlen($username) > USERNAME_MAX_LENGTH) {
            return ['valid' => false, 'message' => 'Username must not exceed ' . USERNAME_MAX_LENGTH . ' characters', 'sanitized' => $username];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'message' => 'Username can only contain letters, numbers, and underscores', 'sanitized' => $username];
        }
        
        return ['valid' => true, 'message' => '', 'sanitized' => $username];
    }
    
    /**
     * Validate password strength
     * @param string $password
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validatePassword($password) {
        if (empty($password)) {
            return ['valid' => false, 'message' => 'Password is required'];
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['valid' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
        }
        
        if (!preg_match('/[A-Za-z]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one letter'];
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one number'];
        }
        
        // Optional: Check for special characters
        // if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        //     return ['valid' => false, 'message' => 'Password must contain at least one special character'];
        // }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Validate email address
     * @param string $email
     * @return array ['valid' => bool, 'message' => string, 'sanitized' => string]
     */
    public static function validateEmail($email) {
        $email = trim($email);
        
        if (empty($email)) {
            return ['valid' => false, 'message' => 'Email is required', 'sanitized' => ''];
        }
        
        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format', 'sanitized' => $sanitized];
        }
        
        return ['valid' => true, 'message' => '', 'sanitized' => $sanitized];
    }
    
    /**
     * Validate age
     * @param int $age
     * @param int $min Minimum age (default 13)
     * @param int $max Maximum age (default 100)
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateAge($age, $min = 13, $max = 100) {
        $age = intval($age);
        
        if ($age < $min) {
            return ['valid' => false, 'message' => "Age must be at least {$min} years old"];
        }
        
        if ($age > $max) {
            return ['valid' => false, 'message' => "Age must not exceed {$max} years"];
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Validate message content
     * @param string $message
     * @param int $maxLength Maximum length (default from config)
     * @return array ['valid' => bool, 'message' => string, 'sanitized' => string]
     */
    public static function validateMessage($message, $maxLength = null) {
        $maxLength = $maxLength ?? MAX_MESSAGE_LENGTH;
        $message = trim($message);
        
        if (empty($message)) {
            return ['valid' => false, 'message' => 'Message cannot be empty', 'sanitized' => ''];
        }
        
        if (strlen($message) > $maxLength) {
            return ['valid' => false, 'message' => "Message exceeds maximum length of {$maxLength} characters", 'sanitized' => $message];
        }
        
        // Check for spam patterns (basic)
        if (self::containsSpam($message)) {
            return ['valid' => false, 'message' => 'Message contains prohibited content', 'sanitized' => $message];
        }
        
        return ['valid' => true, 'message' => '', 'sanitized' => $message];
    }
    
    /**
     * Basic spam detection
     * @param string $text
     * @return bool
     */
    private static function containsSpam($text) {
        // Common spam patterns
        $spamPatterns = [
            '/\b(viagra|cialis|casino|lottery|winner)\b/i',
            '/\b(buy now|click here|limited time|act now)\b/i',
            '/(http[s]?:\/\/){2,}/', // Multiple URLs
            '/(.)\1{20,}/', // Repeated characters (20+ times)
        ];
        
        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize HTML output
     * @param string $text
     * @param bool $allowLineBreaks
     * @return string
     */
    public static function sanitizeOutput($text, $allowLineBreaks = true) {
        $sanitized = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        if ($allowLineBreaks) {
            $sanitized = nl2br($sanitized);
        }
        
        return $sanitized;
    }
    
    /**
     * Validate integer ID
     * @param mixed $id
     * @return array ['valid' => bool, 'message' => string, 'value' => int]
     */
    public static function validateId($id) {
        $id = intval($id);
        
        if ($id <= 0) {
            return ['valid' => false, 'message' => 'Invalid ID', 'value' => 0];
        }
        
        return ['valid' => true, 'message' => '', 'value' => $id];
    }
    
    /**
     * Validate URL
     * @param string $url
     * @return array ['valid' => bool, 'message' => string, 'sanitized' => string]
     */
    public static function validateUrl($url) {
        $url = trim($url);
        
        if (empty($url)) {
            return ['valid' => false, 'message' => 'URL is required', 'sanitized' => ''];
        }
        
        $sanitized = filter_var($url, FILTER_SANITIZE_URL);
        
        if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'message' => 'Invalid URL format', 'sanitized' => $sanitized];
        }
        
        return ['valid' => true, 'message' => '', 'sanitized' => $sanitized];
    }
    
    /**
     * Check if string contains only alphanumeric characters
     * @param string $str
     * @return bool
     */
    public static function isAlphanumeric($str) {
        return preg_match('/^[a-zA-Z0-9]+$/', $str);
    }
    
    /**
     * Validate file upload
     * @param array $file $_FILES array element
     * @param array $allowedTypes Allowed file extensions
     * @param int $maxSize Maximum file size in bytes
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
        $allowedTypes = $allowedTypes ?? ALLOWED_UPLOAD_TYPES;
        $maxSize = $maxSize ?? UPLOAD_MAX_SIZE;
        
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['valid' => false, 'message' => 'Invalid file upload'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload failed'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'message' => 'File size exceeds maximum allowed size'];
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }
        
        return ['valid' => true, 'message' => ''];
    }
}
?>
