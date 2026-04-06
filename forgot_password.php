<?php
// forgot_password.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/mailer.php';

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIXED: Changed from 'email' to 'login_id' to match your HTML form
    $login_id = trim($_POST['login_id'] ?? '');

    if (empty($login_id)) {
        $error_msg = "Please enter your email or username.";
    } else {
        // FIXED: Check both email AND username
        $stmt = $pdo->prepare("SELECT * FROM member WHERE email = ? OR username = ?");
        $stmt->execute([$login_id, $login_id]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 1800); 

            $stmt = $pdo->prepare("UPDATE member SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            // Make sure the path to reset_password.php is correct
            $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            
            // Replaced backslashes with forward slashes just in case dirname() acts up on Windows
            $reset_link = str_replace('\\', '/', $reset_link);

            $headline = "Password Reset Request";
            $body_content = "<p>You requested a password reset. Click the link below to set a new password:</p>
                             <p style='text-align: center; margin: 30px 0;'>
                                <a href='$reset_link' style='background-color: #ee4d2d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
                             </p>
                             <p>Or copy and paste this URL into your browser: <br><small>$reset_link</small></p>
                             <p>This link will expire in 30 minutes.</p>";

            if (send_formatted_email($user['email'], $user['username'], 'Password Reset', $headline, $body_content)) {
                $success_msg = "If your account exists, a recovery link has been sent.";
            } else {
                $error_msg = "Failed to send email. Please check your server settings.";
            }
        } else {
            // For security, always show success even if the account doesn't exist to prevent hackers guessing emails
            $success_msg = "If your account exists, a recovery link has been sent.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Account - Online Accessory Store</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title">Recover Account</div>
        
        <?php if (!empty($success_msg)): ?>
            <div class="auth-success" style="color: green; background-color: #dfd; border: 1px solid green; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
                <?= $success_msg ?>
            </div>
            <p style="text-align: center; color: #6b7280; font-size: 13px;">
                Please check your inbox (and spam folder) for the recovery link.
            </p>
        <?php else: ?>
            <div class="auth-subtitle" style="text-align: center; margin-bottom: 15px;">
                Enter your registered <strong>email or username</strong> below and we will send you a secure link to recover your access.
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="auth-error" style="color: red; background-color: #fdd; border: 1px solid red; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <input type="text" name="login_id" class="auth-input" placeholder="Email or Username" required>
                <button type="submit" class="auth-btn">Send Recovery Email</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer" style="margin-top: 20px; text-align: center; font-size: 14px;">
            Remembered your password? <a href="login.php" style="color: #0056b3;">Log In</a>
        </div>
    </div>
</body>
</html>