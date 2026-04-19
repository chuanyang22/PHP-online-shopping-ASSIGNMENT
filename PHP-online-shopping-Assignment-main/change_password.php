<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

$current_lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
require_once file_exists($lang_file) ? $lang_file : __DIR__ . "/lang/en.php";

auth('Member');

$user_id    = $_SESSION['user_id'];
$errors     = [];
$success_msg = '';

$stmt = $pdo->prepare("SELECT * FROM member WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (isset($_POST['change_password'])) {
    $current_pw = $_POST['current_password'];
    $new_pw     = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];

    if (password_verify($current_pw, $user['password'])) {
        if (strlen($new_pw) < 8) {
            $errors['new_password']     = "New password must be at least 8 characters.";
        } elseif ($new_pw !== $confirm_pw) {
            $errors['confirm_password'] = "New passwords do not match.";
        } else {
            $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE member SET password = ? WHERE id = ?")->execute([$hashed_pw, $user_id]);
            $success_msg = "Password changed successfully!";
        }
    } else {
        $errors['current_password'] = "Incorrect current password.";
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['change_password'] ?></title>
    <link rel="stylesheet" href="css/mainstyle.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title"><?= $lang['change_password'] ?></div>

        <div class="auth-footer mb-15">
            <a href="profile.php" class="link-primary"><?= $lang['back_to_profile'] ?></a>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="auth-success"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
            <div class="auth-error"><?= $err ?></div>
        <?php endforeach; ?>

        <form method="POST" action="change_password.php">
            <input type="password" name="current_password" class="auth-input"
                   placeholder="<?= htmlspecialchars($lang['current_password']) ?>" required>
            <input type="password" name="new_password" class="auth-input"
                   placeholder="<?= htmlspecialchars($lang['new_password']) ?>" required>
            <input type="password" name="confirm_password" class="auth-input"
                   placeholder="<?= htmlspecialchars($lang['confirm_password']) ?>" required>
            <button type="submit" name="change_password" class="auth-btn">
                <?= $lang['change_pw_btn'] ?>
            </button>
        </form>
    </div>
</body>
</html>