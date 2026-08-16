<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userUid = $_SESSION['user_uid'] ?? null;
if (!$userUid) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE uid = ?");
$stmt->execute([$userUid]);
$user = $stmt->fetch();
$firstName = htmlspecialchars(explode(' ', $user['name'])[0]);

$taskStmt = $pdo->prepare("SELECT * FROM tasks WHERE user_uid = ? AND status = 'Approved' ORDER BY id DESC");
$taskStmt->execute([$userUid]);
$approvedTasks = $taskStmt->fetchAll();

$checkStmt = $pdo->prepare("SELECT * FROM checks WHERE user_uid = ? ORDER BY id DESC");
$checkStmt->execute([$userUid]);
$checks = $checkStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Earnings & Payouts - Secret Shopper®</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --primary-blue: #002e5b; --accent-red: #a94442; --accent-blue: #4484f1; --accent-green: #2e7d32; --border-color: #e2e8f0; --text-main: #334155; --text-muted: #64748b; }
        * { box-sizing: border-box; transition: all 0.2s ease-in-out; }
        body { margin: 0; padding: 0; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #f1f4f8; color: var(--text-main); }
        header { background-color: var(--primary-blue); padding: 0 30px; display: flex; align-items: center; justify-content: space-between; height: 65px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header-logo { height: 45px; }
        .header-nav { display: flex; align-items: center; gap: 20px; }
        .header-nav a { color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; }
        .header-nav a:hover, .header-nav a.active { background: rgba(255,255,255,0.1); }
        .user-dropdown { position: relative; display: inline-block; }
        .user-menu-btn { background: rgba(255,255,255,0.15); color: white; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: #ffffff; min-width: 180px; box-shadow: 0px 8px 16px rgba(0,0,0,0.15); border-radius: 6px; z-index: 1000; overflow: hidden; border: 1px solid var(--border-color); }
        .dropdown-content a { color: var(--text-main); padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; }
        .dropdown-content a:hover { background-color: #f8fafc; color: var(--primary-blue); }
        .user-dropdown:hover .dropdown-content { display: block; }
        .main-container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .content-panel { background: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border-color); margin-bottom: 25px; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px; }
        .stat-card h3 { margin: 0 0 5px 0; font-size: 24px; color: var(--primary-blue); }
        .stat-card p { margin: 0; font-size: 12px; color: var(--text-muted); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--border-color); }
        th { background: #f8fafc; font-weight: 600; color: var(--text-muted); }
        @media(max-width: 768px) { header { padding: 0 15px; } .header-nav a span { display: none; } }
    </style>
</head>
<body>
    <header>
        <img src="assets/logo1.png" alt="Secret Shopper®" class="header-logo">
        <div class="header-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
            <div class="user-dropdown">
                <button class="user-menu-btn"><i class="fas fa-user-circle"></i> <?php echo $firstName; ?> <i class="fas fa-chevron-down" style="font-size: 10px;"></i></button>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-id-badge"></i> My Profile</a>
                    <a href="earnings.php" style="background-color:#f8fafc; color:var(--primary-blue);"><i class="fas fa-wallet"></i> Earnings & Payouts</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Account Settings</a>
                    <a href="logout.php" style="color: var(--accent-red); border-top: 1px solid var(--border-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="content-panel">
            <h2 style="margin-top:0; color:var(--primary-blue); font-size:20px;"><i class="fas fa-wallet"></i> Earnings & Funding Ledger</h2>
            <p style="color:var(--text-muted); font-size:13px; margin-bottom:20px;">Summary of assigned task reimbursements and funding checks.</p>
            
            <div class="stat-grid">
                <div class="stat-card">
                    <h3><?php echo count($approvedTasks); ?></h3>
                    <p>Approved Evaluation Reports</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($checks); ?></h3>
                    <p>Issued Funding Checks</p>
                </div>
            </div>

            <h3 style="font-size:16px; margin-bottom:10px;">Approved Store Evaluations</h3>
            <?php if(empty($approvedTasks)): ?>
                <p style="color:var(--text-muted); font-size:13px;">No approved reports yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Store Name</th>
                            <th>Status</th>
                            <th>Review Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($approvedTasks as $at): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($at['store_name']); ?></strong></td>
                                <td><span style="color:var(--accent-green); font-weight:600;"><?php echo htmlspecialchars($at['status']); ?></span></td>
                                <td><?php echo htmlspecialchars(substr($at['review'], 0, 60)) . '...'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>