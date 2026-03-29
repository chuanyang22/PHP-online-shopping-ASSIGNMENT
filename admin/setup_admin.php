<?php
require_once '../lib/db.php';

$email = 'admin@fithub.com';
$password = password_hash('admin123', PASSWORD_DEFAULT); // Securely scrambles the password!

try {
    $stmt = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
    $stmt->execute([$email, $password]);
    echo "<h1>Admin account created successfully!</h1>";
    echo "<p><strong>Email:</strong> admin@fithub.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><em>You can now delete this setup_admin.php file for security!</em></p>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>