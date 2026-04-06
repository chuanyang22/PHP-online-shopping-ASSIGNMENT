<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// 1. Delete the token from the database
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE member SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// 2. Delete the 30-day auto-login cookie from their browser
setcookie("auto_login_token", "", time() - 3600, "/");

// 3. Destroy normal session
session_unset();
session_destroy();

header("Location: login.php");
exit;
?>