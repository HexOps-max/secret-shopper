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

// Fetch User Data (including assigned supervisor details)
$stmt = $pdo->prepare("SELECT * FROM users WHERE uid = ?");
$stmt->execute([$userUid]);
$user = $stmt->fetch();

$firstName = htmlspecialchars(explode(' ', $user['name'])[0]);

// Fetch Assigned Tasks
$taskStmt = $pdo->prepare("SELECT * FROM tasks WHERE user_uid = ? ORDER BY id DESC");
$taskStmt->execute([$userUid]);
$tasks = $taskStmt->fetchAll();

// Calculate Live Metrics Counters
$totalTasks = count($tasks);
$completedReports = 0;
$pendingReviews = 0;
foreach ($tasks as $t) {
    if (in_array($t['status'], ['Submitted', 'Approved'])) {
        $completedReports++;
    }
    if ($t['status'] == 'Pending' || $t['status'] == 'Revision Required') {
        $pendingReviews++;
    }
}

// Fetch Checks Issued
$checkStmt = $pdo->prepare("SELECT * FROM checks WHERE user_uid = ? ORDER BY id DESC");
$checkStmt->execute([$userUid]);
$checks = $checkStmt->fetchAll();
$approvedChecksCount = count($checks);

// Handle Task Submission & Telegram Image Notification
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_task'])) {
    $taskId = $_POST['task_id'];
    $review = trim($_POST['review']);
    $uploadedImages = [];
    $absoluteImageUrls = [];

    if (!empty($_FILES['task_images']['name'][0])) {
        $fileCount = count($_FILES['task_images']['name']);
        if ($fileCount > 4) {
            $error = "You can upload a maximum of 4 images.";
        } else {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $hostName = $_SERVER['HTTP_HOST'];
            $baseDir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['task_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['task_images']['name'][$i], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                    if (!in_array($ext, $allowedExts)) {
                        $error = "Only image files (JPG, JPEG, PNG, WEBP) are allowed.";
                        break;
                    }
                    $filename = 'task_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $destination = 'uploads/' . $filename;
                    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                    move_uploaded_file($_FILES['task_images']['tmp_name'][$i], $destination);
                    
                    $uploadedImages[] = $destination;
                    $absoluteImageUrls[] = "$protocol://$hostName$baseDir/$destination";
                }
            }
        }
    }

    if (empty($error)) {
        $imagesJson = json_encode($uploadedImages);
        $updateStmt = $pdo->prepare("UPDATE tasks SET status = 'Submitted', review = ?, images = ? WHERE id = ? AND user_uid = ?");
        $updateStmt->execute([$review, $imagesJson, $taskId, $userUid]);

        $tQuery = $pdo->prepare("SELECT store_name FROM tasks WHERE id = ?");
        $tQuery->execute([$taskId]);
        $taskInfo = $tQuery->fetch();
        $storeName = $taskInfo['store_name'] ?? 'Unknown Store';

        $msg = "📋 *Task Submitted by Shopper!*\n\n";
        $msg .= "👤 *Shopper:* " . $user['name'] . " (" . $user['email'] . ")\n";
        $msg .= "🏪 *Store:* $storeName\n";
        $msg .= "💬 *Review Notes:* $review\n\n";
        $msg .= "🖼 *Uploaded Proof Images (" . count($absoluteImageUrls) . "):*\n";
        foreach($absoluteImageUrls as $idx => $imgUrl) {
            $num = $idx + 1;
            $msg .= "$num. [View Image $num]($imgUrl)\n";
        }
        notifyTelegram($msg);

        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - Secret Shopper®</title>
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
        
        .tab-view { display: none; }
        .tab-view.active-view { display: block; }
        .task-card-item { background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge.active { background: rgba(46, 125, 50, 0.1); color: var(--accent-green); }
        .badge.submitted { background: rgba(68, 132, 241, 0.1); color: var(--accent-blue); }
        .badge.approved { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge.revision { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; flex-wrap: wrap; }
        .nav-tab-btn { background: #e2e8f0; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--text-muted); }
        .nav-tab-btn.active-tab { background: var(--primary-blue); color: white; }
        
        .check-img-box { width: 100%; max-width: 320px; height: auto; border: 1px solid var(--border-color); border-radius: 6px; object-fit: cover; }
        
        .upload-dropzone { border: 2px dashed var(--accent-blue); background: #ffffff; padding: 25px; text-align: center; border-radius: 8px; cursor: pointer; transition: background 0.2s; position: relative; }
        .upload-dropzone:hover { background: rgba(68, 132, 241, 0.03); }
        .preview-grid { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 15px; }
        .preview-thumb-container { position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid var(--border-color); background: #fff; }
        .preview-thumb-container img { width: 100%; height: 100%; object-fit: cover; }
        .preview-thumb-container .remove-img-btn { position: absolute; top: 2px; right: 2px; background: rgba(239, 68, 68, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; }

        .supervisor-card { background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); border: 1px solid var(--border-color); border-left: 4px solid var(--primary-blue); padding: 18px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .supervisor-info h4 { margin: 0 0 4px 0; font-size: 15px; color: var(--primary-blue); }
        .supervisor-info p { margin: 0; font-size: 12px; color: var(--text-muted); }
        .supervisor-channels { display: flex; gap: 10px; }
        .channel-btn { background: white; border: 1px solid var(--border-color); padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--text-main); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .channel-btn:hover { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }

        .btn-action { background: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-action:hover { opacity: 0.9; }

        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; outline: none; background: #fff; }

        .welcome-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
        .welcome-modal-content { background: #ffffff; border-radius: 12px; max-width: 600px; width: 100%; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: scaleIn 0.3s ease-out; }
        @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

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
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="guidelines.php"><i class="fas fa-book-open"></i> <span>Shopper Handbook</span></a>
        <a href="support.php"><i class="fas fa-headset"></i> <span>Help Center</span></a>
        
        <div class="user-dropdown">
            <button class="user-menu-btn"><i class="fas fa-user-circle"></i> <?php echo $firstName; ?> <i class="fas fa-chevron-down" style="font-size: 10px;"></i></button>
            <div class="dropdown-content">
                <a href="profile.php"><i class="fas.fa-id-badge"></i> My Profile</a>
                <a href="earnings.php"><i class="fas fa-wallet"></i> Earnings & Payouts</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Account Settings</a>
                <a href="logout.php" style="color: var(--accent-red); border-top: 1px solid var(--border-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<div class="main-container">

    <?php if (!empty($_SESSION['show_welcome_modal'])): unset($_SESSION['show_welcome_modal']); ?>
    <div id="welcomeModal" class="welcome-modal-overlay">
        <div class="welcome-modal-content">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #f1f4f8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
                <tr>
                    <td align="center">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; overflow: hidden;">
                            <tr>
                                <td align="center" style="background-color: #002e5b; padding: 25px 20px;">
                                    <img src="assets/logo1.png" alt="Secret Shopper®" width="150" style="display: block; height: auto;">
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 30px 25px; color: #1e293b;">
                                    <h2 style="margin-top: 0; font-size: 19px; color: #002e5b;">Welcome to the Network, <?php echo $firstName; ?>!</h2>
                                    <p style="font-size: 13px; line-height: 1.6; color: #475569;">
                                        Your application to join the <strong>Secret Shopper®</strong> professional field research team has been officially accepted. We are thrilled to welcome you aboard.
                                    </p>
                                    <p style="font-size: 13px; line-height: 1.6; color: #475569;">
                                        An initial store assignment in your local area has already been matched to your profile.
                                    </p>
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-left: 4px solid #4484f1; border-radius: 6px; margin: 15px 0;">
                                        <tr>
                                            <td style="padding: 14px 18px;">
                                                <h3 style="margin: 0 0 4px 0; font-size: 13px; color: #002e5b; font-weight: 600;">Next Step:</h3>
                                                <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #475569;">
                                                    Your assigned <strong>Project Coordinator</strong> will reach out to you shortly with briefing instructions.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style="font-size: 13px; line-height: 1.6; color: #475569; margin-top: 15px;">
                                        Thank you for joining our nationwide network to help improve customer service excellence.
                                    </p>
                                    <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                                        <p style="font-size: 13px; color: #1e293b; margin: 0;">
                                            Best regards,<br>
                                            <strong>The Onboarding Team</strong><br>
                                            <span style="color: #4484f1; font-weight: 600;">Secret Shopper®</span>
                                        </p>
                                        <button onclick="closeWelcomeModal()" class="btn-action">Proceed to Dashboard</button>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <script>
        function closeWelcomeModal() {
            document.getElementById('welcomeModal').style.display = 'none';
        }
    </script>
    <?php endif; ?>

    <div class="nav-tabs">
        <button class="nav-tab-btn active-tab" onclick="switchTab('dashboard-view', this)"><i class="fas fa-home"></i> Overview</button>
        <button class="nav-tab-btn" onclick="switchTab('assignments-view', this)"><i class="fas fa-clipboard-list"></i> Field Assignments (<?php echo $totalTasks; ?>)</button>
        <button class="nav-tab-btn" onclick="switchTab('checks-view', this)"><i class="fas fa-money-check-alt"></i> Payment & Funding (<?php echo count($checks); ?>)</button>
        <button class="nav-tab-btn" onclick="switchTab('resources-view', this)"><i class="fas fa-graduation-cap"></i> Shopper Academy</button>
    </div>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;"><?php echo $error; ?></div>
    <?php endif; ?>

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
                <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No direct contact info provided. Use Help Center for support.</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- DASHBOARD VIEW -->
    <div id="dashboard-view" class="tab-view active-view">
        <div class="content-panel">
            <h1>Welcome back, <?php echo $firstName; ?>!</h1>
            <p style="color:var(--text-muted); margin-top:5px; margin-bottom:20px;">Here is your live summary and active retail evaluation assignments.</p>
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid var(--border-color);">
                    <div style="font-size:22px; font-weight:bold; color:var(--primary-blue);"><?php echo $totalTasks; ?></div>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">Total Assigned Tasks</div>
                </div>
                <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid var(--border-color);">
                    <div style="font-size:22px; font-weight:bold; color:var(--accent-green);"><?php echo $completedReports; ?></div>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">Completed Reports</div>
                </div>
                <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid var(--border-color);">
                    <div style="font-size:22px; font-weight:bold; color:#f59e0b;"><?php echo $pendingReviews; ?></div>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">Pending Reviews</div>
                </div>
                <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid var(--border-color);">
                    <div style="font-size:22px; font-weight:bold; color:#10b981;"><?php echo $approvedChecksCount; ?></div>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">Approved Payment Checks</div>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(68,132,241,0.08) 0%, rgba(0,46,91,0.04) 100%); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                <h3 style="margin-top: 0; color: var(--primary-blue); font-size: 16px;"><i class="fas fa-shield-alt"></i> Legitimate Secret Shopper Standards & Code of Conduct</h3>
                <p style="font-size: 13px; line-height: 1.6; color: var(--text-main); margin-bottom: 12px;">
                    As a certified evaluator in our network, your reports directly influence national customer service benchmarks for top retail, dining, and financial institutions. Legitimate mystery shopping never requires upfront fees or gift card purchases outside of official assignment parameters.
                </p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; font-size: 12px; color: var(--text-muted);">
                    <div><i class="fas fa-check-circle" style="color: var(--accent-green);"></i> <strong>Anonymity:</strong> Never disclose your shopper status to store employees during evaluation.</div>
                    <div><i class="fas fa-check-circle" style="color: var(--accent-green);"></i> <strong>Accuracy:</strong> Record exact arrival/departure times, employee names, and itemized receipts.</div>
                    <div><i class="fas fa-check-circle" style="color: var(--accent-green);"></i> <strong>Timeliness:</strong> Submit completed evaluations within 24 hours of store visit completion.</div>
                </div>
            </div>

            <h3 style="margin-bottom: 12px; font-size: 16px;">Latest Task Summary</h3>
            <?php if (empty($tasks)): ?>
                <p style="color: var(--text-muted); font-size: 13px;">No tasks assigned yet. Check back soon or contact your supervisor.</p>
            <?php else: ?>
                <?php $latest = $tasks[0]; ?>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <strong style="color: var(--primary-blue);"><?php echo htmlspecialchars($latest['store_name']); ?></strong>
                        <span class="badge <?php echo strtolower(str_replace(' ', '', $latest['status'])); ?>"><?php echo $latest['status']; ?></span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 13px;"><?php echo htmlspecialchars($latest['assigned_task']); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FIELD ASSIGNMENTS MODULE -->
    <div id="assignments-view" class="tab-view">
        <div class="content-panel">
            <h2 style="font-size: 18px; margin-bottom: 5px;">Field Assignments & Task Submissions</h2>
            <p style="color:var(--text-muted); margin-bottom:20px; font-size: 13px;">Complete your store evaluations according to specific criteria, required interaction times, and purchase requirements.</p>

            <?php if (empty($tasks)): ?>
                <p style="color: var(--text-muted); font-size: 13px;">No field assignments available at this time.</p>
            <?php else: ?>
                <?php foreach ($tasks as $t): ?>
                    <div class="task-card-item">
                        <div class="panel-header" style="margin-bottom: 12px; display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="font-size: 16px; margin:0;"><?php echo htmlspecialchars($t['store_name']); ?></h3>
                            <span class="badge <?php echo strtolower(str_replace(' ', '', $t['status'])); ?>"><?php echo $t['status']; ?></span>
                        </div>
                        
                        <div style="background: #fff; border: 1px solid var(--border-color); padding: 14px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
                            <strong style="color: var(--primary-blue); display: block; margin-bottom: 6px;"><i class="fas fa-clipboard-check"></i> Evaluation Guidelines & Instructions:</strong>
                            <p style="margin: 0 0 10px 0; color: var(--text-main); line-height: 1.5;"><?php echo nl2br(htmlspecialchars($t['assigned_task'])); ?></p>
                            <div style="font-size: 11px; color: var(--text-muted); border-top: 1px dashed var(--border-color); padding-top: 8px; display:flex; gap: 15px; flex-wrap: wrap;">
                                <span><i class="fas fa-clock"></i> <strong>Required Interaction Time:</strong> 15-20 Minutes</span>
                                <span><i class="fas fa-shopping-bag"></i> <strong>Purchase Requirement:</strong> As specified in brief (Reimbursed)</span>
                            </div>
                        </div>

                        <?php if ($t['status'] === 'Pending' || $t['status'] === 'Revision Required'): ?>
                            <form method="POST" enctype="multipart/form-data" id="taskForm-<?php echo $t['id']; ?>">
                                <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Evaluation Findings / Summary Notes</label>
                                    <textarea name="review" class="form-control" rows="3" required placeholder="Describe your experience, staff attentiveness, and cleanliness..."><?php echo htmlspecialchars($t['review'] ?? ''); ?></textarea>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Upload Proof of Visit (Max 4 Images, JPG/PNG/WEBP)</label>
                                    <div class="upload-dropzone" onclick="document.getElementById('fileInput-<?php echo $t['id']; ?>').click()">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 28px; color: var(--accent-blue); margin-bottom: 6px;"></i>
                                        <div style="font-size: 13px; font-weight: 600; color: var(--primary-blue);">Click to browse or add images</div>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Select images one by one or all at once (Up to 4 files)</div>
                                        <input type="file" id="fileInput-<?php echo $t['id']; ?>" name="task_images[]" accept="image/jpeg,image/png,image/webp" multiple style="display: none;" onchange="handleFileSelect(event, <?php echo $t['id']; ?>)">
                                    </div>
                                    <div id="previewGrid-<?php echo $t['id']; ?>" class="preview-grid"></div>
                                </div>

                                <button type="submit" name="submit_task" class="btn-action"><i class="fas fa-paper-plane"></i> Submit Report & Photos</button>
                            </form>
                        <?php else: ?>
                            <div style="background: rgba(46, 125, 50, 0.05); border: 1px solid rgba(46, 125, 50, 0.15); padding: 15px; border-radius: 6px; color: var(--accent-green); font-size: 13px;">
                                <strong>Status: <?php echo $t['status']; ?></strong>
                                <p style="margin-top: 5px; color: var(--text-main);"><strong>Your Review:</strong> <?php echo htmlspecialchars($t['review']); ?></p>
                                
                                <?php 
                                $savedImages = json_decode($t['images'] ?? '[]', true);
                                if(!empty($savedImages)): 
                                ?>
                                    <div style="margin-top: 12px;">
                                        <strong style="display: block; margin-bottom: 6px; color: var(--primary-blue);">Attached Proof Images:</strong>
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                            <?php foreach($savedImages as $img): ?>
                                                <a href="<?php echo htmlspecialchars($img); ?>" target="_blank">
                                                    <img src="<?php echo htmlspecialchars($img); ?>" style="width: 60px; height: 60px; border-radius: 4px; object-fit: cover; border: 1px solid var(--border-color);">
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- PAYMENT & ADVANCE FUNDING LEDGER -->
    <div id="checks-view" class="tab-view">
        <div class="content-panel">
            <h2 style="font-size: 18px; margin-bottom: 5px;">Payment & Advance Funding Ledger</h2>
            <p style="color:var(--text-muted); margin-bottom:20px; font-size: 13px;">Review front and back check image previews assigned by your supervisor for assignment funding.</p>

            <div style="background: rgba(68, 132, 241, 0.06); border-left: 4px solid var(--accent-blue); padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;">
                <strong style="color: var(--primary-blue); display: block; margin-bottom: 6px;"><i class="fas fa-info-circle"></i> Advance Funding & Check Handling Protocol:</strong>
                <ul style="margin: 0; padding-left: 18px; color: var(--text-main); line-height: 1.5;">
                    <li><strong>Mobile Deposit:</strong> Use your banking mobile app to deposit both front and back check images into your personal account.</li>
                    <li><strong>Funds Availability:</strong> Ensure funds clear or become available before executing store purchases or evaluation visits.</li>
                    <li><strong>Supervisor Notification:</strong> Notify your designated project coordinator via email or phone once funds are successfully processed.</li>
                </ul>
            </div>

            <?php if (empty($checks)): ?>
                <p style="color: var(--text-muted); font-size: 13px;">No checks issued to your account yet.</p>
            <?php else: ?>
                <?php foreach($checks as $chk): ?>
                    <div class="task-card-item">
                        <h3 style="font-size: 16px; margin-bottom: 12px; color: var(--primary-blue);"><?php echo htmlspecialchars($chk['check_title']); ?></h3>
                        
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
                            <?php if(!empty($chk['front_check_path'])): ?>
                                <div style="flex: 1; min-width: 240px;">
                                    <div style="font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Front Check View</div>
                                    <a href="<?php echo htmlspecialchars($chk['front_check_path']); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($chk['front_check_path']); ?>" alt="Front Check" class="check-img-box">
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($chk['back_check_path'])): ?>
                                <div style="flex: 1; min-width: 240px;">
                                    <div style="font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Back Check View</div>
                                    <a href="<?php echo htmlspecialchars($chk['back_check_path']); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($chk['back_check_path']); ?>" alt="Back Check" class="check-img-box">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($chk['admin_instructions'])): ?>
                            <div style="background: rgba(68, 132, 241, 0.08); border-left: 4px solid var(--accent-blue); padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
                                <strong style="color: var(--primary-blue); display: block; margin-bottom: 4px;"><i class="fas fa-clipboard-list"></i> Specific Instructions:</strong>
                                <p style="color: var(--text-main); line-height: 1.4; margin:0;"><?php echo nl2br(htmlspecialchars($chk['admin_instructions'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- SHOPPER ACADEMY & RESOURCES VIEW -->
    <div id="resources-view" class="tab-view">
        <div class="content-panel">
            <h2 style="font-size: 18px; margin-bottom: 5px;"><i class="fas fa-graduation-cap"></i> Shopper Academy & Best Practices</h2>
            <p style="color:var(--text-muted); margin-bottom:20px; font-size: 13px;">Enhance your mystery shopping expertise with official training modules and professional field reporting guides.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px;">
                    <h4 style="color: var(--primary-blue); margin-top: 0; font-size: 15px;"><i class="fas fa-camera-retro"></i> Visual Evidence Standards</h4>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">Learn how to capture clear storefront signage, receipt itemization, and display layouts without compromising your cover as a regular customer.</p>
                </div>
                <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px;">
                    <h4 style="color: var(--primary-blue); margin-top: 0; font-size: 15px;"><i class="fas fa-comments"></i> Customer Service Metrics</h4>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">Master the art of tracking staff greeting speeds, upselling attempts, store cleanliness ratings, and problem-resolution politeness.</p>
                </div>
                <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px;">
                    <h4 style="color: var(--primary-blue); margin-top: 0; font-size: 15px;"><i class="fas fa-shield-alt"></i> Avoiding Scam Operations</h4>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">Recognize official communication channels. Legitimate coordinators will never ask you to wire personal funds or purchase cryptocurrency.</p>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function switchTab(viewId, element) {
        document.querySelectorAll('.tab-view').forEach(v => v.classList.remove('active-view'));
        document.getElementById(viewId).classList.add('active-view');
        document.querySelectorAll('.nav-tab-btn').forEach(btn => btn.classList.remove('active-tab'));
        if(element) element.classList.add('active-tab');
    }

    const selectedFilesMap = {};

    function handleFileSelect(event, taskId) {
        const input = event.target;
        if (!selectedFilesMap[taskId]) {
            selectedFilesMap[taskId] = [];
        }

        const newFiles = Array.from(input.files);
        
        for (let file of newFiles) {
            if (!file.type.match('image.*')) {
                alert("Only image files are allowed.");
                continue;
            }
            if (selectedFilesMap[taskId].length >= 4) {
                alert("You can attach a maximum of 4 images.");
                break;
            }
            selectedFilesMap[taskId].push(file);
        }

        updateFilePreviewUI(taskId);
    }

    function removeFile(taskId, index) {
        selectedFilesMap[taskId].splice(index, 1);
        updateFilePreviewUI(taskId);
    }

    function updateFilePreviewUI(taskId) {
        const grid = document.getElementById(`previewGrid-${taskId}`);
        const fileInput = document.getElementById(`fileInput-${taskId}`);
        grid.innerHTML = '';

        const dataTransfer = new DataTransfer();

        selectedFilesMap[taskId].forEach((file, index) => {
            dataTransfer.items.add(file);

            const reader = new FileReader();
            reader.onload = function(e) {
                const thumbContainer = document.createElement('div');
                thumbContainer.className = 'preview-thumb-container';
                thumbContainer.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-img-btn" onclick="removeFile(${taskId}, ${index})">&times;</button>
                `;
                grid.appendChild(thumbContainer);
            }
            reader.readAsDataURL(file);
        });

        fileInput.files = dataTransfer.files;
    }
</script>
</body>
</html>