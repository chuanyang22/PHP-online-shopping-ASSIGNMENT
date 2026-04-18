<?php
// admin/admin_sidebar.php
// Include this at the top of every admin page INSIDE .admin-layout

$current_page = basename($_SERVER['PHP_SELF']);
function admin_nav_active(string $page): string {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <h2>🛍️ Admin Panel</h2>
        <p>Management System</p>
    </div>

    <nav class="admin-nav">
        <a href="dashboard.php"       class="<?= admin_nav_active('dashboard.php') ?>">
            📊 Dashboard
        </a>
        <a href="products_crud.php"   class="<?= admin_nav_active('products_crud.php') ?>">
            📦 Products
        </a>
        <a href="admin_member.php"    class="<?= admin_nav_active('admin_member.php') ?>">
            👥 Members
        </a>
        <a href="categories_crud.php" class="<?= admin_nav_active('categories_crud.php') ?>">
            📂 Categories
        </a>
        <a href="order_list.php"      class="<?= admin_nav_active('order_list.php') ?>">
            🧾 Orders
        </a>

        <hr class="nav-divider">

        <div class="nav-logout">
            <a href="logout.php">🚪 Logout</a>
        </div>
    </nav>
</aside>