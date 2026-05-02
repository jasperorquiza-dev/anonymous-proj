<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'username' => $_SESSION['user_username']
        ];
    }
    return null;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function checkIfBanned() {
    if (isLoggedIn()) {
        require_once '../admin/admin_functions.php';
        $user_id = $_SESSION['user_id'];
        
        if (isUserBanned($user_id)) {
            session_destroy();
            header('Location: login.php?error=banned');
            exit;
        }
    }
}
