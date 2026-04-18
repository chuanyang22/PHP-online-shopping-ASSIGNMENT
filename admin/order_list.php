<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

auth('Admin');

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $order_id])) {
        $_SESSION['admin_msg'] = "Order #$order_id updated to $new_status";
    } else {
        $_SESSION['admin_msg'] = "Failed to update order #$order_id";
    }
    header("Location: order_list.php");
    exit();
}

// Search and Filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$query = "SELECT o.*, m.username, m.email 
          FROM orders o 
          JOIN member m ON o.member_id = m.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (o.id LIKE ? OR m.username LIKE ? OR m.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY o.order_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get counts for stats
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
$shipped_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Shipped'")->fetchColumn();
$delivered_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn();
$cancelled_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Cancelled'")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
</head>
<body class="admin-body">
<div class="admin-layout admin-layout-flex">

    <?php require_once 'admin_sidebar.php'; ?>

    <main class="admin-main admin-main-flex">
        <div class="admin-card admin-card-full">
            
            <div class="flex-between-center mb-20">
                <h2 class="mt-0 mb-0 text-blue-title">🧾 Manage Orders</h2>
                
                <form method="GET" action="" class="admin-search-form">
                    <input type="text" name="search" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>" class="admin-input-full search-input-w200">
                    
                    <select name="status_filter" class="admin-input-full search-select-auto">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Shipped" <?= $status_filter == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="Delivered" <?= $status_filter == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    
                    <button type="submit" class="btn-search-blue">Search</button>
                    <a href="order_list.php" class="btn-reset-gray">Reset</a>
                </form>
            </div>

            <?php if (isset($_SESSION['admin_msg'])): ?>
                <div class="alert-success mb-15">
                    <?= htmlspecialchars($_SESSION['admin_msg']); unset($_SESSION['admin_msg']); ?>
                </div>
            <?php endif; ?>

            <table class="admin-table">
                <thead>
                    <tr class="bg-dark-blue text-white">
                        <th>Order ID</th>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="th-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $index => $order): ?>
                            <?php $bg = ($index % 2 == 0) ? 'bg-light-gray' : ''; ?>
                            <tr class="border-bottom-gray <?= $bg ?>">
                                <td class="font-bold">#<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['username']) ?></td>
                                <td><?= htmlspecialchars($order['email']) ?></td>
                                <td>
                                    <form method="POST" action="" class="inline-form form-flex-row">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="admin-input-full search-select-auto">
                                            <option value="Pending"   <?= $order['status'] == 'Pending'   ? 'selected' : '' ?>>Pending</option>
                                            <option value="Shipped"   <?= $order['status'] == 'Shipped'   ? 'selected' : '' ?>>Shipped</option>
                                            <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-edit-blue">Update</button>
                                    </form>
                                </td>
                                <td class="td-right">
                                    <a href="order_details.php?id=<?= $order['id'] ?>" class="btn-search-blue">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="center-padding-50 text-gray">📭 No orders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </main>

</div>
</body>
</html>