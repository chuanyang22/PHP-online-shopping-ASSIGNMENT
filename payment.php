<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/cart_persist.php';

// 1. Security Check: Make sure they are logged in and have an order ID
if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// 2. Fetch the specific order details from the database
$stmt = $pdo->prepare("SELECT total_amount, status FROM orders WHERE id = ? AND member_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<div class='text-center mt-50'><h2>Order Not Found.</h2><a href='index.php'>Go Home</a></div>");
}

// 3. Handle the background 'Success' ping from PayPal (USING GET NOW)
if (isset($_GET['action']) && $_GET['action'] === 'complete_payment') {
    
    // Change order status to 'Completed' so the database accepts it!
    $update = $pdo->prepare("UPDATE orders SET status = 'Completed' WHERE id = ?");
    $update->execute([$order_id]);
    
    // Clear their shopping cart (session + saved cart)
    $_SESSION['cart'] = [];
    cart_clear_member_cart($pdo, (int) $user_id);
    
    // Send them straight to the Order History!
    header("Location: member/order_history.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="payment-body">
    <div class="payment-card">
        
        <?php if (!isset($order) || !$order): ?>
            <div class="payment-error">
                <h2>Order Not Found.</h2>
                <a href="index.php" class="link-primary">Go Home</a>
            </div>
        <?php else: ?>

            <h2 class="payment-title">Secure Checkout</h2>
            <p class="payment-subtitle">Order #<?= htmlspecialchars($order_id) ?></p>

            <div class="payment-amount">
                $<?= number_format($order['total_amount'], 2) ?>
            </div>

            <div id="paypal-button-container"></div>

            <script src="https://www.paypal.com/sdk/js?client-id=AUs-N0E4V8HRGsAx54opyGxI2UXVk2npBD7c2ArivbMkaTdIPhls9vHk6A_I8ikJNAWpv05tQ7OqS1skE&currency=USD"></script>
            <script>
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: '<?= number_format($order['total_amount'], 2, '.', '') ?>' 
                                }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        window.location.href = "payment.php?order_id=<?= htmlspecialchars($order_id) ?>&action=complete_payment";
                    },
                    onCancel: function (data) {
                        alert("Payment window closed. Please click the button again when you are ready to pay.");
                    }
                }).render('#paypal-button-container');
            </script>
            
        <?php endif; ?>
        
    </div>
</body>
</html>