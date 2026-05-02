<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function isMaster() {
    return !empty($_SESSION['is_master']) && $_SESSION['is_master'] === true;
}
?>