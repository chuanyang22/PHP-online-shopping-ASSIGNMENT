<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../lib/db.php';

// 1. Check if the URL has an ID (e.g., product_edit.php?id=3)
if (!isset($_GET['id'])) {
    die("Error: No product ID provided.");
}
$product_id = $_GET['id'];

// 2. Fetch the product's current details from the database
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Error: Product not found in the database.");
}

// 3. Listen for the "Update Product" button click
if (isset($_POST['update_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    
    // Check if they uploaded a NEW photo, otherwise keep the old one
    $image_name = $product['image_name']; 
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $image_name = $_FILES['product_image']['name'];
        $tmp_name = $_FILES['product_image']['tmp_name'];
        move_uploaded_file($tmp_name, "../uploads/" . $image_name);
    }

    // Send the updated info back to the database
    try {
        $update_stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock_quantity = ?, image_name = ? WHERE id = ?");
        $update_stmt->execute([$name, $price, $stock, $image_name, $product_id]);
        
        // Success pop-up and redirect back to the main table!
        echo "<script>
                alert('Product updated successfully!');
                window.location.href = 'products_crud.php';
              </script>";
    } catch(PDOException $e) {
        echo "Error updating product: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .admin-container { max-width: 600px; margin: auto; background: #f9f9f9; padding: 20px; border-radius: 5px; border: 1px solid #ddd; }
        .form-group { margin-bottom: 15px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .cancel-btn { background: #666; text-decoration: none; padding: 10px 15px; color: white; display: inline-block; margin-left: 10px;}
    </style>
</head>
<body>

<div class="admin-container">
    <h2>Edit Product #<?php echo htmlspecialchars($product['id']); ?></h2>
    
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Name:</label>
            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Price (RM):</label>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Stock Quantity:</label>
            <input type="number" name="stock" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Current Photo:</label><br>
            <img src="../uploads/<?php echo htmlspecialchars($product['image_name']); ?>" width="80" style="border: 1px solid #ccc; margin-top: 5px;"><br><br>
            
            <label>Upload New Photo (Leave blank to keep current photo):</label><br>
            <input type="file" name="product_image" accept="image/*">
        </div>
        
        <button type="submit" name="update_product">Update Product</button>
        <a href="products_crud.php" class="cancel-btn">Cancel</a>
    </form>
</div>

</body>
</html>