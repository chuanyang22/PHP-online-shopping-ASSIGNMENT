<?php
// login_otp.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/cart_persist.php';

$current_lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
require_once file_exists($lang_file) ? $lang_file : __DIR__ . "/lang/en.php";

if (!isset($_SESSION['pending_user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = trim($_POST['otp_code']);

    if (empty($otp_code)) {
        $errors['general'] = "Please enter the 6-digit code we sent you.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM member WHERE id = ?");
        $stmt->execute([$_SESSION['pending_user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['otp_code'] === $otp_code && strtotime($user['otp_expires']) > time()) {
                $pdo->prepare("UPDATE member SET otp_code = NULL, otp_expires = NULL, failed_attempts = 0, lockout_count = 0, lockout_time = NULL WHERE id = ?")
                    ->execute([$user['id']]);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];
                unset($_SESSION['pending_user_id']);

                if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                $dbCart = cart_load_member_cart($pdo, (int) $user['id']);
                $_SESSION['cart'] = cart_merge_carts($_SESSION['cart'], $dbCart);
                cart_save_member_cart($pdo, (int) $user['id'], $_SESSION['cart']);

                if (isset($_SESSION['wants_remember_me']) && $_SESSION['wants_remember_me'] === true) {
                    $token = bin2hex(random_bytes(32));
                    $pdo->prepare("UPDATE member SET remember_token = ? WHERE id = ?")->execute([$token, $user['id']]);
                    setcookie("auto_login_token", $token, time() + (30 * 24 * 60 * 60), "/");
                    unset($_SESSION['wants_remember_me']);
                }

                echo "<script>
                        alert('Login successful! Welcome back, " . htmlspecialchars($user['username']) . "!');
                        window.location.href = 'index.php';
                      </script>";
                exit();
            } else {
                $errors['general'] = "Incorrect or expired code. Please try again.";
            }
        } else {
            unset($_SESSION['pending_user_id']);
            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['enter_code'] ?></title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title"><?= $lang['enter_code'] ?></div>
        <p class="otp-instruction"><?= $lang['otp_instruction'] ?></p>

        <?php if (isset($errors['general'])): ?>
            <div class="auth-error-box"><?= $errors['general'] ?></div>
        <?php endif; ?>

        <form method="POST" action="login_otp.php">
            <input type="text" name="otp_code" class="auth-input otp-input"
                   placeholder="<?= htmlspecialchars($lang['digit_code']) ?>" required>
            <button type="submit" class="auth-btn"><?= $lang['verify_btn'] ?></button>
        </form>
    </div>
</body>
</html>