<?php
// edit_profile.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

auth('Member'); 

$error_msg = "";
$success_msg = "";

// Get current info
$stmt = $pdo->prepare("SELECT username, email FROM member WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);

    // Check if the requested username or email is already taken by ANOTHER user
    $stmt = $pdo->prepare("SELECT id FROM member WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$new_username, $new_email, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        $error_msg = "That username or email is already taken. Please choose another.";
    } else {
        // Update database
        $stmt = $pdo->prepare("UPDATE member SET username = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$new_username, $new_email, $_SESSION['user_id']])) {
            // Update the session so the Navigation Bar updates immediately!
            $_SESSION['username'] = $new_username; 
            
            $success_msg = "Profile updated successfully!";
            $user['username'] = $new_username; // Update form fields
            $user['email'] = $new_email;
        } else {
            $error_msg = "Failed to update profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile Info</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title">Edit Profile Details</div>

        <?php if (!empty($error_msg)): ?>
            <div class="auth-error" style="color: red; margin-bottom: 15px;"><?= $error_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($success_msg)): ?>
            <div class="auth-success" style="color: green; margin-bottom: 15px;"><?= $success_msg ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_profile.php">
            <label style="font-size: 14px; color: #555;">Username</label>
            <input type="text" name="username" class="auth-input" value="<?= htmlspecialchars($user['username']) ?>" required>
            
            <label style="font-size: 14px; color: #555;">Email Address</label>
            <input type="email" name="email" class="auth-input" value="<?= htmlspecialchars($user['email']) ?>" required>

            <button type="submit" class="auth-btn">SAVE CHANGES</button>
        </form>

        <div class="auth-footer" style="margin-top: 15px; text-align: center;">
            <a href="profile.php" style="color: #666;">Cancel</a>
        </div>
    </div>
</body>
</html>