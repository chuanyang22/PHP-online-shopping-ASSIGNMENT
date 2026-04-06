<?php
// reset_password.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/mailer.php';

// FIXED: Removed auth('Member') because they are NOT logged in when resetting!

$error_msg = "";
$success_msg = "";
$token_valid = false;
$user_id = null;
$token = "";

// 1. Verify the Token from the URL
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

// 2. Handle the Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 8) {
        $error_msg = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the database: change password, clear the token, and UNBLOCK the account!
        $stmt = $pdo->prepare("UPDATE member SET password = ?, reset_token = NULL, reset_expires = NULL, status = 'Active', failed_attempts = 0, lockout_count = 0, lockout_time = NULL WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success_msg = "Your password has been successfully reset! Your account is active.";
            $token_valid = false; // Hide the form
        } else {
            $error_msg = "System error: Failed to update password.";
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
            <div class="auth-error" style="color: red; background-color: #fdd; border: 1px solid red; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="auth-success" style="color: green; background-color: #dfd; border: 1px solid green; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
                <?= $success_msg ?>
            </div>
            <br>
            <a href="login.php" class="auth-btn" style="text-decoration: none; display: block; text-align: center; box-sizing: border-box;">GO TO LOGIN</a>
        <?php endif; ?>

        <?php if ($token_valid): ?>
            <div class="auth-subtitle" style="text-align: center; margin-bottom: 15px;">
                Please enter your new professional password below. This must be a minimum of 8 characters.
            </div>
            <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>">
                <input type="password" name="new_password" class="auth-input" placeholder="New Password (min. 8 characters)" required>
                <input type="password" name="confirm_password" class="auth-input" placeholder="Confirm New Password" required>

                <button type="submit" class="auth-btn">Update Password</button>
            </form>
        <?php endif; ?>

        <?php if (!$token_valid && empty($success_msg)): ?>
            <div class="auth-footer" style="margin-top: 20px; text-align: center;">
                <a href="login.php" style="color: #0056b3;">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>