<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
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

$user_name = $user['name'] ?? 'User';
$user_email = $user['email'] ?? 'user@petlor.com';

$name_parts = explode(' ', trim($user_name));
$first_name = $name_parts[0];
$initials = strtoupper(substr($first_name, 0, 1));
if (isset($name_parts[1])) {
    $initials .= strtoupper(substr($name_parts[1], 0, 1));
} else {
    $initials .= strtoupper(substr($first_name, 1, 1) ?: '');
}

$stmt = $db->prepare("SELECT COUNT(*) FROM pet WHERE user_id = ? AND (is_archived = 0 OR is_archived IS NULL)");
$stmt->execute([$user_id]);
$pets_count = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) FROM vaccination v 
    JOIN medicalrecord m ON v.record_id = m.id 
    JOIN pet p ON m.pet_id = p.id 
    WHERE p.user_id = ? AND v.next_due_date < CURDATE() AND (p.is_archived = 0 OR p.is_archived IS NULL)
");
$stmt->execute([$user_id]);
$overdue_vaccines = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM booking WHERE user_id = ? AND start_time > NOW()");
$stmt->execute([$user_id]);
$upcoming_bookings = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(ci.quantity), 0) FROM cartitem ci JOIN cart c ON ci.cart_id = c.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT * FROM pet WHERE user_id = ? AND (is_archived = 0 OR is_archived IS NULL) LIMIT 2");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$dropdown_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = 0;
foreach ($dropdown_notifications as $n) {
    if ($n['is_read'] == 0) $unread_count++;
}

$stmt = $db->prepare("
    SELECT DATE_FORMAT(w.logged_at, '%b %d') as date_label, w.weight 
    FROM weightlog w 
    JOIN pet p ON w.pet_id = p.id 
    WHERE p.user_id = ? 
    ORDER BY w.logged_at ASC LIMIT 6
");
$stmt->execute([$user_id]);
$weight_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$weight_labels = !empty($weight_records) ? array_column($weight_records, 'date_label') : ['No Data'];
$weight_values = !empty($weight_records) ? array_column($weight_records, 'weight') : [0];

if (isset($_GET['mark_read'])) {
    $stmt = $db->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: petownerDashboard.php");
    exit();
}

?>
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

        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid #E0E0E0; display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .nav-item:hover:not(.active) { background: #f0f0f0; }

        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #E0E0E0; }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase;}
        
        .content-padding { padding: 2rem; }
        .page-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #E0E0E0; display: flex; align-items: center; gap: 15px; }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-info h3 { font-size: 1.5rem; }
        .stat-info p { font-size: 0.75rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; }

        .charts-row { display: block; margin-bottom: 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #E0E0E0; }
        .card-header { display: flex; justify-content: space-between; margin-bottom: 1rem; align-items: center; }

        .bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .pet-item { display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #E0E0E0; border-radius: 10px; margin-bottom: 10px; }
        .notification-item { display: flex; gap: 15px; padding: 1rem; border: 1px solid #E0E0E0; border-radius: 10px; margin-bottom: 10px; font-size: 0.85rem; }
        
        .empty-state { color: #999; font-size: 0.9rem; text-align: center; padding: 1rem; }
        .logout-btn { color: var(--text-gray); transition: 0.2s; font-size: 1.1rem; }
        .logout-btn:hover { color: #d32f2f; }

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
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item active"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="healthLogs.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Health Logs</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Symptom Checker</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
        
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Welcome back, <?= htmlspecialchars($first_name) ?> 👋</strong></div>
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
                        <?php if(empty($dropdown_notifications)): ?>
                            <div class="dropdown-item" style="text-align: center; color: #999; padding: 20px;">No new notifications</div>
                        <?php else: ?>
                            <?php foreach($dropdown_notifications as $dn): ?>
                                <div class="dropdown-item <?= $dn['is_read'] == 0 ? 'unread' : '' ?>">
                                    <strong style="<?= $dn['type'] == 'Recall' ? 'color:#DC2626;' : '' ?>"><?= htmlspecialchars($dn['type']) ?> Alert</strong><br>
                                    <?= htmlspecialchars($dn['message']) ?>
                                    <div class="dropdown-time"><?= date('M j, Y g:i A', strtotime($dn['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <strong><?= htmlspecialchars($user_name) ?></strong><br>
                    <span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span>
                </div>
                <a href="../Auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
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
                    <div class="stat-info"><p>Pets</p><h3><?= $pets_count ?></h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box" style="background: #E8F5E9; color: #4CAF50;"><i class="fa-solid fa-syringe"></i></div>
                    <div class="stat-info"><p>Overdue Vaccines</p><h3 <?= $overdue_vaccines > 0 ? 'style="color: #d32f2f;"' : '' ?>><?= $overdue_vaccines ?></h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box" style="background: #E8F5E9; color: #4CAF50;"><i class="fa-solid fa-calendar"></i></div>
                    <div class="stat-info"><p>Upcoming Bookings</p><h3><?= $upcoming_bookings ?></h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box" style="background: #E8F5E9; color: #4CAF50;"><i class="fa-solid fa-basket-shopping"></i></div>
                    <div class="stat-info"><p>Cart Items</p><h3><?= $cart_items ?></h3></div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <strong>Health Trend (Weight)</strong>
                    </div>
                    <canvas id="healthChart" height="80"></canvas>
                </div>
            </div>

            <div class="bottom-row">
                <div class="card">
                    <h3>Your Pets</h3><br>
                    <?php if (count($pets) > 0): ?>
                        <?php foreach ($pets as $pet): ?>
                            <div class="pet-item">
                                <div style="display: flex; gap: 10px;">
                                    <div class="avatar" style="background: #FFF3E0; color:#333; font-size:1.2rem;">
                                        <?= strtolower($pet['species']) == 'cat' ? '🐱' : '🐶' ?>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($pet['name']) ?></strong><br>
                                        <small><?= htmlspecialchars($pet['breed']) ?> • <?= htmlspecialchars($pet['weight']) ?> kg</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No pets registered yet.</div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Recent Notifications</h3><br>
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <i class="fa-solid fa-bell" style="color: #4CAF50;"></i>
                                <div>
                                    <strong><?= htmlspecialchars($notification['type']) ?></strong><br>
                                    <small><?= htmlspecialchars($notification['message']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">You're all caught up! No recent alerts.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const weightLabels = <?= json_encode($weight_labels) ?>;
        const weightData = <?= json_encode($weight_values) ?>;

        const ctx1 = document.getElementById('healthChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: weightLabels,
                datasets: [{
                    label: 'Weight (kg)',
                    data: weightData,
                    borderColor: '#589A64',
                    tension: 0.3,
                    fill: false,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: { plugins: { legend: { display: false } } }
        });

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