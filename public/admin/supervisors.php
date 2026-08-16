<?php
require_once '../db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$successMsg = '';
$errorMsg = '';

// Handle Adding a New Supervisor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supervisor'])) {
    $name = trim($_POST['supervisor_name']);
    $email = trim($_POST['supervisor_email']);
    $phone = trim($_POST['supervisor_phone']);

    if (!empty($name) && !empty($email)) {
        $stmt = $pdo->prepare("INSERT INTO supervisors (supervisor_name, supervisor_email, supervisor_phone) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $phone]);
        $successMsg = "Supervisor added successfully!";
    } else {
        $errorMsg = "Supervisor name and email are required.";
    }
}

// Handle Editing an Existing Supervisor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_supervisor'])) {
    $id = $_POST['supervisor_id'];
    $name = trim($_POST['supervisor_name']);
    $email = trim($_POST['supervisor_email']);
    $phone = trim($_POST['supervisor_phone']);

    if (!empty($id) && !empty($name) && !empty($email)) {
        $stmt = $pdo->prepare("UPDATE supervisors SET supervisor_name = ?, supervisor_email = ?, supervisor_phone = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $id]);
        $successMsg = "Supervisor updated successfully!";
    } else {
        $errorMsg = "Supervisor name and email are required for updates.";
    }
}

// Handle Deleting a Supervisor
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM supervisors WHERE id = ?");
    $stmt->execute([$delId]);
    header("Location: supervisors.php");
    exit;
}

// Fetch all supervisors
$supStmt = $pdo->query("SELECT * FROM supervisors ORDER BY id DESC");
$supervisors = $supStmt->fetchAll();

if (file_exists('header.php')) {
    include 'header.php';
} elseif (file_exists('../header.php')) {
    include '../header.php';
} else {
    echo '<!DOCTYPE html><html><head><title>Manage Supervisors</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body style="background:#f1f4f8; margin:0; padding:0;">';
}
?>

<div style="padding: 25px; max-width: 1200px; margin: 0 auto; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <h1 style="font-size: 20px; color: #002e5b; margin-bottom: 5px;">Manage Project Supervisors</h1>
    <p style="color: #475569; font-size: 13px; margin-bottom: 25px;">Add and edit support contacts. New user signups will automatically and randomly be assigned one of these supervisors.</p>

    <?php if ($successMsg): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: 500;"><i class="fas fa-check-circle"></i> <?php echo $successMsg; ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: 500;"><i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <!-- Add Supervisor Form -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
        <h3 style="font-size: 16px; color: #002e5b; margin-top: 0; margin-bottom: 15px;"><i class="fas fa-user-tie"></i> Add New Supervisor</h3>
        <form method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Supervisor Name</label>
                    <input type="text" name="supervisor_name" placeholder="e.g., Sarah Jenkins" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Email Address</label>
                    <input type="email" name="supervisor_email" placeholder="sarah@secretshopper.com" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Phone Number (Optional)</label>
                    <input type="text" name="supervisor_phone" placeholder="+1 (555) 019-2834" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                </div>
            </div>
            <button type="submit" name="add_supervisor" style="background: #002e5b; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px;"><i class="fas fa-plus"></i> Save Supervisor</button>
        </form>
    </div>

    <h2 style="font-size: 18px; color: #002e5b; margin-bottom: 15px;">Active Supervisors List (<?php echo count($supervisors); ?>)</h2>
    <?php if(empty($supervisors)): ?>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; color: #475569; font-size: 13px;">No supervisors added yet. Please add at least one supervisor.</div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
            <?php foreach($supervisors as $s): ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); display:flex; flex-direction:column; justify-content:space-between;">
                    <div>
                        <h3 style="font-size: 16px; color: #002e5b; margin-top:0; margin-bottom: 8px;"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($s['supervisor_name']); ?></h3>
                        <p style="font-size: 13px; color: #475569; margin: 4px 0;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($s['supervisor_email']); ?></p>
                        <?php if(!empty($s['supervisor_phone'])): ?>
                            <p style="font-size: 13px; color: #475569; margin: 4px 0;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($s['supervisor_phone']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <button onclick="openEditModal('<?php echo $s['id']; ?>', '<?php echo addslashes($s['supervisor_name']); ?>', '<?php echo addslashes($s['supervisor_email']); ?>', '<?php echo addslashes($s['supervisor_phone'] ?? ''); ?>')" style="background: #f1f4f8; color: #002e5b; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer;"><i class="fas fa-edit"></i> Edit</button>
                        <a href="supervisors.php?delete=<?php echo $s['id']; ?>" onclick="return confirm('Are you sure you want to delete this supervisor?');" style="color: #ef4444; font-size: 12px; text-decoration: none; font-weight: 600;"><i class="fas fa-trash-alt"></i> Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Supervisor Modal Popup -->
<div id="editModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; width: 100%; max-width: 450px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); position: relative;">
        <span onclick="closeEditModal()" style="position: absolute; right: 20px; top: 15px; font-size: 20px; cursor: pointer; color: #64748b;">&times;</span>
        <h3 style="color: #002e5b; margin-top: 0; margin-bottom: 20px;"><i class="fas fa-user-edit"></i> Edit Supervisor Details</h3>
        <form method="POST">
            <input type="hidden" name="edit_supervisor" value="1">
            <input type="hidden" name="supervisor_id" id="editId">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Supervisor Name</label>
                <input type="text" name="supervisor_name" id="editName" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Email Address</label>
                <input type="email" name="supervisor_email" id="editEmail" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px;">Phone Number</label>
                <input type="text" name="supervisor_phone" id="editPhone" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
            </div>
            <button type="submit" style="background: #002e5b; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; width: 100%;"><i class="fas fa-save"></i> Update Supervisor</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, email, phone) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
</body></html>