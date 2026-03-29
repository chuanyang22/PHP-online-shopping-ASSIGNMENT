<?php
// Turn on errors so we can see if anything is hiding!
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../lib/db.php';

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Find the admin in the database
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the password
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $admin['email'];
        header("Location: products_crud.php"); // Send them to the dashboard!
        exit;
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0;
            background: linear-gradient(135deg, #0b1c3d 0%, #4a90e2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            text-align: center;
            z-index: 10;
        }
        h2 { color: #0b1c3d; margin-top: 0; }
        .form-group { margin-bottom: 20px; text-align: left; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 12px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;
        }
        button {
            width: 100%; padding: 12px; background: #4a90e2; color: white;
            border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1.1em;
            transition: 0.3s;
        }
        button:hover { background: #0b1c3d; }
        .error { color: #dc3545; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 20px; }

        /* --- STYLES FOR THE BACK LINK --- */
        .back-link {
            display: block;
            margin-top: 25px;
            color: #666;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: bold;
            transition: 0.3s;
        }
        .back-link:hover {
            color: #4a90e2;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>🔒 Secure Admin Panel</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label><strong>Email Address:</strong></label>
            <input type="email" name="email" required placeholder="admin@fithub.com">
        </div>
        <div class="form-group">
            <label><strong>Password:</strong></label>
            <input type="password" name="password" required placeholder="Enter password">
        </div>
        <button type="submit" name="login">Login to Dashboard</button>
    </form>

    <a href="../login.php" class="back-link">⬅️ Back to Store Login</a>
</div>

</body>
</html>