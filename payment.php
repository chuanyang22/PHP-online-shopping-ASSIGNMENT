<?php
session_start();
require_once 'lib/db.php';

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
    die("<div style='text-align:center; margin-top:50px;'><h2>Order Not Found.</h2><a href='index.php'>Go Home</a></div>");
}

// 3. Handle the background 'Success' ping from PayPal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_payment') {
    // Change order status to Paid!
    $update = $pdo->prepare("UPDATE orders SET status = 'Paid' WHERE id = ?");
    $update->execute([$order_id]);
    
    // NOW we can finally clear their shopping cart
    $_SESSION['cart'] = [];
    
    // Send them to the success view
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
    <style>
        .payment-container { max-width: 500px; margin: 60px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
        .amount { font-size: 2.5em; color: #27ae60; font-weight: bold; margin: 20px 0; }
        .msg-success { background: #e8f5e9; color: #2e7d32; padding: 20px; border-radius: 8px; border: 1px solid #c8e6c9; margin-bottom: 20px; }
    </style>
</head>
<body style="margin: 0; background-color: #f5f5f5;">

    <div class="navbar">
        <div class="navbar-brand">
            <a href="index.php">🛍️ Online Store</a>
        </div>
    </div>

    <div class="payment-container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="msg-success">
                <h2>🎉 Payment Successful!</h2>
                <p>Thank you for your purchase. Your Order ID is <strong>#<?= htmlspecialchars($order_id) ?></strong>.</p>
            </div>
            <a href="index.php" style="text-decoration: none; background: #3498db; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold;">Return to Homepage</a>
            
        <?php elseif ($order['status'] === 'Paid'): ?>
            <h2>This order has already been paid!</h2>
            <br>
            <a href="index.php">Return to Homepage</a>

        <?php else: ?>
            <h2>Complete Your Payment</h2>
            <p>Order ID: #<?= htmlspecialchars($order_id) ?></p>
            
            <div class="amount">Total: $<?= number_format($order['total_amount'], 2) ?></div>
            
            <div id="paypal-button-container"></div>

            <form id="success-form" action="payment.php?order_id=<?= htmlspecialchars($order_id) ?>" method="POST">
                <input type="hidden" name="action" value="complete_payment">
            </form>

            <script src="https://www.paypal.com/sdk/js?client-id=AUs-N0E4V8HRGsAx54opyGxI2UXVk2npBD7c2ArivbMkaTdIPhls9vHk6A_I8ikJNAWpv05tQ7OqS1skE&currency=MYR&disable-funding=card"></script>
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
                        return actions.order.capture().then(function(details) {
                            // Payment is done! Tell PHP to update the database.
                            document.getElementById('success-form').submit();
                        });
                    }
                }).render('#paypal-button-container');
            </script>
            
        <?php endif; ?>
        
    </div>
</body>
</html>
