<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// Kick them out if they aren't logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view your wishlist. <a href='login.php'>Click here to login.</a>");
}

$user_id = $_SESSION['user_id'];

// Fetch only the products that are in this specific user's wishlist
$stmt = $pdo->prepare("
    SELECT p.* FROM products p
    JOIN wishlist w ON p.id = w.product_id
    WHERE w.member_id = ?
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist</title>
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
    </div><!-- /.navbar -->
    
    <div class="home-container">
        <h2>Your Wishlist ❤️</h2>

        <?php if(isset($_SESSION['popup'])): ?>
            <div class="auth-success-box"><?= htmlspecialchars($_SESSION['popup']) ?></div>
            <?php unset($_SESSION['popup']); ?>
        <?php endif; ?>

        <?php if(count($wishlist_items) > 0): ?>
            <div class="product-grid">
                <?php foreach($wishlist_items as $item): ?>
                    <div class="product-card">
                        <?php $imagePath = !empty($item['image_name']) ? 'uploads/' . htmlspecialchars($item['image_name']) : 'uploads/default.png'; ?>
                        <img src="<?= $imagePath ?>" class="product-image">
                        
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="price">$<?= number_format($item['price'], 2) ?></p>
                        
                        <a href="product_detail.php?id=<?= $item['id'] ?>" class="btn btn-block-sm">View Details</a>
                        
                        <form action="wishlist_action.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-remove btn-block-sm">Remove from Wishlist</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Your wishlist is currently empty! <a href="index.php" class="link-primary">Go find some cool stuff.</a></p>
        <?php endif; ?>

    </div>
</body>
</html>