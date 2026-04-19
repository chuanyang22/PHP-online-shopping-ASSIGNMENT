<?php
// product_detail.php
session_start(); 
require_once 'lib/db.php';

// Include language support
$current_lang = $_SESSION['lang'] ?? 'en';
if (file_exists(__DIR__ . "/lang/{$current_lang}.php")) {
    require_once __DIR__ . "/lang/{$current_lang}.php";
}

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
        die("<h2 class='text-center text-main mt-50'>Error: Product not found.</h2>");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$cart_toast_message = null;
if (!empty($_SESSION['cart_flash_key'])) {
    $flash_key = $_SESSION['cart_flash_key'];
    unset($_SESSION['cart_flash_key']);
    if ($flash_key === 'added_to_cart') {
        $cart_toast_message = $lang['added_to_cart'] ?? 'Product added to your cart.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?></title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="home-body">
    <?php include 'header.php'; ?>
    
    <div class="page-container">
        <h2><?= htmlspecialchars($product['name']) ?></h2>
        
        <?php 
            $img_path = (!empty($product['image_name']) && file_exists('uploads/' . $product['image_name'])) 
                ? 'uploads/' . $product['image_name'] 
                : 'uploads/default.png';
        ?>
        <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img-large">
        
        <p><strong><?= $lang['category'] ?? 'Category' ?>:</strong> <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
        <p class="product-price">RM <?= number_format($product['price'], 2) ?></p>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <form action="cart.php" method="POST" class="detail-form mb-20">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="return" value="product_detail.php?id=<?= (int) $product['id'] ?>">
            
            <label for="quantity" class="qty-label font-bold mr-15"><?= $lang['qty'] ?? 'Qty' ?>:</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>" class="qty-input-small inline-block">
            <br><br>
            
            <?php if ($product['stock_quantity'] > 0): ?>
                <button type="submit" class="btn btn-add-to-cart btn-success-solid"><?= $lang['add_to_cart'] ?? 'Add to Cart' ?></button>
            <?php else: ?>
                <button type="button" class="btn btn-disabled" disabled><?= $lang['out_of_stock'] ?? 'Out of Stock' ?></button>
            <?php endif; ?>
        </form>

        <?php if ($in_wishlist): ?>
            <p class="wishlist-saved-msg text-danger-link mt-15">❤️ <?= $lang['saved_wishlist'] ?? 'Saved to Wishlist' ?></p>
        <?php else: ?>
            <form action="wishlist_action.php" method="POST" class="detail-form">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="action" value="add">
                <button type="submit" class="btn btn-wishlist text-danger-link mt-15">🤍 <?= $lang['add_to_wishlist'] ?? 'Add to Wishlist' ?></button>
            </form>
        <?php endif; ?>
        
        <br><br>
        <a href="index.php" class="btn-back">← <?= $lang['start_shopping'] ?? 'Back to Shop' ?></a>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>