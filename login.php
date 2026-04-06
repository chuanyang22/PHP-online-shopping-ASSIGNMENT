<?php
// login.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/mailer.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = $_POST['login_id'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // --- NEW VALIDATION: Check for empty fields BEFORE checking the database ---
    if ($password === '') {
        $errors['general'] = "Please fill in your password.";
    } elseif ($login_id === '') {
        $errors['general'] = "Please fill in your email or username.";
    } else {
        // Only run the database checks if both boxes have something in them
        $stmt = $pdo->prepare("SELECT * FROM member WHERE email = ? OR username = ?");
        $stmt->execute([$login_id, $login_id]);
        $user = $stmt->fetch();

       // Handle "Remember Me" Cookie
        if (isset($_POST['remember'])) {
            setcookie("user_login", $login_id, time() + (30 * 24 * 60 * 60), "/"); // Keeps saving the username
            $_SESSION['wants_remember_me'] = true; // NEW: Tell the OTP page to keep them logged in
        } else {
            if (isset($_COOKIE["user_login"])) {
                setcookie("user_login", "", time() - 3600, "/");
            }
            $_SESSION['wants_remember_me'] = false; // NEW
        }

        if ($user) {
            // HARD ACCOUNT BLOCK CHECK
            if ($user['status'] == 'Blocked') {
                $errors['general'] = "Your account is blocked due to too many failed attempts! <a href='forgot_password.php' style='color: #ee4d2d;'>Please reset your password to unblock.</a>";
            }

            // ESCALATING TEMPORARY LOCKOUT CHECK
            if ($user['lockout_time'] !== null && empty($errors)) {
                $lockout_time = strtotime($user['lockout_time']);
                $current_time = time();

                if ($current_time < $lockout_time) {
                    $minutes_left = ceil(($lockout_time - $current_time) / 60);
                    $errors['general'] = "Account temporarily locked. Please try again in $minutes_left minute(s).";
                } else {
                    $stmt = $pdo->prepare("UPDATE member SET lockout_time = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);
                }
            }

            // ONLY PROCEED IF NO LOCKOUT ERRORS
            if (empty($errors)) {
                if (password_verify($password, $user['password'])) {
                    
                    // LOGIN PHASE 1: PASSWORD SUCCESS -> SEND OTP
                    $otp = random_int(100000, 999999);
                    $otp_expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes

                    $stmt = $pdo->prepare("UPDATE member SET otp_code = ?, otp_expires = ? WHERE id = ?");
                    $stmt->execute([$otp, $otp_expires, $user['id']]);

                    $headline = "Your Secure Log In Code";
                    $body_content = "<p>Welcome back! Please enter this code on the website to complete your login:</p>
                                     <h2 style='background-color: #f8f9fa; padding: 20px; text-align: center; color: #ee4d2d; letter-spacing: 5px;'>$otp</h2>
                                     <p>This code will expire in 5 minutes.</p>";
                    
                    if (send_formatted_email($user['email'], $user['username'], 'Your Store Login Code', $headline, $body_content)) {
                        $_SESSION['pending_user_id'] = $user['id']; 
                        header("Location: login_otp.php");
                        exit();
                    } else {
                        $stmt = $pdo->prepare("UPDATE member SET otp_code = NULL, otp_expires = NULL WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        $errors['general'] = "System Error: Failed to send OTP email.";
                    }

                } else {
                    // FAILED ATTEMPT LOGIC
                    $attempts = $user['failed_attempts'] + 1;
                    
                    if ($attempts >= 3) {
                        $lockout_count = $user['lockout_count'] + 1;
                        $minutes = 5 + (5 * ($lockout_count - 1));
                        
                        if ($minutes > 15) $minutes = 15;
                        $lockout_until = date('Y-m-d H:i:s', time() + ($minutes * 60));
                        
                        if ($lockout_count >= 4) {
                            $stmt = $pdo->prepare("UPDATE member SET failed_attempts = 0, lockout_time = NULL, status = 'Blocked' WHERE id = ?");
                            $stmt->execute([$user['id']]);
                            $errors['general'] = "Too many failed login attempts! Account is blocked! Please reset your password to unblock.";
                        } else {
                            $stmt = $pdo->prepare("UPDATE member SET failed_attempts = ?, lockout_time = ?, lockout_count = ? WHERE id = ?");
                            $stmt->execute([$attempts, $lockout_until, $lockout_count, $user['id']]);
                            $errors['general'] = "Too many failed attempts. Account locked for $minutes minute(s).";
                        }
                    } else {
                        $stmt = $pdo->prepare("UPDATE member SET failed_attempts = ? WHERE id = ?");
                        $stmt->execute([$attempts, $user['id']]);
                        $chances_left = 3 - $attempts;
                        $errors['general'] = "Incorrect password. You have $chances_left attempt(s) left.";
                    }
                }
            }
        } else {
            $errors['general'] = "Account not found. <a href='register.php' style='color: #ee4d2d;'>Please register here.</a>";
        }
    } // End of empty fields check
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/mainstyle.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/login.js"></script>
    <title>Log In - Online Accessory Store</title>
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title">Log In</div>
        
        <p style="text-align: center; color: #121010; margin-bottom: 20px; font-size: 14px; box-shadow: 0 5px 5px rgba(58, 36, 36, 0.1); padding: 15px; border-radius: 4px;">
            Please enter your email or username and password to log in.
        </p>

        <?php if (!empty($errors['general'])): ?>
            <div class="auth-error" style="color: red; background-color: #fdd; border: 1px solid red; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
                <?= $errors['general'] ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <input type="text" style="display:none">
            <input type="password" style="display:none">

            <input type="text" name="login_id" class="auth-input" placeholder="Email or Username" required autocomplete="one-time-code" value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>">
            <input type="password" name="password" class="auth-input" placeholder="Password" required autocomplete="new-password">

            <div class="remember-me-container">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember Me</label>
            </div>

            <button type="submit" class="auth-btn">Log In</button>
        </form>

        <div class="auth-footer" style="margin-top: 20px; text-align: center; font-size: 14px;">
            New to Online Accessory Store? <a href="register.php" style="color: #0056b3; text-decoration: underline;">Sign Up</a>
            <br><br>
            <a href="forgot_password.php" style="color: #ee4d2d; font-weight: bold; text-decoration: none;">Forgot Password or Account Blocked?</a>
            
            <br><br>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">
            <a href="admin/login.php" style="color: #666; font-size: 13px; text-decoration: none; font-weight: bold;">🛡️ Log in as Admin</a>
        </div>
    </div>
</body>
</html>