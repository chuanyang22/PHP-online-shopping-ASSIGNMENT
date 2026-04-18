<?php
// forgot_password.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/mailer.php';

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');

    if (empty($login_id)) {
        $error_msg = "Please enter your email or username.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM member WHERE email = ? OR username = ?");
        $stmt->execute([$login_id, $login_id]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 1800); 

            $stmt = $pdo->prepare("UPDATE member SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

            $subject = "Password Reset Request";
            $body = "
                <div class='email-otp'>
                    <h2>Password Reset</h2>
                    <p>Click the link below to securely reset your password. This link expires in 30 minutes.</p>
                    <a href='$reset_link' class='email-btn'>Reset My Password</a>
                </div>
            ";
            
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
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title">Recover Account</div>

        <?php if (!empty($success_msg)): ?>
            <div class="auth-success-box">
                <?= $success_msg ?>
            </div>
            <p class="auth-muted-text" style="text-align: center; font-size: 14px; color: #666;">
                Please check your inbox (and spam folder) for the recovery link.
            </p>
        <?php else: ?>
            <div class="auth-subtitle">
                Enter your registered <strong>email or username</strong> below and we will send you a secure link to recover your access.
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="auth-error-box">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <input type="text" name="login_id" class="auth-input" placeholder="Email or Username" required>
                <button type="submit" class="auth-btn">Send Recovery Email</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer-text">
            <br>
            <a href="login.php" class="link-primary">Back to Login</a>
        </div>
    </div>
</body>
</html>