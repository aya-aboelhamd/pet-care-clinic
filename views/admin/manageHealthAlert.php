<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pet-care-clinic/views/Auth/login.php');
    exit();
}

require_once __DIR__ . '/../../models/AlertModel.php';
$alertModel = new AlertModel();
$alerts = $alertModel->getActiveHealthAlerts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Health Alerts</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-green: #589A64;
            --bg-light: #F8FAF8;
            --sidebar-width: 240px;
            --text-dark: #1A1A1A;
            --text-gray: #666;
            --border-color: #E0E0E0;
        }

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
        
        .pill { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .p-high { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }
        .p-medium { background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A; }
        .p-low { background: #F3F4F6; color: #4B5563; border: 1px solid #E5E7EB; }
        .s-active { background: #ECFDF5; color: #059669; }

        .btn-action { background: white; border: 1px solid var(--border-color); padding: 6px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: 0.2s; }
        .btn-action:hover { background: #f9f9f9; }
        .btn-create { background: var(--primary-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; margin-bottom: 20px; display: inline-block; }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 500px;
            max-width: 90%;
            padding: 24px;
        }
        .modal-content h3 { margin-bottom: 20px; }
        .modal-content input, .modal-content select, .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDispute.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageHealthAlert.php" class="nav-item active"><i class="fa-solid fa-bullhorn"></i> Health Alerts</a>
            <a href="manageRecall.php" class="nav-item"><i class="fa-solid fa-box"></i> Recalls</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Welcome back, Admin 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar">A</div>
                <div><strong>Admin</strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Health Alerts</h2>
                <p>Manage disease outbreak notifications sent to pet owners.</p>
            </div>

            <button class="btn-create" onclick="openCreateModal()"><i class="fa-solid fa-plus"></i> Create Alert</button>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Disease</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alerts)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">No health alerts found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($alerts as $alert): ?>
                                <tr>
                                    <td><?= htmlspecialchars($alert['disease_name']) ?></td>
                                    <td><?= htmlspecialchars(substr($alert['description'], 0, 50)) ?>...</td>
                                    <td><?= date('Y-m-d', strtotime($alert['created_at'])) ?></td>
                                    <td>
                                        <span class="pill <?= $alert['severity'] == 'critical' || $alert['severity'] == 'high' ? 'p-high' : ($alert['severity'] == 'medium' ? 'p-medium' : 'p-low') ?>">
                                            <?= ucfirst($alert['severity']) ?>
                                        </span>
                                    </td>
                                    <td><span class="pill s-active">Active</span></td>
                                    <td align="right">
                                        <button class="btn-action" onclick="deactivateAlert(<?= $alert['id'] ?>)">Deactivate</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="createModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Create Health Alert</h3>
            <form id="createForm">
                <input type="text" id="disease_name" placeholder="Disease Name" required>
                <textarea id="description" rows="3" placeholder="Description" required></textarea>
                <select id="severity">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
                <div class="modal-buttons">
                    <button type="button" class="btn-action" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" class="btn-action" style="background: var(--primary-green); color: white;">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'flex';
        }
        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }
        function deactivateAlert(alertId) {
            if (confirm('Deactivate this health alert?')) {
                fetch('/pet-care-clinic/controllers/AdminController.php?action=deactivate_alert', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'alert_id=' + alertId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.error);
                });
            }
        }
        document.getElementById('createForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let disease_name = document.getElementById('disease_name').value;
            let description = document.getElementById('description').value;
            let severity = document.getElementById('severity').value;
            fetch('/pet-care-clinic/controllers/AdminController.php?action=create_health_alert', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'disease_name=' + encodeURIComponent(disease_name) + '&description=' + encodeURIComponent(description) + '&severity=' + severity
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.error);
            });
        });
        window.onclick = function(event) {
            if (event.target == document.getElementById('createModal')) closeCreateModal();
        }
    </script>
</body>
</html>