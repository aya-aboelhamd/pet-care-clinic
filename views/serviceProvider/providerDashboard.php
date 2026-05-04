<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Provider Dashboard</title>
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

        .demo-switch { padding: 1.5rem; border-top: 1px solid var(--border-color); }
        .demo-switch h4 { font-size: 0.65rem; color: #999; margin-bottom: 10px; }
        .role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
        .role-btn { padding: 5px; text-align: center; font-size: 0.75rem; border-radius: 20px; color: var(--text-gray); text-decoration: none; }
        .role-btn.active { background: #DFF0E2; color: var(--primary-green); font-weight: 600; }

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
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }

        .view-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
        }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card {
            background: white;
            padding: 1.2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 45px; height: 45px;
            background: #DFF0E2;
            color: var(--primary-green);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .stat-info p { font-size: 0.65rem; color: #999; text-transform: uppercase; font-weight: 700; }
        .stat-info h3 { font-size: 1.4rem; font-weight: 700; }
        .trend { font-size: 0.7rem; font-weight: 600; margin-top: 2px; }
        .trend.up { color: var(--primary-green); }

        /* Main Grid */
        .dashboard-main { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .card h4 { margin-bottom: 1.5rem; font-size: 1rem; }

        /* Chart Placeholder (simplified) */
        .chart-container { height: 250px; display: flex; align-items: flex-end; gap: 15px; padding-bottom: 20px; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc; position: relative; }
        .bar { background: var(--primary-green); width: 100%; border-radius: 4px 4px 0 0; }
        .month-label { position: absolute; bottom: -25px; font-size: 0.75rem; color: #999; width: 100%; text-align: center; }

        /* Bookings List */
        .booking-item {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .booking-info strong { font-size: 0.9rem; display: block; }
        .booking-info span { font-size: 0.75rem; color: #999; }
        
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-confirmed { background: #E8F5E9; color: #4CAF50; border: 1px solid #C8E6C9; }
        .badge-pending { background: #FFF3E0; color: #E65100; border: 1px solid #FFE0B2; }

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
            <a href="providerDashboard.php" class="nav-item active"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="bookingRequests.php" class="nav-item"><i class="fa-regular fa-calendar-check"></i> Booking Requests</a>
            <a href="qrCheckin.php" class="nav-item"><i class="fa-solid fa-qrcode"></i> QR Check-in</a>
            <a href="walkTracker.php" class="nav-item"><i class="fa-solid fa-shoe-prints"></i> Walk Tracker</a>
        </div>


    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Provider Portal / <strong>Welcome back, Bella 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell" style="position: relative;"><span style="position: absolute; top: -2px; right: -2px; width: 7px; height: 7px; background: red; border-radius: 50%;"></span></i>
                <div class="avatar">BW</div>
                <div><strong>Bella Walks Co.</strong><br><span style="font-size: 0.75rem; color: #999;">provider@petlor.com</span></div>
                <i class="fa-solid fa-right-from-bracket" style="color: #ccc; margin-left: 10px; border: 1px solid #eee; padding: 5px; border-radius: 5px;"></i>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <div>
                    <h2>Provider Dashboard</h2>
                    <p>Your bookings, check-ins and earnings at a glance.</p>
                </div>
                <button class="view-btn" onclick="window.location.href='bookingRequests.php'">
                    View bookings
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-regular fa-clipboard"></i></div>
                    <div class="stat-info">
                        <p>Pending Requests</p>
                        <h3>2</h3>
                        <div class="trend up">▲ +1 today</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-shoe-prints"></i></div>
                    <div class="stat-info">
                        <p>Active Sessions</p>
                        <h3>2</h3>
                        <div class="trend" style="color: #999;">In progress</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-qrcode"></i></div>
                    <div class="stat-info">
                        <p>Check-ins Today</p>
                        <h3>5</h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-dollar-sign"></i></div>
                    <div class="stat-info">
                        <p>This Month</p>
                        <h3>$1,820</h3>
                        <div class="trend up">▲ +12%</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-main">
                <!-- Bar Chart -->
                <div class="card">
                    <h4>Bookings per month</h4>
                    <div class="chart-container">
                        <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end; height:100%; position:relative;">
                            <div class="bar" style="height: 40%;"></div>
                            <div class="month-label">Nov</div>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end; height:100%; position:relative;">
                            <div class="bar" style="height: 55%;"></div>
                            <div class="month-label">Dec</div>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end; height:100%; position:relative;">
                            <div class="bar" style="height: 65%;"></div>
                            <div class="month-label">Jan</div>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end; height:100%; position:relative;">
                            <div class="bar" style="height: 75%;"></div>
                            <div class="month-label">Feb</div>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end; height:100%; position:relative;">
                            <div class="bar" style="height: 85%;"></div>
                            <div class="month-label">Mar</div>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end; height:100%; position:relative;">
                            <div class="bar" style="height: 95%;"></div>
                            <div class="month-label">Apr</div>
                        </div>
                    </div>
                </div>

                <!-- Today's Bookings -->
                <div class="card">
                    <h4>Today's bookings</h4>
                    <div class="booking-item">
                        <div class="booking-info">
                            <strong>Max · Walking</strong>
                            <span>2026-04-28 · 08:30</span>
                        </div>
                        <span class="badge badge-confirmed">Confirmed</span>
                    </div>
                    <div class="booking-item">
                        <div class="booking-info">
                            <strong>Luna · Grooming</strong>
                            <span>2026-05-02 · 14:00</span>
                        </div>
                        <span class="badge badge-pending">Pending</span>
                    </div>
                    <div class="booking-item">
                        <div class="booking-info">
                            <strong>Max · Boarding</strong>
                            <span>2026-05-15 · 10:00</span>
                        </div>
                        <span class="badge badge-pending">Pending</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>


