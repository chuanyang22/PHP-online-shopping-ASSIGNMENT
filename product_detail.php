<?php
// product_detail.php
session_start(); // We MUST start the session to check who is logged in!
require_once 'lib/db.php';

if (!isset($_GET['id'])) {
    die("Error: Product ID is missing.");
}
$product_id = $_GET['id'];

// Check if this item is already in the logged-in user's wishlist
$in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $w_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE member_id = ? AND product_id = ?");
    $w_stmt->execute([$_SESSION['user_id'], $product_id]);
    $in_wishlist = $w_stmt->rowCount() > 0;
    // Check if this item is already in the cart session
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
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f0f2f5; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        .detail-card { 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            max-width: 500px; 
            width: 90%; 
            text-align: center; 
        }
        .detail-card img { 
            width: 100%; 
            max-height: 300px; 
            object-fit: contain; 
            border-radius: 10px; 
            margin-bottom: 20px; 
        }
        .price { 
            font-size: 28px; 
            color: #28a745; 
            font-weight: bold; 
            margin: 10px 0; 
        }
        .category { color: #666; font-style: italic; margin-bottom: 20px; }
        .stock { 
            display: inline-block; 
            padding: 5px 15px; 
            background: #e9ecef; 
            border-radius: 20px; 
            font-weight: bold; 
        }
        .back-link { 
            display: block; 
            margin-top: 30px; 
            text-decoration: none; 
            color: #4a90e2; 
            font-weight: bold; 
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="detail-card">
    <img src="uploads/<?php echo htmlspecialchars($product['image_name']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    
    <div class="category">Category: <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
    
    <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
    
    <div class="stock">
        <?php if ($product['stock_quantity'] > 0): ?>
            ✅ In Stock: <?php echo $product['stock_quantity']; ?>
        <?php else: ?>
            ❌ Out of Stock
        <?php endif; ?>
    </div>

    <?php if ($cart_qty > 0): ?>
        <div style="background: #e1f5fe; color: #0277bd; padding: 10px; border-radius: 5px; margin-top: 15px; font-weight: bold; text-align: center; border-left: 4px solid #03a9f4;">
            🛒 This item is already inside your cart (Qty: <?= $cart_qty ?>)
        </div>
    <?php endif; ?>

    <?php if ($in_wishlist): ?>
        <div style="background: #fce4ec; color: #c2185b; padding: 10px; border-radius: 5px; margin-top: 10px; font-weight: bold; text-align: center; border-left: 4px solid #e91e63;">
            ❤️ This item is already inside your wishlist
        </div>
    <?php endif; ?>

    <?php if ($product['stock_quantity'] > 0): ?>
        <form action="cart.php" method="POST" style="margin-top: 25px;">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="action" value="add">
            
            <label for="quantity" style="font-weight: bold; color: #555;">Qty:</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="width: 60px; padding: 8px; text-align: center; border-radius: 5px; border: 1px solid #ccc; margin: 0 10px;">
            
            <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px;">🛒 Add to Cart</button>
        </form>
    <?php endif; ?>

    <?php if ($in_wishlist): ?>
        <form action="wishlist_action.php" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="action" value="remove">
            <button type="submit" style="background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px; width: 100%; max-width: 250px;">❌ Remove from Wishlist</button>
        </form>
    <?php else: ?>
        <form action="wishlist_action.php" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="action" value="add">
            <button type="submit" style="background: #e84393; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px; width: 100%; max-width: 250px;">❤️ Save to Wishlist</button>
        </form>
    <?php endif; ?>

    <a href="index.php" class="back-link">⬅ Back to Store</a>
</div>

</body>

<?php if (isset($_SESSION['popup'])): ?>
    <div id="toast-message" style="position: fixed; top: 30px; left: 50%; transform: translateX(-50%); background: #2c3e50; color: white; padding: 12px 24px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 9999; font-weight: bold; font-size: 1em; text-align: center; transition: opacity 0.5s ease; border-top: 4px solid #2ecc71;">
        <?= htmlspecialchars($_SESSION['popup']) ?>
    </div>
    <script>
        // Automatically fade out and remove the popup after 3 seconds
        setTimeout(() => {
            let toast = document.getElementById('toast-message');
            if(toast) {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }
        }, 3000);
    </script>
    <?php unset($_SESSION['popup']); // Clear it so it doesn't show up again on refresh ?>
<?php endif; ?>

</html>