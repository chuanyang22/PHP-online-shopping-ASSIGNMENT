<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

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
</head>
<body>
    <div style="text-align: center; margin-top: 20px;">
        <h2>Change Password</h2>
        <a href="profile.php">Back to Profile Hub</a>
        <br><br>

        <?php if (!empty($success_msg)): ?>
            <p style='color:green;'><?= $success_msg ?></p>
        <?php endif; ?>

        <form method="POST" action="change_password.php" id="change-password-form">
            
            <div>
                <label>Current Password:</label><br>
                <input type="password" name="current_password" required>
                <?php display_error($errors, 'current_password'); ?>
            </div>
            <br>
            <div>
                <label>New Password:</label><br>
                <input type="password" name="new_password" required>
                <?php display_error($errors, 'new_password'); ?>
            </div>
            <br>
            <div>
                <label>Confirm New Password:</label><br>
                <input type="password" name="confirm_password" required>
                <?php display_error($errors, 'confirm_password'); ?>
            </div>
            <br>
            <button type="submit" name="change_password">Change Password</button>
        </form>
    </div>

    <script>
        document.getElementById('change-password-form').onsubmit = function() {
            return confirm("Are you sure you want to change your password?");
        }
    </script>

</body>
</html>