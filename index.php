<?php 
// This loads your database, session, and navbar
include 'header.php'; 

// --- SEARCH & PRODUCT FETCHING LOGIC ---
$search_keyword = '';
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id";

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search_keyword = trim($_GET['search']);
    $query .= " WHERE p.name LIKE :search OR p.description LIKE :search";
}

$stmt = $pdo->prepare($query);

if ($search_keyword !== '') {
    $stmt->execute(['search' => "%$search_keyword%"]);
} else {
    $stmt->execute();
}

$products = $stmt->fetchAll();
?>

<div class="home-container">
    <h2 class="home-title">Welcome to the Store!</h2>
    
    <form method="GET" action="index.php" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="Search for products..." value="<?= htmlspecialchars($search_keyword) ?>">
        <button type="submit" class="btn">Search</button>
        <?php if($search_keyword !== ''): ?>
            <a href="index.php" class="btn-clear">Clear</a>
        <?php endif; ?>
    </form>

    <?php if(count($products) > 0): ?>
        <div class="product-grid">
            <?php foreach($products as $product): ?>
                <div class="product-card">
                     <?php $imagePath = !empty($product['image_name']) ? 'uploads/' . htmlspecialchars($product['image_name']) : 'uploads/default.png'; ?>
                    <img src="<?= $imagePath ?>" class="product-image" alt="<?= htmlspecialchars($product['name']) ?>">
    
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="product-category"><?= htmlspecialchars($product['category_name']) ?></p>
                    <p class="price">$<?= number_format($product['price'], 2) ?></p>

                 <div class="product-actions">
        
                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn btn-view">View Details</a>

                    <div class="action-row">
            
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-add-cart">🛒 Add</button>
                            </form>
                        <?php else: ?>
                    <div>
                        <button type="button" class="btn btn-add-cart" onclick="window.location.href='login.php';">🛒 Add</button>
                    </div>
                <?php endif; ?>

                <form action="wishlist_action.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="action" value="add">
                    <button type="submit" class="btn btn-wishlist">❤️ Save</button>
                </form>

         </div>
                </div>  
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No products found matching your search.</p>
    <?php endif; ?>
</div>

<?php 
// If you have a footer, include it here:
if(file_exists('footer.php')){
    include 'footer.php'; 
}
?>