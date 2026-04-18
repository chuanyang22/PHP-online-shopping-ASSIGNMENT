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

// Fetch all orders
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

<div class="order-container">
    <h2>🛍️ <?= $lang['my_purchases'] ?? 'My Purchases' ?></h2>

    <?php if ($success_msg): ?>
        <div class="alert-success">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($orders): ?>
        <?php foreach ($orders as $order): ?>
            <?php 
                $status_class = 'status-' . strtolower($order['status']); 
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <strong><?= $lang['order_no'] ?? 'Order #' ?><?= htmlspecialchars($order['id']) ?></strong>
                        <div class="order-summary mt-15">
                            <?= $lang['date'] ?? 'Date' ?>: <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?>
                        </div>
                    </div>
                    <div class="order-status <?= $status_class ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </div>
                </div>

                <div class="order-body">
                    <div><?= $lang['items'] ?? 'Items' ?>: <?= $order['item_count'] ?></div>
                    <div class="order-total">
                        <?= $lang['total'] ?? 'Total' ?>: <span>RM <?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div class="action-buttons">
                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn-view"><?= $lang['view_details'] ?? 'View Details' ?></a>
                        
                        <?php if ($order['status'] === 'Pending' || $order['status'] === 'Completed' || $order['status'] === 'Paid'): ?>
                            <form action="cancel_order.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to cancel this order? This cannot be undone.');">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                <button type="submit" class="btn-cancel">❌ <?= $lang['cancel_order'] ?? 'Cancel Order' ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <p>📭 You haven't placed any orders yet.</p>
            <a href="../index.php" class="btn-shop"><?= $lang['start_shopping'] ?? 'Start Shopping' ?></a>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>