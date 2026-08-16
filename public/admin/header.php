<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure only authenticated admins can access backend panels
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Secret Shopper® Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --primary-blue: #002e5b; --secondary-blue: #001f3f; --accent-blue: #4484f1; --accent-green: #2e7d32; --bg-light: #f1f4f8; --text-main: #1e293b; --text-muted: #475569; --border-color: #e2e8f0; --sidebar-width: 260px; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-main); display: flex; min-height: 100vh; flex-direction: column; }
        
        .app-container { display: flex; flex: 1; width: 100%; position: relative; }
        aside { width: var(--sidebar-width); background-color: var(--primary-blue); color: white; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; display: flex; flex-direction: column; transition: transform 0.3s ease; }
        .sidebar-brand { padding: 20px; display: flex; align-items: center; justify-content: space-between; background-color: var(--secondary-blue); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand img { height: 35px; width: auto; }
        .sidebar-menu { list-style: none; padding: 20px 0; flex: 1; overflow-y: auto; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 14px 24px; color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 500; }
        .sidebar-menu li.active a, .sidebar-menu li a:hover { color: white; background-color: rgba(255,255,255,0.08); border-left: 4px solid var(--accent-blue); }
        
        .main-content-wrapper { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; min-height: 100vh; width: calc(100% - var(--sidebar-width)); }
        header { background-color: #ffffff; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; height: 70px; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 99; }
        .mobile-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--primary-blue); cursor: pointer; }
        
        .dashboard-body { padding: 25px; flex: 1; width: 100%; max-width: 1200px; margin: 0 auto; }
        .content-panel { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid var(--border-color); margin-bottom: 25px; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px; }
        .btn-action { background-color: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .form-control, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: #fff; outline: none; }
        
        @media(max-width: 768px) {
            aside { transform: translateX(-100%); }
            aside.mobile-open { transform: translateX(0); }
            .main-content-wrapper { margin-left: 0; width: 100%; }
            .mobile-toggle { display: block; }
            .dashboard-body { padding: 15px; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <aside id="mobileSidebar">
            <div class="sidebar-brand">
                <span style="font-weight: bold; font-size: 15px; color: #fff;">Admin Control Panel</span>
                <button onclick="toggleSidebar()" class="mobile-toggle" style="color:#fff;"><i class="fas fa-times"></i></button>
            </div>
            <ul class="sidebar-menu">
                <li class="<?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>"><a href="admin.php"><i class="fas fa-chart-line"></i> Overview & Metrics</a></li>
                <li class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"><a href="users.php"><i class="fas fa-users"></i> Shopper Management</a></li>
                <li class="<?php echo ($current_page == 'tasks.php') ? 'active' : ''; ?>"><a href="tasks.php"><i class="fas fa-tasks"></i> Task Creator</a></li>
                <li class="<?php echo ($current_page == 'supervisors.php') ? 'active' : ''; ?>"><a href="supervisors.php"><i class="fas fa-tasks"></i> Supervisors</a></li>
                <li class="<?php echo ($current_page == 'checks.php') ? 'active' : ''; ?>"><a href="checks.php"><i class="fas fa-money-check"></i> Check Issuer</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
            </ul>
        </aside>
        <div class="main-content-wrapper">
            <header>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <div style="font-weight: 600; color: var(--primary-blue); font-size: 15px;">Secret Shopper® Administration</div>
                </div>
                <div style="font-weight: 600; font-size: 14px; color: var(--text-muted);"><i class="fas fa-user-shield"></i> Administrator</div>
            </header>
            <div class="dashboard-body">
<script>
    function toggleSidebar() { document.getElementById('mobileSidebar').classList.toggle('mobile-open'); }
</script>