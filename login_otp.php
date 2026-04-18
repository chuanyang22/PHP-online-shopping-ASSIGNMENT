<?php
// login_otp.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

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
                
                // LOGIN SUCCESS!
                $stmt = $pdo->prepare("UPDATE member SET otp_code = NULL, otp_expires = NULL, failed_attempts = 0, lockout_count = 0, lockout_time = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                unset($_SESSION['pending_user_id']);

                // ==========================================
                // NEW: AUTO-LOGIN TOKEN GENERATOR
                // ==========================================
                if (isset($_SESSION['wants_remember_me']) && $_SESSION['wants_remember_me'] === true) {
                    $token = bin2hex(random_bytes(32)); 
                    
                    $stmt = $pdo->prepare("UPDATE member SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]); 
                    
                    setcookie("auto_login_token", $token, time() + (30 * 24 * 60 * 60), "/");
                    unset($_SESSION['wants_remember_me']); // Clean up session variable
                }
                // ==========================================

                // JAVASCRIPT SUCCESS POP-UP!
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/mainstyle.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Code - Online Accessory Store</title>
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title">Enter Login Code</div>
        <p class="otp-instruction">Please enter the 6-digit code sent to your email. It expires in 5 minutes.</p>

        <?php if (isset($errors['general'])): ?>
            <div class="auth-error-box">
                <?= $errors['general'] ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login_otp.php">
            <input type="text" name="otp_code" class="auth-input otp-input" placeholder="6-Digit Code" required>
            <button type="submit" class="auth-btn">VERIFY CODE</button>
        </form>
    </div>
</body>
</html>