<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info for display
$stmt = $pdo->prepare("SELECT * FROM member WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile Hub</title>
    </head>
<body>

    <div style="text-align: center; margin-top: 20px;">
        <h2>My Profile</h2>
        <a href="index.php">Back to Home</a> | <a href="logout.php">Logout</a>
        <br><br>

        <div id="current-photo">
            <?php if (!empty($user['profile_photo'])): ?>
                <img src="uploads/<?= sanitize($user['profile_photo']) ?>" alt="Profile Photo" width="150" style="border-radius: 50%; border: 3px solid #ccc;">
            <?php else: ?>
                <img src="https://via.placeholder.com/150" alt="Generic Placeholder" width="150" style="border-radius: 50%;">
            <?php endif; ?>
        </div>
        
        <h3>Welcome, <?= sanitize($user['username']) ?>!</h3>
        <p>Email: <?= sanitize($user['email']) ?></p>
        <p>Member Level: <?= sanitize($user['role']) ?></p>

        <hr>

        <div>
            <a href="upload_photo.php"><button>Upload New Photo</button></a>
            &nbsp;
            <a href="change_password.php"><button>Change Password</button></a>
        </div>
    </div>

    <?php 
    if (isset($_GET['login_success']) && $_GET['login_success'] == 1):
    ?>
        <script>
            alert("Login successful! Welcome to your Profile.");
        </script>
    <?php endif; ?>

</body>
</html>