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

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$member_id = $_SESSION['user_id'];

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND member_id = ?");
$stmt->execute([$order_id, $member_id]);
$order = $stmt->fetch();

if (!$order) {
    die("<div class='text-center p-50'><h2 class='text-main'>Order not found.</h2><a href='order_history.php'>← Back to Orders</a></div>");
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
    <title><?= $lang['order_details'] ?? 'Order Details' ?> #<?= $order_id ?></title>
    <link rel="stylesheet" href="../css/mainstyle.css">
</head>
<body class="home-body">

    <?php include '../header.php'; ?>

    <div class="order-container">
        
        <div class="mb-20">
            <a href="order_history.php" class="link-blue no-underline">← Back to Orders</a>
        </div>

        <div class="order-card">
            <div class="order-header no-border m-0 p-0">
                <div>
                    <h2><?= $lang['order_details'] ?? 'Order Details' ?> #<?= htmlspecialchars($order['id']) ?></h2>
                    <div class="text-muted mt-5">
                        <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?>
                    </div>
                </div>
                <div class="order-status status-<?= strtolower($order['status']) ?>">
                    <?= htmlspecialchars($order['status']) ?>
                </div>
            </div>
        </div>

        <h3 class="mt-20 mb-10 text-main"><?= $lang['items'] ?? 'Items' ?></h3>
        <div class="bg-card radius-8 overflow-hidden border-solid">
            <?php foreach ($items as $item): ?>
                <?php 
                    $img_path = (!empty($item['image_name']) && file_exists('../uploads/' . $item['image_name'])) 
                        ? '../uploads/' . $item['image_name'] 
                        : '../uploads/default.png';
                ?>
                <div class="item-card">
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

        <div class="total-card">
            <div class="total-label"><?= $lang['total_amount'] ?? 'Total Amount' ?></div>
            <div class="total-amount">RM <?= number_format($order['total_amount'], 2) ?></div>
        </div>

        <?php if ($order['status'] === 'Pending'): ?>
            <div class="cancel-card">
                <form method="POST" action="cancel_order.php" 
                      onsubmit="return confirm('Are you sure you want to cancel this order? This cannot be undone.');">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn-cancel"><?= $lang['cancel_order'] ?? 'Cancel Order' ?></button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>