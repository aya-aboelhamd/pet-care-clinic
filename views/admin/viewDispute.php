<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';

$database = new Database();
$db = $database->getConnection();

$admin_id = $_SESSION['user_id'];
$stmt_admin = $db->prepare("SELECT name FROM users WHERE id = ?");
$stmt_admin->execute([$admin_id]);
$admin_name = $stmt_admin->fetchColumn() ?: 'Admin';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $dispute_id = $_POST['dispute_id'];
    
    $update_stmt = $db->prepare("UPDATE disputes SET status = ? WHERE id = ?");
    $update_stmt->execute([$new_status, $dispute_id]);
    
    if ($new_status == 'Resolved') {
        $stmt_info = $db->prepare("
            SELECT d.owner_id, b.provider_id 
            FROM disputes d 
            LEFT JOIN booking b ON d.booking_id = b.id 
            WHERE d.id = ?
        ");
        $stmt_info->execute([$dispute_id]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
        
        if ($info) {
            $msg_owner = "Your dispute #" . $dispute_id . " has been resolved by the admin. Please check your email for further details.";
            $msg_provider = "A dispute regarding one of your recent bookings (Dispute #" . $dispute_id . ") has been resolved by the administration.";
            
            $notif_stmt = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Dispute Resolved', ?, 0)");
            
            if (!empty($info['owner_id'])) {
                $notif_stmt->execute([$info['owner_id'], $msg_owner]);
            }
            if (!empty($info['provider_id'])) {
                $notif_stmt->execute([$info['provider_id'], $msg_provider]);
            }
        }
    }
    
    header("Location: viewDispute.php?id=" . $dispute_id . "&success=1");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manageDisputes.php");
    exit();
}

$dispute_id = $_GET['id'];
$stmt = $db->prepare("
    SELECT d.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone, b.service_type 
    FROM disputes d 
    JOIN users u ON d.owner_id = u.id 
    LEFT JOIN booking b ON d.booking_id = b.id
    WHERE d.id = ?
");
$stmt->execute([$dispute_id]);
$dispute = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dispute) {
    echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h2>Dispute not found</h2><a href='manageDisputes.php'>Go Back</a></div>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - View Dispute</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; color: var(--text-dark); }
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; justify-content: space-between; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .content-padding { padding: 2rem; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--text-gray); text-decoration: none; font-weight: 500; font-size: 0.9rem; margin-bottom: 1rem; }
        
        .grid-container { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .card h3 { font-size: 1.1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        
        .info-group { margin-bottom: 1.5rem; }
        .info-group label { display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 5px; font-weight: 600; text-transform: uppercase; }
        .info-group p { font-size: 0.95rem; line-height: 1.5; }
        
        .status-box { background: #f9f9f9; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color); }
        .form-group { margin-bottom: 15px; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; }
        .btn-update { background: var(--primary-green); color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: 600; width: 100%; transition: 0.2s; }
        .alert-success { background: #DCFCE7; color: #16A34A; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #BBF7D0; }
        
        .pill { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; display: inline-block; text-transform: capitalize; }
        .s-open { background: #FFEDD5; color: #EA580C; }
        .s-investigating { background: #E0F2FE; color: #0284C7; }
        .s-resolved { background: #ECFDF5; color: #059669; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item active"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
            <a href="productRecalls.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Product Recalls</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Dispute Details</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div><strong><?= htmlspecialchars($admin_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <a href="manageDisputes.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Disputes</a>
            
            <div class="header-actions">
                <div class="page-header" style="margin-bottom: 0;">
                    <h2>Dispute #<?= htmlspecialchars($dispute['id']) ?></h2>
                    <p>Submitted on <?= date('F j, Y, g:i a', strtotime($dispute['created_at'])) ?></p>
                </div>
                <?php
                    $status_class = 's-open';
                    if(strtolower($dispute['status']) == 'investigating') $status_class = 's-investigating';
                    if(strtolower($dispute['status']) == 'resolved') $status_class = 's-resolved';
                ?>
                <span class="pill <?= $status_class ?>"><?= htmlspecialchars($dispute['status']) ?></span>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert-success"><i class="fa-solid fa-check"></i> Status updated successfully.</div>
            <?php endif; ?>

            <div class="grid-container">
                <div class="card">
                    <h3>Dispute Details</h3>
                    <div class="info-group">
                        <label>Reported By (Pet Owner)</label>
                        <p>
                            <strong><i class="fa-regular fa-user"></i> <?= htmlspecialchars($dispute['owner_name']) ?></strong><br>
                            <i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($dispute['owner_email']) ?><br>
                            <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($dispute['owner_phone'] ?? 'N/A') ?>
                        </p>
                    </div>
                    
                    <?php if(!empty($dispute['service_type'])): ?>
                    <div class="info-group">
                        <label>Related Service</label>
                        <p><?= htmlspecialchars($dispute['service_type']) ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="info-group">
                        <label>Complaint / Reason</label>
                        <p style="background: #f5f5f5; padding: 15px; border-radius: 8px; border: 1px solid #eee;">
                            <?= nl2br(htmlspecialchars($dispute['reason'])) ?>
                        </p>
                    </div>
                </div>

                <div class="card status-box">
                    <h3>Update Status</h3>
                    <form method="POST">
                        <input type="hidden" name="dispute_id" value="<?= $dispute['id'] ?>">
                        <div class="form-group">
                            <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:8px; color:#666;">Current Status</label>
                            <select name="status">
                                <option value="Open" <?= $dispute['status'] == 'Open' ? 'selected' : '' ?>>Open</option>
                                <option value="Investigating" <?= $dispute['status'] == 'Investigating' ? 'selected' : '' ?>>Investigating</option>
                                <option value="Resolved" <?= $dispute['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn-update">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>