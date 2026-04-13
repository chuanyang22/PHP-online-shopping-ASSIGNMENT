<?php
// admin_member.php
session_start();

// FIXED: Removed the "../" so it points to the correct folder!
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// =======================================================
// DIRECT SECURITY CHECK
// =======================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo "<script>
            alert('Access Denied: You must be an Admin to view this page.');
            window.location.href = 'index.php';
          </script>";
    exit();
}
// =======================================================

// Handle Banning / Unbanning users
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $member_id = $_GET['id'];
    
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
    // FIXED: Changed to admin_member.php (No 's')
    header("Location: admin_member.php");
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
    <link rel="stylesheet" href="css/mainstyle.css?v=<?php echo time(); ?>">
</head>
<body style="background-color: #f5f5f5; font-family: Arial, sans-serif;">
    <div class="admin-container">
        <h2 style="margin-top: 0; color: #333;">👥 Admin Dashboard: Member Management</h2>
        <a href="index.php" style="color: #0056b3; text-decoration: none; font-weight: bold;">&larr; Back to Home</a>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <form method="GET" action="admin_member.php" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by username or email..." style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" class="action-btn" style="width: auto; padding: 10px 20px; margin-top: 0;">Search</button>
            <a href="admin_member.php" style="padding: 10px; color: #888; text-decoration: none;">Clear</a>
        </form>

        <table class="admin-table">
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
                            <a href="admin_member.php?toggle_status=1&id=<?= $m['id'] ?>" class="btn-toggle btn-ban" onclick="return confirm('Block this user?');">Block User</a>
                        <?php else: ?>
                            <a href="admin_member.php?toggle_status=1&id=<?= $m['id'] ?>" class="btn-toggle btn-unban">Unblock User</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <em style="color: #888;">(You)</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($members)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 20px; color: #666;">No members found matching your search.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>