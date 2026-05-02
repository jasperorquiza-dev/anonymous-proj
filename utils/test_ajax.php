<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'AJAX working']);
?>
