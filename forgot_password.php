<?php
// forgot_password.php
session_start();
require_once 'lib/db.php';
require_once 'lib/mailer.php';

$success_msg = "";
$error_msg = "";

if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); 
}
if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $_SESSION['error_msg'] = "Please enter your email address.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM member WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 1800); 

            $stmt = $pdo->prepare("UPDATE member SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $current_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $current_url = explode('?', $current_url)[0]; 
            $reset_link = str_replace('forgot_password.php', 'reset_password.php', $current_url) . "?token=" . $token;

            $headline = "Account Recovery";
            $body_content = '
            <div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; padding: 40px 20px;">
                <div style="max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 35px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid #ee4d2d;">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; letter-spacing: 0.5px;">Online Accessory Store</h2>
                    </div>
                    <p style="font-size: 16px; color: #333333; margin-bottom: 20px;">Hello <strong>' . htmlspecialchars($user['username']) . '</strong>,</p>
                    <p style="font-size: 15px; color: #555555; line-height: 1.6; margin-bottom: 15px;">We received a request to reset your password or unblock your account.</p>
                    <p style="font-size: 15px; color: #555555; line-height: 1.6; margin-bottom: 30px;">To secure your account and set a new password, please click the button below. If your account was locked due to multiple login attempts, this will also restore your access.</p>
                    <div style="text-align: center; margin-bottom: 35px;">
                        <a href="' . $reset_link . '" style="background-color: #ee4d2d; color: #ffffff; padding: 14px 28px; text-decoration: none; font-weight: bold; font-size: 15px; border-radius: 5px; display: inline-block;">RESET MY PASSWORD</a>
                    </div>
                    <div style="border-top: 1px solid #eeeeee; padding-top: 20px; text-align: center;">
                        <p style="font-size: 12px; color: #999999; margin: 0; line-height: 1.5;">If you did not request this change, you can safely ignore this email.<br>Your account remains perfectly secure.</p>
                    </div>
                </div>
            </div>';

            if (send_formatted_email($user['email'], $user['username'], "Password Reset Request", $headline, $body_content, $reset_link, "RESET PASSWORD")) {
                $_SESSION['success_msg'] = "If an account matches that email, a secure recovery link has been sent.";
            } else {
                $_SESSION['error_msg'] = "System error: Failed to send recovery email. Please try again later.";
            }
        } else {
            $_SESSION['success_msg'] = "If an account matches that email, a secure recovery link has been sent.";
        }
    }
    
    header("Location: forgot_password.php");
    exit();
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
            <div class="auth-success">
                <?= $success_msg ?>
            </div>
            <p style="text-align: center; color: #6b7280; font-size: 13px;">
                Please check your inbox (and spam folder) for the recovery link.
            </p>
        <?php else: ?>
            <div class="auth-subtitle">
                Enter your registered <strong>email or username</strong> below and we will send you a secure link to recover your access.
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="auth-error">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <input type="text" name="login_id" class="auth-input" placeholder="Email or Username" required>
                <button type="submit" class="auth-btn">Send Recovery Email</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer">
            Remembered your details? <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>