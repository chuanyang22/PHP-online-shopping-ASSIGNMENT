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
        // QUICK DATABASE CHECK: How many do we actually have in stock?
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $stock_data = $stmt->fetch();
        $max_stock = $stock_data ? (int)$stock_data['stock_quantity'] : 0;

        if ($action === 'add') {
            $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            $current_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
            $new_qty = $current_qty + $qty;
            
            // Server-side block: If they try to add more than exists, cap it at the max stock
            if ($new_qty > $max_stock) {
                $_SESSION['cart'][$product_id] = $max_stock;
            } else {
                $_SESSION['cart'][$product_id] = $new_qty;
            }

            $_SESSION['popup'] = "🛒 Item added to your cart!";
            
        } elseif ($action === 'update') {
            $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            // Server-side block: Cap the manual update at max stock
            if ($qty > $max_stock) {
                $qty = $max_stock;
            }

            if ($qty > 0) {
                $_SESSION['cart'][$product_id] = $qty;
            } else {
                unset($_SESSION['cart'][$product_id]); // Remove if quantity set to 0
            }
            
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    
    // Check where the user came from. If we know, send them back there. Otherwise, default to cart.php
    $return_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart.php';
    
    header("Location: " . $return_url);
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
</head>
<body class="cart-body">

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
                                    
                                    <input type="number" name="quantity" value="<?= $item['cart_qty'] ?>" min="1" max="<?= $item['stock_quantity'] ?>" style="width: 50px; padding: 5px;" onchange="this.form.submit()">
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