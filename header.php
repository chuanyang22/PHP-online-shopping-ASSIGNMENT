<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------
// ROOT DETECTION — works whether included from root OR
// from a subfolder like member/order_history.php
// -------------------------------------------------------
$root='/';

// Language Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = $_SESSION['lang'] ?? 'en';

// Load language file relative to project root
$project_root = __DIR__;
$lang_file = $project_root . "/lang/{$current_lang}.php";
if (!file_exists($lang_file)) {
    $lang_file = $project_root . "/lang/en.php";
}
require_once $lang_file;
require_once $project_root . '/lib/db.php';
require_once $project_root . '/lib/helpers.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!empty($_SESSION['user_id']) && empty($_SESSION['cart'])) {
    require_once $project_root . '/lib/cart_persist.php';
    $_SESSION['cart'] = cart_load_member_cart($pdo, (int) $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Accessory Store</title>
    <link rel="stylesheet" href="<?= $root ?>css/mainstyle.css">
</head>
<body class="home-body">

    <div class="navbar">
        <div class="navbar-brand">
            <a href="<?= $root ?>index.php">🛍️ Online Store</a>
        </div>

        <div class="navbar-profile">
            <a href="<?= $root ?>index.php">🏠 <?= $lang['home'] ?? 'Home' ?></a>
            <span class="navbar-divider"></span>

            <span class="lang-links">
                <a href="?lang=en" class="<?= $current_lang == 'en' ? 'active-lang' : '' ?>">EN</a> |
                <a href="?lang=my" class="<?= $current_lang == 'my' ? 'active-lang' : '' ?>">MY</a> |
                <a href="?lang=cn" class="<?= $current_lang == 'cn' ? 'active-lang' : '' ?>">CN</a>
            </span>
            <span class="navbar-divider"></span>

            <button id="theme-toggle" class="btn-theme-nav" title="Toggle Theme">🌓</button>
            <span class="navbar-divider"></span>

            <?php if (isset($_SESSION['username'])): ?>
                <a href="<?= $root ?>cart.php">🛒 <?= $lang['cart'] ?? 'My Cart' ?></a>
                <span class="navbar-divider"></span>

                <a href="<?= $root ?>member/order_history.php">📜 <?= $lang['my_orders'] ?? 'My Orders' ?></a>
                <span class="navbar-divider"></span>

                <a href="<?= $root ?>wishlist.php">❤️ <?= $lang['my_wishlist'] ?? 'My Wishlist' ?></a>
                <span class="navbar-divider"></span>

                <a href="<?= $root ?>profile.php">🧏‍♂️ <?= $lang['my_profile'] ?? 'My Profile' ?></a>
                <span class="navbar-divider"></span>

                <a href="<?= $root ?>logout.php"><?= $lang['logout'] ?? 'Logout' ?></a>
            <?php else: ?>
                <a href="<?= $root ?>login.php"><?= $lang['login'] ?? 'Login' ?></a>
                <span class="navbar-divider"></span>
                <a href="<?= $root ?>register.php"><?= $lang['register'] ?? 'Register' ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['popup'])): ?>
        <div class="toast-message" id="toast-msg"><?= htmlspecialchars($_SESSION['popup']) ?></div>
        <?php unset($_SESSION['popup']); ?>
        <script>
            setTimeout(function () {
                var t = document.getElementById('toast-msg');
                if (t) { t.style.transition = 'opacity 0.5s'; t.style.opacity = '0'; }
            }, 2500);
        </script>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var themeToggle = document.getElementById('theme-toggle');
            var body = document.body;
            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark-mode');
            }
            themeToggle.addEventListener('click', function () {
                body.classList.toggle('dark-mode');
                localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
            });
        });
    </script>