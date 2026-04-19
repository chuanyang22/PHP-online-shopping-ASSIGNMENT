<?php
session_start();
require_once '../lib/db.php'; // Remember to use ../ since we are inside the 'member' folder

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$order_id = $_POST['order_id'] ?? null;
$user_id = $_SESSION['user_id'];

if ($order_id) {
    try {
        $pdo->beginTransaction();

        // 2. Verify the order exists, belongs to this user, and is eligible for cancellation
        $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND member_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();

        // If the order exists and is either Pending or Completed (Paid)
        if ($order && in_array($order['status'], ['Pending', 'Completed'])) {
            
            // 3. Mark the order as Cancelled
            $update = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?");
            $update->execute([$order_id]);

            // 4. Look up exactly what items were in this order
            $items_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items_stmt->execute([$order_id]);
            $items = $items_stmt->fetchAll();

            // 5. Return those items to the store inventory!
            $restore_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            foreach ($items as $item) {
                $restore_stock->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->commit();
            
            // Set a success notification
            $_SESSION['popup'] = "🚫 Order #$order_id has been successfully cancelled.";

        } else {
            $pdo->rollBack();
            // If they tried to bypass the system to cancel a Shipped order
            $_SESSION['popup'] = "⚠️ Error: This order has already been shipped and cannot be cancelled.";
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['popup'] = "⚠️ Failed to cancel order: " . $e->getMessage();
    }
}

// Magically return them back to their order history page
header("Location: order_history.php");
exit();
?>