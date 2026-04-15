<?php
require_once '../lib/auth.php';
require_once '../lib/db.php';
require_once '../lib/helpers.php';

auth('Member');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $member_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND member_id = ? AND status = 'Pending'");
    
    if ($stmt->execute([$order_id, $member_id]) && $stmt->rowCount() > 0) {
        header("Location: order_history.php?success=cancelled");
    } else {
        header("Location: order_history.php?error=cancel_failed");
    }
    exit();
}

header("Location: order_history.php");
exit();
?>