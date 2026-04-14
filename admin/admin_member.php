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
    <title>Member Maintenance Dashboard</title>
    <link rel="stylesheet" href="../css/mainstyle.css?v=<?php echo time(); ?>">
    <style>
        /* Small custom adjustments for this page */
        .member-table td { 
            vertical-align: middle; 
            padding: 15px 10px; 
        }

        .member-table th { 
            padding: 15px 10px;
         }
        .status-badge { 
            padding: 5px 10px; 
            border-radius: 20px; 
            font-weight: bold; 
            font-size: 0.85em; 
            text-transform: uppercase; 
        }

        .active-badge { 
            background-color: #d1fae5; 
            color: #065f46; 
        }

        .blocked-badge { 
            background-color: #fee2e2; 
            color: #991b1b; 
        }

        .navbar-search { 
            background-color: #f0fdf4; 
            padding: 20px; 
            border-left: 5px solid #22c55e;
            border-radius: 8px;
            margin-bottom: 30px; 
        }
        
    </style>
</head>
<body style="margin: 0; background-color: #1e3a8a; font-family: 'Segoe UI', sans-serif;">
    
    <div class="main-dashboard-container" style="max-width: 1200px; margin: 40px auto; padding: 20px;">
        
        <div class="dashboard-card" style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            
            <h1 style="color: #1e3a8a; margin-top: 0; margin-bottom: 10px;">👥 Member Maintenance Dashboard</h1>
            <a href="products_crud.php" style="color: #3b82f6; text-decoration: none; font-size: 0.9em; font-weight: bold;">&larr; Back to Products Dashboard</a>
            
            <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 25px 0;">

            <div class="navbar-search">
                <form method="GET" action="admin_member.php" style="display: flex; gap: 15px; align-items: center;">
                    <label style="font-weight: bold; color: #166534;">Search Members:</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by username or email..." 
                           style="padding: 12px; width: 400px; border: 1px solid #c3e6cb; border-radius: 6px; font-size: 1em;">
                    <button type="submit" class="btn" style="background-color: #3b82f6; padding: 12px 24px; margin-top: 0;">Search</button>
                    <a href="admin_member.php" style="color: #3b82f6; text-decoration: none; font-weight: bold;">Clear Search</a>
                </form>
            </div>

            <h3 style="color: #1e3a8a; margin-bottom: 20px;">Current System Members</h3>

            <table class="member-table" style="width: 100%; border-collapse: collapse; background-color: white;">
                <tr style="background-color: #0b1c3d; color: white;">
                    <th style="text-align: left;">ID</th>
                    <th style="text-align: left;">Username</th>
                    <th style="text-align: left;">Email</th>
                    <th style="text-align: left;">Role</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
                <?php foreach ($members as $index => $m): ?>
                <tr style="border-bottom: 1px solid #e5e7eb; <?= ($index % 2 == 0) ? 'background-color: #f9fafb;' : ''; ?>">
                    <td style="font-weight: bold; color: #1e3a8a;">#<?= $m['id'] ?></td>
                    <td style="font-weight: bold;"><?= htmlspecialchars($m['username']) ?></td>
                    <td style="color: #6b7280;"><?= htmlspecialchars($m['email']) ?></td>
                    <td>
                        <span class="status-badge" style="background-color: #e0f2fe; color: #075985;">
                            <?= htmlspecialchars($m['role']) ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($m['status'] === 'Active'): ?>
                            <span class="status-badge active-badge">Active</span>
                        <?php else: ?>
                            <span class="status-badge blocked-badge">Blocked</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($m['id'] != $_SESSION['user_id']): ?>
                            <?php if ($m['status'] === 'Active'): ?>
                                <a href="admin_member.php?toggle_status=1&id=<?= $m['id'] ?>" 
                                   class="btn-toggle" 
                                   style="background-color: #ef4444; color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 0.9em;"
                                   onclick="return confirm('WARNING: Are you sure you want to BLOCK this user?');">Block User</a>
                            <?php else: ?>
                                <a href="admin_member.php?toggle_status=1&id=<?= $m['id'] ?>" 
                                   class="btn-toggle" 
                                   style="background-color: #10b981; color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 0.9em;"
                                   >Unblock User</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <em style="color: #9ca3af; font-size: 0.9em; padding-right: 15px;">(You)</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($members)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #6b7280; font-style: italic;">No members found matching your search criteria.</td></tr>
                <?php endif; ?>
            </table>

        </div>
    </div>
</body>
</html>