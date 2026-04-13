<?php
session_start();
require_once 'lib/db.php';

// Ensure the user is logged in before saving anything
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if they try to save while logged out
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;
$action = $_POST['action'] ?? '';

if ($product_id && $action) {
    if ($action === 'add') {
        // 1. Check if it's already in the wishlist so we don't add duplicates
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE member_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        if ($stmt->rowCount() == 0) {
            // 2. If it's not there, insert it!
            $insert = $pdo->prepare("INSERT INTO wishlist (member_id, product_id) VALUES (?, ?)");
            $insert->execute([$user_id, $product_id]);
        }
        // Send them back to the homepage
        header("Location: index.php?msg=saved");
        
    } elseif ($action === 'remove') {
        // Delete the item from the wishlist
        $delete = $pdo->prepare("DELETE FROM wishlist WHERE member_id = ? AND product_id = ?");
        $delete->execute([$user_id, $product_id]);
        // Send them back to the wishlist page
        header("Location: wishlist.php?msg=removed");
    }
} else {
    header("Location: index.php");
}
exit();
?>