<?php
require_once '../db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure Admin Authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$successMsg = '';
$errorMsg = '';

// Handle New Task Creation (Admin can assign multiple tasks to any user)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $targetUserUid = trim($_POST['user_uid']);
    $storeName = trim($_POST['store_name']);
    $assignedTask = trim($_POST['assigned_task']);

    if (!empty($targetUserUid) && !empty($storeName) && !empty($assignedTask)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_uid, store_name, assigned_task, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$targetUserUid, $storeName, $assignedTask]);
        $successMsg = "Task successfully assigned!";
    } else {
        $errorMsg = "Please fill in all required fields to assign a task.";
    }
}

// Handle Task Deletion
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$delId]);
    header("Location: tasks.php");
    exit;
}

// Fetch all users for the assignment dropdown selector
$usersStmt = $pdo->query("SELECT uid, name, email FROM users ORDER BY name ASC");
$allUsers = $usersStmt->fetchAll();

// Fetch all tasks with corresponding user details
$taskStmt = $pdo->query("SELECT tasks.*, users.name as shopper_name, users.email as shopper_email FROM tasks LEFT JOIN users ON tasks.user_uid = users.uid ORDER BY tasks.id DESC");
$tasks = $taskStmt->fetchAll();

// Header include check
if (file_exists('header.php')) {
    include 'header.php';
} elseif (file_exists('../header.php')) {
    include '../header.php';
} else {
    echo '<!DOCTYPE html><html><head><title>Admin Tasks</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body style="background:#f1f4f8; margin:0; padding:0;">';
}
?>

<div style="padding: 25px; max-width: 1200px; margin: 0 auto; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <h1 style="font-size: 20px; color: #002e5b; margin-bottom: 5px;">Manage & Assign Store Evaluation Tasks</h1>
    <p style="color: #475569; font-size: 13px; margin-bottom: 25px;">Assign multiple tasks across different users and inspect shopper proof submissions.</p>

    <?php if ($successMsg): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;"><?php echo $successMsg; ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;"><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <!-- Assign New Task Form Panel -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
        <h3 style="font-size: 16px; color: #002e5b; margin-top: 0; margin-bottom: 15px;"><i class="fas fa-plus-circle"></i> Assign New Task to Shopper</h3>
        <form method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Select Shopper / User</label>
                    <select name="user_uid" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required>
                        <option value="">-- Choose Shopper --></option>
                        <?php foreach($allUsers as $u): ?>
                            <option value="<?php echo htmlspecialchars($u['uid']); ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Store Name & Location</label>
                    <input type="text" name="store_name" placeholder="e.g., Walmart Supercenter #124" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Detailed Instructions / Evaluation Criteria</label>
                <textarea name="assigned_task" rows="3" placeholder="Specify interaction times, purchase requirements, and evaluation guidelines..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required></textarea>
            </div>
            <button type="submit" name="assign_task" style="background: #002e5b; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px;"><i class="fas fa-paper-plane"></i> Assign Task Now</button>
        </form>
    </div>

    <h2 style="font-size: 18px; color: #002e5b; margin-bottom: 15px;">All Assigned Tasks & Submissions</h2>
    <?php if(empty($tasks)): ?>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; color: #475569; font-size: 13px;">No tasks found in the database.</div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach($tasks as $t): ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h3 style="font-size: 16px; color: #002e5b; margin-bottom: 3px;"><?php echo htmlspecialchars($t['store_name']); ?></h3>
                            <span style="font-size: 12px; color: #475569;"><i class="fas fa-user"></i> Shopper: <strong><?php echo htmlspecialchars($t['shopper_name'] ?? 'Unassigned'); ?></strong> (<?php echo htmlspecialchars($t['shopper_email']); ?>)</span>
                        </div>
                        <span style="font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 12px; background: <?php echo ($t['status'] == 'Submitted') ? 'rgba(68,132,241,0.1); color:#4484f1;' : 'rgba(245,158,11,0.1); color:#f59e0b;'; ?>"><?php echo $t['status']; ?></span>
                    </div>

                    <div style="background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 13px;">
                        <strong style="color: #002e5b; display: block; margin-bottom: 3px;">Assigned Instructions:</strong>
                        <p style="color: #1e293b; margin:0;"><?php echo nl2br(htmlspecialchars($t['assigned_task'])); ?></p>
                    </div>

                    <?php if($t['status'] === 'Submitted'): ?>
                        <div style="background: rgba(46, 125, 50, 0.04); border: 1px solid rgba(46, 125, 50, 0.2); padding: 14px; border-radius: 6px; margin-bottom: 12px; font-size: 13px;">
                            <strong style="color: #2e7d32; display: block; margin-bottom: 4px;"><i class="fas fa-check-circle"></i> Shopper Review Notes:</strong>
                            <p style="color: #1e293b; margin-bottom: 12px;"><?php echo nl2br(htmlspecialchars($t['review'] ?? 'No notes provided.')); ?></p>

                            <?php 
                            $attachedImages = json_decode($t['images'] ?? '[]', true);
                            if(!empty($attachedImages)): 
                            ?>
                                <div>
                                    <strong style="display: block; margin-bottom: 6px; color: #002e5b; font-size: 12px;"><i class="fas fa-images"></i> Attached Proof Images (<?php echo count($attachedImages); ?>):</strong>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <?php foreach($attachedImages as $imgPath): ?>
                                            <a href="../<?php echo htmlspecialchars($imgPath); ?>" target="_blank" title="Click to view full size">
                                                <img src="../<?php echo htmlspecialchars($imgPath); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff;">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 12px; color: #64748b; font-style: italic;">No images attached to this submission.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                        <a href="tasks.php?delete=<?php echo $t['id']; ?>" onclick="return confirm('Are you sure you want to delete this task?');" style="color: #ef4444; font-size: 12px; text-decoration: none; font-weight: 600; padding: 6px 10px;"><i class="fas fa-trash-alt"></i> Delete Task</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body></html>