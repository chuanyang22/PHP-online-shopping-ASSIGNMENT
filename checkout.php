<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';
require_once 'lib/cart_persist.php';

// Include language support if you have it, otherwise default to English
$current_lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    require_once $lang_file;
}

if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to checkout. <a href='login.php'>Click here to login.</a>");
}

$user_id = $_SESSION['user_id'];
$error = '';

// Fetch saved address from DB so the user doesn't have to retype it
$stmt = $pdo->prepare("SELECT address FROM member WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
$saved_address = $user_data['address'] ?? '';

// Fetch cart items & calculate total while verifying current stock
$cart_total = 0;
$cart_items = [];
$placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($_SESSION['cart']));
$products_in_cart = $stmt->fetchAll();

foreach ($products_in_cart as $product) {
    $qty = $_SESSION['cart'][$product['id']];
    
    // SAFETY CHECK: Ensure quantity doesn't exceed available stock
    if ($qty > $product['stock_quantity']) {
        $error = "Error: " . htmlspecialchars($product['name']) . " only has " . $product['stock_quantity'] . " left in stock.";
    }

    $subtotal = $product['price'] * $qty;
    $cart_total += $subtotal;
    $product['cart_qty'] = $qty;
    $product['subtotal'] = $subtotal;
    $cart_items[] = $product;
}

// Handle form submission AFTER PayPal approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $shipping_address  = trim($_POST['shipping_address'] ?? '');
    $paypal_order_id   = trim($_POST['paypal_order_id'] ?? '');
    $paypal_capture_id = trim($_POST['paypal_capture_id'] ?? '');

    if (empty($shipping_address)) {
        $error = "Please provide a shipping address.";
    } elseif (empty($paypal_order_id)) {
        $error = "Payment was not completed. Please use the PayPal button.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Save their new address for next time if they changed it
            if ($shipping_address !== $saved_address) {
                $update_stmt = $pdo->prepare("UPDATE member SET address = ? WHERE id = ?");
                $update_stmt->execute([$shipping_address, $user_id]);
            }

            // Save order — using your original query structure
            $stmt = $pdo->prepare("
                INSERT INTO orders 
                    (member_id, total_amount, shipping_address, status, payment_method, paypal_order_id, paypal_capture_id)
                VALUES 
                    (?, ?, ?, 'Paid', 'PayPal', ?, ?)
            ");
            $stmt->execute([$user_id, $cart_total, $shipping_address, $paypal_order_id, $paypal_capture_id]);
            $order_id = $pdo->lastInsertId();

            // Save order items & deduct stock
            $stmt_items   = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            $update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($cart_items as $item) {
                $stmt_items->execute([$order_id, $item['id'], $item['cart_qty'], $item['price']]);
                $update_stock->execute([$item['cart_qty'], $item['id']]);
            }

            $pdo->commit();

            // Clear cart after successful order (session + saved cart)
            cart_clear_member_cart($pdo, $user_id);
            $_SESSION['cart'] = [];

            // Redirect to your specific history page
            header("Location: member/order_history.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to save order: " . $e->getMessage();
        }
    }
}
?>

<?php include 'header.php'; ?>

<script src="https://www.paypal.com/sdk/js?client-id=AUs-N0E4V8HRGsAx54opyGxI2UXVk2npBD7c2ArivbMkaTdIPhls9vHk6A_I8ikJNAWpv05tQ7OqS1sk&currency=MYR&disable-funding=card"></script>

<div class="checkout-container page-container">
    <h2 class="text-center">💳 <?= $lang['checkout'] ?? 'Checkout' ?></h2>

    <?php if ($error): ?>
        <div class="msg-error p-15 mb-20 text-center font-bold" style="background: #ffebee; color: #c62828; border-radius: 4px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="order-summary-box mb-20 p-20" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px;">
        <h3 class="text-center mb-15"><?= $lang['order_summary'] ?? 'Order Summary' ?></h3>
        
        <div class="checkout-items-list mb-20">
            <?php foreach($cart_items as $item): ?>
                <div class="item-card flex align-center mb-10" style="border-bottom: 1px dashed #ccc; padding-bottom: 10px;">
                    <?php $img = (!empty($item['image_name']) && file_exists('uploads/'.$item['image_name'])) ? 'uploads/'.$item['image_name'] : 'uploads/default.png'; ?>
                    <div class="item-image mr-15">
                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                    </div>
                    <div class="item-details flex-grow">
                        <div class="item-name font-bold"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="text-muted text-sm"><?= $lang['qty'] ?? 'Qty' ?>: <?= $item['cart_qty'] ?></div>
                    </div>
                    <div class="item-price font-bold text-main">
                        RM <?= number_format($item['subtotal'], 2) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="text-right mt-20" style="color: #27ae60;">
            Total: <strong>RM <?= number_format($cart_total, 2) ?></strong>
        </h3>
    </div>

    <form id="checkout-form" action="checkout.php" method="POST">
        <div class="form-group mb-15">
            <label for="shipping_address" class="form-label font-bold block mb-5">
                <?= $lang['shipping_address'] ?? 'Shipping Address' ?>:
            </label>
            <textarea name="shipping_address" id="shipping_address"
                      rows="3" class="address-textarea w-full p-10 border-radius-4" 
                      style="width: 100%; border: 1px solid #ccc;" required><?= htmlspecialchars($saved_address) ?></textarea>
        </div>

        <input type="hidden" name="paypal_order_id"   id="paypal_order_id">
        <input type="hidden" name="paypal_capture_id" id="paypal_capture_id">
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
                alert('<?= addslashes($lang['enter_address'] ?? 'Please enter your shipping address before paying.') ?>');
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
        onCancel: function () { alert('Payment cancelled. Your cart is still saved.'); },
        onError:  function (err) { alert('A PayPal error occurred. Please try again.'); console.error(err); }
    }).render('#paypal-button-container');
</script>

<?php include 'footer.php'; ?>