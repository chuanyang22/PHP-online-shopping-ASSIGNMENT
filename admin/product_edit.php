<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../lib/db.php';

if (!isset($_GET['id'])) {
    die("Error: No product ID provided.");
}
$product_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Error: Product not found in the database.");
}

if (isset($_POST['update_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id']; 
    
    $image_name = $product['image_name']; 
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $image_name = $_FILES['product_image']['name'];
        $tmp_name = $_FILES['product_image']['tmp_name'];
        move_uploaded_file($tmp_name, '../uploads/' . $image_name);
    }
    
    $update_stmt = $pdo->prepare("UPDATE products SET name=?, price=?, stock_quantity=?, image_name=?, category_id=? WHERE id=?");
    if ($update_stmt->execute([$name, $price, $stock, $image_name, $category_id, $product_id])) {
        echo "<script>alert('Product updated!'); window.location.href='products_crud.php';</script>";
    } else {
        echo "Error updating record.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
</head>
<body class="admin-body">

<div class="dashboard-chart-card admin-container-sm">
    <h2 class="text-blue-title mt-0">✏️ Edit Product</h2>
    <a href="products_crud.php" class="text-blue-link inline-block mb-20">&larr; Back to Products</a>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="admin-form-group">
            <label class="font-bold">Name:</label>
            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="admin-input-full">
        </div>

        <div class="admin-form-group mt-15">
            <label class="font-bold">Category:</label>
            <select name="category_id" required class="admin-input-full">
                <option value="">-- Select Category --</option>
                <?php
                $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
                while($cat = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = ($cat['id'] == $product['category_id']) ? "selected" : "";
                    echo "<option value='" . $cat['id'] . "' " . $selected . ">" . htmlspecialchars($cat['name']) . "</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="admin-form-group mt-15">
            <label class="font-bold">Price (RM):</label>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required class="admin-input-full">
        </div>
        
        <div class="admin-form-group mt-15">
            <label class="font-bold">Stock Quantity:</label>
            <input type="number" name="stock" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required class="admin-input-full">
        </div>
        
        <div class="admin-form-group mt-15">
            <label class="font-bold">Current Photo:</label><br>
            <img src="../uploads/<?php echo htmlspecialchars($product['image_name']); ?>" class="product-edit-img mt-5 mb-10"><br>
            
            <label class="font-bold">Upload New Photo</label> (Leave blank to keep current photo):<br>
            <input type="file" name="product_image" accept="image/*" class="file-upload-margin mt-5">
        </div>
        
        <div class="mt-25">
            <button type="submit" name="update_product" class="btn-success-solid">Update Product</button>
        </div>
    </form>
</div>

</body>
</html>