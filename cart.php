<?php
// cart.php
include 'header.php'; // Loads session, pdo, helpers, and language
require_once __DIR__ . '/lib/cart_persist.php';

// --- 1. HANDLE CART ACTIONS (Add/Update/Remove) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = $_POST['product_id'];

    if ($_POST['action'] === 'add') {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
    } elseif ($_POST['action'] === 'update') {
        $new_qty = (int)$_POST['quantity'];
        if ($new_qty > 0) {
            $_SESSION['cart'][$product_id] = $new_qty;
        }
    } elseif ($_POST['action'] === 'remove') {
        unset($_SESSION['cart'][$product_id]);
    }

    if (!empty($_SESSION['user_id'])) {
        cart_save_member_cart($pdo, (int) $_SESSION['user_id'], $_SESSION['cart']);
    }
    
    header("Location: cart.php");
    exit();
}

// --- 2. PREPARE CART DATA & CALCULATE TOTAL ---
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        
        // PHP SAFETY CHECK: If stock dropped since they added it, auto-correct the session
        if ($qty > $product['stock_quantity']) {
            $qty = $product['stock_quantity']; 
            $_SESSION['cart'][$product['id']] = $qty;
        }
        
        $subtotal = $product['price'] * $qty;
        $total += $subtotal;
        
        $product['qty'] = $qty;
        $product['subtotal'] = $subtotal;
        $cart_items[] = $product;
    }
}
?>

<div class="cart-container">
    <h2>🛒 <?= $lang['cart'] ?? 'Your Shopping Cart' ?></h2>

    <?php if (empty($cart_items)): ?>
        <div class="empty-state">
            <p><?= $lang['cart_empty'] ?? 'Your cart is currently empty.' ?></p>
            <a href="index.php" class="btn"><?= $lang['start_shopping'] ?? 'Back to Shop' ?></a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th><?= $lang['product'] ?? 'Product' ?></th>
                    <th><?= $lang['price'] ?? 'Price' ?></th>
                    <th><?= $lang['qty'] ?? 'Quantity' ?></th>
                    <th><?= $lang['subtotal'] ?? 'Subtotal' ?></th>
                    <th><?= $lang['action'] ?? 'Action' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td>
                            <?php 
                                $img = (!empty($item['image_name']) && file_exists('uploads/'.$item['image_name'])) 
                                    ? 'uploads/'.$item['image_name'] 
                                    : 'uploads/default.png'; 
                            ?>
                            <img src="<?= $img ?>" class="cart-img" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?= htmlspecialchars($item['name']) ?>
                        </td>
                        <td>RM <?= number_format($item['price'], 2) ?></td>
                        <td>
                            <form action="cart.php" method="POST" class="form-inline">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="number" name="quantity" value="<?= $item['qty'] ?>" min="1" max="<?= $item['stock_quantity'] ?>" class="qty-input auto-submit-qty">
                                </form>
                        </td>
                        <td>RM <?= number_format($item['subtotal'], 2) ?></td>
                        <td>
                            <form action="cart.php" method="POST" class="form-inline">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="btn-remove">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <h3>Total: RM <?= number_format($total, 2) ?></h3>
            <a href="checkout.php" class="btn-checkout">
                <?= $lang['proceed_to_checkout'] ?? 'Proceed to Checkout' ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Find all the quantity inputs we marked with the class
        const qtyInputs = document.querySelectorAll('.auto-submit-qty');
        
        qtyInputs.forEach(input => {
            // Listen for any changes (like clicking the arrows or typing a number and clicking away)
            input.addEventListener('change', function () {
                let maxStock = parseInt(this.getAttribute('max'));
                let currentVal = parseInt(this.value);

                // Check 1: Did they type a number higher than stock?
                if (currentVal > maxStock) {
                    this.value = maxStock;
                    alert('Only ' + maxStock + ' items available in stock. Quantity adjusted.');
                }
                
                // Check 2: Did they type a negative number or 0?
                if (currentVal < 1 || isNaN(currentVal)) {
                    this.value = 1;
                }

                // Automatically submit the form to update the PHP session and calculate new totals
                this.closest('form').submit();
            });
        });
    });
</script>

<?php include 'footer.php'; ?>