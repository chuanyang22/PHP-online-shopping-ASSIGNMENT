<?php
// reset_password.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/mailer.php';

$error_msg = "";
$success_msg = "";
$token_valid = false;
$user_id = null;
$token = "";

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM member WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user && strtotime($user['reset_expires']) > time()) {
        $token_valid = true;
        $user_id = $user['id'];
    } else {
        $error_msg = "This link is invalid or has expired. <a href='forgot_password.php' style='color: #ee4d2d; font-weight: bold;'>Request a new one</a>.";
    }
} else {
    $error_msg = "No reset token provided. Please use the link sent to your email.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error_msg = "Please fill in both password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "Passwords do not match! Please try again.";
    } elseif (strlen($new_password) < 8) { 
        $error_msg = "Your new password must be at least 8 characters long."; 
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE member SET password = ?, reset_token = NULL, reset_expires = NULL, failed_attempts = 0, lockout_count = 0, lockout_time = NULL, status = 'Active' WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success_msg = "Success! Your password has been updated and your access is fully restored.";
            $token_valid = false; 
        } else {
            $error_msg = "System error: Failed to update password. Please contact support.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Online Accessory Store</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title">Set New Password</div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="auth-error">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="auth-success">
                <?= $success_msg ?>
            </div>
            <a href="login.php" class="auth-btn" style="text-decoration: none; display: block; text-align: center; box-sizing: border-box;">GO TO LOGIN</a>
        <?php endif; ?>

        <?php if ($token_valid): ?>
            <div class="auth-subtitle">
                Please enter your new professional password below. This must be a minimum of 8 characters.
            </div>
            <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>">
                <input type="password" name="new_password" class="auth-input" placeholder="New Password (min. 8 characters)" required>
                <input type="password" name="confirm_password" class="auth-input" placeholder="Confirm New Password" required>

                <button type="submit" class="auth-btn">Update Password</button>
            </form>
        <?php endif; ?>

        <?php if (!$token_valid && empty($success_msg)): ?>
            <div class="auth-footer">
                <a href="login.php">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>