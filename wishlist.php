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
    <style>
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .product-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .product-image { max-width: 100%; height: 150px; object-fit: cover; border-radius: 4px; }
        .price { color: #27ae60; font-weight: bold; font-size: 1.2em; }
        .btn { display: inline-block; background: #3498db; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; margin-top: 10px; cursor: pointer; border: none; font-size: 1em; }
        .btn-remove { background: #e74c3c; width: 100%; box-sizing: border-box; }
        .btn-remove:hover { background: #c0392b; }
    </style>
</head>
<body style="margin: 0; background-color: #f5f5f5;">

    <div class="navbar">
        <div class="navbar-brand">
            <a href="index.php">🛍️ Online Store</a>
        </div>
        <div class="navbar-profile">
            <a href="index.php">Back to Shop</a>
        </div>
    </div>

    <div style="padding: 20px; max-width: 1200px; margin: 0 auto;">
        <h2>My Saved Items ❤️</h2>

        <?php if(isset($_GET['msg']) && $_GET['msg'] === 'removed'): ?>
            <p style="color: #c0392b; font-weight: bold;">Item removed from wishlist.</p>
        <?php endif; ?>

        <?php if(count($wishlist_items) > 0): ?>
            <div class="product-grid">
                <?php foreach($wishlist_items as $item): ?>
                    <div class="product-card">
                        <?php $imagePath = !empty($item['image_name']) ? 'uploads/' . htmlspecialchars($item['image_name']) : 'uploads/default.png'; ?>
                        <img src="<?= $imagePath ?>" class="product-image">
                        
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="price">$<?= number_format($item['price'], 2) ?></p>
                        
                        <a href="product_detail.php?id=<?= $item['id'] ?>" class="btn" style="width: 100%; box-sizing: border-box; margin-bottom: 5px;">View Details</a>
                        
                        <form action="wishlist_action.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-remove">Remove from Wishlist</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Your wishlist is currently empty! <a href="index.php">Go find some cool stuff.</a></p>
        <?php endif; ?>

    </div>
</body>
</html>