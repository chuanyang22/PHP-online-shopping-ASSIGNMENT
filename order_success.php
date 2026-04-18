<?php
session_start();
$order_id = intval($_GET['order_id'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmed</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="order-success-body">
    <h1>🎉 Payment Successful!</h1>
    <p>Your order <strong>#<?= $order_id ?></strong> has been placed and paid via PayPal.</p>
    <a href="index.php" class="link-primary">Continue Shopping</a>
</body>
</html>