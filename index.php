<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Accessory Store - Home</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body style="margin: 0; background-color: #f5f5f5;">

    <div class="navbar">
        
        <div class="navbar-brand">
            <a href="index.php">🛍️ Online Store</a>
        </div>
        
        <div class="navbar-profile">
            <?php if(isset($_SESSION['username'])): ?>
                
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <span class="navbar-divider"></span>
                <a href="profile.php">My Profile</a>
                <span class="navbar-divider"></span>
                <a href="logout.php">Logout</a>

            <?php else: ?>
                
                <a href="register.php">Sign Up</a>
                <span class="navbar-divider"></span>
                <a href="login.php">Login</a>

            <?php endif; ?>
        </div>
    </div>

    <div class="home-container">
        <h2>Welcome to the Store!</h2>
        <p>This is where we will display all your amazing products.</p>
        
        <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p>Product grid goes here...</p>
        </div>
    </div>

</body>
</html>