<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

auth('Member');

// Include language support
$current_lang = $_SESSION['lang'] ?? 'en';
if (file_exists(__DIR__ . "/../lang/{$current_lang}.php")) {
    require_once __DIR__ . "/../lang/{$current_lang}.php";
}

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

<?php include '../header.php'; ?>

<div class="purchases-container page-container">
    <div class="page-header mb-20">
        <h2>🛍️ <?= $lang['my_purchases'] ?? 'My Purchases' ?></h2>
        <p class="header-subtitle"><?= $lang['track_history'] ?? 'Track your order history' ?></p>
    </div>

    <?php if ($success_msg === 'cancelled'): ?>
        <div class="alert-success">
            ✅ <?= $lang['order_cancelled_success'] ?? 'Order has been successfully cancelled.' ?>
        </div>
    <?php elseif ($error_msg === 'cancel_failed'): ?>
        <div class="alert-error">
            ❌ <?= $lang['order_cancel_failed'] ?? 'Unable to cancel this order. Only pending orders can be cancelled.' ?>
        </div>
    <?php endif; ?>

    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): 
            // FETCH ITEMS FOR THIS SPECIFIC ORDER
            $item_stmt = $pdo->prepare("
                SELECT oi.*, p.name, p.image_name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $item_stmt->execute([$order['id']]);
            $items = $item_stmt->fetchAll();
            
            // Set dynamic status colors based on class names
            $status_class = '';
            switch(strtolower($order['status'])) {
                case 'completed': 
                case 'delivered': $status_class = 'status-completed'; break;
                case 'shipped':   $status_class = 'status-shipped'; break;
                case 'cancelled': $status_class = 'status-cancelled'; break;
                default:          $status_class = 'status-pending'; // Pending/Paid
            }
        ?>
            
            <div class="order-card">
                
                <div class="order-header">
                    <div class="shop-info">
                        <strong><?= $lang['order_no'] ?? 'Order #' ?><?= htmlspecialchars($order['id']) ?></strong>
                        <span class="order-date">
                            <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?>
                        </span>
                    </div>
                    <div class="status-badge <?= $status_class ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </div>
                </div>

                <div class="order-items">
                    <?php foreach ($items as $item): 
                        $img_path = (!empty($item['image_name']) && file_exists('../uploads/' . $item['image_name'])) 
                            ? '../uploads/' . htmlspecialchars($item['image_name']) 
                            : '../uploads/default.png';
                    ?>
                        <div class="order-item">
                            <div class="order-item-image">
                                <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            </div>
                            <div class="order-item-details">
                                <div class="order-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="order-item-qty"><?= $lang['qty'] ?? 'Qty' ?>: <?= $item['quantity'] ?></div>
                                <div class="order-item-price">
                                    RM <?= number_format($item['price_at_purchase'], 2) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-footer">
                    <div class="order-total-text">
                        <span class="order-summary-text">
                            <?= $order['item_count'] ?> <?= $lang['items'] ?? 'item(s)' ?>
                        </span>
                        <?= $lang['total'] ?? 'Total' ?>: <strong class="order-total-price">RM <?= number_format($order['total_amount'], 2) ?></strong>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn-view-order">
                            <?= $lang['view_details'] ?? 'View Details' ?>
                        </a>
                        
                        <?php if (in_array($order['status'], ['Pending', 'Completed', 'Paid'])): ?>
                            <form action="cancel_order.php" method="POST" style="margin: 0;" onsubmit="return confirm('<?= addslashes($lang['confirm_cancel'] ?? 'Are you sure you want to cancel this order? This cannot be undone.') ?>');">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                <button type="submit" class="btn-cancel-order">
                                    ❌ <?= $lang['cancel_order'] ?? 'Cancel Order' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <div class="empty-orders">
            <p>📭 <?= $lang['no_orders'] ?? "You haven't placed any orders yet." ?></p>
            <a href="../index.php" class="btn"><?= $lang['start_shopping'] ?? 'Start Shopping' ?></a>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>