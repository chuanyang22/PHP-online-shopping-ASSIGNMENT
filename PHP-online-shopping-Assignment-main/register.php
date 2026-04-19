<?php
require_once 'lib/db.php';
require_once 'lib/helpers.php';

$errors      = [];
$success_msg = '';  

// NEW: Catch the success message after the page refreshes!
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_msg = "Congratulations, you have successfully registered! <br><a href='login.php' class='auth-link-bold'>Click here to Log In</a>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username         = sanitize($_POST['username']);
    $email            = sanitize($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Username validation
    if (empty($username)) {
        $errors['username'] = "Username is required.";
    } else if (strlen($username) < 2 || strlen($username) > 20) {
        $errors['username'] = "Bro, username must be between 2 to 20 characters lah.";
    }

    // Email validation
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Aiyoh use correct email format lah...";
    }

    // Password validation
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } else if (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters lah, safety mah.";
    }

    // Confirm password validation
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Aiyoh, copy your password also can mismatch....";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM member WHERE email = ?");  
        $stmt->execute([$email]);  
        if ($stmt->rowCount() > 0) {
            $errors['email'] = "This email is already registered.";  
        }
    }

    // Insert new member
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role            = 'Member';  

        $sql  = "INSERT INTO member (username, email, password, role) VALUES (?, ?, ?, ?)";  
        $stmt = $pdo->prepare($sql);  

        try {
            $stmt->execute([$username, $email, $hashed_password, $role]);
            
            // Redirect to show the success message
            header("Location: register.php?success=1");
            exit;

        } catch (PDOException $e) {
            $errors['general'] = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title">Create Account</div>

        <?php if (!empty($success_msg)): ?>
            <div class="auth-success-box">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="auth-error-box">
                <ul class="auth-error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="text" name="username" class="auth-input" placeholder="Username" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
            <input type="email" name="email" class="auth-input" placeholder="Email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
            <input type="password" name="password" class="auth-input" placeholder="Password">
            <input type="password" name="confirm_password" class="auth-input" placeholder="Confirm Password">
            
            <button type="submit" class="auth-btn">NEXT</button>
        </form>

        <div class="auth-footer-text">
            Already have an account? <a href="login.php" class="link-primary">Login</a>
            <br><br>
            <a href="index.php" class="link-primary">Return to Store</a>
        </div>
    </div>
</body>
</html>