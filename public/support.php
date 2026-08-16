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

// Fetch User Data for dynamic display and supervisor contact info
$stmt = $pdo->prepare("SELECT * FROM users WHERE uid = ?");
$stmt->execute([$userUid]);
$user = $stmt->fetch();

$firstName = htmlspecialchars(explode(' ', $user['name'])[0]);

// Handle Support Ticket Submission (optional local action or notification trigger)
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (!empty($subject) && !empty($message)) {
        // Construct notification for Telegram or support desk if desired
        $msg = "🆘 *Support Request Submitted*\n\n";
        $msg .= "👤 *Shopper:* " . $user['name'] . " (" . $user['email'] . ")\n";
        $msg .= "📌 *Subject:* $subject\n";
        $msg .= "💬 *Message:* $message\n";
        
        if (function_exists('notifyTelegram')) {
            notifyTelegram($msg);
        }
        
        $successMessage = "Your support request has been successfully transmitted to your project supervisor and the help desk team. We will get back to you shortly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Help Center & Support - Secret Shopper®</title>
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

        .main-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .content-panel { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border-color); margin-bottom: 25px; }
        
        .supervisor-card { background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); border: 1px solid var(--border-color); border-left: 4px solid var(--primary-blue); padding: 18px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .supervisor-info h4 { margin: 0 0 4px 0; font-size: 15px; color: var(--primary-blue); }
        .supervisor-info p { margin: 0; font-size: 12px; color: var(--text-muted); }
        .supervisor-channels { display: flex; gap: 10px; }
        .channel-btn { background: white; border: 1px solid var(--border-color); padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--text-main); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .channel-btn:hover { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }

        .faq-item { background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 15px; }
        .faq-item h4 { margin: 0 0 8px 0; color: var(--primary-blue); font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .faq-item p { margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.5; }

        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; outline: none; background: #fff; }
        .btn-action { background: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-action:hover { opacity: 0.9; }

        @media(max-width: 768px) {
            header { padding: 0 15px; }
            .header-nav { gap: 10px; }
            .header-nav a span { display: none; }
            .supervisor-card { flex-direction: column; align-items: flex-start; }
            .supervisor-channels { width: 100%; justify-content: space-between; }
            .channel-btn { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>

<header>
    <img src="assets/logo1.png" alt="Secret Shopper®" class="header-logo">
    <div class="header-nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="guidelines.php"><i class="fas fa-book-open"></i> <span>Shopper Handbook</span></a>
        <a href="support.php" class="active"><i class="fas fa-headset"></i> <span>Help Center</span></a>
        
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

    <!-- SUPERVISOR GUIDANCE SECTION (Dynamic Optional Rendering) -->
    <?php 
        $supName = !empty($user['supervisor_name']) ? htmlspecialchars($user['supervisor_name']) : 'Project Coordination Desk';
        $supEmail = !empty($user['supervisor_email']) ? trim($user['supervisor_email']) : '';
        $supPhone = !empty($user['supervisor_phone']) ? trim($user['supervisor_phone']) : '';
    ?>
    <div class="supervisor-card">
        <div class="supervisor-info">
            <h4><i class="fas fa-user-tie"></i> Designated Project Supervisor: <?php echo $supName; ?></h4>
            <p>Your coordinator handles briefing guidelines, review approvals, and advance funding support. Contact via official channels below.</p>
        </div>
        <div class="supervisor-channels">
            <?php if (!empty($supEmail)): ?>
                <a href="mailto:<?php echo htmlspecialchars($supEmail); ?>" class="channel-btn"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($supEmail); ?></a>
            <?php endif; ?>
            <?php if (!empty($supPhone)): ?>
                <a href="tel:<?php echo htmlspecialchars($supPhone); ?>" class="channel-btn"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($supPhone); ?></a>
            <?php endif; ?>
            <?php if (empty($supEmail) && empty($supPhone)): ?>
                <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No direct contact info provided. Use ticket form below.</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($successMessage): ?>
        <div style="background: rgba(46, 125, 50, 0.1); color: var(--accent-green); padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; border: 1px solid rgba(46, 125, 50, 0.2);">
            <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>

    <!-- HELP & SUPPORT CONTENT -->
    <div class="content-panel">
        <h2 style="font-size: 20px; color: var(--primary-blue); margin-top: 0; margin-bottom: 5px;"><i class="fas fa-headset"></i> Help Center & Support Desk</h2>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 25px;">Find quick answers to common questions or submit a direct inquiry to your assigned supervisor.</p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start;" class="support-grid">
            
            <!-- FAQ Section -->
            <div>
                <h3 style="font-size: 16px; color: var(--primary-blue); margin-top: 0; margin-bottom: 15px;"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                
                <div class="faq-item">
                    <h4><i class="fas fa-chevron-right" style="font-size: 11px; color: var(--accent-blue);"></i> How do I deposit project funding checks?</h4>
                    <p>Use your banking mobile application to take a clean photo of both the front and back of the check assigned in your Payment & Funding ledger. Ensure funds are fully available before executing your store evaluation purchases.</p>
                </div>

                <div class="faq-item">
                    <h4><i class="fas fa-chevron-right" style="font-size: 11px; color: var(--accent-blue);"></i> What should I do if my store visit is delayed?</h4>
                    <p>If you cannot complete an assignment within the scheduled timeframe, notify your designated project supervisor immediately via phone or email so re-scheduling or reassignment can take place.</p>
                </div>

                <div class="faq-item">
                    <h4><i class="fas fa-chevron-right" style="font-size: 11px; color: var(--accent-blue);"></i> When will my submitted reports be reviewed?</h4>
                    <p>Supervisors typically audit and review field reports within 24 to 48 hours of submission. If revisions are required, you will receive a status notification on your dashboard.</p>
                </div>
            </div>

            <!-- Support Ticket Form -->
            <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px;">
                <h3 style="font-size: 16px; color: var(--primary-blue); margin-top: 0; margin-bottom: 15px;"><i class="fas fa-paper-plane"></i> Send Direct Support Inquiry</h3>
                
                <form method="POST">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Subject / Topic</label>
                        <input type="text" name="subject" class="form-control" required placeholder="e.g., Question about check deposit or store assignment">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Message Details</label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="Describe your question or issue clearly..."></textarea>
                    </div>

                    <button type="submit" name="submit_ticket" class="btn-action"><i class="fas fa-paper-plane"></i> Submit Support Ticket</button>
                </form>
            </div>

        </div>
    </div>

</div>

<style>
@media(max-width: 900px) {
    .support-grid { grid-template-columns: 1fr !important; }
}
</style>
</body>
</html>