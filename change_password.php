<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

auth('Member'); // NEW: Only logged-in users can access this page

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_msg = '';

// Re-fetching user to check the hashed password
$stmt = $pdo->prepare("SELECT * FROM member WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (isset($_POST['change_password'])) {
    $current_pw = $_POST['current_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];

    if (password_verify($current_pw, $user['password'])) {
        
        if (strlen($new_pw) < 8) {
            $errors['new_password'] = "New password must be at least 8 characters.";
        } else if ($new_pw !== $confirm_pw) {
            $errors['confirm_password'] = "New passwords do not match.";
        } else {
            $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE member SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_pw, $user_id]);
            $success_msg = "Password changed successfully!";
        }
    } else {
        $errors['current_password'] = "Incorrect current password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="css/mainstyle.css"> 
</head>

<body class="auth-body"> <div class="auth-card">
        <h2 style="margin-top: 0; color: #333;">Change Password</h2>
        
        <p>
            <a href="profile.php" style="color: #7231fd; text-decoration: under; font-weight: bold;">Back to Profile Hub</a>
        </p>
        <br>

        <?php if (isset($error_msg)): ?>
            <div style="color: red; font-size: 13px; margin-bottom: 15px;"><?= $error_msg ?></div>
        <?php endif; ?>
        <?php if (isset($success_msg)): ?>
            <div style="color: green; border: 1px solid green; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="change_password.php">
            
            <input type="password" name="current_password" class="auth-input" placeholder="Current Password" required>
            
            <input type="password" name="new_password" class="auth-input" placeholder="New Password" required>
            
            <input type="password" name="confirm_password" class="auth-input" placeholder="Confirm New Password" required>
            
            <button type="submit" name="change_password" class="auth-btn">Change Password</button>

        </form>
    </div>

</body>
</html>