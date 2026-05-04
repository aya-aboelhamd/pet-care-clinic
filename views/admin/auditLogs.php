<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Audit Logs</title>
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

        /* --- Sidebar --- */
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; justify-content: space-between; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }

        /* --- Main Content --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .content-padding { padding: 2rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.95rem; }

        /* Search/Filter Bar */
        .filter-container { position: relative; margin-bottom: 2rem; }
        .filter-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        .filter-input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid var(--border-color); border-radius: 10px; outline: none; font-size: 0.9rem; background: white; }

        /* Audit Table Design based on image_96f85a.png */
        .table-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #fff; padding: 15px; font-size: 0.85rem; font-weight: 600; color: var(--text-gray); border-bottom: 1px solid var(--border-color); }
        td { padding: 15px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); color: #333; }
        
        .monospace { font-family: 'Courier New', Courier, monospace; color: #666; font-size: 0.85rem; }
        .action-text { font-weight: 600; }

    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item active"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Audit Logs</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar">A</div>
                <div><strong>Admin</strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Audit Logs</h2>
                <p>Tamper-evident record of platform activity.</p>
            </div>

            <!-- Filter Bar as seen in image_96f85a.png -->
            <div class="filter-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="filter-input" placeholder="Filter by actor, action or target...">
            </div>

            <div class="table-card">
                <table>
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
                        <tr>
                            <td class="monospace">2026-04-25 14:22</td>
                            <td>admin@petlor.com</td>
                            <td class="action-text">Suspended user</td>
                            <td>user@example.com</td>
                            <td class="monospace">10.0.0.4</td>
                        </tr>
                        <tr>
                            <td class="monospace">2026-04-12 09:10</td>
                            <td>vet@petlor.com</td>
                            <td class="action-text">Created prescription</td>
                            <td>Pet #p1</td>
                            <td class="monospace">10.0.0.7</td>
                        </tr>
                        <tr>
                            <td class="monospace">2026-04-11 16:48</td>
                            <td>owner@petlor.com</td>
                            <td class="action-text">Booked service</td>
                            <td>Booking #b1</td>
                            <td class="monospace">10.0.0.2</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>

