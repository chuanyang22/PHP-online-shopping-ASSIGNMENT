<?php
include 'header.php'; // loads session, db, $lang, navbar

// --- 1. FETCH ALL CATEGORIES FOR THE DROPDOWN ---
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$all_categories = $cat_stmt->fetchAll();

// --- 2. DYNAMIC SEARCH & FILTER LOGIC ---
$search_keyword = '';
$category_filter = '';

// Base query using WHERE 1=1 so we can easily append dynamic conditions
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

$params = [];

// Check if there is a text search
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search_keyword = trim($_GET['search']);
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params['search'] = "%$search_keyword%";
}

// Check if a category is selected
if (isset($_GET['category_filter']) && $_GET['category_filter'] !== '') {
    $category_filter = $_GET['category_filter'];
    $query .= " AND p.category_id = :category";
    $params['category'] = $category_filter;
}

// Execute the dynamic query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="home-container">
    <h2 class="home-title"><?= $lang['welcome'] ?? 'Welcome to Our Store' ?></h2>

    <form method="GET" action="index.php" class="search-bar">
        
        <select name="category_filter" class="search-select">
            <option value=""><?= $lang['all_categories'] ?? 'All Categories' ?></option>
            <?php foreach($all_categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($category_filter == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="search" class="search-input"
               placeholder="<?= htmlspecialchars($lang['search_placeholder'] ?? 'Search products...') ?>"
               value="<?= htmlspecialchars($search_keyword) ?>">
               
        <button type="submit" class="btn"><?= $lang['search_btn'] ?? 'Search' ?></button>
        
        <?php if ($search_keyword !== '' || $category_filter !== ''): ?>
            <a href="index.php" class="btn-clear"><?= $lang['clear_btn'] ?? 'Clear' ?></a>
        <?php endif; ?>
    </form>

    <?php if (count($products) > 0): ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php $imagePath = (!empty($product['image_name']) && file_exists('uploads/' . $product['image_name']))
                        ? 'uploads/' . htmlspecialchars($product['image_name'])
                        : 'uploads/default.png'; ?>
                    <img src="<?= $imagePath ?>" class="product-image" alt="<?= htmlspecialchars($product['name']) ?>">

                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="product-category"><?= htmlspecialchars($product['category_name']) ?></p>
                    <p class="price">RM <?= number_format($product['price'], 2) ?></p>

                    <div class="product-actions">
                        <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn btn-view">
                            <?= $lang['view_details'] ?? 'View Details' ?>
                        </a>

                        <div class="action-row">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form action="cart.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-add-cart">
                                        <?= $lang['add_to_cart'] ?? 'Add to Cart' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div>
                                    <button type="button" class="btn btn-add-cart"
                                            onclick="window.location.href='login.php';">
                                        <?= $lang['add_to_cart'] ?? 'Add to Cart' ?>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <form action="wishlist_action.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="action" value="add">
                                <button type="submit" class="btn btn-wishlist">
                                    <?= $lang['save_wishlist'] ?? 'Wishlist' ?>
                                </button>
                            </form>

                        </div></div></div><?php endforeach; ?>
        </div><?php else: ?>
        <p><?= $lang['no_products'] ?? 'No products found.' ?></p>
    <?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchForm = document.querySelector('.search-bar');
    const categorySelect = document.querySelector('.search-select');
    
    // Auto-submit ONLY when the user selects a new category
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            searchForm.submit();
        });
    }
});
</script>

</div><?php if (file_exists('footer.php')) include 'footer.php'; ?>