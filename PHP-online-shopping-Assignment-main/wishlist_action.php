<?php
session_start();
require_once 'lib/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;
$action = $_POST['action'] ?? '';

if ($product_id && $action) {
    if ($action === 'add') {
        // 1. Check if it is already in the wishlist
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE member_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        if ($stmt->rowCount() == 0) {
            // 2. If it is NOT there, insert it and show the success message
            $insert = $pdo->prepare("INSERT INTO wishlist (member_id, product_id) VALUES (?, ?)");
            $insert->execute([$user_id, $product_id]);
            
            $_SESSION['popup'] = "❤️ Item saved to your wishlist!";
        } else {
            // 3. If it IS already there, show the specific alert message instead
            $_SESSION['popup'] = "ℹ️ This item is already inside your wishlist!";
        }
        
    } elseif ($action === 'remove') {
        $delete = $pdo->prepare("DELETE FROM wishlist WHERE member_id = ? AND product_id = ?");
        $delete->execute([$user_id, $product_id]);
        
        // Save the popup message
        $_SESSION['popup'] = "❌ Item removed from your wishlist.";
    }
}

// Magically send them back to the exact page they clicked the button on!
$return_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: " . $return_url);
exit();
?>