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

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';

    if (password_verify($currentPass, $user['password'])) {
        if (!empty($newPass)) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $upStmt = $pdo->prepare("UPDATE users SET password = ? WHERE uid = ?");
            $upStmt->execute([$hashed, $userUid]);
            $successMsg = "Password updated successfully!";
        } else {
            $errorMsg = "New password cannot be empty.";
        }
    } else {
        $errorMsg = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Account Settings - Secret Shopper®</title>
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
        .main-container { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        .content-panel { background: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border-color); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; outline: none; }
        .btn-action { background: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; }
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
                    <a href="earnings.php"><i class="fas fa-wallet"></i> Earnings & Payouts</a>
                    <a href="settings.php" style="background-color:#f8fafc; color:var(--primary-blue);"><i class="fas fa-cog"></i> Account Settings</a>
                    <a href="logout.php" style="color: var(--accent-red); border-top: 1px solid var(--border-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="content-panel">
            <h2 style="margin-top:0; color:var(--primary-blue); font-size:20px;"><i class="fas fa-cog"></i> Account Settings</h2>
            <p style="color:var(--text-muted); font-size:13px; margin-bottom:20px;">Manage your password security settings.</p>

            <?php if($successMsg): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;"><?php echo $successMsg; ?></div>
            <?php endif; ?>
            <?php if($errorMsg): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;"><?php echo $errorMsg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <button type="submit" name="update_password" class="btn-action"><i class="fas fa-save"></i> Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>