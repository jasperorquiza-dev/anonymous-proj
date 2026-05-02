<?php
/**
 * CSRF Protection Utilities
 * Provides token generation and validation for form security
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CSRF token and store it in the session
 * @return string The generated token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get the current CSRF token
 * @return string|null The token or null if not set
 */
function getCSRFToken() {
    return $_SESSION['csrf_token'] ?? null;
}

/**
 * Validate a CSRF token
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF token input field
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF token from POST request and die if invalid
 * @param bool $ajax Whether this is an AJAX request (returns JSON instead of HTML)
 */
function verifyCSRFToken($ajax = false) {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    
    if (!validateCSRFToken($token)) {
        if ($ajax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Please refresh the page and try again.']);
            exit;
        } else {
            http_response_code(403);
            die('Invalid security token. Please refresh the page and try again.');
        }
    }
}

/**
 * Regenerate CSRF token (call after successful login/logout)
 */
function regenerateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
?>
