<?php
// header.php for User Pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_uid'])) {
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
    <title>Secret Shopper® Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --primary-blue: #002e5b; --secondary-blue: #001f3f; --accent-blue: #4484f1; --accent-green: #2e7d32; --bg-light: #f1f4f8; --text-main: #1e293b; --text-muted: #475569; --border-color: #e2e8f0; --sidebar-width: 260px; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-main); display: flex; min-height: 100vh; flex-direction: column; }
        
        /* Layout wrapper */
        .app-container { display: flex; flex: 1; width: 100%; position: relative; }
        
        aside { width: var(--sidebar-width); background-color: var(--primary-blue); color: white; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; display: flex; flex-direction: column; transition: transform 0.3s ease; }
        .sidebar-brand { padding: 20px; display: flex; align-items: center; justify-content: space-between; background-color: var(--secondary-blue); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand img { height: 38px; width: auto; }
        .sidebar-menu { list-style: none; padding: 20px 0; flex: 1; overflow-y: auto; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 14px 24px; color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 500; }
        .sidebar-menu li.active a, .sidebar-menu li a:hover { color: white; background-color: rgba(255,255,255,0.08); border-left: 4px solid var(--accent-blue); }
        
        .main-content-wrapper { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; min-height: 100vh; width: calc(100% - var(--sidebar-width)); }
        header { background-color: #ffffff; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; height: 70px; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 99; }
        .mobile-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--primary-blue); cursor: pointer; }
        
        .dashboard-body { padding: 25px; flex: 1; width: 100%; max-width: 1100px; margin: 0 auto; }
        .content-panel { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid var(--border-color); margin-bottom: 25px; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px; }
        .btn-action { background-color: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .form-control, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: #fff; outline: none; }
        
        /* Responsive Breakpoints */
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
                <img src="assets/logo1.png" alt="Secret Shopper">
                <button onclick="toggleSidebar()" style="background:none; border:none; color:white; font-size:18px; cursor:pointer;" class="mobile-toggle"><i class="fas fa-times"></i></button>
            </div>
            <ul class="sidebar-menu">
                <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard & Tasks</a></li>
                <li class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
            </ul>
        </aside>
        <div class="main-content-wrapper">
            <header>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <div style="font-weight: 600; color: var(--primary-blue); font-size: 15px;">Evaluator Portal</div>
                </div>
                <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Shopper'); ?></div>
            </header>
            <div class="dashboard-body">
<script>
    function toggleSidebar() {
        document.getElementById('mobileSidebar').classList.toggle('mobile-open');
    }
</script>