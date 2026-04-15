<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

auth('Member');

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$member_id = $_SESSION['user_id'];

// Fetch order details - verify ownership
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND member_id = ?");
$stmt->execute([$order_id, $member_id]);
$order = $stmt->fetch();

if (!$order) {
    die("<div style='text-align: center; padding: 50px;'><h2>Order not found.</h2><a href='order_history.php'>← Back to Orders</a></div>");
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?= $order_id ?></title>
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

        .details-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        /* Back Link */
        .back-link {
            display: inline-block;
            margin-bottom: 16px;
            color: #ee4d2d;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Order Header Card */
        .order-header-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .order-id {
            font-size: 18px;
            font-weight: 600;
            color: #222;
            margin-bottom: 8px;
        }

        .order-date {
            font-size: 13px;
            color: #999;
            margin-bottom: 12px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-completed { background: #e8f5e9; color: #00ab56; }
        .status-pending { background: #fff8e1; color: #f39c12; }
        .status-shipped { background: #e3f2fd; color: #3498db; }
        .status-cancelled { background: #fef2f2; color: #e74c3c; }

        /* Timeline */
        .timeline-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .timeline-title {
            font-size: 14px;
            font-weight: 600;
            color: #222;
            margin-bottom: 20px;
        }

        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 60px;
            right: 60px;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .timeline-step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: #e0e0e0;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s;
        }

        .timeline-step.completed .timeline-icon {
            background: #00ab56;
            color: white;
        }

        .timeline-step.active .timeline-icon {
            background: #ee4d2d;
            color: white;
            transform: scale(1.1);
        }

        .timeline-step .step-label {
            font-size: 12px;
            font-weight: 500;
            color: #888;
        }

        .timeline-step.completed .step-label {
            color: #00ab56;
        }

        .timeline-step.active .step-label {
            color: #ee4d2d;
            font-weight: 600;
        }

        /* Info Card */
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .info-title {
            font-size: 14px;
            font-weight: 600;
            color: #222;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #efefef;
        }

        .address-text {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
        }

        /* Items Card */
        .items-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .items-title {
            font-size: 14px;
            font-weight: 600;
            color: #222;
            padding: 16px 20px;
            background: white;
            border-bottom: 1px solid #efefef;
        }

        .order-item {
            display: flex;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 70px;
            height: 70px;
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
        }

        .item-quantity {
            font-size: 13px;
            color: #888;
            margin-bottom: 6px;
        }

        .item-price {
            font-size: 14px;
            font-weight: 600;
            color: #ee4d2d;
        }

        /* Total Section */
        .total-card {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            text-align: right;
        }

        .total-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #ee4d2d;
        }

        /* Cancel Button */
        .cancel-card {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .btn-cancel {
            background: white;
            border: 1px solid #e74c3c;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #e74c3c;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #fef2f2;
            border-color: #c0392b;
        }

        /* Status Message */
        .status-message {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 12px;
        }

        .status-msg-pending {
            background: #fff8e1;
            color: #f39c12;
        }

        .status-msg-shipped {
            background: #e3f2fd;
            color: #3498db;
        }

        .status-msg-completed {
            background: #e8f5e9;
            color: #00ab56;
        }

        .status-msg-cancelled {
            background: #fef2f2;
            color: #e74c3c;
        }

        @media (max-width: 600px) {
            .timeline::before {
                left: 30px;
                right: 30px;
            }
            .timeline-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
            .step-label {
                font-size: 10px;
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

    <div class="details-container">
        <a href="order_history.php" class="back-link">← Back to My Purchases</a>

        <!-- Order Header -->
        <div class="order-header-card">
            <div class="order-id">Order #<?= $order['id'] ?></div>
            <div class="order-date">Placed on <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></div>
            <span class="status-badge status-<?= strtolower($order['status']) ?>"><?= $order['status'] ?></span>
        </div>

        <!-- Timeline: To Ship → To Receive → Completed -->
        <div class="timeline-card">
            <div class="timeline-title">Order Status</div>
            <div class="timeline">
                <?php
                $current = $order['status'];
                $step1_completed = ($current == 'Shipped' || $current == 'Delivered');
                $step2_completed = ($current == 'Delivered');
                $step1_active = ($current == 'Shipped');
                $step2_active = ($current == 'Delivered');
                ?>
                <div class="timeline-step <?= $step1_completed ? 'completed' : ($step1_active ? 'active' : '') ?>">
                    <div class="timeline-icon">📦</div>
                    <div class="step-label">To Ship</div>
                </div>
                <div class="timeline-step <?= $step2_completed ? 'completed' : ($step2_active ? 'active' : '') ?>">
                    <div class="timeline-icon">🚚</div>
                    <div class="step-label">To Receive</div>
                </div>
                <div class="timeline-step <?= $current == 'Delivered' ? 'completed' : '' ?>">
                    <div class="timeline-icon">✅</div>
                    <div class="step-label">Delivered</div>
                </div>
            </div>

            <!-- Status Message -->
            <?php if ($order['status'] == 'Pending'): ?>
                <div class="status-message status-msg-pending">⏳ Your order is pending. Once shipped, you'll see the tracking info here.</div>
            <?php elseif ($order['status'] == 'Shipped'): ?>
                <div class="status-message status-msg-shipped">🚚 Your order is on the way! Please confirm receipt when you receive it.</div>
            <?php elseif ($order['status'] == 'Completed'): ?>
                <div class="status-message status-msg-completed">✅ Order completed! Thank you for shopping with us.</div>
            <?php elseif ($order['status'] == 'Cancelled'): ?>
                <div class="status-message status-msg-cancelled">❌ This order has been cancelled.</div>
            <?php endif; ?>
        </div>

        <!-- Shipping Address -->
        <div class="info-card">
            <div class="info-title">📬 Shipping Address</div>
            <div class="address-text"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></div>
        </div>

        <!-- Order Items -->
        <div class="items-card">
            <div class="items-title">🛍️ Items Ordered</div>
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
                        <div class="item-price">RM <?= number_format($item['price_at_purchase'], 2) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Total Amount -->
        <div class="total-card">
            <div class="total-label">Total Amount</div>
            <div class="total-amount">RM <?= number_format($order['total_amount'], 2) ?></div>
        </div>

        <!-- Cancel Button (Only for Pending Orders) -->
        <?php if ($order['status'] === 'Pending'): ?>
            <div class="cancel-card">
                <form method="POST" action="cancel_order.php" 
                      onsubmit="return confirm('Are you sure you want to cancel this order? This cannot be undone.');">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn-cancel">Cancel Order</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>