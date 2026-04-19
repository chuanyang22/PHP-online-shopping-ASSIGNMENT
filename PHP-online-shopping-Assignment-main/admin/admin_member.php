<?php
// admin_member.php
session_start();

// FIXED: Using ../ to correctly point to the lib folder from inside the admin folder
require_once '../lib/db.php';
require_once '../lib/helpers.php';

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
            window.location.href = '../index.php';
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
    // FIXED: Removed the 's' so it redirects to the correct page
    header("Location: admin_member.php");
    exit;
}

// Search Logic
$search = $_GET['search'] ?? '';
if ($search) {
    // FIXED: Ensured the Admin is hidden even during a search
    $stmt = $pdo->prepare("SELECT id, username, email, role, status FROM member WHERE (username LIKE ? OR email LIKE ?) AND id != ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", $_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("SELECT id, username, email, role, status FROM member WHERE id != ? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]);
}
$members = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Maintenance</title>
    <link rel="stylesheet" href="../css/mainstyle.css">
</head>
<body class="admin-body">
<div class="admin-layout">

    <?php require_once 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="dashboard-chart-card">

            <h1 class="dashboard-title mt-0 mb-10">👥 Member Maintenance</h1>
            <hr class="border-bottom-gray my-25">

            <!-- Search -->
            <div class="admin-search-box">
                <form method="GET" action="admin_member.php" class="flex-center-gap-12">
                    <label class="search-label">Search Members:</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search by username or email..."
                           class="search-input-lg">
                    <button type="submit" class="btn-search-primary">Search</button>
                    <a href="admin_member.php" class="text-blue-link">Clear</a>
                </form>
            </div>

            <h3 class="text-blue-title mb-20">Current System Members</h3>

            <table class="member-table">
                <tr class="bg-dark-blue">
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="center-padding-20">Status</th>
                    <th class="align-right">Actions</th>
                </tr>
                <?php foreach ($members as $index => $m): ?>
                <tr class="border-bottom-gray <?= ($index % 2 == 0) ? 'bg-light-gray' : '' ?>">
                    <td class="font-bold-blue">#<?= $m['id'] ?></td>
                    <td class="font-bold"><?= htmlspecialchars($m['username']) ?></td>
                    <td class="text-gray"><?= htmlspecialchars($m['email']) ?></td>
                    <td><span class="status-badge badge-role"><?= htmlspecialchars($m['role']) ?></span></td>
                    <td class="center-padding-20">
                        <?php if ($m['status'] === 'Active'): ?>
                            <span class="status-badge badge-active">Active</span>
                        <?php else: ?>
                            <span class="status-badge badge-blocked">Blocked</span>
                        <?php endif; ?>
                    </td>
                    <td class="align-right">
                        <?php if ($m['id'] != $_SESSION['user_id']): ?>
                            <?php if ($m['status'] === 'Active'): ?>
                                <a href="admin_member.php?toggle_status=1&id=<?= $m['id'] ?>"
                                   class="btn-toggle-block"
                                   onclick="return confirm('WARNING: Block this user?');">Block</a>
                            <?php else: ?>
                                <a href="admin_member.php?toggle_status=1&id=<?= $m['id'] ?>"
                                   class="btn-toggle-unblock">Unblock</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <em class="text-you">(You)</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="6" class="center-padding-50 text-gray font-italic">
                            No members found matching your search.
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

        </div>
    </main>

</div><!-- /.admin-layout -->
</body>
</html>