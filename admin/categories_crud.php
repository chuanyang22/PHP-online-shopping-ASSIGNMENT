<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../lib/db.php';

if (isset($_POST['delete_category'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$delete_id]);
        echo "<script>alert('Category successfully deleted!'); window.location.href='categories_crud.php';</script>";
    } catch (PDOException $e) {
        echo "Error deleting category: " . $e->getMessage();
    }
}

if (isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        echo "<script>alert('Category successfully added!'); window.location.href='categories_crud.php';</script>";
    } catch (PDOException $e) {
        echo "Error saving category: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
    
</head>
<body class="admin-body">
<div class="admin-layout">

    <?php require_once 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="dashboard-chart-card">

            <h2 class="dashboard-title mt-0 mb-20">📂 Category Maintenance</h2>

            <!-- Add category form -->
            <form method="POST" action="" class="flex-center-gap-12 admin-form-box-mb20">
                <label class="font-bold">New Category Name:</label>
                <input type="text" name="category_name" required class="admin-input-w-300">
                <button type="submit" name="add_category" class="btn-success-solid">➕ Add Category</button>
            </form>

            <table class="member-table">
                <thead>
                    <tr class="bg-dark-blue">
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
                            echo "<tr><td colspan='3' class='center-padding-20 text-gray'>No categories found.</td></tr>";
                        } else {
                            foreach ($categories as $index => $category) {
                                $bg = ($index % 2 == 0) ? 'class="bg-light-gray"' : '';
                                echo "<tr $bg class='border-bottom-gray'>";
                                echo "<td class='font-bold-blue'>#" . $category['id'] . "</td>";
                                echo "<td class='font-bold'>" . htmlspecialchars($category['name']) . "</td>";
                                echo "<td>
                                        <form method='POST' action='' class='inline-form'>
                                            <input type='hidden' name='delete_id' value='" . $category['id'] . "'>
                                            <button type='submit' name='delete_category' class='btn-toggle-block btn-sm'
                                                onclick=\"return confirm('Delete this category?');\">Delete</button>
                                        </form>
                                      </td>";
                                echo "</tr>";
                            }
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='3'>Error: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

        </div>
    </main>

</div><!-- /.admin-layout -->
</body>
</html>