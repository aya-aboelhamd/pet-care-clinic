<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['name'] ?? 'Provider';
$user_email = $user['email'] ?? 'provider@petlor.com';

$name_parts = explode(' ', trim($user_name));
$first_name = $name_parts[0];
$initials = strtoupper(substr($first_name, 0, 1));
if (isset($name_parts[1])) {
    $initials .= strtoupper(substr($name_parts[1], 0, 1));
} else {
    $initials .= strtoupper(substr($first_name, 1, 1) ?: '');
}

$stmt = $db->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = 0;
foreach ($notifications as $n) {
    if ($n['is_read'] == 0) $unread_count++;
}

if (isset($_GET['mark_read'])) {
    $stmt = $db->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: providerDashboard.php");
    exit();
}

$stmt = $db->prepare("SELECT COUNT(*) FROM booking WHERE provider_id = ? AND status = 'Pending'");
$stmt->execute([$user_id]);
$pending_count = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM booking WHERE provider_id = ? AND (status = 'Active' OR status = 'In Progress')");
$stmt->execute([$user_id]);
$active_count = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM booking WHERE provider_id = ? AND DATE(start_time) = CURDATE()");
$stmt->execute([$user_id]);
$checkins_today = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(total_price), 0) FROM booking WHERE provider_id = ? AND MONTH(start_time) = MONTH(CURDATE()) AND YEAR(start_time) = YEAR(CURDATE()) AND (status = 'Completed' OR status = 'Confirmed')");
$stmt->execute([$user_id]);
$monthly_earnings = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT b.*, p.name as pet_name 
    FROM booking b 
    JOIN pet p ON b.pet_id = p.id 
    WHERE b.provider_id = ? AND DATE(b.start_time) = CURDATE() 
    ORDER BY b.start_time ASC
");
$stmt->execute([$user_id]);
$todays_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_num = date('m', strtotime("-$i months"));
    $year_num = date('Y', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM booking WHERE provider_id = ? AND MONTH(start_time) = ? AND YEAR(start_time) = ?");
    $stmt->execute([$user_id, $month_num, $year_num]);
    $count = $stmt->fetchColumn();
    
    $chart_data[] = ['label' => $month_name, 'count' => $count];
}

$max_count = max(array_column($chart_data, 'count'));
if ($max_count == 0) $max_count = 1;

