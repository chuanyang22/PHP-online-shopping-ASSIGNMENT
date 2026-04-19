<?php
$host    = 'localhost';
$dbname  = 'amit1014_assignment';
$port   = 3306;
$db_user = 'root';  
$db_pass = '';      

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $db_user,  
        $db_pass   
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// AUTO-LOGIN CHECKER
// If the user's session died (they closed the window), but they have the 30-day cookie:
if (!isset($_SESSION['user_id']) && isset($_COOKIE['auto_login_token'])) {
    $token = $_COOKIE['auto_login_token'];
    
    // We need id, username, and role so the website loads properly!
    $stmt = $pdo->prepare("SELECT id, username, role FROM member WHERE remember_token = ?");
    $stmt->execute([$token]);
    $auto_user = $stmt->fetch();
    
    if ($auto_user) {
        // Token matches! Log them back in automatically!
        $_SESSION['user_id'] = $auto_user['id'];
        $_SESSION['username'] = $auto_user['username'];
        $_SESSION['role'] = $auto_user['role'];
    } else {
        // Token is fake or expired, delete the cookie
        setcookie("auto_login_token", "", time() - 3600, "/");
    }
}
?>