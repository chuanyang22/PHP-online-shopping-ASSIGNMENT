<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// --- SEARCH & PRODUCT FETCHING LOGIC ---
$search_keyword = '';
// Base SQL query fetching products and their category names
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id";

// If a search was submitted, append the WHERE clause
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search_keyword = trim($_GET['search']);
    $query .= " WHERE p.name LIKE :search OR p.description LIKE :search";
}

$stmt = $pdo->prepare($query);

// Execute with or without the search parameter
if ($search_keyword !== '') {
    $stmt->execute(['search' => "%$search_keyword%"]);
} else {
    $stmt->execute();
}

$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Accessory Store - Home</title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body style="margin: 0; background-color: #f5f5f5;">
    
    <div class="navbar">
        <div class="navbar-brand">
            <a href="index.php">🛍️ Online Store</a>
        </div>
        
        <div class="navbar-profile">
            <a href="index.php">🏠 Home</a>
            <span class="navbar-divider"></span>

            <?php if(isset($_SESSION['username'])): ?>
                <!-- <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                
                <span class="navbar-divider"></span> -->
                <a href="cart.php">🛒 My Cart</a>
                

                <span class="navbar-divider"></span>
                <a href="wishlist.php">❤️ My Wishlist</a>
                
                <span class="navbar-divider"></span>
                <a href="profile.php">🧏‍♂️ My Profile</a>
                
                <span class="navbar-divider"></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="register.php">Sign Up</a>
                <span class="navbar-divider"></span>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="home-container" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
        <h2>Welcome to the Store!</h2>
        
        <form method="GET" action="index.php" class="search-bar">
            <input type="text" name="search" placeholder="Search for products..." value="<?= htmlspecialchars($search_keyword) ?>">
            <button type="submit" class="btn" style="margin-top: 0;">Search</button>
            <?php if($search_keyword !== ''): ?>
                <a href="index.php" class="btn" style="background: #e74c3c; margin-top: 0;">Clear</a>
            <?php endif; ?>
        </form>

        <?php if(count($products) > 0): ?>
            <div class="product-grid">
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <?php $imagePath = !empty($product['image_name']) ? 'uploads/' . htmlspecialchars($product['image_name']) : 'uploads/default.png'; ?>
                        <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                        
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p style="font-size: 0.9em; color: #7f8c8d;"><?= htmlspecialchars($product['category_name']) ?></p>
                        <p class="price">$<?= number_format($product['price'], 2) ?></p>
                        
                        <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn">View Details</a>

                        <form action="cart.php" method="POST" style="display: inline-block;">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn" style="background: #27ae60;">🛒 Add</button>
                        </form>

                        <form action="wishlist_action.php" method="POST" style="display: inline-block;">

                        <form action="wishlist_action.php" method="POST" style="display: inline-block;">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="btn" style="background: #9b59b6;">❤️ Save</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No products found matching your search.</p>
        <?php endif; ?>

    </div>

</body>
</html>