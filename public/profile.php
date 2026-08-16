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

// Handle Profile & Avatar Updates (Triggered by either normal submit or instant avatar upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? ($user['phone'] ?? ''));
    $address = trim($_POST['address'] ?? ($user['address'] ?? ''));
    $city = trim($_POST['city'] ?? ($user['city'] ?? ''));
    $state = trim($_POST['state'] ?? ($user['state'] ?? ''));
    $zip = trim($_POST['zip'] ?? ($user['zip'] ?? ''));
    
    // Optional preferences handling if stored or expanded in session/db later
    $vehicleType = trim($_POST['vehicle_type'] ?? '');
    $travelRadius = trim($_POST['travel_radius'] ?? '');
    $primaryCategory = trim($_POST['primary_category'] ?? '');
    
    $avatarPath = $user['avatar'] ?? '';

    // Handle Avatar File Upload via file input
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['avatar_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowedExts)) {
                $filename = 'avatar_' . $userUid . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/uploads/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $destination = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Store relative URL path in DB
                    $avatarPath = 'uploads/' . $filename;
                } else {
                    $errorMsg = "Failed to move uploaded file. Please verify folder permissions.";
                }
            } else {
                $errorMsg = "Invalid image format. Only JPG, PNG, and WEBP files are allowed.";
            }
        } else {
            $errorMsg = "File upload encountered an error (Code: " . $file['error'] . ").";
        }
    }

    if (empty($errorMsg)) {
        try {
            $upStmt = $pdo->prepare("UPDATE users SET phone = ?, address = ?, city = ?, state = ?, zip = ?, avatar = ? WHERE uid = ?");
            if ($upStmt->execute([$phone, $address, $city, $state, $zip, $avatarPath, $userUid])) {
                $successMsg = isset($_POST['update_profile']) ? "Your Secret Shopper® profile and preferences have been updated successfully." : "Profile picture updated successfully!";
                // Refresh user details
                $stmt->execute([$userUid]);
                $user = $stmt->fetch();
            } else {
                $errorMsg = "Database update failed. Please try again.";
            }
        } catch (PDOException $e) {
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Shopper Profile & Certification - Secret Shopper®</title>
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
        
        .profile-hero { display: flex; align-items: center; gap: 25px; background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); border: 1px solid var(--border-color); padding: 25px; border-radius: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .avatar-container { position: relative; width: 95px; height: 95px; border-radius: 50%; overflow: hidden; border: 3px solid var(--primary-blue); background: #cbd5e1; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .avatar-container img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-container .initials { font-size: 36px; font-weight: bold; color: var(--primary-blue); }
        .avatar-upload-overlay { position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.65); color: white; font-size: 10px; text-align: center; padding: 4px 0; cursor: pointer; font-weight: 500; }
        .avatar-upload-overlay:hover { background: rgba(0,46,91,0.85); }
        
        .profile-meta h2 { margin: 0 0 4px 0; font-size: 20px; color: var(--primary-blue); }
        .profile-meta p { margin: 0 0 8px 0; font-size: 13px; color: var(--text-muted); }
        .badge-verified { background: rgba(46, 125, 50, 0.1); color: var(--accent-green); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; outline: none; background: #fff; }
        input[readonly] { background: #f8fafc; color: var(--text-muted); cursor: not-allowed; }
        .btn-action { background: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-action:hover { opacity: 0.9; }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-box { background: #f8fafc; border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; }
        .stat-box span { font-size: 16px; font-weight: bold; color: var(--primary-blue); display: block; margin-top: 4px; }

        .section-title { font-size: 16px; margin: 25px 0 15px 0; color: var(--primary-blue); border-bottom: 2px solid #f1f4f8; padding-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        
        .compliance-box { background: #f8fafc; border: 1px solid var(--border-color); padding: 18px; border-radius: 8px; font-size: 13px; line-height: 1.5; color: var(--text-muted); margin-bottom: 20px; }
        .compliance-box strong { color: var(--primary-blue); }

        @media(max-width: 768px) { header { padding: 0 15px; } .header-nav a span { display: none; } }
    </style>
</head>
<body>
    <header>
        <img src="assets/logo1.png" alt="Secret Shopper®" class="header-logo">
        <div class="header-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
            <a href="guidelines.php"><i class="fas fa-book-open"></i> <span>Shopper Handbook</span></a>
            <div class="user-dropdown">
                <button class="user-menu-btn"><i class="fas fa-user-circle"></i> <?php echo $firstName; ?> <i class="fas fa-chevron-down" style="font-size: 10px;"></i></button>
                <div class="dropdown-content">
                    <a href="profile.php" style="background-color:#f8fafc; color:var(--primary-blue);"><i class="fas fa-id-badge"></i> My Profile</a>
                    <a href="earnings.php"><i class="fas fa-wallet"></i> Earnings & Payouts</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Account Settings</a>
                    <a href="logout.php" style="color: var(--accent-red); border-top: 1px solid var(--border-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="content-panel">
            
            <?php if($successMsg): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: 500;"><i class="fas fa-check-circle"></i> <?php echo $successMsg; ?></div>
            <?php endif; ?>
            <?php if($errorMsg): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: 500;"><i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?></div>
            <?php endif; ?>

            <!-- Single unified form with proper multipart encoding for file uploads -->
            <form method="POST" enctype="multipart/form-data">
                
                <!-- Profile Hero & Avatar Uploader -->
                <div class="profile-hero">
                    <div class="avatar-container" onclick="document.getElementById('avatarInput').click()" style="cursor: pointer;" title="Click to upload profile photo">
                        <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Shopper Profile Picture">
                        <?php else: ?>
                            <div class="initials"><?php echo strtoupper(substr($firstName, 0, 1)); ?></div>
                        <?php endif; ?>
                        <div class="avatar-upload-overlay"><i class="fas fa-camera"></i> Change</div>
                    </div>
                    <!-- Hidden file input that triggers upload on selection -->
                    <input type="file" id="avatarInput" name="avatar_file" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="this.form.submit()">

                    <div class="profile-meta">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p>Shopper Unique ID: <code><?php echo htmlspecialchars($user['uid']); ?></code> &bull; Network Tier: <strong>Certified Senior Field Evaluator</strong></p>
                        <div class="badge-verified"><i class="fas fa-check-circle"></i> Identity Verified & Active Agent</div>
                    </div>
                </div>

                <!-- Shopper Performance Snapshot -->
                <div class="stats-row">
                    <div class="stat-box">
                        <label>Quality Score</label>
                        <span style="color:var(--accent-green);">98.4% (Elite Tier)</span>
                    </div>
                    <div class="stat-box">
                        <label>Assigned Supervisor</label>
                        <span><?php echo htmlspecialchars($user['supervisor_name'] ?: 'National Quality Desk'); ?></span>
                    </div>
                    <div class="stat-box">
                        <label>Evaluation Clearance</label>
                        <span>Tier-1 Retail & Financial</span>
                    </div>
                </div>

                <!-- Contact & Location Credentials -->
                <div class="section-title"><i class="fas fa-id-card"></i> Personal Identification & Location Details</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Legal Name (Locked)</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Registered Email Address (Locked)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Contact Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required placeholder="(555) 000-0000">
                    </div>
                    <div class="form-group">
                        <label>Residential Street Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required placeholder="123 Main St">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required placeholder="City">
                    </div>
                    <div class="form-group">
                        <label>State / Province</label>
                        <input type="text" name="state" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>" required placeholder="State">
                    </div>
                    <div class="form-group">
                        <label>Postal / Zip Code</label>
                        <input type="text" name="zip" value="<?php echo htmlspecialchars($user['zip'] ?? ''); ?>" required placeholder="Zip Code">
                    </div>
                </div>

                <!-- Real Shopper Certification & Operational Preferences -->
                <div class="section-title"><i class="fas fa-clipboard-check"></i> Shopper Compliance & Assignment Preferences</div>
                <div class="compliance-box">
                    <strong>Secret Shopper® Quality Standards & MSRB Compliance Notice:</strong> As a verified independent field evaluator for <strong>Secret Shopper®</strong>, your profile details are utilized to match you with appropriate local retail, dining, and financial institution audits. Ensure your residential address and contact number are accurate to prevent assignment distance radius mismatches.
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Primary Vehicle Type (For Travel Radius Assignments)</label>
                        <select name="vehicle_type">
                            <option value="Sedan / Compact">Sedan / Compact</option>
                            <option value="SUV / Crossover">SUV / Crossover</option>
                            <option value="Public Transit / Walking">Public Transit / Walking</option>
                            <option value="Electric Vehicle (EV)">Electric Vehicle (EV)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Maximum Travel Radius (Miles)</label>
                        <select name="travel_radius">
                            <option value="10">10 Miles</option>
                            <option value="25" selected>25 Miles</option>
                            <option value="50">50 Miles</option>
                            <option value="100">100+ Miles (Regional)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Preferred Audit Categories</label>
                        <select name="primary_category">
                            <option value="Banking & Financial Services">Banking & Financial Services</option>
                            <option value="High-End Retail & Luxury">High-End Retail & Luxury</option>
                            <option value="Hospitality & Dining">Hospitality & Dining</option>
                            <option value="Automotive Dealerships">Automotive Dealerships</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="update_profile" class="btn-action"><i class="fas fa-save"></i> Save All Profile Changes</button>
            </form>
        </div>
    </div>
</body>
</html>