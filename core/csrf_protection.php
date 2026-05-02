<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function getCSRFToken() {
    return $_SESSION['csrf_token'] ?? null;
}
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
function csrfTokenField() {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
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
function regenerateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
?>