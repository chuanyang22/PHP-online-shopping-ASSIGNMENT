<?php

// Always start the session so we can access $_SESSION variables
session_start();

// Check if a user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Kick out users who are NOT logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: /login.php");
        exit;
    }
}

// Kick out users who are NOT Admins
function require_admin() {
    require_login(); // First, ensure they are logged in
    
    if ($_SESSION['user_role'] !== 'Admin') {
        // If they are just a member, send them to the homepage or profile
        header("Location: /index.php");
        exit;
    }
}
?>