<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// Include language support
$current_lang = $_SESSION['lang'] ?? 'en';
if (file_exists(__DIR__ . "/lang/{$current_lang}.php")) {
    require_once __DIR__ . "/lang/{$current_lang}.php";
}

// Kick them out if they aren't logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view your wishlist. <a href='login.php'>Click here to login.</a>");
}

$user_id = $_SESSION['user_id'];

// Fetch only the products that are in this specific user's wishlist
$stmt = $pdo->prepare("
    SELECT p.* FROM products p
    JOIN wishlist w ON p.id = w.product_id
    WHERE w.member_id = ?
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>

    <div class="page-container">
        <h2>❤️ <?= $lang['my_wishlist'] ?? 'My Wishlist' ?></h2>

        <?php if (isset($_SESSION['popup'])): ?>
            <p class="msg-success"><?= htmlspecialchars($_SESSION['popup']) ?></p>
            <?php unset($_SESSION['popup']); ?>
        <?php endif; ?>

        <?php if(count($wishlist_items) > 0): ?>
            <div class="product-grid">
                <?php foreach($wishlist_items as $item): ?>
                    <div class="product-card">
                        <?php $imagePath = !empty($item['image_name']) ? 'uploads/' . htmlspecialchars($item['image_name']) : 'uploads/default.png'; ?>
                        <img src="<?= $imagePath ?>" class="product-image" alt="<?= htmlspecialchars($item['name']) ?>">
                        
                        <h3 class="product-title"><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="product-price">RM <?= number_format($item['price'], 2) ?></p>
                        
                        <div class="product-actions btn-group-col btn-full-width">
                            
                            <a href="product_detail.php?id=<?= $item['id'] ?>" class="btn btn-view m-0">
                                <?= $lang['view_details'] ?? 'View Details' ?>
                            </a>
                            
                            <form action="wishlist_action.php" method="POST" class="m-0">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="btn btn-clear btn-full-width no-border m-0">
                                    <?= $lang['remove_from_wishlist'] ?? 'Remove from Wishlist' ?>
                                </button>
                            </form>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><?= $lang['empty_wishlist'] ?? 'Your wishlist is currently empty!' ?> <a href="index.php" class="link-primary"><?= $lang['go_find_stuff'] ?? 'Go find some cool stuff.' ?></a></p>
        <?php endif; ?>

    </div>

<?php include 'footer.php'; ?>