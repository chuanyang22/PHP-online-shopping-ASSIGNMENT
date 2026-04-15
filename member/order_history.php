<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

auth('Member');

$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// Fetch all orders for the logged-in user
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    WHERE o.member_id = ? 
    ORDER BY o.order_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Purchases</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .purchases-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        .page-header {
            margin-bottom: 20px;
        }

        .page-header h2 {
            font-size: 22px;
            font-weight: 600;
            color: #222;
            margin: 0;
        }

        .page-header p {
            font-size: 13px;
            color: #ee4d2d;
            margin-top: 4px;
        }

        /* Alert Messages */
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            border-left: 3px solid #4caf50;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            border-left: 3px solid #f44336;
        }

        /* Order Card */
        .order-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        /* Order Header */
        .order-header {
            padding: 14px 16px;
            background: white;
            border-bottom: 1px solid #efefef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .shop-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .shop-icon {
            font-size: 18px;
        }

        .shop-name {
            font-weight: 600;
            font-size: 14px;
            color: #222;
        }

        .order-id {
            font-size: 12px;
            color: #999;
            margin-left: 8px;
        }

        .status-badge {
            font-size: 13px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 4px;
        }

        .status-completed {
            color: #00ab56;
            background: #e8f5e9;
        }

        .status-pending {
            color: #f39c12;
            background: #fff8e1;
        }

        .status-shipped {
            color: #3498db;
            background: #e3f2fd;
        }

        .status-cancelled {
            color: #e74c3c;
            background: #fef2f2;
        }

        /* Order Items */
        .order-items {
            padding: 8px 16px;
        }

        .order-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
            background: #f8f8f8;
            border-radius: 8px;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 14px;
            font-weight: 500;
            color: #222;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .item-quantity {
            font-size: 13px;
            color: #666;
        }

        .item-price-row {
            margin-top: 6px;
        }

        .item-price {
            font-size: 14px;
            font-weight: 600;
            color: #ee4d2d;
        }

        /* Order Footer */
        .order-footer {
            padding: 12px 16px;
            background: #fafafa;
            border-top: 1px solid #efefef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .order-summary {
            font-size: 13px;
            color: #666;
        }

        .order-total {
            font-size: 14px;
            font-weight: 600;
            color: #222;
        }

        .order-total span {
            font-size: 18px;
            color: #ee4d2d;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-view {
            background: white;
            border: 1px solid #ddd;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #555;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: #f5f5f5;
            border-color: #ccc;
        }

        .btn-cancel {
            background: white;
            border: 1px solid #e74c3c;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #e74c3c;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #fef2f2;
            border-color: #c0392b;
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }

        .btn-shop {
            background: #ee4d2d;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-shop:hover {
            background: #d73c1c;
        }

        @media (max-width: 600px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            .action-buttons {
                width: 100%;
            }
            .btn-view, .btn-cancel {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="navbar-brand">
            <a href="../index.php">🛍️ Online Store</a>
        </div>
        <div class="navbar-profile">
            <a href="../index.php">🏠 Home</a>
            <span class="navbar-divider"></span>
            <a href="../cart.php">🛒 My Cart</a>
            <span class="navbar-divider"></span>
            <a href="order_history.php" style="color: #38BDF8;">📜 My Orders</a>
            <span class="navbar-divider"></span>
            <a href="../wishlist.php">❤️ My Wishlist</a>
            <span class="navbar-divider"></span>
            <a href="../profile.php">🧏‍♂️ My Profile</a>
            <span class="navbar-divider"></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="purchases-container">
        <div class="page-header">
            <h2>My Purchases</h2>
            <p>Track your order history</p>
        </div>

        <?php if ($success_msg === 'cancelled'): ?>
            <div class="alert-success">✅ Order has been successfully cancelled.</div>
        <?php elseif ($error_msg === 'cancel_failed'): ?>
            <div class="alert-error">❌ Unable to cancel this order. Only pending orders can be cancelled.</div>
        <?php endif; ?>

        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order): 
                // Fetch items for this order
                $item_stmt = $pdo->prepare("
                    SELECT oi.*, p.name, p.image_name 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?
                ");
                $item_stmt->execute([$order['id']]);
                $items = $item_stmt->fetchAll();
                
                // Status badge colors
                $status_class = '';
                switch($order['status']) {
                case 'Pending': $status_class = 'status-pending'; break;
                case 'Shipped': $status_class = 'status-shipped'; break;
                case 'Delivered': $status_class = 'status-delivered'; break;
                case 'Cancelled': $status_class = 'status-cancelled'; break;
                default: $status_class = 'status-pending';
                }
            ?>
                <div class="order-card">
                    <!-- Header: Shop info + Status -->
                    <div class="order-header">
                        <div class="shop-info">
                            <span class="shop-icon">🛍️</span>
                            <span class="shop-name">Online Store</span>
                            <span class="order-id">Order #<?= $order['id'] ?></span>
                        </div>
                        <span class="status-badge <?= $status_class ?>"><?= $order['status'] ?></span>
                    </div>

                    <!-- Order Items -->
                    <div class="order-items">
                        <?php foreach ($items as $item): 
                            $img_path = (!empty($item['image_name']) && file_exists('../uploads/' . $item['image_name'])) 
                                ? '../uploads/' . $item['image_name'] 
                                : '../uploads/default.png';
                        ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="item-quantity">x<?= $item['quantity'] ?></div>
                                    <div class="item-price-row">
                                        <span class="item-price">RM <?= number_format($item['price_at_purchase'], 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Footer: Total + Actions -->
                    <div class="order-footer">
                        <div class="order-summary">
                            Total <?= count($items) ?> item(s)
                        </div>
                        <div class="order-total">
                            Total: <span>RM <?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                        <div class="action-buttons">
                            <a href="order_details.php?id=<?= $order['id'] ?>" class="btn-view">View Details</a>
                            
                            <?php if ($order['status'] === 'Pending'): ?>
                                <form method="POST" action="cancel_order.php" style="display: inline;" 
                                      onsubmit="return confirm('Cancel this order? This cannot be undone.');">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn-cancel">Cancel Order</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>📭 You haven't placed any orders yet.</p>
                <a href="../index.php" class="btn-shop">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>