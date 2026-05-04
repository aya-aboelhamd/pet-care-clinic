<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Disputes</title>
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

        /* Table Design based on image_974e5c.png */
        .table-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #fff; padding: 18px 15px; font-size: 0.85rem; font-weight: 500; color: var(--text-gray); border-bottom: 1px solid var(--border-color); }
        td { padding: 18px 15px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        
        /* Status & Priority Pills */
        .pill { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        
        /* Priorities */
        .p-medium { background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A; }
        .p-high { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }
        .p-low { background: #F3F4F6; color: #4B5563; border: 1px solid #E5E7EB; }

        /* Statuses */
        .s-open { background: #FFEDD5; color: #EA580C; }
        .s-investigating { background: #E0F2FE; color: #0284C7; }
        .s-resolved { background: #ECFDF5; color: #059669; }

        .btn-action { background: white; border: 1px solid var(--border-color); padding: 6px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: 0.2s; }
        .btn-action:hover { background: #f9f9f9; }

    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item active"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
        </div>
    </div>

    <!-- Main Content -->
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
                            <th>Priority</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sarah Johnson</td>
                            <td>Refund for cancelled grooming</td>
                            <td>2026-04-20</td>
                            <td><span class="pill p-medium">Medium</span></td>
                            <td><span class="pill s-open">Open</span></td>
                            <td align="right"><button class="btn-action">Open</button></td>
                        </tr>
                        <tr>
                            <td>Tom Hardy</td>
                            <td>Provider no-show</td>
                            <td>2026-04-18</td>
                            <td><span class="pill p-high">High</span></td>
                            <td><span class="pill s-investigating">Investigating</span></td>
                            <td align="right"><button class="btn-action">Open</button></td>
                        </tr>
                        <tr>
                            <td>Jane Doe</td>
                            <td>Wrong product shipped</td>
                            <td>2026-04-10</td>
                            <td><span class="pill p-low">Low</span></td>
                            <td><span class="pill s-resolved">Resolved</span></td>
                            <td align="right"><button class="btn-action">Open</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>

