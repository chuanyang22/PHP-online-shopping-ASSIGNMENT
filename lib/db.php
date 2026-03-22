<?php
$host    = 'localhost';
$dbname  = 'amit1014_assignment';
$port   = 3306;
$db_user = 'root';  // 
$db_pass = '';      // ✅ renamed

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $db_user,  // ✅
        $db_pass   // ✅
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>