<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

auth('Member');

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
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="profile-card">
        <h2>My Profile</h2>
        <p>
            <a href="index.php" style="color: #3d1ac7; text-decoration: underline;">Back to Home</a> | 
            <a href="logout.php" style="color: #3d1ac7; text-decoration: underline;">Logout</a> |
            <a href="member/order_history.php" style="color: #3d1ac7; text-decoration: underline">Orders History</a> 
        </p>
        <br>

        <?php if (!empty($user['profile_photo']) && file_exists('uploads/' . $user['profile_photo'])): ?>
            <img src="uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile Picture" class="profile-pic"
                 style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #ee4d2d;">
        <?php else: ?>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&size=150&background=random&color=fff"
                 alt="Default Avatar" class="profile-pic" style="border-radius: 50%;">
        <?php endif; ?>

        <div class="profile-info">
            <h3>Welcome, <?= htmlspecialchars($user['username']) ?>!</h3>
            <p>Email: <?= htmlspecialchars($user['email']) ?></p>
            <p>Member Level: <?= htmlspecialchars($user['role']) ?></p>
        </div>

        <hr class="profile-divider">

        <?php if (isset($_GET['photo_updated'])): ?>
            <div class="auth-success">✅ Profile photo updated successfully!</div>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="upload_photo.php">
                <button class="auth-btn" style="width: 100%;">UPLOAD NEW PHOTO</button>
            </a>
            <a href="change_password.php">
                <button class="auth-btn" style="width: 100%;">CHANGE PASSWORD</button>
            </a>
            <a href="edit_profile.php">
                <button class="auth-btn" style="width: 100%;">EDIT PROFILE DETAILS</button>
            </a>    
        </div>

    </div>
</body>
</html>