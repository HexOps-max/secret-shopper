<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: admin.php"); exit; }

// Handle Status Toggle or User Deletion if requested
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = $_GET['id'];
    if ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    }
    header("Location: users.php");
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
include 'header.php';
?>

<div class="content-panel">
    <div class="panel-header">
        <h2 style="font-size: 18px;">Shopper Management</h2>
        <span style="font-size: 13px; color: var(--text-muted);">Total Registered: <?php echo count($users); ?></span>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px;">UID</th>
                    <th style="padding: 12px;">Name</th>
                    <th style="padding: 12px;">Contact Info</th>
                    <th style="padding: 12px;">Location</th>
                    <th style="padding: 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px; font-weight: bold; color: var(--primary-blue);"><?php echo htmlspecialchars($u['uid']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($u['name']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($u['email']); ?><br><span style="color:var(--text-muted); font-size:11px;"><?php echo htmlspecialchars($u['phone']); ?></span></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($u['city']); ?>, <?php echo htmlspecialchars($u['state']); ?></td>
                    <td style="padding: 12px;">
                        <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" onclick="return confirm('Are you sure you want to remove this shopper?');" style="color: #ef4444; text-decoration: none; font-weight: bold;"><i class="fas fa-trash-alt"></i> Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div></body></html>