<?php
require_once '../db.php';
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$pendingTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'Pending'")->fetchColumn();
$totalChecks = $pdo->query("SELECT COUNT(*) FROM checks")->fetchColumn();
include 'header.php';
?>
<div class="card">
    <h2><i class="fas fa-chart-line" style="color: #4484f1;"></i> Platform Statistics</h2>
    <hr>
    <div style="display: flex; gap: 20px;">
        <div style="flex: 1; background: #162238; padding: 20px; border-radius: 8px; border: 1px solid #334155; text-align:center;">
            <div style="font-size: 28px; font-weight: bold; color: #4484f1;"><?php echo $totalUsers; ?></div>
            <div style="font-size: 13px; color: #94a3b8; margin-top: 5px;">Total Shoppers</div>
        </div>
        <div style="flex: 1; background: #162238; padding: 20px; border-radius: 8px; border: 1px solid #334155; text-align:center;">
            <div style="font-size: 28px; font-weight: bold; color: #4ade80;"><?php echo $totalTasks; ?></div>
            <div style="font-size: 13px; color: #94a3b8; margin-top: 5px;">Total Tasks Assigned</div>
        </div>
        <div style="flex: 1; background: #162238; padding: 20px; border-radius: 8px; border: 1px solid #334155; text-align:center;">
            <div style="font-size: 28px; font-weight: bold; color: #fbbf24;"><?php echo $pendingTasks; ?></div>
            <div style="font-size: 13px; color: #94a3b8; margin-top: 5px;">Pending Submissions</div>
        </div>
        <div style="flex: 1; background: #162238; padding: 20px; border-radius: 8px; border: 1px solid #334155; text-align:center;">
            <div style="font-size: 28px; font-weight: bold; color: #38bdf8;"><?php echo $totalChecks; ?></div>
            <div style="font-size: 13px; color: #94a3b8; margin-top: 5px;">Checks Uploaded</div>
        </div>
    </div>
</div>
</div></body></html>