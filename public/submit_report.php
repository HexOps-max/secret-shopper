<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_uid']) || !isset($_GET['task_id'])) {
    header("Location: dashboard.php");
    exit;
}

$userUid = $_SESSION['user_uid'];
$taskId = $_GET['task_id'];

// Verify task belongs to user
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_uid = ?");
$stmt->execute([$taskId, $userUid]);
$task = $stmt->fetch();

if (!$task) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewText = trim($_POST['review_text']);
    
    if (empty($reviewText)) {
        $error = "Please provide your detailed store evaluation review.";
    } else {
        // Update task status and store review notes
        $updateStmt = $pdo->prepare("UPDATE tasks SET status = 'Submitted', review = ? WHERE id = ?");
        $updateStmt->execute([$reviewText, $taskId]);
        
        // Optional: Telegram Notification integration can be appended here
        
        header("Location: dashboard.php?submitted=true");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Store Evaluation Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --primary-blue: #002e5b; --accent-blue: #4484f1; --bg-light: #f1f4f8; --text-main: #1e293b; --text-muted: #475569; --border-color: #e2e8f0; }
        body { background-color: var(--bg-light); color: var(--text-main); font-family: Arial, sans-serif; padding: 30px; display: flex; justify-content: center; }
        .form-card { background: white; max-width: 600px; width: 100%; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; margin-top: 5px; margin-bottom: 15px; outline: none; }
        .btn { background: var(--primary-blue); color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <div class="form-card">
        <h2 style="color: var(--primary-blue); margin-bottom: 5px;">Store Evaluation Report</h2>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Target: <strong><?php echo htmlspecialchars($task['store_name']); ?></strong></p>

        <?php if($error): ?>
            <div style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label style="font-size: 12px; font-weight: bold; color: var(--text-muted);">Detailed Evaluation & Experience Report</label>
            <textarea name="review_text" class="form-control" rows="6" placeholder="Describe customer service interactions, store cleanliness, speed of service, and item availability..." required></textarea>

            <label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display: block; margin-top: 10px;">Upload Evidentiary Photos (Up to 4 images)</label>
            <input type="file" name="evidence_photos[]" class="form-control" accept="image/*" multiple style="padding: 8px;">

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Submit Report for Audit</button>
                <a href="dashboard.php" style="background: #e2e8f0; color: var(--text-main); padding: 12px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>