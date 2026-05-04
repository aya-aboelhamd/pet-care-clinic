<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Pet Profile</title>
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
            --danger-red: #FEE2E2;
            --danger-text: #DC2626;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            flex-shrink: 0;
        }

        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .nav-item:hover:not(.active) { background: #f0f0f0; }

        /* --- Main Content --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .content-padding { padding: 2rem; }

        /* --- Profile Header --- */
        .profile-banner {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .pet-avatar-large {
            width: 120px;
            height: 120px;
            background: #F0F7F1;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }
        .pet-main-info h2 { font-size: 2rem; margin-bottom: 5px; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-right: 5px; }
        .badge-allergy { background: #FFF4E5; color: #D97706; border: 1px solid #FFEBCD; }
        .badge-condition { background: var(--danger-red); color: var(--danger-text); }

        /* --- Stats Grid --- */
        .profile-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }

        .card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-title { font-weight: 700; font-size: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }

        /* --- Weight Update Section --- */
        .metric-row { display: flex; align-items: center; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #F5F5F5; }
        .metric-label { font-size: 0.9rem; color: var(--text-gray); }
        .metric-value { font-weight: 700; color: var(--text-dark); }

        .weight-input-group { display: flex; align-items: center; gap: 10px; }
        .weight-input {
            width: 80px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-weight: 700;
            text-align: center;
            outline: none;
        }
        .weight-input:focus { border-color: var(--primary-green); }
        .save-weight-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }

        /* --- History List --- */
        .history-item { display: flex; gap: 15px; padding: 10px 0; border-bottom: 1px solid #F9F9F9; }
        .history-icon { width: 35px; height: 35px; border-radius: 50%; background: #F0F4F0; display: flex; align-items: center; justify-content: center; color: var(--primary-green); font-size: 0.8rem; }
        .history-text { font-size: 0.85rem; }
        .history-date { font-size: 0.75rem; color: #999; }

        /* --- Prescription Styles --- */
        .presc-box { background: #F0F7FF; border: 1px solid #D1E9FF; border-radius: 10px; padding: 12px; margin-top: 10px; }
        .presc-title { font-weight: 700; color: #1E40AF; font-size: 0.85rem; margin-bottom: 4px; display: block; }
        .presc-sub { font-size: 0.75rem; color: #4B5563; line-height: 1.4; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item active"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">My Pets / <strong>Max's Profile</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar-circle">SJ</div>
                <div><strong>Sarah Johnson</strong><br><span style="font-size: 0.75rem; color: #999;">owner@petlor.com</span></div>
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
        </header>

        <div class="content-padding">
            <div class="profile-banner">
                <div class="pet-avatar-large">🐶</div>
                <div class="pet-main-info">
                    <h2>Max</h2>
                    <p style="color: var(--text-gray); margin-bottom: 12px;">Golden Retriever • 4 years • Male</p>
                    <div>
                        <span class="badge badge-allergy">⚠️ Chicken Allergy</span>
                        <span class="badge badge-allergy">⚠️ Wheat Allergy</span>
                        <span class="badge badge-condition">🚫 Mild Hip Dysplasia</span>
                    </div>
                </div>

            </div>

            <div class="profile-grid">
                <div class="main-stats">
                    <div class="card">
                        <div class="card-title">Health Metrics <i class="fa-solid fa-heart-pulse" style="color: var(--primary-green);"></i></div>
                        
                        <div class="metric-row">
                            <span class="metric-label">Current Weight</span>
                            <div class="weight-input-group">
                                <input type="number" class="weight-input" value="28.5" step="0.1">
                                <span style="font-weight: 600; color: var(--text-gray);">kg</span>
                                <button class="save-weight-btn">Update</button>
                            </div>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Last Vet Visit</span>
                            <span class="metric-value">2026-03-15</span>
                        </div>
                        
                        <div class="metric-row">
                            <span class="metric-label">Activity Level</span>
                            <span class="metric-value" style="color: var(--primary-green);">High (Active)</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title">Upcoming Vaccinations</div>
                        <div class="metric-row">
                            <span>Distemper</span>
                            <span style="color: #D97706; font-weight: 600;">Due in 12 days</span>
                        </div>
                        <div class="metric-row" style="border: none;">
                            <span>Rabies</span>
                            <span>Due 2026-08-12</span>
                        </div>
                    </div>
                </div>

                <div class="activity-sidebar">
                    <div class="card">
                        <div class="card-title">Recent Activity</div>
                        
                        <div class="history-item">
                            <div class="history-icon"><i class="fa-solid fa-weight-scale"></i></div>
                            <div>
                                <p class="history-text">Weight updated to <strong>28.5 kg</strong></p>
                                <p class="history-date">Yesterday, 10:30 AM</p>
                            </div>
                        </div>

                        <div class="history-item">
                            <div class="history-icon"><i class="fa-solid fa-syringe"></i></div>
                            <div>
                                <p class="history-text">Bordetella vaccine given</p>
                                <p class="history-date">2025-03-01</p>
                            </div>
                        </div>

                        <div class="history-item" style="border: none;">
                            <div class="history-icon"><i class="fa-solid fa-stethoscope"></i></div>
                            <div>
                                <p class="history-text">General Checkup at Dr. Lee</p>
                                <p class="history-date">2025-01-20</p>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title">Active Prescription <i class="fa-solid fa-prescription-bottle-medical" style="color: #3B82F6;"></i></div>
                        
                        <div class="presc-box">
                            <span class="presc-title">Apoquel (Oclacitinib) 16mg</span>
                            <p class="presc-sub">Give 1 tablet orally once daily for allergy control. <br><strong>Dr. Emily Chen</strong></p>
                        </div>

                        <div class="presc-box" style="background: #F0FDF4; border-color: #BBF7D0;">
                            <span class="presc-title" style="color: #166534;">Dasuquin Joint Health</span>
                            <p class="presc-sub">1 soft chew daily with food to support hip mobility.</p>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>


