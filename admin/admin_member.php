<?php
// admin_members.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// ONLY ADMINS ALLOWED! If a normal member tries to enter, it kicks them out.
auth('Admin'); 

// Handle Banning / Unbanning users
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $member_id = $_GET['id'];
    
    // Prevent the admin from banning themselves
    if ($member_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT status FROM member WHERE id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        
        if ($member) {
            $new_status = ($member['status'] === 'Active') ? 'Blocked' : 'Active';
            $update_stmt = $pdo->prepare("UPDATE member SET status = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $member_id]);
        }
    }
    header("Location: admin_members.php");
    exit;
}

// Search Logic
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT id, username, email, role, status FROM member WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT id, username, email, role, status FROM member ORDER BY id DESC");
}
$members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Member Management</title>
    <link rel="stylesheet" href="css/mainstyle.css">
    <style>
        .admin-container { max-width: 900px; margin: 40px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }
        .status-active { color: green; font-weight: bold; }
        .status-blocked { color: red; font-weight: bold; }
        .btn-toggle { padding: 6px 12px; border-radius: 4px; text-decoration: none; color: white; font-size: 12px; }
        .btn-ban { background-color: #dc3545; }
        .btn-unban { background-color: #28a745; }
    </style>
</head>
<body style="background-color: #f5f5f5; font-family: Arial, sans-serif;">
    <div class="admin-container">
        <h2>👥 Admin Dashboard: Member Management</h2>
        <a href="index.php" style="color: #0056b3;">&larr; Back to Home</a>
        <hr>

        <form method="GET" action="admin_members.php" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by username or email..." style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" class="auth-btn" style="width: auto; padding: 10px 20px;">Search</button>
            <a href="admin_members.php" style="padding: 10px; color: #888; text-decoration: none;">Clear</a>
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($members as $m): ?>
            <tr>
                <td><?= $m['id'] ?></td>
                <td><?= htmlspecialchars($m['username']) ?></td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td><?= htmlspecialchars($m['role']) ?></td>
                <td class="<?= $m['status'] === 'Active' ? 'status-active' : 'status-blocked' ?>">
                    <?= htmlspecialchars($m['status']) ?>
                </td>
                <td>
                    <?php if ($m['id'] != $_SESSION['user_id']): ?>
                        <?php if ($m['status'] === 'Active'): ?>
                            <a href="admin_members.php?toggle_status=1&id=<?= $m['id'] ?>" class="btn-toggle btn-ban" onclick="return confirm('Block this user?');">Block User</a>
                        <?php else: ?>
                            <a href="admin_members.php?toggle_status=1&id=<?= $m['id'] ?>" class="btn-toggle btn-unban">Unblock User</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <em>(You)</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($members)): ?>
                <tr><td colspan="6" style="text-align: center;">No members found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>