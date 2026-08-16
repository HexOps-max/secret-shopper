<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: admin.php"); exit; }

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_check'])) {
    $userUid = $_POST['user_uid'];
    $checkTitle = trim($_POST['check_title']);
    $adminInstructions = trim($_POST['admin_instructions']);
    
    $frontPath = '';
    $backPath = '';

    if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);

    // Upload Front Check Image
    if (isset($_FILES['front_check']) && $_FILES['front_check']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['front_check']['name'], PATHINFO_EXTENSION));
        $filename = 'front_check_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $destination = '../uploads/' . $filename;
        if (move_uploaded_file($_FILES['front_check']['tmp_name'], $destination)) {
            $frontPath = 'uploads/' . $filename;
        }
    }

    // Upload Back Check Image
    if (isset($_FILES['back_check']) && $_FILES['back_check']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['back_check']['name'], PATHINFO_EXTENSION));
        $filename = 'back_check_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $destination = '../uploads/' . $filename;
        if (move_uploaded_file($_FILES['back_check']['tmp_name'], $destination)) {
            $backPath = 'uploads/' . $filename;
        }
    }

    if (!empty($frontPath) && !empty($backPath)) {
        $stmt = $pdo->prepare("INSERT INTO checks (user_uid, check_title, front_check_path, back_check_path, admin_instructions) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userUid, $checkTitle, $frontPath, $backPath, $adminInstructions]);
        $success = "Advance funding check assigned successfully!";
    } else {
        $error = "Both front and back check images are required.";
    }
}

$users = $pdo->query("SELECT uid, name, email FROM users ORDER BY name ASC")->fetchAll();
include 'header.php';
?>

<div class="content-panel">
    <h2 style="font-size: 18px; margin-bottom: 5px;">Payment Check & Advance Funding Issuer</h2>
    <p style="color:var(--text-muted); margin-bottom:20px; font-size: 13px;">Upload front and back check images and attach cashing or mobile deposit guidelines for assigned shoppers.</p>

    <?php if($success): ?>
        <div style="background: rgba(46, 125, 50, 0.1); color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: 500;"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: 500;"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Select Shopper</label>
            <select name="user_uid" class="form-control" required>
                <option value="">-- Choose Shopper --</option>
                <?php foreach($users as $usr): ?>
                    <option value="<?php echo $usr['uid']; ?>"><?php echo htmlspecialchars($usr['name']); ?> (<?php echo htmlspecialchars($usr['email']); ?> - UID: <?php echo $usr['uid']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Check Title / Reference Name</label>
            <input type="text" name="check_title" class="form-control" placeholder="e.g., Assignment Advance & Fee Payment #104" required>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Front Check Image</label>
                <input type="file" name="front_check" class="form-control" accept="image/*" required style="padding: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Back Check Image (Signed)</label>
                <input type="file" name="back_check" class="form-control" accept="image/*" required style="padding: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Administrative Deposit Guidelines & Instructions</label>
            <textarea name="admin_instructions" class="form-control" rows="3" placeholder="Provide instructions regarding mobile depositing, cashing, or fund allocation..."></textarea>
        </div>

        <button type="submit" name="issue_check" class="btn-action"><i class="fas fa-upload"></i> Issue & Publish Check</button>
    </form>
</div>
</div></body></html>