<?php
// --- SECURITY LOCK ---
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
// ---------------------

// Turn on the lights for errors!
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Connect to the database
require_once '../lib/db.php';

// --- DELETE PRODUCT LOGIC ---
if (isset($_POST['delete_product'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        echo "<script>alert('Product successfully deleted!'); window.location.href='products_crud.php';</script>";
    } catch(PDOException $e) {
        echo "Error deleting product: " . $e->getMessage();
    }
}

// --- ADD PRODUCT LOGIC ---
if (isset($_POST['add_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock']; 
    $category_id = $_POST['category_id']; // Grab the selected category

    $image_name = $_FILES['product_image']['name'];
    $tmp_name = $_FILES['product_image']['tmp_name'];
    $target_folder = "../uploads/";

    move_uploaded_file($tmp_name, $target_folder . $image_name);

    try {
        // Now inserting the category_id as well
        $stmt = $pdo->prepare("INSERT INTO products (name, price, image_name, stock_quantity, category_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $image_name, $stock, $category_id]);
        echo "<script>alert('Product successfully added to database!'); window.location.href='products_crud.php';</script>";
    } catch(PDOException $e) {
        echo "Error saving product: " . $e->getMessage();
    }
}

// --- SEARCH LOGIC PREPARATION ---
$search_keyword = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_keyword = trim($_GET['search']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <style>
        /* --- NEW BLUE THEME STYLES --- */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            padding: 40px 20px; 
            margin: 0;
            background: linear-gradient(135deg, #0b1c3d 0%, #4a90e2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .admin-container { 
            max-width: 1100px; 
            margin: auto; 
            background: #ffffff; 
            padding: 30px 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        h1, h2 { color: #0b1c3d; } 

        .form-group { margin-bottom: 15px; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;
        }

        button { 
            padding: 10px 20px; background: #4a90e2; color: white; 
            border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.3s;
        }
        button:hover { background: #0b1c3d; } 
        
        button.delete-btn { background: #dc3545; } 
        button.delete-btn:hover { background: #a71d2a; }

        .btn-green { background: #28a745; }
        .btn-green:hover { background: #1e7e34; }
        .btn-grey { background: #6c757d; }
        .btn-grey:hover { background: #5a6268; }
        
        /* --- STYLES FOR THE LOGOUT BUTTON --- */
        .btn-red { background: #dc3545; }
        .btn-red:hover { background: #a71d2a; }

        .search-container { 
            margin-top: 40px; margin-bottom: 20px; padding: 20px; 
            background: #f0f8ff; border-left: 5px solid #4a90e2; border-radius: 5px; 
        }
        .search-container input[type="text"] { 
            padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #0b1c3d; color: white; padding: 12px; text-align: left; }
        td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: middle; }
        tr:nth-child(even) { background-color: #f9f9f9; } 
        
        .low-stock { color: #dc3545; font-weight: bold; } 

        /* --- NEW: CUTE & AESTHETIC ALERT BANNER STYLES --- */
        .alert-banner {
            background-color: #fff4f4;
            border-left: 6px solid #ff4d4d;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            display: flex;
            align-items: flex-start;
            box-shadow: 0 4px 10px rgba(255, 77, 77, 0.15);
        }
        .alert-icon { font-size: 1.8em; margin-right: 15px; line-height: 1; }
        .alert-content p { margin: 0 0 5px 0; color: #cc0000; font-weight: bold; font-size: 1.1em; }
        .alert-content ul { margin: 0; padding-left: 20px; color: #a71d2a; }
        .alert-content li { margin-bottom: 3px; }
    </style>
</head>
<body>

<div class="admin-container">
    <h1>Product Maintenance Dashboard</h1>

    <div style="margin-bottom: 25px; padding: 15px; background: #e8f5e9; border-radius: 8px; border-left: 5px solid #28a745; display: flex; align-items: center;">
        <strong style="margin-right: 15px; font-size: 1.1em; color: #1e7e34;">Admin Navigation:</strong> 
        
        <a href="categories_crud.php" style="text-decoration: none; margin-right: 10px;">
            <button type="button" class="btn-green">📁 Manage Categories</button>
        </a>
        
        <a href="../index.php" style="text-decoration: none;">
            <button type="button" class="btn-grey">🛍️ View Storefront</button>
        </a>
        
        <a href="logout.php" style="text-decoration: none; margin-left: auto;">
            <button type="button" class="btn-red">🚪 Log Out</button>
        </a>
    </div>

    <?php
    try {
        $alert_stmt = $pdo->query("SELECT name, stock_quantity FROM products WHERE stock_quantity < 5 ORDER BY stock_quantity ASC");
        $low_stock_items = $alert_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($low_stock_items) > 0) {
            echo "<div class='alert-banner'>";
            echo "<div class='alert-icon'>🔔</div>";
            echo "<div class='alert-content'>";
            echo "<p>Restock Needed! The following items are running low:</p>";
            echo "<ul>";
            foreach ($low_stock_items as $item) {
                echo "<li><strong>" . htmlspecialchars($item['name']) . "</strong> &mdash; Only <strong>" . $item['stock_quantity'] . "</strong> left in stock!</li>";
            }
            echo "</ul>";
            echo "</div>";
            echo "</div>";
        }
    } catch(PDOException $e) {
        // Silently ignore alert errors so it doesn't break the page
    }
    ?>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h2 style="margin-top: 0;">Add New Product</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label><strong>Product Name:</strong></label>
                <input type="text" name="product_name" required>
            </div>
            
            <div class="form-group">
                <label><strong>Category:</strong></label>
                <select name="category_id" required>
                    <option value="">-- Select a Category --</option>
                    <?php
                    $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
                    while ($cat = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='" . $cat['id'] . "'>" . htmlspecialchars($cat['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label><strong>Price (RM):</strong></label>
                <input type="number" step="0.01" name="price" required>
            </div>
            <div class="form-group">
                <label><strong>Stock Quantity:</strong></label>
                <input type="number" name="stock" required>
            </div>
            <div class="form-group">
                <label><strong>Product Photo:</strong></label><br>
                <input type="file" name="product_image" accept="image/*" required style="margin-top: 10px;">
            </div>
            <button type="submit" name="add_product">Save Product</button>
        </form>
    </div>

    <div class="search-container">
        <form action="" method="GET">
            <label style="font-size: 1.1em; margin-right: 10px;"><strong>Search Inventory:</strong></label>
            <input type="text" name="search" placeholder="Search by Name or ID..." value="<?php echo htmlspecialchars($search_keyword); ?>">
            <button type="submit">Search</button>
            <a href="products_crud.php" style="margin-left: 15px; text-decoration: none; color: #4a90e2; font-weight: bold;">Clear Search</a>
        </form>
    </div>

    <h2 style="margin-top: 30px;">Current Products</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Photo</th>
                <th>Name</th>
                <th>Category</th> <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            try {
                if ($search_keyword !== "") {
                    $stmt = $pdo->prepare("
                        SELECT products.*, categories.name AS category_name 
                        FROM products 
                        LEFT JOIN categories ON products.category_id = categories.id 
                        WHERE products.id = ? OR products.name LIKE ? 
                        ORDER BY products.id DESC
                    ");
                    $stmt->execute([$search_keyword, "%" . $search_keyword . "%"]); 
                } else {
                    $stmt = $pdo->query("
                        SELECT products.*, categories.name AS category_name 
                        FROM products 
                        LEFT JOIN categories ON products.category_id = categories.id 
                        ORDER BY products.id DESC
                    ");
                }
                
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($products) == 0) {
                    echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>No products found.</td></tr>";
                } else {
                    foreach ($products as $product) {
                        echo "<tr>";
                        echo "<td><strong>#" . $product['id'] . "</strong></td>";
                        echo "<td><img src='../uploads/" . htmlspecialchars($product['image_name']) . "' width='60' style='border-radius: 5px; border: 1px solid #ccc;'></td>";
                        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                        
                        $cat_display = $product['category_name'] ? htmlspecialchars($product['category_name']) : "<em style='color:#999;'>Uncategorized</em>";
                        echo "<td>" . $cat_display . "</td>";
                        
                        echo "<td><strong>RM " . number_format($product['price'], 2) . "</strong></td>";
                        
                        $stock_level = $product['stock_quantity'];
                        if ($stock_level < 5) {
                            echo "<td class='low-stock'>" . $stock_level . " (Low!)</td>";
                        } else {
                            echo "<td>" . $stock_level . "</td>";
                        }
                        
                        echo "<td>
                                <a href='product_edit.php?id=" . $product['id'] . "'><button type='button' style='margin-right: 5px;'>Edit</button></a> 
                                <form method='POST' action='' style='display:inline-block;'>
                                    <input type='hidden' name='delete_id' value='" . $product['id'] . "'>
                                    <button type='submit' name='delete_product' class='delete-btn' onclick=\"return confirm('Are you sure you want to delete this product?');\">Delete</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                }
            } catch(PDOException $e) {
                echo "<tr><td colspan='7'>Error loading products: " . $e->getMessage() . "</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>