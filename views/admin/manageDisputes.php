<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/ManageDisputesModel.php';

$database = new Database();
$db = $database->getConnection();
$disputesModel = new ManageDisputesModel($db);

$admin_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_name = $stmt->fetchColumn() ?: 'Admin';

$disputes = $disputesModel->getAllDisputes();

function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'resolved': return 's-resolved';
        case 'investigating': return 's-investigating';
        default: return 's-open';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Disputes</title>
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
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.95rem; }
        .table-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #fff; padding: 18px 15px; font-size: 0.85rem; font-weight: 500; color: var(--text-gray); border-bottom: 1px solid var(--border-color); }
        td { padding: 18px 15px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .pill { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: capitalize; }
        .s-open { background: #FFEDD5; color: #EA580C; }
        .s-investigating { background: #E0F2FE; color: #0284C7; }
        .s-resolved { background: #ECFDF5; color: #059669; }
        .btn-action { background: white; border: 1px solid var(--border-color); color: var(--text-dark); padding: 6px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-action:hover { background: #f9f9f9; }
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
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Welcome back 👋</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div><strong><?= htmlspecialchars($admin_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Disputes</h2>
                <p>Review and resolve user-reported issues.</p>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($disputes)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #999; padding: 30px;">No disputes found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($disputes as $d): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($d['owner_name']) ?></strong></td>
                                    <td><?= htmlspecialchars(strlen($d['reason']) > 50 ? substr($d['reason'], 0, 50) . '...' : $d['reason']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($d['created_at'])) ?></td>
                                    <td><span class="pill <?= getStatusClass($d['status']) ?>"><?= htmlspecialchars($d['status']) ?></span></td>
                                    <td align="right">
                                        <a href="viewDispute.php?id=<?= $d['id'] ?>" class="btn-action">Open</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>