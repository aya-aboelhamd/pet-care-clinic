<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/AdminDashboardModel.php';

$database = new Database();
$db = $database->getConnection();
$adminModel = new AdminDashboardModel($db);

$user_id = $_SESSION['user_id'];

$total_users = $adminModel->getTotalUsers();
$open_disputes = $adminModel->getOpenDisputesCount();
$notifications_today = $adminModel->getNotificationsTodayCount();
$recent_alerts = $adminModel->getRecentAlerts();
$recent_logs = $adminModel->getRecentLogs();

$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin_data['name'] ?? 'Admin';
$admin_email = $admin_data['email'] ?? 'admin@petlor.com';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; --alert-red: #E53935; }
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
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.2rem; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 45px; height: 45px; background: #DFF0E2; color: var(--primary-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-icon.red { background: #FFF1F0; color: var(--alert-red); }
        .stat-info p { font-size: 0.65rem; color: #999; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
        .stat-info h3 { font-size: 1.4rem; font-weight: 700; }
        .admin-main { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .card-title { font-size: 1rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .alert-item { border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; margin-bottom: 10px; position: relative;}
        .alert-info strong { display: block; font-size: 0.85rem; color: var(--alert-red); margin-bottom: 4px;}
        .alert-info span { font-size: 0.7rem; color: var(--text-gray); display: block;}
        .severity-badge { position: absolute; top: 12px; right: 12px; font-size: 0.65rem; padding: 2px 8px; border-radius: 4px; font-weight: 700; }
        .severity-HIGH { background: #FFEBEE; color: #C62828; }
        .severity-MEDIUM { background: #FFF3E0; color: #E65100; }
        .severity-LOW { background: #E8F5E9; color: #2E7D32; }
        .shortcuts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.5rem; }
        .shortcut-btn { background: white; border: 1px solid var(--border-color); padding: 12px; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; font-weight: 500; cursor: pointer; text-decoration: none; color: inherit; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item active"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
            <a href="productRecalls.php" class="nav-item"><i class="fa-solid fa-rotate-left"></i> Product Recalls</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Welcome back 👋</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div><strong><?= htmlspecialchars($admin_name) ?></strong><br><span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($admin_email) ?></span></div>
                <a href="../Auth/logout.php" style="color: #ccc; margin-left: 10px; text-decoration: none;"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Admin Dashboard</h2>
                <p>Platform health and operational overview.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-users-group"></i></div>
                    <div class="stat-info"><p>Total Users</p><h3><?= $total_users ?></h3></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="stat-info"><p>Open Disputes</p><h3><?= $open_disputes ?></h3></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-bell"></i></div>
                    <div class="stat-info"><p>Notifications</p><h3><?= $notifications_today ?></h3><div style="font-size: 0.6rem; color: #999;">Sent today</div></div>
                </div>

            </div>

            <div class="admin-main">
                <div class="card">
                    <div class="card-title"><i class="fa-solid fa-virus-covid" style="color: var(--alert-red);"></i> Recent Disease Alerts</div>
                    <p style="font-size: 0.8rem; color: #666; margin-bottom: 1rem;">Latest warnings sent by veterinarians.</p>
                    
                    <?php if (empty($recent_alerts)): ?>
                        <div style="text-align: center; color: #999; padding: 20px;">No recent alerts.</div>
                    <?php else: ?>
                        <?php foreach ($recent_alerts as $alert): ?>
                            <div class="alert-item">
                                <span class="severity-badge severity-<?= $alert['severity'] ?>"><?= $alert['severity'] ?></span>
                                <div class="alert-info">
                                    <strong><?= htmlspecialchars($alert['disease_name']) ?></strong>
                                    <span>Vet: Dr. <?= htmlspecialchars($alert['vet_name']) ?> | Region: <?= htmlspecialchars($alert['target_region']) ?></span>
                                    <span style="color: #aaa; margin-top: 3px;"><i class="fa-regular fa-clock"></i> <?= date('M j, g:i A', strtotime($alert['sent_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-title">Recent System Logs</div>
                    <div style="font-size: 0.75rem; line-height: 2;">
                        <?php if(empty($recent_logs)): ?>
                            <div style="color: #999;">No logs available.</div>
                        <?php else: ?>
                            <?php foreach($recent_logs as $log): ?>
                                <div style="border-bottom: 1px solid #f5f5f5; padding-bottom: 5px;">
                                    • <?= htmlspecialchars($log['action']) ?> 
                                    <span style="color: #aaa;">(<?= date('H:i', strtotime($log['created_at'])) ?>)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="shortcuts">
                <a href="manageUsers.php" class="shortcut-btn"><i class="fa-solid fa-user-gear"></i> Manage users</a>
                <a href="manageDisputes.php" class="shortcut-btn"><i class="fa-solid fa-shield-check"></i> Review disputes</a>
                <a href="auditLogs.php" class="shortcut-btn"><i class="fa-solid fa-clock-rotate-left"></i> Audit logs</a>
            </div>
        </div>
    </div>
</body>
</html>