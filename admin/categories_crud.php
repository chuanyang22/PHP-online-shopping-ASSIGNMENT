<?php
// Turn on the lights for errors!
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../lib/db.php';

// --- DELETE CATEGORY LOGIC ---
if (isset($_POST['delete_category'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$delete_id]);
        echo "<script>alert('Category successfully deleted!'); window.location.href='categories_crud.php';</script>";
    } catch(PDOException $e) {
        echo "Error deleting category: " . $e->getMessage();
    }
}

// --- ADD CATEGORY LOGIC ---
if (isset($_POST['add_category'])) {
    $name = $_POST['category_name'];

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        echo "<script>alert('Category successfully added!'); window.location.href='categories_crud.php';</script>";
    } catch(PDOException $e) {
        echo "Error saving category: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <style>
        /* Exact same blue theme as your Products Dashboard */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            padding: 40px 20px; 
            margin: 0;
            background: linear-gradient(135deg, #0b1c3d 0%, #4a90e2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .admin-container { 
            max-width: 800px; /* Slightly narrower for categories */
            margin: auto; 
            background: #ffffff; 
            padding: 30px 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        h1, h2 { color: #0b1c3d; }

        .form-group { margin-bottom: 15px; }
        .form-group input {
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

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #0b1c3d; color: white; padding: 12px; text-align: left; }
        td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: middle; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        
        /* A nice back button link to get back to products */
        .nav-link { 
            display: inline-block; margin-bottom: 20px; color: #4a90e2; 
            text-decoration: none; font-weight: bold; 
        }
        .nav-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="admin-container">
    <a href="products_crud.php" class="nav-link">← Back to Products Dashboard</a>

    <h1>Category Maintenance</h1>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h2 style="margin-top: 0;">Add New Category</h2>
        <form action="" method="POST">
            <div class="form-group">
                <label><strong>Category Name:</strong> (e.g., Electronics, Clothing, Snacks)</label>
                <input type="text" name="category_name" required>
            </div>
            <button type="submit" name="add_category">Save Category</button>
        </form>
    </div>

    <h2 style="margin-top: 30px;">Current Categories</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($categories) == 0) {
                    echo "<tr><td colspan='3' style='text-align:center; padding: 20px;'>No categories found.</td></tr>";
                } else {
                    foreach ($categories as $category) {
                        echo "<tr>";
                        echo "<td><strong>#" . $category['id'] . "</strong></td>";
                        echo "<td>" . htmlspecialchars($category['name']) . "</td>";
                        echo "<td>
                                <form method='POST' action='' style='display:inline-block;'>
                                    <input type='hidden' name='delete_id' value='" . $category['id'] . "'>
                                    <button type='submit' name='delete_category' class='delete-btn' onclick=\"return confirm('Are you sure you want to delete this category?');\">Delete</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                }
            } catch(PDOException $e) {
                echo "<tr><td colspan='3'>Error loading categories: " . $e->getMessage() . "</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>