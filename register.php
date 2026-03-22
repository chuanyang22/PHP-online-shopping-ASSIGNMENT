<?php
require_once 'lib/db.php';
require_once 'lib/helpers.php';

$errors      = [];
$success_msg = '';  

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username         = sanitize($_POST['username']);
    $email            = sanitize($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Username validation
    if (empty($username)) {
        $errors['username'] = "Username is required.";
    } else if (strlen($username) < 2 || strlen($username) > 20) {
        $errors['username'] = "Bro, username must be between 2 to 20 characters lah.";
    }

    // Email validation
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Aiyoh use correct email format lah...";
    }

    // Password validation
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } else if (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters lah, safety mah.";
    }

    // Confirm password validation
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Aiyoh, copy your password also can mismatch....";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM member WHERE email = ?");  
        $stmt->execute([$email]);  
        if ($stmt->rowCount() > 0) {
            $errors['email'] = "This email is already registered.";  
        }
    }

    // Insert new member
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role            = 'Member';  

        $sql  = "INSERT INTO member (username, email, password, role) VALUES (?, ?, ?, ?)";  
        $stmt = $pdo->prepare($sql);  

        try {
            $stmt->execute([$username, $email, $hashed_password, $role]);
            $success_msg = "Congratulations, you have successfully registered!";  
            $username = ''; // clear the form
            $email = '';    // clear the form

            header("Location: register.php?success=1");
            exit;

        } catch (PDOException $e) {
            // THIS WILL SHOW US THE EXACT PROBLEM
            $errors['general'] = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/registerstyle.css">
    <script src="js/register.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Registration</title>
</head>
<body>
    
    <h2>Register an Account</h2>

    <?php if (!empty($success_msg)): ?>
        <p style='color:green;'><?= $success_msg ?> <a href="login.php">Login here</a></p>
    <?php endif; ?>
    
    <?php display_error($errors, 'general'); ?>

    <form method='POST' action="register.php">
        <div>
            <label>Username:</label>
            <input type="text" name="username" value="<?= isset($username) ? sanitize($username) : '' ?>">
            <?php display_error($errors, 'username'); ?> 
        </div>
        <br>
        <div>
            <label>Email:</label>
            <input type="text" name="email" value="<?= isset($email) ? sanitize($email) : '' ?>">
            <?php display_error($errors, 'email'); ?>
        </div>
        <br>
        <div>
            <label>Password:</label>
            <input type="password" name="password">
            <?php display_error($errors, 'password'); ?>
        </div>
        <br>
        <div>
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password">
            <?php display_error($errors, 'confirm_password'); ?>
        </div>
        <br>
        <button type="submit">Register</button>
    </form>
    
    <br>
    <p>Already have an account? <a href="login.php">Login here</a></p>
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <script>
            alert("Congratulations! You have successfully registered. You can now log in.");
            window.location.href = "login.php"; // Redirect to login page after they click 'OK'
        </script>
    <?php endif; ?>

</body>
</html>