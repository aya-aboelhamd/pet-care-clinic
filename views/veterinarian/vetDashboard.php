<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
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

$user_name = $user['name'] ?? 'Veterinarian';
$user_email = $user['email'] ?? 'vet@petlor.com';

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
    header("Location: vetDashboard.php");
    exit();
}

$stmt = $db->prepare("SELECT COUNT(*) FROM booking WHERE provider_id = ?");
$stmt->execute([$user_id]);
$total_appointments = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) FROM booking WHERE provider_id = ?");
$stmt->execute([$user_id]);
$active_clients = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(p.id) FROM prescription p JOIN medicalrecord m ON p.record_id = m.id WHERE m.vet_id = ?");
$stmt->execute([$user_id]);
$prescriptions_issued = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM lab_results WHERE vet_id = ?");
$stmt->execute([$user_id]);
$lab_results_count = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT b.start_time, b.service_type, b.status, p.name as pet_name, u.name as owner_name 
    FROM booking b 
    JOIN pet p ON b.pet_id = p.id 
    JOIN users u ON b.user_id = u.id
    WHERE b.provider_id = ? AND (DATE(b.start_time) >= CURDATE() OR b.status = 'Pending')
    ORDER BY b.start_time ASC LIMIT 5
");
$stmt->execute([$user_id]);
$todays_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
    SELECT l.test_name, l.uploaded_at, l.technical_data, l.is_critical, p.name as pet_name, p.species 
    FROM lab_results l 
    JOIN pet p ON l.pet_id = p.id 
    WHERE l.vet_id = ? 
    ORDER BY l.uploaded_at DESC LIMIT 4
