<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// Load language
$current_lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
require_once file_exists($lang_file) ? $lang_file : __DIR__ . "/lang/en.php";

if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to checkout. <a href='login.php'>Click here to login.</a>");
}

$user_id = $_SESSION['user_id'];
$error   = '';

// Fetch saved address
$stmt = $pdo->prepare("SELECT address FROM member WHERE id = ?");
$stmt->execute([$user_id]);
$user_data     = $stmt->fetch();
$saved_address = $user_data['address'] ?? '';

// Fetch cart items & verify current stock
$cart_total = 0;
$cart_items = [];
$placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($_SESSION['cart']));
$products_in_cart = $stmt->fetchAll();

foreach ($products_in_cart as $product) {
    $qty = $_SESSION['cart'][$product['id']];
    
    // FINAL STOCK CHECK: Ensure quantity doesn't exceed available stock
    if ($qty > $product['stock_quantity']) {
        $error = "Error: " . htmlspecialchars($product['name']) . " only has " . $product['stock_quantity'] . " left in stock.";
    }

    $subtotal = $product['price'] * $qty;
    $cart_total += $subtotal;
    $product['cart_qty'] = $qty;
    $product['subtotal'] = $subtotal;
    $cart_items[] = $product;
}

// Handle POST after PayPal approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $shipping_address  = trim($_POST['shipping_address']  ?? '');
    $paypal_order_id   = trim($_POST['paypal_order_id']   ?? '');
    $paypal_capture_id = trim($_POST['paypal_capture_id'] ?? '');

    if (empty($shipping_address) || empty($paypal_order_id) || empty($paypal_capture_id)) {
        $error = "Error processing payment details. Please contact support.";
    } else {
        // Update user address if it changed
        if ($shipping_address !== $saved_address) {
            $update_stmt = $pdo->prepare("UPDATE member SET address = ? WHERE id = ?");
            $update_stmt->execute([$shipping_address, $user_id]);
        }

        // Insert into orders table with status 'Paid'
        $stmt = $pdo->prepare("INSERT INTO orders (member_id, total_amount, shipping_address, status, paypal_capture_id, paypal_order_id) VALUES (?, ?, ?, 'Paid', ?, ?)");
        if ($stmt->execute([$user_id, $cart_total, $shipping_address, $paypal_capture_id, $paypal_order_id])) {
            $order_id  = $pdo->lastInsertId();
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            $stock_stmt= $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($cart_items as $item) {
                // Save each item and REDUCE stock quantity in the database
                $item_stmt->execute([$order_id, $item['id'], $item['cart_qty'], $item['price']]);
                $stock_stmt->execute([$item['cart_qty'], $item['id']]);
            }
            
            unset($_SESSION['cart']);
            header("Location: order_success.php?id=" . $order_id);
            exit();
        } else {
            $error = "Failed to save order to database.";
        }
    }
}
?>

<?php include 'header.php'; ?>

<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD"></script>

<div class="checkout-container">
    <h2 class="text-center">💳 <?= $lang['checkout'] ?></h2>

    <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="order-summary-box p-30">
        <h3 class="text-center">
            <?= $lang['order_summary'] ?>: <strong>RM <?= number_format($cart_total, 2) ?></strong>
        </h3>
    </div>

    <form id="checkout-form" action="checkout.php" method="POST">
        <label for="shipping_address" class="form-label">
            <?= $lang['shipping_address'] ?>:
        </label>
        <textarea name="shipping_address" id="shipping_address"
                  rows="3" class="address-textarea" required><?= htmlspecialchars($saved_address) ?></textarea>

        <input type="hidden" name="paypal_order_id"   id="paypal_order_id"   value="">
        <input type="hidden" name="paypal_capture_id" id="paypal_capture_id" value="">
    </form>

    <?php if (empty($error)): ?>
        <div id="paypal-button-container" class="mt-20"></div>
    <?php else: ?>
        <div class="text-center mt-20">
            <a href="cart.php" class="btn"><?= $lang['back_to_cart'] ?? 'Return to Cart' ?></a>
        </div>
    <?php endif; ?>
</div>

<script>
    paypal.Buttons({
        onClick: function (data, actions) {
            var address = document.getElementById('shipping_address').value.trim();
            if (address === '') {
                alert('<?= addslashes($lang['enter_address']) ?>');
                return actions.reject();
            }
            return actions.resolve();
        },
        createOrder: function (data, actions) {
            return actions.order.create({
                purchase_units: [{ amount: { value: '<?= number_format($cart_total, 2, '.', '') ?>' } }]
            });
        },
        onApprove: function (data, actions) {
            return actions.order.capture().then(function (details) {
                document.getElementById('paypal_order_id').value   = data.orderID;
                document.getElementById('paypal_capture_id').value = details.purchase_units[0].payments.captures[0].id;
                document.getElementById('checkout-form').submit();
            });
        },
        onCancel: function () { alert('Payment cancelled.'); },
        onError:  function (err) { alert('A PayPal error occurred.'); }
    }).render('#paypal-button-container');
</script>

<?php include 'footer.php'; ?>