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
    <title>Admin - Order Management</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-container { max-width: 1300px; margin: 20px auto; padding: 0 20px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .stat-card .stat-value { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .stat-card .stat-label { font-size: 13px; color: #7f8c8d; margin-top: 5px; }
        .stat-card.pending .stat-value { color: #f39c12; }
        .stat-card.shipped .stat-value { color: #3498db; }
        .stat-card.delivered .stat-value { color: #27ae60; }
        .stat-card.cancelled .stat-value { color: #e74c3c; }
        .stat-card.revenue .stat-value { color: #9b59b6; }

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

        .alert {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }

        .filter-bar {
            background: white;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label { font-size: 12px; font-weight: 600; color: #7f8c8d; }
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 180px;
        }

        .filter-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .clear-btn {
            background: #95a5a6;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 600;
            display: inline-block;
        }

        .orders-table {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th {
            background: #f8f9fa;
            padding: 14px 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
        }
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            font-size: 14px;
        }
        tr:hover { background: #fafafa; }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fff8e1; color: #f39c12; }
        .status-shipped { background: #e3f2fd; color: #3498db; }
        .status-delivered { background: #e8f5e9; color: #27ae60; }
        .status-cancelled { background: #fef2f2; color: #e74c3c; }

        .action-form { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .status-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
        }
        .update-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .view-btn {
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .empty-state { text-align: center; padding: 50px; color: #999; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="admin-nav">
        <div class="nav-buttons">
            <a href="dashboard.php" class="nav-btn primary">📊 Dashboard</a>
            <a href="order_list.php" class="nav-btn secondary" style="background:#2980b9;">📦 Orders</a>
            <a href="products_crud.php" class="nav-btn primary">🛒 Products</a>
            <a href="categories_crud.php" class="nav-btn primary">📁 Categories</a>
            <a href="admin_member.php" class="nav-btn primary">👥 Members</a>
        </div>
        <div>
            👋 <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong> | 
            <a href="logout.php" style="color:#e74c3c; text-decoration:none;">Logout</a>
        </div>
    </div>

    <?php if (isset($_SESSION['admin_msg'])): ?>
        <div class="alert" id="alertMsg">✅ <?= htmlspecialchars($_SESSION['admin_msg']) ?></div>
        <script>setTimeout(() => { let alert = document.getElementById('alertMsg'); if (alert) alert.style.display = 'none'; }, 3000);</script>
        <?php unset($_SESSION['admin_msg']); ?>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value"><?= $total_orders ?></div><div class="stat-label">Total Orders</div></div>
        <div class="stat-card pending"><div class="stat-value"><?= $pending_count ?></div><div class="stat-label">Pending</div></div>
        <div class="stat-card shipped"><div class="stat-value"><?= $shipped_count ?></div><div class="stat-label">Shipped</div></div>
        <div class="stat-card delivered"><div class="stat-value"><?= $delivered_count ?></div><div class="stat-label">Delivered</div></div>
        <div class="stat-card cancelled"><div class="stat-value"><?= $cancelled_count ?></div><div class="stat-label">Cancelled</div></div>
        <div class="stat-card revenue"><div class="stat-value">RM <?= number_format($total_revenue ?? 0, 2) ?></div><div class="stat-label">Revenue</div></div>
    </div>

    <div class="filter-bar">
        <form method="GET" action="order_list.php" class="filter-form">
            <div class="filter-group">
                <label>🔍 Search</label>
                <input type="text" name="search" placeholder="Order ID, Customer..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
                <label>📌 Status</label>
                <select name="status_filter">
                    <option value="">All</option>
                    <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Shipped" <?= $status_filter == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="Delivered" <?= $status_filter == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="filter-btn">Apply</button>
            </div>
            <?php if ($search || $status_filter): ?>
                <div class="filter-group">
                    <a href="order_list.php" class="clear-btn">Clear</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="orders-table">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): 
                        $status_class = 'status-' . strtolower($order['status']);
                    ?>
                        <tr>
                            <td><strong>#<?= $order['id'] ?></strong></td>
                            <td>
                                <?= htmlspecialchars($order['username']) ?><br>
                                <small style="color:#999;"><?= htmlspecialchars($order['email']) ?></small>
                            </td>
                            <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                            <td><strong>RM <?= number_format($order['total_amount'], 2) ?></strong></td>
                            <td><span class="status-badge <?= $status_class ?>"><?= $order['status'] ?></span></td>
                            <td>
                                <div class="action-form">
                                    <form method="POST" style="display: flex; gap: 6px;">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="status-select">
                                            <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Shipped" <?= $order['status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="update-btn">Update</button>
                                    </form>
                                    <a href="order_details.php?id=<?= $order['id'] ?>" class="view-btn">View</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty-state">📭 No orders found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>