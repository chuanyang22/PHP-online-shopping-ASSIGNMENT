<?php
// product_detail.php
require_once 'lib/db.php';

// 1. Get the Product ID from the URL (the ?id=1 part)
if (!isset($_GET['id'])) {
    die("Error: Product ID is missing.");
}
$product_id = $_GET['id'];

// 2. Fetch the product details from the database
try {
    $stmt = $pdo->prepare("SELECT products.*, categories.name AS category_name 
                           FROM products 
                           LEFT JOIN categories ON products.category_id = categories.id 
                           WHERE products.id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Error: Product not found in our database.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Details</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f0f2f5; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        .detail-card { 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            max-width: 500px; 
            width: 90%; 
            text-align: center; 
        }
        .detail-card img { 
            width: 100%; 
            max-height: 300px; 
            object-fit: contain; 
            border-radius: 10px; 
            margin-bottom: 20px; 
        }
        .price { 
            font-size: 28px; 
            color: #28a745; 
            font-weight: bold; 
            margin: 10px 0; 
        }
        .category { color: #666; font-style: italic; margin-bottom: 20px; }
        .stock { 
            display: inline-block; 
            padding: 5px 15px; 
            background: #e9ecef; 
            border-radius: 20px; 
            font-weight: bold; 
        }
        .back-link { 
            display: block; 
            margin-top: 30px; 
            text-decoration: none; 
            color: #4a90e2; 
            font-weight: bold; 
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="detail-card">
    <img src="uploads/<?php echo htmlspecialchars($product['image_name']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    
    <div class="category">Category: <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
    
    <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
    
    <div class="stock">
        <?php if ($product['stock_quantity'] > 0): ?>
            ✅ In Stock: <?php echo $product['stock_quantity']; ?>
        <?php else: ?>
            ❌ Out of Stock
        <?php endif; ?>
    </div>

    <a href="index.php" class="back-link">⬅ Back to Store</a>
</div>

</body>
</html>