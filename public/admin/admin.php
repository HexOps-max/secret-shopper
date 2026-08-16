<?php
require_once '../db.php';
session_start();

// Simple admin login handler for setup convenience
if (isset($_POST['admin_login'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'secretshopper2026') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Invalid administrator credentials.";
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Admin Portal Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #002e5b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: Arial, sans-serif; }
        .login-box { background: white; padding: 35px; border-radius: 12px; width: 100%; max-width: 380px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        input { width: 100%; padding: 10px; margin: 10px 0 20px 0; border: 1px solid #ccc; border-radius: 6px; }
        button { width: 100%; padding: 10px; background: #002e5b; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2 style="color: #002e5b; margin-bottom: 5px;">Admin Login</h2>
        <p style="font-size: 12px; color: #64748b; margin-bottom: 20px;">Secret Shopper® Control Backbone</p>
        <?php if(isset($login_error)) echo "<p style='color:red; font-size:12px;'>$login_error</p>"; ?>
        <form method="POST">
            <label style="font-size:12px; font-weight:bold;">Username</label>
            <input type="text" name="username" required>
            <label style="font-size:12px; font-weight:bold;">Password</label>
            <input type="password" name="password" required>
            <button type="submit" name="admin_login">Authenticate</button>
        </form>
    </div>
</body>
</html>
<?php exit; endif;

include 'header.php';

// Fetch Metrics Counters
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$submittedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'Submitted'")->fetchColumn();
$totalChecks = $pdo->query("SELECT COUNT(*) FROM checks")->fetchColumn();

// Fetch Recent Submitted Reports for Quick Audit
$recentSubmissions = $pdo->query("SELECT t.*, u.name as shopper_name, u.email as shopper_email FROM tasks t JOIN users u ON t.user_uid = u.uid WHERE t.status = 'Submitted' ORDER BY t.id DESC LIMIT 5")->fetchAll();
?>

<div class="content-panel">
    <h1>Control Center Overview</h1>
    <p style="color:var(--text-muted); margin-top:5px; margin-bottom:25px;">Live performance metrics across your nationwide evaluation network.</p>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div style="background:#f8fafc; padding:20px; border-radius:8px; border:1px solid var(--border-color);">
            <div style="font-size:26px; font-weight:bold; color:var(--primary-blue);"><?php echo $totalUsers; ?></div>
            <div style="font-size:13px; color:var(--text-muted); margin-top:4px;"><i class="fas fa-users"></i> Registered Shoppers</div>
        </div>
        <div style="background:#f8fafc; padding:20px; border-radius:8px; border:1px solid var(--border-color);">
            <div style="font-size:26px; font-weight:bold; color:var(--accent-blue);"><?php echo $totalTasks; ?></div>
            <div style="font-size:13px; color:var(--text-muted); margin-top:4px;"><i class="fas fa-tasks"></i> Total Assigned Tasks</div>
        </div>
        <div style="background:#f8fafc; padding:20px; border-radius:8px; border:1px solid var(--border-color);">
            <div style="font-size:26px; font-weight:bold; color: #f59e0b;"><?php echo $submittedTasks; ?></div>
            <div style="font-size:13px; color:var(--text-muted); margin-top:4px;"><i class="fas fa-clipboard-check"></i> Pending Audit Reviews</div>
        </div>
        <div style="background:#f8fafc; padding:20px; border-radius:8px; border:1px solid var(--border-color);">
            <div style="font-size:26px; font-weight:bold; color:var(--accent-green);"><?php echo $totalChecks; ?></div>
            <div style="font-size:13px; color:var(--text-muted); margin-top:4px;"><i class="fas fa-money-check"></i> Issued Payment Checks</div>
        </div>
    </div>

    <h3 style="margin-bottom: 15px; font-size: 16px;"><i class="fas fa-bell"></i> Recent Shopper Task Submissions Awaiting Audit</h3>
    <?php if (empty($recentSubmissions)): ?>
        <p style="color: var(--text-muted); font-size: 13px;">No pending task reports to review at this moment.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach($recentSubmissions as $sub): ?>
                <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 15px; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <strong style="color: var(--primary-blue);"><?php echo htmlspecialchars($sub['store_name']); ?></strong>
                        <span style="background: rgba(68,132,241,0.1); color: var(--accent-blue); padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;"><?php echo $sub['status']; ?></span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px;"><strong>Shopper:</strong> <?php echo htmlspecialchars($sub['shopper_name']); ?> (<?php echo htmlspecialchars($sub['shopper_email']); ?>)</p>
                    <p style="font-size: 13px; color: var(--text-main); background: white; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);"><strong>Review Notes:</strong> <?php echo htmlspecialchars($sub['review']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</div></body></html>