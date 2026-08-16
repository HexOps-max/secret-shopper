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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Shopper Handbook & Guidelines - Secret Shopper®</title>
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
        .handbook-section { margin-bottom: 30px; }
        .handbook-section h3 { color: var(--primary-blue); font-size: 17px; border-bottom: 2px solid var(--border-color); padding-bottom: 8px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .handbook-section p, .handbook-section li { font-size: 13px; line-height: 1.6; color: var(--text-main); }
        .handbook-section ul { padding-left: 20px; margin-top: 5px; }
        .handbook-card { background: #f8fafc; border: 1px solid var(--border-color); padding: 18px; border-radius: 8px; margin-bottom: 15px; }
        @media(max-width: 768px) { header { padding: 0 15px; } .header-nav a span { display: none; } }
    </style>
</head>
<body>
    <header>
        <img src="assets/logo1.png" alt="Secret Shopper®" class="header-logo">
        <div class="header-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
            <a href="guidelines.php" class="active"><i class="fas fa-book-open"></i> <span>Shopper Handbook</span></a>
            <div class="user-dropdown">
                <button class="user-menu-btn"><i class="fas fa-user-circle"></i> <?php echo $firstName; ?> <i class="fas fa-chevron-down" style="font-size: 10px;"></i></button>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-id-badge"></i> My Profile</a>
                    <a href="earnings.php"><i class="fas fa-wallet"></i> Earnings & Payouts</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Account Settings</a>
                    <a href="logout.php" style="color: var(--accent-red); border-top: 1px solid var(--border-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="content-panel">
            <h2 style="margin-top:0; color:var(--primary-blue); font-size:22px;"><i class="fas fa-book-open"></i> Official Shopper Handbook & Code of Conduct</h2>
            <p style="color:var(--text-muted); font-size:13px; margin-bottom:25px;">Please review these professional standards prior to conducting any store evaluations.</p>

            <div class="handbook-section">
                <h3><i class="fas fa-shield-alt"></i> 1. Core Principles & Confidentiality</h3>
                <p>As a professional field researcher with Secret Shopper®, your assessments provide critical customer experience insights to major retail chains. Strict confidentiality is required at all times:</p>
                <ul>
                    <li>Never reveal your identity or status as a mystery shopper to store employees or management.</li>
                    <li>Do not discuss specific evaluation locations, client brands, or compensation details on public social media forums.</li>
                    <li>Always adhere strictly to the scheduled evaluation window provided in your assignment brief.</li>
                </ul>
            </div>

            <div class="handbook-section">
                <h3><i class="fas fa-clipboard-check"></i> 2. Executing Store Visits</h3>
                <div class="handbook-card">
                    <strong style="color:var(--primary-blue); display:block; margin-bottom:5px;">Pre-Visit Preparation</strong>
                    <p style="margin:0;">Read the complete assignment brief and questionnaire before arriving at the location. Note mandatory interaction requirements, employee greeting timelines, and specific promotional questions.</p>
                </div>
                <div class="handbook-card">
                    <strong style="color:var(--primary-blue); display:block; margin-bottom:5px;">During the Visit</strong>
                    <p style="margin:0;">Act naturally as a standard customer. Observe employee attentiveness, store cleanliness, product availability, and checkout efficiency. Memorize key employee names if possible.</p>
                </div>
                <div class="handbook-card">
                    <strong style="color:var(--primary-blue); display:block; margin-bottom:5px;">Post-Visit Reporting</strong>
                    <p style="margin:0;">Complete your evaluation report and upload required proof images (receipts, storefront photos) within 24 hours of completing the assignment.</p>
                </div>
            </div>

            <div class="handbook-section">
                <h3><i class="fas fa-headset"></i> 3. Support & Coordinator Communication</h3>
                <p>If you encounter unexpected closures, inventory shortages, or require assistance with advance funding checks, contact your designated project coordinator immediately through your official dashboard channels or assigned supervisor email.</p>
            </div>
        </div>
    </div>
</body>
</html>