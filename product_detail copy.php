<?php
// product_detail.php
session_start(); 
require_once 'lib/db.php';

if (!isset($_GET['id'])) {
    die("Error: Product ID is missing.");
}
$product_id = $_GET['id'];

$in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $w_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE member_id = ? AND product_id = ?");
    $w_stmt->execute([$_SESSION['user_id'], $product_id]);
    $in_wishlist = $w_stmt->rowCount() > 0;
    $cart_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
}

try {
    $stmt = $pdo->prepare("SELECT products.*, categories.name AS category_name 
                           FROM products 
                           LEFT JOIN categories ON products.category_id = categories.id 
                           WHERE products.id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Error: Product not found in our database.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Details</title>
    <link rel="stylesheet" href="css/mainstyle.css"> 
</head>
<body class="detail-page-body">

<div class="detail-card">
    <img src="uploads/<?php echo htmlspecialchars($product['image_name']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    <div class="detail-category">Category: <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
    <div class="detail-price">RM <?php echo number_format($product['price'], 2); ?></div>
    
    <div class="detail-stock">
        <?php if ($product['stock_quantity'] > 0): ?>
            ✅ In Stock: <?php echo $product['stock_quantity']; ?>
        <?php else: ?>
            ❌ Out of Stock
        <?php endif; ?>
    </div>

    <?php if (isset($cart_qty) && $cart_qty > 0): ?>
        <div class="cart-alert">
            🛒 You have <?php echo $cart_qty; ?> of this in your cart.
        </div>
    <?php endif; ?>

    <form action="cart.php" method="POST" class="detail-form">
        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
        <input type="hidden" name="action" value="add">
        <label for="quantity" class="qty-label">Qty:</label>
        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="qty-input">
        <br>
        <?php if ($product['stock_quantity'] > 0): ?>
            <button type="submit" class="btn btn-add-to-cart">Add to Cart</button>
        <?php else: ?>
            <button type="button" class="btn btn-out-of-stock" disabled>Out of Stock</button>
        <?php endif; ?>
    </form>

    <?php if ($in_wishlist): ?>
        <p class="wishlist-saved-msg">❤️ Saved to Wishlist</p>
    <?php else: ?>
        <form action="wishlist_action.php" method="POST" class="detail-form">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="action" value="add">
            <button type="submit" class="btn-detail-wishlist">❤️ Save to Wishlist</button>
        </form>
    <?php endif; ?>

    <a href="index.php" class="detail-back-link">⬅ Back to Store</a>
</div>

</body>
</html>