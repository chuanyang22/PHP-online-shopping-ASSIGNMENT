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

if (!$order) {
    die("<div style='text-align: center; padding: 50px;'><h2>Order not found.</h2><a href='order_list.php'>← Back to Orders</a></div>");
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-container { max-width: 1000px; margin: 20px auto; padding: 0 20px; }

        .admin-nav {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .nav-buttons { display: flex; flex-wrap: wrap; gap: 10px; }
        .nav-btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        .nav-btn.primary { background: #27ae60; color: white; }
        .nav-btn.secondary { background: #3498db; color: white; }

        .back-link {
            display: inline-block;
            margin-bottom: 16px;
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }

        .order-id { font-size: 20px; font-weight: 700; color: #2c3e50; }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-pending { background: #fff8e1; color: #f39c12; }
        .status-shipped { background: #e3f2fd; color: #3498db; }
        .status-delivered { background: #e8f5e9; color: #27ae60; }
        .status-cancelled { background: #fef2f2; color: #e74c3c; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item { margin-bottom: 12px; }
        .info-label { font-size: 12px; color: #999; margin-bottom: 4px; }
        .info-value { font-size: 14px; color: #2c3e50; }
        .address-text { font-size: 14px; color: #555; line-height: 1.5; }

        .status-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
        }

        .status-select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
        }

        .update-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th {
            text-align: left;
            padding: 12px 0;
            font-size: 13px;
            color: #999;
            font-weight: normal;
            border-bottom: 1px solid #f0f0f0;
        }
        .items-table td {
            padding: 14px 0;
            border-bottom: 1px solid #f5f5f5;
            vertical-align: middle;
        }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .total-row { background: #fafafa; }
        .total-row td { padding: 16px 0; font-weight: 600; font-size: 16px; }
        .grand-total { color: #ee4d2d; font-size: 20px; }

        @media (max-width: 700px) { .info-grid { grid-template-columns: 1fr; gap: 12px; } }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="admin-nav">
        <div class="nav-buttons">
            <a href="dashboard.php" class="nav-btn primary">📊 Dashboard</a>
            <a href="order_list.php" class="nav-btn secondary">📦 Orders</a>
            <a href="products_crud.php" class="nav-btn primary">🛒 Products</a>
            <a href="categories_crud.php" class="nav-btn primary">📁 Categories</a>
            <a href="admin_member.php" class="nav-btn primary">👥 Members</a>
        </div>
        <div>
            👋 <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong> | 
            <a href="logout.php" style="color:#e74c3c; text-decoration:none;">Logout</a>
        </div>
    </div>

    <a href="order_list.php" class="back-link">← Back to Orders</a>

    <div class="card">
        <div class="order-header">
            <div class="order-id">Order #<?= $order['id'] ?></div>
            <span class="status-badge status-<?= strtolower($order['status']) ?>"><?= $order['status'] ?></span>
        </div>
        <div class="info-value" style="color:#999; font-size:13px;">
            Placed on: <?= date('l, d F Y \a\t h:i A', strtotime($order['order_date'])) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Customer Information</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <div class="info-label">Customer Name</div>
                    <div class="info-value"><?= htmlspecialchars($order['username']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?= htmlspecialchars($order['email']) ?></div>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <div class="info-label">Shipping Address</div>
                    <div class="address-text"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Update Order Status</div>
        <form method="POST" class="status-form">
            <select name="status" class="status-select">
                <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Shipped" <?= $order['status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button type="submit" name="update_status" class="update-btn">Update Status</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Items Ordered</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): 
                    $img_path = (!empty($item['image_name']) && file_exists('../uploads/' . $item['image_name'])) 
                        ? '../uploads/' . $item['image_name'] 
                        : '../uploads/default.png';
                ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?= $img_path ?>" class="product-img">
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                            </div>
                        </div>
                        <td>RM <?= number_format($item['price_at_purchase'], 2) ?></div>
                        <td>x<?= $item['quantity'] ?></div>
                        <td>RM <?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></div>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Grand Total</td>
                    <td class="grand-total">RM <?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>