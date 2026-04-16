<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// 1. Kick them back to the shop if their cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// 2. Ensure they are logged in! An order needs to be tied to a member_id
if (!isset($_SESSION['user_id'])) {
    // If you have a specific way you want to handle errors, you can adjust this
    die("You must be logged in to checkout. <a href='login.php'>Click here to login.</a>");
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// 3. Fetch current cart items to calculate the grand total
$cart_total = 0;
$cart_items = [];
$placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
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

// 4. Handle the Checkout Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');

    if (empty($shipping_address)) {
        $error = "Please provide a shipping address.";
    } else {
        try {
            // Start a database transaction (prevents partial orders if something crashes)
            $pdo->beginTransaction();

            // Step A: Create the main order record
            $stmt = $pdo->prepare("INSERT INTO orders (member_id, total_amount, shipping_address, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$user_id, $cart_total, $shipping_address]);
            
            // Grab the ID of the order we just created
            $order_id = $pdo->lastInsertId();

            // Step B: Insert every item from the cart into order_items
            $stmt_items = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            $update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($cart_items as $item) {
                // Save the item history
                $stmt_items->execute([$order_id, $item['id'], $item['cart_qty'], $item['price']]);
                // Deduct the purchased amount from our available stock
                $update_stock->execute([$item['cart_qty'], $item['id']]);
            }

            // Commit the transaction to save everything permanently
            $pdo->commit();
            
            // Redirect the user straight to the new payment module
            header("Location: payment.php?order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack(); // Undo any partial database changes
            $error = "Failed to place order. " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="css/mainstyle.css">
    <style>
        .checkout-container { max-width: 800px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .summary-box { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #ddd; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: vertical; height: 100px; }
        .btn-submit { background: #27ae60; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1em; width: 100%; font-weight: bold; }
        .btn-submit:hover { background: #2196f3; }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; text-align: center; }
        .msg-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .msg-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    </style>
</head>
<body style="margin: 0; background-color: #f5f5f5;">

    <div class="navbar">
        <div class="navbar-brand">
            <a href="index.php">🛍️ Online Store</a>
        </div>
    </div>

    <div class="checkout-container">
        <h2>Checkout</h2>

        <?php if ($error): ?>
            <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="msg msg-success">
                <?= htmlspecialchars($success) ?><br><br>
                <a href="index.php" style="color: #2e7d32; text-decoration: underline;">Return to Home</a>
            </div>
        <?php else: ?>
            
            <div class="summary-box">
                <h3>Order Summary</h3>
                <ul style="list-style-type: none; padding: 0;">
                    <?php foreach ($cart_items as $item): ?>
                        <li style="margin-bottom: 10px; border-bottom: 1px dashed #ccc; padding-bottom: 10px;">
                            <?= htmlspecialchars($item['name']) ?> (x<?= $item['cart_qty'] ?>) 
                            <span style="float: right;">$<?= number_format($item['subtotal'], 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <h3 style="text-align: right; margin-top: 20px; color: #27ae60;">Total: $<?= number_format($cart_total, 2) ?></h3>
            </div>

            <form id="checkout-form" action="checkout.php" method="POST">
                <div class="form-group">
                    <label for="shipping_address">Shipping Address</label>
                    <textarea name="shipping_address" id="shipping_address" placeholder="Enter your full street address, city, and zip code..." required></textarea>
                </div>
                
                <div id="paypal-button-container" style="margin-top: 20px;"></div>
            </form>

        <?php endif; ?>
    </div>

    <script src="https://www.paypal.com/sdk/js?client-id=AUs-N0E4V8HRGsAx54opyGxI2UXVk2npBD7c2ArivbMkaTdIPhls9vHk6A_I8ikJNAWpv05tQ7OqS1sk&currency=MYR&disable-funding=card"></script>
    
    <script>
        paypal.Buttons({
            // Step A: Make sure they typed an address BEFORE opening the PayPal popup
            onClick: function(data, actions) {
                let address = document.getElementById('shipping_address').value.trim();
                if (address === '') {
                    alert('Please enter your shipping address before paying.');
                    return actions.reject(); // Stops the popup from opening
                }
                return actions.resolve(); // Allows the popup to open
            },

            // Step B: Pass your actual PHP cart total to PayPal
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            // number_format ensures it is always formatted as 0.00 without commas, which PayPal requires
                            value: '<?= number_format($cart_total, 2, '.', '') ?>' 
                        }
                    }]
                });
            },

            // Step C: What to do when the buyer successfully pays
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    
                    // The payment is complete! 
                    // Now we magically click the hidden form submit so your PHP saves the order to the database
                    document.getElementById('checkout-form').submit();
                    
                });
            }
        }).render('#paypal-button-container');
    </script>

</body>
</html>