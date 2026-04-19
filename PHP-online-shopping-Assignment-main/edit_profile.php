<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

$current_lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
require_once file_exists($lang_file) ? $lang_file : __DIR__ . "/lang/en.php";

auth('Member');

$error_msg   = "";
$success_msg = "";

$stmt = $pdo->prepare("SELECT username, email FROM member WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_email    = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id FROM member WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$new_username, $new_email, $_SESSION['user_id']]);

    if ($stmt->fetch()) {
        $error_msg = "That username or email is already taken. Please choose another.";
    } else {
        $stmt = $pdo->prepare("UPDATE member SET username = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$new_username, $new_email, $_SESSION['user_id']])) {
            $_SESSION['username'] = $new_username;
            $success_msg          = "Profile updated successfully!";
            $user['username']     = $new_username;
            $user['email']        = $new_email;
        } else {
            $error_msg = "Failed to update profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['edit_profile'] ?></title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title"><?= $lang['edit_profile'] ?></div>

        <?php if (!empty($error_msg)): ?>
            <div class="auth-error"><?= $error_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($success_msg)): ?>
            <div class="auth-success"><?= $success_msg ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_profile.php">
            <label class="form-label"><?= $lang['username'] ?></label>
            <input type="text" name="username" class="auth-input"
                   value="<?= htmlspecialchars($user['username']) ?>" required>

            <label class="form-label"><?= $lang['email'] ?></label>
            <input type="email" name="email" class="auth-input"
                   value="<?= htmlspecialchars($user['email']) ?>" required>

            <button type="submit" class="auth-btn"><?= $lang['save_changes'] ?></button>
        </form>

        <div class="auth-footer mt-15">
            <a href="profile.php" class="link-primary"><?= $lang['cancel'] ?></a>
        </div>
    </div>
</body>
</html>