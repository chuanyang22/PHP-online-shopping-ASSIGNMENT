<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// 1. Initialize the cart session array if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 2. Handle Add, Update, and Remove actions from forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = $_POST['product_id'] ?? null;
    
    if ($product_id) {
        if ($action === 'add') {
            $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            // If item is already in cart, add to existing quantity. Otherwise, set it.
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $qty;
            } else {
                $_SESSION['cart'][$product_id] = $qty;
            }
        } elseif ($action === 'update') {
            $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            if ($qty > 0) {
                $_SESSION['cart'][$product_id] = $qty;
            } else {
                unset($_SESSION['cart'][$product_id]); // Remove if quantity set to 0
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    // Redirect to the same page to prevent form resubmission popups on refresh
    header("Location: cart.php");
    exit();
}

// 3. Fetch details for the products currently in the cart
$cart_items = [];
$cart_total = 0;

if (!empty($_SESSION['cart'])) {
    // Create placeholders like (?, ?, ?) based on how many items are in the cart
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    
    // Fetch only the products that match the IDs in our session
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($_SESSION['cart']));
    $products_in_cart = $stmt->fetchAll();

    foreach ($products_in_cart as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $qty;
        $cart_total += $subtotal;
        
        $product['cart_qty'] = $qty;
        $product['subtotal'] = $subtotal;
        $cart_items[] = $product;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart</title>
    <link rel="stylesheet" href="css/mainstyle.css">
    <style>
        .cart-container { max-width: 900px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .cart-img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; vertical-align: middle; margin-right: 10px; }
        .btn-small { background: #34495e; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
        .btn-remove { background: #e74c3c; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
        .cart-summary { text-align: right; font-size: 1.2em; }
        .btn-checkout { background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px; font-weight: bold; }
    </style>
</head>
<body style="margin: 0; background-color: #f5f5f5;">

    <div class="navbar">
        <div class="navbar-brand">
            <a href="index.php">🛍️ Online Store</a>
        </div>
        <div class="navbar-profile">
             <a href="cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
        </div>
    </div>

    <div class="cart-container">
        <h2>Your Shopping Cart</h2>

        <?php if (empty($cart_items)): ?>
            <p>Your cart is currently empty. <a href="index.php">Continue shopping.</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <?php $imagePath = !empty($item['image_name']) ? 'uploads/' . htmlspecialchars($item['image_name']) : 'uploads/default.png'; ?>
                                <img src="<?= $imagePath ?>" class="cart-img" alt="Product">
                                <?= htmlspecialchars($item['name']) ?>
                            </td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td>
                                <form action="cart.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <input type="number" name="quantity" value="<?= $item['cart_qty'] ?>" min="1" max="<?= $item['stock_quantity'] ?>" style="width: 50px;">
                                    <button type="submit" class="btn-small">Update</button>
                                </form>
                            </td>
                            <td><strong>$<?= number_format($item['subtotal'], 2) ?></strong></td>
                            <td>
                                <form action="cart.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <p><strong>Grand Total: $<?= number_format($cart_total, 2) ?></strong></p>
                <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>