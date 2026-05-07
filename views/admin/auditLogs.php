<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/AuditLogsModel.php';

$database = new Database();
$db = $database->getConnection();
$auditModel = new AuditLogsModel($db);

$admin_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_name = $stmt->fetchColumn() ?: 'Admin';

$logs = $auditModel->getAllLogs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Audit Logs</title>
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
        .filter-container { position: relative; margin-bottom: 2rem; }
        .filter-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        .filter-input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid var(--border-color); border-radius: 10px; outline: none; font-size: 0.9rem; background: white; }
        .table-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #fff; padding: 15px; font-size: 0.85rem; font-weight: 600; color: var(--text-gray); border-bottom: 1px solid var(--border-color); }
        td { padding: 15px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); color: #333; }
        .monospace { font-family: 'Courier New', Courier, monospace; color: #666; font-size: 0.85rem; }
        .action-text { font-weight: 600; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item active"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
            <a href="productRecalls.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Product Recalls</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Audit Logs</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div><strong><?= htmlspecialchars($admin_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Audit Logs</h2>
                <p>Tamper-evident record of platform activity.</p>
            </div>

            <div class="filter-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="filterInput" class="filter-input" placeholder="Filter by actor, action or target..." onkeyup="filterTable()">
            </div>

            <div class="table-card">
                <table id="logsTable">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Target</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($logs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #999; padding: 30px;">No logs found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($logs as $log): ?>
                                <tr>
                                    <td class="monospace"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                                    <td class="log-searchable"><?= htmlspecialchars($log['actor'] ?? 'System') ?></td>
                                    <td class="action-text log-searchable"><?= htmlspecialchars($log['action']) ?></td>
                                    <td class="log-searchable"><?= htmlspecialchars($log['target']) ?></td>
                                    <td class="monospace"><?= htmlspecialchars($log['ip_address']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterTable() {
            let input = document.getElementById("filterInput").value.toLowerCase();
            let table = document.getElementById("logsTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let textContent = tr[i].textContent || tr[i].innerText;
                if (textContent.toLowerCase().indexOf(input) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>