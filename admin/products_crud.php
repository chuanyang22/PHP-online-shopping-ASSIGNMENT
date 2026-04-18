<?php
session_start();
require_once '../lib/db.php';
require_once '../lib/helpers.php';

// --- SECURITY LOCK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================
// 1. HANDLE DELETE PRODUCT
// ==========================================
if (isset($_POST['delete_product'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        echo "<script>alert('Product successfully deleted!'); window.location.href='products_crud.php';</script>";
    } catch(PDOException $e) {
        echo "<script>alert('Error deleting product: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// ==========================================
// 2. HANDLE ADD PRODUCT
// ==========================================
if (isset($_POST['add_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock']; 
    $category_id = $_POST['category_id']; 

    $image_name = $_FILES['product_image']['name'];
    $tmp_name = $_FILES['product_image']['tmp_name'];
    $upload_dir = '../uploads/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    move_uploaded_file($tmp_name, $upload_dir . $image_name);

    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, stock_quantity, image_name, category_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $stock, $image_name, $category_id]);
        echo "<script>alert('Product successfully added!'); window.location.href='products_crud.php';</script>";
    } catch(PDOException $e) {
        echo "<script>alert('Error saving product: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// ==========================================
// 3. FETCH DATA (Categories + Products)
// ==========================================

// Fetch categories for the dropdowns
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Search and Filter inputs
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category_filter'] ?? '';

// Build the query dynamically
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}

if ($category_filter) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
</head>
<body class="admin-body">
<div class="admin-layout">

    <?php require_once 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-card">
            
            <div class="flex-between-center mb-20">
                <h2 class="mt-0 mb-0 text-blue-title">📦 Manage Products</h2>
                
                <form method="GET" action="" class="admin-search-form">
                    <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>" class="admin-input-full search-input-w200">
                    
                    <select name="category_filter" class="admin-input-full search-select-auto">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn-search-blue">Search</button>
                    <a href="products_crud.php" class="btn-reset-gray">Reset</a>
                </form>
            </div>

            <table class="admin-table">
                <thead>
                    <tr class="bg-dark-blue text-white">
                        <th>ID</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th class="th-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) == 0): ?>
                        <tr><td colspan="7" class="center-padding-50 text-gray">No products found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $index => $product): ?>
                            <?php 
                                $bg = ($index % 2 == 0) ? 'bg-light-gray' : ''; 
                                $img_path = (!empty($product['image_name']) && file_exists('../uploads/' . $product['image_name'])) ? '../uploads/' . $product['image_name'] : '../uploads/default.png';
                            ?>
                            <tr class="border-bottom-gray <?= $bg ?>">
                                <td class="font-bold">#<?= $product['id'] ?></td>
                                <td><img src="<?= $img_path ?>" class="product-thumb" alt="Product"></td>
                                <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                                <td>RM <?= number_format($product['price'], 2) ?></td>
                                
                                <td>
                                    <?php if ($product['stock_quantity'] < 5): ?>
                                        <span class="text-danger"><?= $product['stock_quantity'] ?> (Low!)</span>
                                    <?php else: ?>
                                        <?= $product['stock_quantity'] ?>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="td-right">
                                    <a href="product_edit.php?id=<?= $product['id'] ?>" class="btn-edit-blue">Edit</a>
                                    
                                    <form method="POST" action="" class="inline-form">
                                        <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                                        <button type="submit" name="delete_product" class="btn-delete-red" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <hr class="divider-hr">

            <h2 class="mt-0 text-blue-title mb-15">➕ Add New Product</h2>
            
            <form method="POST" action="" enctype="multipart/form-data" class="form-grid">
                
                <div class="admin-form-group">
                    <label class="form-label-block">Product Name:</label>
                    <input type="text" name="product_name" required class="form-input-box">
                </div>

                <div class="admin-form-group">
                    <label class="form-label-block">Category:</label>
                    <select name="category_id" required class="form-input-box">
                        <option value="">Select a Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="form-label-block">Price (RM):</label>
                    <input type="number" step="0.01" name="price" required class="form-input-box">
                </div>

                <div class="admin-form-group">
                    <label class="form-label-block">Stock Quantity:</label>
                    <input type="number" name="stock" required class="form-input-box">
                </div>

                <div class="admin-form-group full-width">
                    <label class="form-label-block">Product Photo:</label>
                    <input type="file" name="product_image" accept="image/*" required class="form-file-box">
                </div>

                <div class="full-width mt-10">
                    <button type="submit" name="add_product" class="btn-add-green">Add Product</button>
                </div>
                
            </form>

        </div>
    </main>

</div>
</body>
</html>