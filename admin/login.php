<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../lib/db.php';

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $admin['id']; 
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['role'] = 'Admin';         
        header("Location: products_crud.php"); 
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
</head>
<body class="admin-login-body">

<div class="login-box">
    <h2 class="mt-0 text-blue-title">🔒 Secure Admin Panel</h2>
    
    <?php if ($error): ?>
        <div class="admin-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="admin-form-group">
            <label class="font-bold">Email Address:</label>
            <input type="email" name="email" required placeholder="admin@fithub.com" class="admin-input-full">
        </div>
        <div class="admin-form-group">
            <label class="font-bold">Password:</label>
            <input type="password" name="password" required class="admin-input-full">
        </div>
        <button type="submit" name="login" class="btn-admin-login">Login</button>
    </form>
    
    <a href="../login.php" class="admin-back-link">⬅ Return to Main Store Login</a>
</div>

</body>
</html>