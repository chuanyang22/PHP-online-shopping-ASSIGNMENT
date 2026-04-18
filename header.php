<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
<body class="home-body">
    
    <div class="navbar">
        <div class="navbar-brand">
            <a href="index.php">🛍️ Online Store</a>
        </div>
        
        <div class="navbar-profile">
            <a href="index.php">🏠 Home</a>
            <span class="navbar-divider"></span>

            <?php if(isset($_SESSION['username'])): ?>
                <a href="cart.php">🛒 My Cart</a>
                <span class="navbar-divider"></span>
                
                <a href="member/order_history.php">📜 My Orders</a>
                <span class="navbar-divider"></span>

                <a href="wishlist.php">❤️ My Wishlist</a>
                <span class="navbar-divider"></span>
                
                <a href="profile.php">🧏‍♂️ My Profile</a>
                <span class="navbar-divider"></span>
                
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="register.php">Sign Up</a>
                <span class="navbar-divider"></span>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>