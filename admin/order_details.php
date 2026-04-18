<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

auth('Admin');

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: order_list.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT o.*, m.username, m.email 
    FROM orders o 
    JOIN member m ON o.member_id = m.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Replaced inline styles with CSS classes
if (!$order) {
    die("<div class='center-padding-50'><h2 class='text-blue-title'>Order not found.</h2><a href='order_list.php' class='btn-reset-gray'>← Back to Orders</a></div>");
}

$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $order_id])) {
        $_SESSION['admin_msg'] = "Order #$order_id updated to $new_status";
    }
    header("Location: order_details.php?id=$order_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Order Details #<?= $order_id ?></title>
    <link rel="stylesheet" href="../css/mainstyle.css">
</head>
<body class="admin-body">
<div class="admin-layout admin-layout-flex">

    <?php require_once 'admin_sidebar.php'; ?>

    <main class="admin-main admin-main-flex">
        <div class="admin-card admin-card-full">
            
            <div class="flex-between-center mb-20">
                <h2 class="mt-0 mb-0 text-blue-title">📦 Order Details #<?= $order['id'] ?></h2>
                <a href="order_list.php" class="btn-reset-gray">← Back to Orders</a>
            </div>

            <?php if (isset($_SESSION['admin_msg'])): ?>
                <div class="msg-success mb-20">
                    <?= htmlspecialchars($_SESSION['admin_msg']); unset($_SESSION['admin_msg']); ?>
                </div>
            <?php endif; ?>

            <div class="order-info-card">
                <p><strong>Customer:</strong> <?= htmlspecialchars($order['username']) ?> (<?= htmlspecialchars($order['email']) ?>)</p>
                <p class="mt-10"><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
                <p class="mt-10"><strong>Current Status:</strong> <span class="font-bold text-blue-title"><?= $order['status'] ?></span></p>
            </div>

            <form method="POST" action="" class="inline-form form-flex-row mb-20">
                <label class="font-bold">Update Status:</label>
                <select name="status" class="admin-input-full search-select-auto">
                    <option value="Pending"   <?= $order['status'] == 'Pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="Shipped"   <?= $order['status'] == 'Shipped'   ? 'selected' : '' ?>>Shipped</option>
                    <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" name="update_status" class="btn-edit-blue">Save</button>
            </form>

            <table class="admin-table">
                <thead>
                    <tr class="bg-dark-blue text-white">
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): 
                        $img_path = (!empty($item['image_name']) && file_exists('../uploads/' . $item['image_name'])) 
                            ? '../uploads/' . $item['image_name'] 
                            : '../uploads/default.png';
                        $bg = ($index % 2 == 0) ? 'bg-light-gray' : '';
                    ?>
                        <tr class="border-bottom-gray <?= $bg ?>">
                            <td>
                                <div class="flex-item-center">
                                    <img src="<?= $img_path ?>" class="product-img-thumb" alt="Product Image">
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                </div>
                            </td>
                            <td>RM <?= number_format($item['price_at_purchase'], 2) ?></td>
                            <td>x<?= $item['quantity'] ?></td>
                            <td class="text-right">RM <?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <tr>
                        <td colspan="3" class="text-right font-bold pt-20">Grand Total</td>
                        <td class="text-right grand-total-text pt-20">RM <?= number_format($order['total_amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>

        </div>
    </main>

</div>
</body>
</html>