?>
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
        
        .bell-wrapper { position: relative; cursor: pointer; display: flex; align-items: center; font-size: 1.1rem; }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: #DC2626; color: white; font-size: 0.6rem; font-weight: bold; padding: 2px 5px; border-radius: 50%; }
        .notif-dropdown { display: none; position: absolute; top: 35px; right: 0; width: 320px; background: white; border: 1px solid #E0E0E0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; max-height: 400px; overflow-y: auto; cursor: default;}
        .notif-dropdown.show { display: block; animation: fadeIn 0.2s ease; }
        .dropdown-header { padding: 12px 15px; border-bottom: 1px solid #E0E0E0; font-weight: bold; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;}
        .dropdown-header a { font-size: 0.75rem; color: var(--primary-green); text-decoration: none; font-weight: normal; }
        .dropdown-item { padding: 12px 15px; border-bottom: 1px solid #F5F5F5; font-size: 0.8rem; line-height: 1.4; color: var(--text-dark); }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item.unread { background: #F0FDF4; border-left: 3px solid var(--primary-green); }
        .dropdown-time { font-size: 0.7rem; color: #999; margin-top: 5px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

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

        .dashboard-main { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .card h4 { margin-bottom: 1.5rem; font-size: 1rem; }

        .chart-container { height: 250px; display: flex; align-items: flex-end; gap: 15px; padding-bottom: 20px; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc; position: relative; }
        .bar { background: var(--primary-green); width: 100%; border-radius: 4px 4px 0 0; transition: height 0.5s; }
        .month-label { position: absolute; bottom: -25px; font-size: 0.75rem; color: #999; width: 100%; text-align: center; }

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

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Provider Portal / <strong>Welcome back, <?= htmlspecialchars($first_name) ?> 👋</strong></div>
            <div class="user-profile">
                
                <div class="bell-wrapper" onclick="toggleNotif(event)">
                    <i class="fa-regular fa-bell"></i>
                    <?php if($unread_count > 0): ?>
                        <span class="notif-badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                    
                    <div class="notif-dropdown" id="notifDropdown" onclick="event.stopPropagation()">
                        <div class="dropdown-header">
                            <span>Notifications</span>
                            <?php if($unread_count > 0): ?>
                                <a href="?mark_read=1">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <?php if(empty($notifications)): ?>
                            <div class="dropdown-item" style="text-align: center; color: #999; padding: 20px;">No new notifications</div>
                        <?php else: ?>
                            <?php foreach($notifications as $dn): ?>
                                <div class="dropdown-item <?= $dn['is_read'] == 0 ? 'unread' : '' ?>">
                                    <strong><?= htmlspecialchars($dn['type']) ?> Alert</strong><br>
                                    <?= htmlspecialchars($dn['message']) ?>
                                    <div class="dropdown-time"><?= date('M j, Y g:i A', strtotime($dn['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div><strong><?= htmlspecialchars($user_name) ?></strong><br><span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span></div>
                <a href="../Auth/logout.php" style="color: #ccc; margin-left: 10px; border: 1px solid #eee; padding: 5px; border-radius: 5px; text-decoration: none;" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
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

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-regular fa-clipboard"></i></div>
                    <div class="stat-info">
                        <p>Pending Requests</p>
                        <h3><?= $pending_count ?></h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-shoe-prints"></i></div>
                    <div class="stat-info">
                        <p>Active Sessions</p>
                        <h3><?= $active_count ?></h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-qrcode"></i></div>
                    <div class="stat-info">
                        <p>Check-ins Today</p>
                        <h3><?= $checkins_today ?></h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-dollar-sign"></i></div>
                    <div class="stat-info">
                        <p>This Month</p>
                        <h3>$<?= number_format($monthly_earnings, 2) ?></h3>
                    </div>
                </div>
            </div>

            <div class="dashboard-main">
                <div class="card">
                    <h4>Bookings per month (Last 6 Months)</h4>
                    <div class="chart-container">
                        <?php foreach($chart_data as $data): ?>
                            <?php $height_percentage = ($data['count'] / $max_count) * 100; ?>
                            <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end; height:100%; position:relative;" title="<?= $data['count'] ?> bookings">
                                <div class="bar" style="height: <?= $height_percentage ?>%;"></div>
                                <div class="month-label"><?= htmlspecialchars($data['label']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <h4>Today's bookings</h4>
                    <?php if (count($todays_bookings) > 0): ?>
                        <?php foreach ($todays_bookings as $booking): ?>
                            <?php
                                $badge_class = 'badge-pending';
                                if (strtolower($booking['status']) == 'confirmed' || strtolower($booking['status']) == 'completed') {
                                    $badge_class = 'badge-confirmed';
                                }
                            ?>
                            <div class="booking-item">
                                <div class="booking-info">
                                    <strong><?= htmlspecialchars($booking['pet_name']) ?> · <?= htmlspecialchars($booking['service_type']) ?></strong>
                                    <span><?= date('H:i', strtotime($booking['start_time'])) ?></span>
                                </div>
                                <span class="badge <?= $badge_class ?>"><?= ucfirst(htmlspecialchars($booking['status'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: #999; padding: 20px;">No bookings scheduled for today.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleNotif(event) {
            event.stopPropagation();
            document.getElementById('notifDropdown').classList.toggle('show');
        }

        window.onclick = function(event) {
            if (!event.target.closest('.bell-wrapper')) {
                var dropdowns = document.getElementsByClassName("notif-dropdown");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>
</html>