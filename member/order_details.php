<?php
require_once 'lib/auth.php';
require_once 'lib/db.php';
require_once 'lib/helpers.php';

require_login();
$order_id = $_GET['id'] ?? 0;
$member_id = $_SESSION['user_id'];

// Fetch order main info
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND member_id = ?");
$stmt->execute([$order_id, $member_id]);
$order = $stmt->fetch();

if (!$order) { die("Order not found."); }

// Fetch items in this order
$stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details #<?= $order['id'] ?></title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body>
    <div class="page-container">
        <h2>Order Details #<?= $order['id'] ?></h2>
        <p>Status: <strong><?= $order['status'] ?></strong></p>
        <p>Shipping to: <?= sanitize($order['shipping_address']) ?></p>
        
        <table width="100%" border="1" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= sanitize($item['name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>RM <?= number_format($item['price_at_purchase'], 2) ?></td>
                    <td>RM <?= number_format($item['quantity'] * $item['price_at_purchase'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Grand Total: RM <?= number_format($order['total_amount'], 2) ?></strong></p>
        <a href="order_history.php">Back to History</a>
    </div>
</body>
</html>