<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/menustyle.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Store</title>
</head>
<body>

    <div style="text-align: center; margin-top: 50px;">
        <h1>Welcome to the Online Store</h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!</p>
            <p>Your Role: <?= htmlspecialchars($_SESSION['role']) ?></p>
            <br>
            <p> <a href="profile.php">Go to My Profile</a></p>
            <a href="logout.php"><button>Logout</button></a>
            
        <?php else: ?>
            <p>Please log in to access your account or browse our exclusive products.</p>
            <br>
            <a href="login.php"><button>Login</button></a>
            <a href="register.php"><button>Register</button></a>
        <?php endif; ?>
    </div>

</body>
</html>