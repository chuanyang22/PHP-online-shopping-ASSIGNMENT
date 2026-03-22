<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email)) { $errors['email'] = "Email is required."; }
    if (empty($password)) { $errors['password'] = "Password is required."; }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM member WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $current_time = new DateTime();
            // Check for lockout
            if ($user['failed_attempts'] >= 3 && $user['lockout_time']) {
                $lockout_time = new DateTime($user['lockout_time']);
                if ($lockout_time > $current_time->modify('-5 minutes')) {
                    $errors['general'] = "Account locked due to too many failed attempts. Try again in 5 minutes.";
                } else {
                    // Reset lockout after 5 mins
                    $stmt = $pdo->prepare("UPDATE member SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $user['failed_attempts'] = 0;
                }
            }

            if (!isset($errors['general'])) {
                if (password_verify($password, $user['password'])) {
                    // Login Success
                    $stmt = $pdo->prepare("UPDATE member SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: profile.php?Login_success=1");
                    exit();
                } else {
                    // Login Failed
                    $new_attempts = $user['failed_attempts'] + 1;
                    $lockout_date = ($new_attempts >= 3) ? date('Y-m-d H:i:s') : NULL;
                    $stmt = $pdo->prepare("UPDATE member SET failed_attempts = ?, lockout_time = ? WHERE id = ?");
                    $stmt->execute([$new_attempts, $lockout_date, $user['id']]);

                    $errors['general'] = ($new_attempts >= 3) ? "Too many failed attempts. Account locked for 5 minutes." : "Invalid email or password.";
                }
            }
        } else {
           $errors['email'] = "Account not found.Please click below to register.";
        
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/menustyle.css">
    <script src="js/login.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <div style="text-align: center; margin-top: 50px;">
        <h2>Login</h2>
        <?php display_error($errors, 'general'); ?>
        <form method="POST" action="login.php" style="display: inline-block; text-align: left;">
            <div>
                <label>Email:</label>
                <br>
                <input type="text" name="email" value="<?= isset($email) ? sanitize($email) : '' ?>">
                <?php display_error($errors, 'email'); ?>
            </div>
            <br>
            <div>
                <label>Password:</label>
                <br><input type="password" name="password"><?php display_error($errors, 'password'); ?>
            </div>
            <br>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>