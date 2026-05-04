<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-green: #589A64;
            --bg-light: #F8FAF8;
            --sidebar-width: 240px;
            --text-dark: #1A1A1A;
            --text-gray: #666;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid #E0E0E0;
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            flex-shrink: 0;
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
            color: var(--text-gray);
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

        .nav-item:hover:not(.active) { background: #f0f0f0; }

        /* --- Main Content --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }

        /* Header */
        header {
            background: white;
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #E0E0E0;
        }

        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        /* Dashboard Grid */
        .content-padding { padding: 2rem; }
        .page-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .add-pet-btn { background: var(--primary-green); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-weight: 600; }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #E0E0E0; display: flex; align-items: center; gap: 15px; }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-info h3 { font-size: 1.5rem; }
        .stat-info p { font-size: 0.75rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; }

        /* Charts Section */
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #E0E0E0; }
        .card-header { display: flex; justify-content: space-between; margin-bottom: 1rem; align-items: center; }

        /* Bottom Section */
        .bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .pet-item { display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #E0E0E0; border-radius: 10px; margin-bottom: 10px; }
        .notification-item { display: flex; gap: 15px; padding: 1rem; border: 1px solid #E0E0E0; border-radius: 10px; margin-bottom: 10px; font-size: 0.85rem; }
        .badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; background: #FFF4E5; color: #FF9800; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item active"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Welcome back, Sarah 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar">SJ</div>
                <div>
                    <strong>Sarah Johnson</strong><br>
                    <span style="font-size: 0.75rem; color: #999;">owner@petlor.com</span>
                </div>
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-title-row">
                <div>
                    <h2>Dashboard</h2>
                    <p style="color: #666; font-size: 0.9rem;">Quick view of your pets, alerts and upcoming activities.</p>
                </div>

            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon-box" style="background: #E8F5E9; color: #4CAF50;"><i class="fa-solid fa-paw"></i></div>
                    <div class="stat-info"><p>Pets</p><h3>2</h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box" style="background: #E8F5E9; color: #4CAF50;"><i class="fa-solid fa-syringe"></i></div>
                    <div class="stat-info"><p>Overdue Vaccines</p><h3 style="color: #d32f2f;">1</h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box" style="background: #E8F5E9; color: #4CAF50;"><i class="fa-solid fa-calendar"></i></div>
                    <div class="stat-info"><p>Upcoming Bookings</p><h3>3</h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box" style="background: #E8F5E9; color: #4CAF50;"><i class="fa-solid fa-basket-shopping"></i></div>
                    <div class="stat-info"><p>Cart Items</p><h3>2</h3></div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <strong>Max — Health Trend</strong>
                        <span class="badge" style="background: #E8F5E9; color: #4CAF50;">Stable</span>
                    </div>
                    <canvas id="healthChart" height="100"></canvas>
                </div>
                <div class="card">
                    <strong>Activity (mins/day)</strong>
                    <canvas id="activityChart" height="200"></canvas>
                </div>
            </div>

            <div class="bottom-row">
                <div class="card">
                    <h3>Your Pets</h3><br>
                    <div class="pet-item">
                        <div style="display: flex; gap: 10px;">
                            <div class="avatar" style="background: #FFF3E0;">🐶</div>
                            <div><strong>Max</strong><br><small>Golden Retriever • 4 yrs</small></div>
                        </div>
                        <span class="badge">2 allergies</span>
                    </div>
                    <div class="pet-item">
                        <div style="display: flex; gap: 10px;">
                            <div class="avatar" style="background: #F3E5F5;">🐱</div>
                            <div><strong>Luna</strong><br><small>British Shorthair • 2 yrs</small></div>
                        </div>
                        <span class="badge">1 allergies</span>
                    </div>
                </div>

                <div class="card">
                    <h3>Recent Notifications</h3><br>
                    <div class="notification-item">
                        <i class="fa-solid fa-circle-exclamation" style="color: #f44336;"></i>
                        <div><strong>Vaccination Overdue</strong><br><small>Bordetella for Max is overdue.</small></div>
                    </div>
                    <div class="notification-item">
                        <i class="fa-solid fa-circle-check" style="color: #4CAF50;"></i>
                        <div><strong>Booking Confirmed</strong><br><small>Walking session on Apr 28 confirmed.</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // الرسم البياني للصحة (Line Chart)
        const ctx1 = document.getElementById('healthChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
                datasets: [{
                    label: 'Weight (kg)',
                    data: [27.7, 28.1, 28.4, 28.6, 28.5, 28.5],
                    borderColor: '#589A64',
                    tension: 0.3,
                    fill: false,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: { plugins: { legend: { display: false } } }
        });

        // الرسم البياني للنشاط (Bar Chart)
        const ctx2 = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
                datasets: [{
                    data: [65, 60, 70, 75, 72, 80],
                    backgroundColor: '#589A64',
                    borderRadius: 5
                }]
            },
            options: { plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>


