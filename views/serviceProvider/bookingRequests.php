<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Booking Requests</title>
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
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            flex-shrink: 0;
            justify-content: space-between;
        }

        .sidebar-logo {
            padding: 0 1.5rem 2rem;
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item {
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.2s;
        }

        .nav-item.active {
            background-color: var(--primary-green);
            color: white;
            margin: 0 10px;
            border-radius: 8px;
        }

        /* --- Main Content --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }

        header {
            background: white;
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .content-padding { padding: 2rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }

        /* Table Card */
        .table-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--text-gray); font-size: 0.8rem; font-weight: 600; padding: 12px 10px; border-bottom: 1px solid var(--border-color); }
        td { padding: 15px 10px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .pet-cell { display: flex; align-items: center; gap: 10px; font-weight: 500; }
        
        /* Status Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-confirmed { background: #E8F5E9; color: #4CAF50; border: 1px solid #C8E6C9; }
        .badge-pending { background: #FFF3E0; color: #E65100; border: 1px solid #FFE0B2; }

        /* Action Buttons */
        .action-cell { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-action {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: white;
            transition: 0.2s;
        }
        .btn-accept:hover { background: #f8faf8; border-color: var(--primary-green); color: var(--primary-green); }
        .btn-decline:hover { background: #fff8f8; border-color: #ff5252; color: #ff5252; }

    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-logo">
                <i class="fa-solid fa-paw"></i> 
                <div>Petlor <br></div>
            </div>
            <a href="providerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="bookingRequests.php" class="nav-item active"><i class="fa-regular fa-calendar-check"></i> Booking Requests</a>
            <a href="qrCheckin.php" class="nav-item"><i class="fa-solid fa-qrcode"></i> QR Check-in</a>
            <a href="walkTracker.php" class="nav-item"><i class="fa-solid fa-shoe-prints"></i> Walk Tracker</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Provider Portal / <strong>Booking Requests</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell" style="position: relative;"><span style="position: absolute; top: -2px; right: -2px; width: 7px; height: 7px; background: red; border-radius: 50%;"></span></i>
                <div class="avatar">BW</div>
                <div><strong>Bella Walks Co.</strong><br><span style="font-size: 0.75rem; color: #999;">provider@petlor.com</span></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Booking Requests</h2>
                <p>Accept or decline incoming requests from pet owners.</p>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div class="pet-cell">🐕 Max</div></td>
                            <td>Walking</td>
                            <td>2026-04-28 · 08:30</td>
                            <td>—</td>
                            <td><span class="badge badge-confirmed">Confirmed</span></td>
                            <td>
                                <div class="action-cell">
                                    <button class="btn-action btn-accept"><i class="fa-solid fa-check"></i> Accept</button>
                                    <button class="btn-action btn-decline"><i class="fa-solid fa-xmark"></i> Decline</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><div class="pet-cell">🐈 Luna</div></td>
                            <td>Grooming</td>
                            <td>2026-05-02 · 14:00</td>
                            <td>—</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td>
                                <div class="action-cell">
                                    <button class="btn-action btn-accept"><i class="fa-solid fa-check"></i> Accept</button>
                                    <button class="btn-action btn-decline"><i class="fa-solid fa-xmark"></i> Decline</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><div class="pet-cell">🐕 Max</div></td>
                            <td>Boarding</td>
                            <td>2026-05-15 · 10:00</td>
                            <td>Allergic to chicken</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td>
                                <div class="action-cell">
                                    <button class="btn-action btn-accept"><i class="fa-solid fa-check"></i> Accept</button>
                                    <button class="btn-action btn-decline"><i class="fa-solid fa-xmark"></i> Decline</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>


