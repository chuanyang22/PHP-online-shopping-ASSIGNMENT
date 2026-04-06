<?php

/**
 * Sanitizes user input to prevent XSS attacks.
 * ALWAYS wrap user data in this before echoing it to the HTML.
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Displays an error message for a specific form field.
 */
function display_error($errors, $field) {
    if (isset($errors[$field])) {
        // Outputting a clean, custom CSS class 'error-text' (You will style this in style.css)
        echo '<span class="error-text" style="color: red; font-size: 0.85em;">' . sanitize($errors[$field]) . '</span>';
    }
}

function auth($required_role = null) {
    // 1. First, check if they are logged in at all
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // 2. If you asked for a specific role (like 'Member' or 'Admin'), check it!
    if ($required_role !== null) {
        // Check if their session role matches the required role
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
            // If they are not the right role, kick them back to the homepage
            echo "<script>
                    alert('Access Denied: You do not have permission to view this page.');
                    window.location.href = 'index.php';
                  </script>";
            exit();
        }
    }
}
?>