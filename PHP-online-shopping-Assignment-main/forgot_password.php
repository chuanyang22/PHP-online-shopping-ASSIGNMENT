<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/mailer.php';

$current_lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
require_once file_exists($lang_file) ? $lang_file : __DIR__ . "/lang/en.php";

$success_msg = "";
$error_msg   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');

    if (empty($login_id)) {
        $error_msg = "Please enter your email or username.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM member WHERE email = ? OR username = ?");
        $stmt->execute([$login_id, $login_id]);
        $user = $stmt->fetch();

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 1800);

            $pdo->prepare("UPDATE member SET reset_token = ?, reset_expires = ? WHERE id = ?")
                ->execute([$token, $expires, $user['id']]);

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $base_dir = str_replace("\\", "/", dirname($_SERVER['PHP_SELF']));
            $base_dir = trim($base_dir, "/");
            $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . ($base_dir === "" || $base_dir === "." ? "" : "/" . $base_dir);
            $reset_link = $base_url . "/reset_password.php?token=" . urlencode($token);

            $headline     = "Password Reset";
            $body_content = "
                <p>Click the link below to securely reset your password. This link expires in 30 minutes.</p>
                <a href='$reset_link' style='background:#ee4d2d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;display:inline-block;'>Reset My Password</a>
            ";

            if (send_formatted_email($user['email'], $user['username'], 'Password Reset', $headline, $body_content)) {
                $success_msg = "If your account exists, a recovery link has been sent.";
            } else {
                $error_msg = "Failed to send email. Please check your server settings.";
            }
        } else {
            $success_msg = "If your account exists, a recovery link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['recover_account'] ?></title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title"><?= $lang['recover_account'] ?></div>

        <?php if (!empty($success_msg)): ?>
            <div class="auth-success"><?= $success_msg ?></div>
            <p class="auth-muted-text"><?= $lang['check_inbox'] ?></p>
        <?php else: ?>
            <div class="auth-subtitle"><?= $lang['forgot_subtitle'] ?></div>

            <?php if (!empty($error_msg)): ?>
                <div class="auth-error-box"><?= $error_msg ?></div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <input type="text" name="login_id" class="auth-input"
                       placeholder="<?= htmlspecialchars($lang['email_or_user']) ?>" required>
                <button type="submit" class="auth-btn"><?= $lang['send_recovery'] ?></button>
            </form>
        <?php endif; ?>

        <div class="auth-footer-text">
            <br>
            <a href="login.php" class="link-primary"><?= $lang['back_to_login'] ?></a>
        </div>
    </div>
</body>
</html>