<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/mailer.php';

// Load language
$current_lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
require_once file_exists($lang_file) ? $lang_file : __DIR__ . "/lang/en.php";

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = $_POST['login_id'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($password === '') {
        $errors['general'] = "Please fill in your password.";
    } elseif ($login_id === '') {
        $errors['general'] = "Please fill in your email or username.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM member WHERE email = ? OR username = ?");
        $stmt->execute([$login_id, $login_id]);
        $user = $stmt->fetch();

        if (isset($_POST['remember'])) {
            setcookie("user_login", $login_id, time() + (30 * 24 * 60 * 60), "/");
            $_SESSION['wants_remember_me'] = true;
        } else {
            if (isset($_COOKIE["user_login"])) setcookie("user_login", "", time() - 3600, "/");
            $_SESSION['wants_remember_me'] = false;
        }

        if ($user) {
            if ($user['status'] == 'Blocked') {
                // REMOVED INLINE CSS HERE
                $errors['general'] = "Your account is blocked! <a href='forgot_password.php' class='auth-link-danger'>Reset your password to unblock.</a>";
            }

            if ($user['lockout_time'] !== null && empty($errors)) {
                $lockout_time = strtotime($user['lockout_time']);
                if (time() < $lockout_time) {
                    $minutes_left   = ceil(($lockout_time - time()) / 60);
                    $errors['general'] = "Account temporarily locked. Try again in $minutes_left minute(s).";
                } else {
                    $pdo->prepare("UPDATE member SET lockout_time = NULL WHERE id = ?")->execute([$user['id']]);
                }
            }

            if (empty($errors)) {
                if (password_verify($password, $user['password'])) {
                    $otp        = random_int(100000, 999999);
                    $otp_expires= date('Y-m-d H:i:s', time() + 300);
                    $pdo->prepare("UPDATE member SET otp_code = ?, otp_expires = ? WHERE id = ?")->execute([$otp, $otp_expires, $user['id']]);

                    $headline     = "Your Secure Log In Code";
                    $body_content = "<p>Welcome back! Enter this code to complete your login:</p>
                                     <h2 style='background:#f8f9fa;padding:20px;text-align:center;color:#ee4d2d;letter-spacing:5px;'>$otp</h2>
                                     <p>This code expires in 5 minutes.</p>";

                    if (send_formatted_email($user['email'], $user['username'], 'Your Store Login Code', $headline, $body_content)) {
                        $_SESSION['pending_user_id'] = $user['id'];
                        header("Location: login_otp.php");
                        exit();
                    } else {
                        $pdo->prepare("UPDATE member SET otp_code = NULL, otp_expires = NULL WHERE id = ?")->execute([$user['id']]);
                        $errors['general'] = "System Error: Failed to send OTP email.";
                    }
                } else {
                    $attempts = $user['failed_attempts'] + 1;
                    if ($attempts >= 3) {
                        $lockout_count = $user['lockout_count'] + 1;
                        $minutes = min(5 + (5 * ($lockout_count - 1)), 15);
                        $lockout_until = date('Y-m-d H:i:s', time() + ($minutes * 60));
                        if ($lockout_count >= 4) {
                            $pdo->prepare("UPDATE member SET failed_attempts = 0, lockout_time = NULL, status = 'Blocked' WHERE id = ?")->execute([$user['id']]);
                            $errors['general'] = "Too many failed attempts. Account blocked!";
                        } else {
                            $pdo->prepare("UPDATE member SET failed_attempts = ?, lockout_time = ?, lockout_count = ? WHERE id = ?")->execute([$attempts, $lockout_until, $lockout_count, $user['id']]);
                            $errors['general'] = "Too many failed attempts. Account locked for $minutes minute(s).";
                        }
                    } else {
                        $pdo->prepare("UPDATE member SET failed_attempts = ? WHERE id = ?")->execute([$attempts, $user['id']]);
                        $errors['general'] = "Incorrect password. " . (3 - $attempts) . " attempt(s) left.";
                    }
                }
            }
        } else {
            // REMOVED INLINE CSS HERE
            $errors['general'] = "Account not found. <a href='register.php' class='auth-link-primary'>Register here.</a>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['login'] ?> — Online Store</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title"><?= $lang['welcome_back'] ?></div>

        <p class="auth-subtitle"><?= $lang['login_subtitle'] ?></p>

        <?php if (isset($errors['general'])): ?>
            <div class="auth-error-box"><?= $errors['general'] ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <input type="text"     class="hidden-input">
            <input type="password" class="hidden-input">

            <input type="text"     name="login_id" class="auth-input"
                   placeholder="<?= htmlspecialchars($lang['email_or_user']) ?>"
                   required autocomplete="one-time-code"
                   value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>">

            <input type="password" name="password" class="auth-input"
                   placeholder="<?= htmlspecialchars($lang['password']) ?>"
                   required autocomplete="new-password">

            <div class="remember-me-container">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember"><?= $lang['remember_me'] ?></label>
            </div>

            <button type="submit" class="auth-btn"><?= $lang['login_btn'] ?></button>
        </form>

        <div class="auth-footer-text">
            <?= $lang['no_account'] ?>
            <a href="register.php" class="auth-link-primary"><?= $lang['sign_up'] ?></a>
            <br><br>
            <a href="forgot_password.php" class="link-danger"><?= $lang['forgot_password'] ?></a>
            <br><br>
            <hr class="auth-divider">
            <a href="../admin/login.php" class="link-primary"><?= $lang['admin_login'] ?></a>
        </div>
    </div>
</body>
</html>