");
$stmt->execute([$user_id]);
$recent_labs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_data = [];
$chart_labels = [];
for ($i = 5; $i >= 0; $i--) {
    $month_num = date('m', strtotime("-$i months"));
    $year_num = date('Y', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    $stmt = $db->prepare("SELECT COUNT(DISTINCT pet_id) FROM medicalrecord WHERE vet_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
    $stmt->execute([$user_id, $month_num, $year_num]);
    $chart_labels[] = $month_name;
    $chart_data[] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Veterinarian Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; color: var(--text-dark); }
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; justify-content: space-between; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .logout-icon { color: var(--text-gray); border: 1px solid var(--border-color); padding: 5px; border-radius: 5px; cursor: pointer; text-decoration: none; transition: 0.2s;}
        .logout-icon:hover { background-color: #f0f0f0; color: #DC2626; border-color: #DC2626;}
        .bell-wrapper { position: relative; cursor: pointer; display: flex; align-items: center; font-size: 1.1rem; }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: #DC2626; color: white; font-size: 0.6rem; font-weight: bold; padding: 2px 5px; border-radius: 50%; }
        .notif-dropdown { display: none; position: absolute; top: 35px; right: 0; width: 320px; background: white; border: 1px solid #E0E0E0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; max-height: 400px; overflow-y: auto; cursor: default;}
        .notif-dropdown.show { display: block; animation: fadeIn 0.2s ease; }
        .dropdown-header { padding: 12px 15px; border-bottom: 1px solid #E0E0E0; font-weight: bold; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;}
        .dropdown-header a { font-size: 0.75rem; color: var(--primary-green); text-decoration: none; font-weight: normal; }
        .dropdown-item { padding: 12px 15px; border-bottom: 1px solid #F5F5F5; font-size: 0.8rem; line-height: 1.4; color: var(--text-dark); }
        .dropdown-item.unread { background: #F0FDF4; border-left: 3px solid var(--primary-green); }
        .dropdown-time { font-size: 0.7rem; color: #999; margin-top: 5px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .content-padding { padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--primary-green); color: white; }
        .stat-info p { font-size: 0.7rem; color: var(--text-gray); text-transform: uppercase; font-weight: 600; }
        .stat-info h3 { font-size: 1.8rem; }
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .card-header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-weight: 600; }
        .schedule-item { display: flex; align-items: center; gap: 12px; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 8px; font-size: 0.85rem; }
        .time { color: var(--text-gray); width: 45px; font-weight: 500; }
        .status-dot { width: 18px; height: 18px; border-radius: 50%; border: 1px solid #ddd; position: relative; }
        .status-dot::after { content: ''; position: absolute; width: 8px; height: 8px; border-radius: 50%; top: 4px; left: 4px; }
        .dot-pending::after { background: #D97706; } .dot-pending { background: #FFF4E5; }
        .dot-confirmed::after { background: #16A34A; } .dot-confirmed { background: #F0FDF4; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th { text-align: left; color: var(--text-gray); font-size: 0.8rem; padding: 10px; border-bottom: 1px solid var(--border-color); }
        td { padding: 12px 10px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); }
        .badge { padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 500; }
        .badge-normal { background: #E8F5E9; color: #4CAF50; }
        .badge-abnormal { background: #FFF3E0; color: #E65100; }
        .empty-state { text-align: center; color: #999; padding: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> <div>Petlor</div></div>
            <a href="vetDashboard.php" class="nav-item active"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="createprescription.php" class="nav-item"><i class="fa-regular fa-file-lines"></i> New Prescription</a>
            <a href="labresults.php" class="nav-item"><i class="fa-solid fa-flask"></i> Lab Results</a>
            <a href="medicalnotes.php" class="nav-item"><i class="fa-regular fa-pen-to-square"></i> Medical Notes</a>
            <a href="diseasealert.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Disease Alerts</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Vet Portal / <strong>Welcome back, Dr. <?= htmlspecialchars($first_name) ?> 👋</strong></div>
            
            <div class="user-profile">
                <div class="bell-wrapper" onclick="toggleNotif(event)">
                    <i class="fa-regular fa-bell"></i>
                    <?php if($unread_count > 0): ?><span class="notif-badge"><?= $unread_count ?></span><?php endif; ?>
                    <div class="notif-dropdown" id="notifDropdown" onclick="event.stopPropagation()">
                        <div class="dropdown-header">
                            <span>Notifications</span>
                            <?php if($unread_count > 0): ?><a href="?mark_read=1">Mark all as read</a><?php endif; ?>
                        </div>
                        <?php foreach($notifications as $dn): ?>
                            <div class="dropdown-item <?= $dn['is_read'] == 0 ? 'unread' : '' ?>">
                                <strong><?= htmlspecialchars($dn['type']) ?> Alert</strong><br>
                                <?= htmlspecialchars($dn['message']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <strong>Dr. <?= htmlspecialchars($user_name) ?></strong><br>
                    <span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span>
                </div>
                <a href="../Auth/logout.php" class="logout-icon" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>
        
        <div class="content-padding">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon-box"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-info"><p>Appointments</p><h3><?= $total_appointments ?></h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info"><p>Active Clients</p><h3><?= $active_clients ?></h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box"><i class="fa-regular fa-file-lines"></i></div>
                    <div class="stat-info"><p>Prescriptions</p><h3><?= $prescriptions_issued ?></h3></div>
                </div>
                <div class="stat-card">
                    <div class="icon-box"><i class="fa-solid fa-flask"></i></div>
                    <div class="stat-info"><p>Lab Reports</p><h3><?= $lab_results_count ?></h3></div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">Patients seen (monthly)</div>
                    <div style="height: 250px;"><canvas id="patientsChart"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-header">Appointments Queue</div>
                    <?php if(empty($todays_schedule)): ?>
                        <div class="empty-state">No upcoming appointments.</div>
                    <?php else: ?>
                        <?php foreach($todays_schedule as $appt): 
                            $status_class = (strtolower($appt['status']) == 'pending') ? 'dot-pending' : 'dot-confirmed';
                        ?>
                            <div class="schedule-item">
                                <span class="time"><?= date('H:i', strtotime($appt['start_time'])) ?></span> 
                                <span style="flex:1">
                                    <strong><?= htmlspecialchars($appt['pet_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($appt['owner_name']) ?> — <?= htmlspecialchars($appt['service_type']) ?></small>
                                </span> 
                                <div class="status-dot <?= $status_class ?>"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Recent lab results</div>
                <?php if(empty($recent_labs)): ?>
                    <div class="empty-state">No recent lab results found.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>Pet</th><th>Test</th><th>Date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_labs as $lab): ?>
                                <tr>
                                    <td><?= (strtolower($lab['species']) == 'cat') ? '🐈' : '🐕' ?> <?= htmlspecialchars($lab['pet_name']) ?></td>
                                    <td><?= htmlspecialchars($lab['test_name']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($lab['uploaded_at'])) ?></td>
                                    <td><span class="badge <?= ($lab['is_critical'] == 1) ? 'badge-abnormal' : 'badge-normal' ?>"><?= ($lab['is_critical'] == 1) ? 'Abnormal' : 'Normal' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleNotif(event) { event.stopPropagation(); document.getElementById('notifDropdown').classList.toggle('show'); }
        window.onclick = function(event) { if (!event.target.closest('.bell-wrapper')) { var dropdowns = document.getElementsByClassName("notif-dropdown"); for (var i = 0; i < dropdowns.length; i++) { if (dropdowns[i].classList.contains('show')) dropdowns[i].classList.remove('show'); } } }

        const chartLabels = <?= json_encode($chart_labels) ?>;
        const chartData = <?= json_encode($chart_data) ?>;
        const ctx = document.getElementById('patientsChart').getContext('2d');
        let gradient = ctx.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, 'rgba(88, 154, 100, 0.2)'); gradient.addColorStop(1, 'rgba(88, 154, 100, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    borderColor: '#589A64',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
        });
    </script>
</body>
</html>