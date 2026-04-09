<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

require_login(); 
$member_id = $_SESSION['user_id']; 

// Handle Cancellation Request
if (isset($_POST['cancel_order_id'])) {
    $order_id = $_POST['cancel_order_id'];
    // Only allow cancellation if the order belongs to the user and is still 'Pending'
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND member_id = ? AND status = 'Pending'");
    $stmt->execute([$order_id, $member_id]);
    header("Location: order_history.php?msg=Order Cancelled");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE member_id = ? ORDER BY order_date DESC");
$stmt->execute([$member_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Order History</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body>
    <div class="page-container">
        <h2>My Order History</h2>
        <table width="100%" border="1" style="border-collapse: collapse;">
            <thead>
                <tr style="background: #f4f4f4;">
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                    <td>RM <?= number_format($order['total_amount'], 2) ?></td>
                    <td><?= $order['status'] ?></td>
                    <td>
                        <a href="order_details.php?id=<?= $order['id'] ?>">View Details</a>
                        <?php if ($order['status'] == 'Pending'): ?>
                            | <form method="POST" style="display:inline;">
                                <input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>">
                                <button type="submit" onclick="return confirm('Cancel this order?')">Cancel Order</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>