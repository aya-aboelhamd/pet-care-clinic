<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Walk Tracker</title>
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
            --alert-red: #E53935;
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; }
        .status-pill { background: #EEF2EE; color: #589A64; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; border: 1px solid #DCE5DC; }

        /* Tracker Main Layout */
        .tracker-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        
        .tracker-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 3rem 2rem; text-align: center; }
        .tracker-label { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 1rem; }
        .timer { font-size: 5rem; font-weight: 700; margin-bottom: 2rem; font-variant-numeric: tabular-nums; }

        .metrics-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 2.5rem; }
        .metric-box { border: 1px solid var(--border-color); border-radius: 10px; padding: 15px; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .metric-box i { color: var(--primary-green); font-size: 1.1rem; margin-bottom: 5px; }
        .metric-value { font-weight: 700; font-size: 0.95rem; }
        .metric-unit { font-size: 0.75rem; color: #999; }

        .controls { display: flex; justify-content: center; align-items: center; gap: 20px; }
        .btn-start { background: var(--primary-green); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 1rem; }
        .end-toggle { display: flex; align-items: center; gap: 8px; font-size: 0.95rem; color: var(--text-dark); cursor: pointer; }

        /* Side List */
        .recent-walks-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .recent-walks-card h4 { margin-bottom: 1.5rem; font-size: 1rem; }
        .walk-item { border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .walk-info strong { display: block; font-size: 0.9rem; }
        .walk-info span { font-size: 0.75rem; color: #999; }
        .walk-meta { font-size: 0.75rem; color: #999; text-align: right; }

        /* Disease Alert Integration */
        .disease-warning { background: #FFF5F5; border: 1px solid #FED7D7; border-radius: 8px; padding: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; color: var(--alert-red); font-size: 0.85rem; font-weight: 500; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="providerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="bookingRequests.php" class="nav-item"><i class="fa-regular fa-calendar-check"></i> Booking Requests</a>
            <a href="qrCheckin.php" class="nav-item"><i class="fa-solid fa-qrcode"></i> QR Check-in</a>
            <a href="walkTracker.php" class="nav-item active"><i class="fa-solid fa-shoe-prints"></i> Walk Tracker</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Provider Portal / <strong>Welcome back, Bella 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar">BW</div>
                <div><strong>Bella Walks Co.</strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <div>
                    <h2 style="font-size: 2rem; font-weight: 700;">Walk Tracker</h2>
                    <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Live tracking for active walking sessions.</p>
                </div>
                <span class="status-pill">Idle</span>
            </div>

            <!-- Integration: Geo-based Disease Alert (Hidden by default, shown based on region) -->
            <div class="disease-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><strong>Regional Alert:</strong> Local vets reported a Parvovirus outbreak near <strong>Maple Park</strong>. Please avoid the grass areas.</span>
            </div>

            <div class="tracker-grid">
                <div class="tracker-card">
                    <p class="tracker-label">WALKING · MAX</p>
                    <div class="timer">0:00</div>

                    <div class="metrics-row">
                        <div class="metric-box">
                            <i class="fa-regular fa-clock"></i>
                            <span class="metric-value">0 min</span>
                            <span class="metric-unit">Duration</span>
                        </div>
                        <div class="metric-box">
                            <i class="fa-solid fa-shoe-prints"></i>
                            <span class="metric-value">0.00 km</span>
                            <span class="metric-unit">Distance</span>
                        </div>
                        <div class="metric-box">
                            <i class="fa-solid fa-location-dot"></i>
                            <span class="metric-value">Maple Park</span>
                            <span class="metric-unit">Area</span>
                        </div>
                    </div>

                    <div class="controls">
                        <button class="btn-start">
                            <i class="fa-solid fa-play"></i> Start walk
                        </button>
                        <label class="end-toggle">
                            <input type="checkbox"> End
                        </label>
                    </div>
                </div>

                <div class="recent-walks-card">
                    <h4>Recent walks</h4>
                    <div class="walk-item">
                        <div class="walk-info"><strong>Max</strong><span>32 min · 1.6 km</span></div>
                        <div class="walk-meta">Yesterday</div>
                    </div>
                    <div class="walk-item">
                        <div class="walk-info"><strong>Luna</strong><span>18 min · 0.7 km</span></div>
                        <div class="walk-meta">2 days ago</div>
                    </div>
                    <div class="walk-item">
                        <div class="walk-info"><strong>Bruno</strong><span>45 min · 2.4 km</span></div>
                        <div class="walk-meta">3 days ago</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>


