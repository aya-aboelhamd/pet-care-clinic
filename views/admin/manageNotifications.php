<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Notifications</title>
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
            --alert-red: #DC2626;
            --alert-blue: #0284C7;
            --alert-orange: #D97706;
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

        /* Layout Grid */
        .notif-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem; align-items: start; }

        /* Form Card */
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .card h4 { margin-bottom: 1.5rem; font-size: 1rem; font-weight: 600; }
        
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; }
        .form-group input, .form-group textarea { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; font-size: 0.9rem; outline: none; transition: border 0.2s; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--primary-green); }
        .form-group textarea { height: 120px; resize: none; }

        .btn-send { width: 100%; background: var(--primary-green); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 1rem; }

        /* Recent Notifications List */
        .notif-item { border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; margin-bottom: 12px; display: flex; align-items: flex-start; gap: 15px; position: relative; }
        .notif-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        
        /* Icon Colors */
        .icon-red { color: var(--alert-red); }
        .icon-green { color: var(--primary-green); }
        .icon-blue { color: var(--alert-blue); }
        .icon-orange { color: var(--alert-orange); }

        .notif-content { flex-grow: 1; }
        .notif-content strong { display: block; font-size: 0.95rem; margin-bottom: 2px; }
        .notif-content p { font-size: 0.85rem; color: var(--text-gray); }
        
        .notif-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
        .time-stamp { font-size: 0.75rem; color: #999; }
        .new-badge { background: #E0F2FE; color: #0369A1; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; }

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
            <a href="manageNotifications.php" class="nav-item active"><i class="fa-solid fa-bullhorn"></i> Notifications</a>
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
                <div><strong>Admin</strong><br><span style="font-size: 0.75rem; color: #999;">admin@petlor.com</span></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Notifications</h2>
                <p>Broadcast announcements and review recent system messages.</p>
            </div>

            <div class="notif-grid">
                <!-- Broadcast New Card -->
                <div class="card">
                    <h4>Broadcast new</h4>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" placeholder="Scheduled maintenance">
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea placeholder="Petlor will be undergoing maintenance..."></textarea>
                    </div>
                    <button class="btn-send"><i class="fa-solid fa-paper-plane"></i> Send</button>
                </div>

                <!-- Recent Notifications List -->
                <div class="card">
                    <h4>Recent notifications</h4>
                    
                    <div class="notif-item">
                        <div class="notif-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="notif-content">
                            <strong>Vaccination Overdue</strong>
                            <p>Bordetella for Max is overdue.</p>
                        </div>
                        <div class="notif-meta">
                            <span class="time-stamp">2h ago</span>
                            <span class="new-badge">New</span>
                        </div>
                    </div>

                    <div class="notif-item">
                        <div class="notif-icon icon-green"><i class="fa-regular fa-circle-check"></i></div>
                        <div class="notif-content">
                            <strong>Booking Confirmed</strong>
                            <p>Walking session on Apr 28 confirmed.</p>
                        </div>
                        <div class="notif-meta">
                            <span class="time-stamp">5h ago</span>
                            <span class="new-badge">New</span>
                        </div>
                    </div>

                    <div class="notif-item">
                        <div class="notif-icon icon-blue"><i class="fa-solid fa-wave-square"></i></div>
                        <div class="notif-content">
                            <strong>Prescription Ready</strong>
                            <p>Dr. Lee issued a new prescription for Max.</p>
                        </div>
                        <div class="notif-meta">
                            <span class="time-stamp">1d ago</span>
                        </div>
                    </div>

                    <div class="notif-item">
                        <div class="notif-icon icon-orange"><i class="fa-solid fa-circle-exclamation"></i></div>
                        <div class="notif-content">
                            <strong>Allergy Warning</strong>
                            <p>A product in your cart contains Chicken.</p>
                        </div>
                        <div class="notif-meta">
                            <span class="time-stamp">2d ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

