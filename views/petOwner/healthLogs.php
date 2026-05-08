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

// --- ⭐️ Security Audit Trigger: تسجيل الدخول للسجل الطبي ---
require_once '../../models/AuditLogsModel.php';
$auditModel = new AuditLogsModel($db);
$action = "Accessed Medical Records";
$target = "Health Logs Page"; 
// بنسجل الحدث فوراً في الداتا بيز
$auditModel->logAction($user_id, $action, $target);
// ---------------------------------------------------------

// 1. جلب بيانات المستخدم
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

$stmt = $db->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$dropdown_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = 0;
foreach ($dropdown_notifications as $n) {
    if ($n['is_read'] == 0) $unread_count++;
}

if (isset($_GET['mark_read'])) {
    $stmt = $db->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: healthLogs.php");
    exit();
}

// 2. جلب كل حيوانات المستخدم للفلتر وللفورم
$stmt = $db->prepare("SELECT id, name, species FROM pet WHERE user_id = ? AND (is_archived = 0 OR is_archived IS NULL)");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. تحديد الحيوان والمقياس المختارين من الفلتر (Default: أول حيوان + الوزن)
$selected_pet_id = $_GET['pet_id'] ?? ($pets[0]['id'] ?? 0);
$selected_metric = $_GET['metric'] ?? 'Weight';

$chart_labels = [];
$chart_data = [];

if ($selected_pet_id) {
    if ($selected_metric == 'Weight') {
        // جلب البيانات من جدول weightlog
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(logged_at, '%b %d') as log_date, weight as val 
            FROM weightlog 
            WHERE pet_id = ? AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY logged_at ASC
        ");
        $stmt->execute([$selected_pet_id]);
    } else {
        // جلب البيانات من جدول health_metrics_log للمقاييس الأخرى
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(logged_at, '%b %d') as log_date, metric_value as val 
            FROM health_metrics_log 
            WHERE pet_id = ? AND metric_name = ? AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY logged_at ASC
        ");
        $stmt->execute([$selected_pet_id, $selected_metric]);
    }

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($logs as $log) {
        $chart_labels[] = $log['log_date'];
        $chart_data[] = (float)$log['val'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Petlor - Health Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase;}
        
        .bell-wrapper { position: relative; cursor: pointer; display: flex; align-items: center; font-size: 1.1rem; }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: #DC2626; color: white; font-size: 0.6rem; font-weight: bold; padding: 2px 5px; border-radius: 50%; }
        .notif-dropdown { display: none; position: absolute; top: 35px; right: 0; width: 320px; background: white; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; max-height: 400px; overflow-y: auto; cursor: default;}
        .notif-dropdown.show { display: block; animation: fadeIn 0.2s ease; }
        .dropdown-header { padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-weight: bold; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;}
        .dropdown-header a { font-size: 0.75rem; color: var(--primary-green); text-decoration: none; font-weight: normal; }
        .dropdown-item { padding: 12px 15px; border-bottom: 1px solid #F5F5F5; font-size: 0.8rem; line-height: 1.4; color: var(--text-dark); }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item.unread { background: #F0FDF4; border-left: 3px solid var(--primary-green); }
        .dropdown-time { font-size: 0.7rem; color: #999; margin-top: 5px; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .logout-btn { color: var(--text-gray); transition: 0.2s; font-size: 1.1rem; }
        .logout-btn:hover { color: #d32f2f; }
        .content-padding { padding: 2rem; max-width: 1100px; width: 100%; margin: 0 auto;}
        .grid-layout { display: grid; grid-template-columns: 350px 1fr; gap: 2rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-gray); }
        select, input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; font-size: 0.9rem; }
        .btn-save { background: var(--primary-green); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; margin-top: 10px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 600; border: 1px solid transparent; }
        .alert-danger { background: #FEF2F2; color: #DC2626; border-color: #FEE2E2; }
        .alert-success { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
        .filter-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .filter-controls { display: flex; gap: 10px; }
        .filter-controls select { width: auto; min-width: 150px; background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="healthLogs.php" class="nav-item active"><i class="fa-solid fa-notes-medical"></i> Health Logs</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item"><i class="fa-solid fa-stethoscope"></i> Symptom Checker</a>
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
            <h2 style="margin-bottom: 5px;">Daily Health & Weight Logs</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem; font-size: 0.9rem;">Monitor your pet's vital signs and weight trends.</p>

            <?php if(isset($_GET['alert'])): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> CRITICAL: Abnormal change detected! Notification sent to your vet.</div>
            <?php elseif(isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Record saved and graph updated.</div>
            <?php endif; ?>

            <div class="grid-layout">
                <div class="card">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem;"><i class="fa-solid fa-plus-circle"></i> Add New Entry</h3>
                    <form method="POST" action="../../controllers/HealthLogController.php">
                        <div class="form-group">
                            <label>Select Pet</label>
                            <select name="pet_id" required>
                                <?php foreach($pets as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $selected_pet_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Metric Type</label>
                            <select name="metric_name" required>
                                <option value="Weight">Weight (kg)</option>
                                <option value="Blood Sugar">Blood Sugar (mg/dL)</option>
                                <option value="Insulin Dose">Insulin Dose (Units)</option>
                                <option value="Water Intake">Water Intake (ml)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Value</label>
                            <input type="number" step="0.1" name="metric_value" placeholder="Enter numeric value" required>
                        </div>
                        <button type="submit" name="log_metric" class="btn-save">Save Entry</button>
                    </form>
                </div>

                <div class="card">
                    <div class="filter-header">
                        <h3 style="font-size: 1rem;"><i class="fa-solid fa-chart-line"></i> Health Trends</h3>
                        <form id="filterForm" method="GET" class="filter-controls">
                            <select name="pet_id" onchange="this.form.submit()">
                                <?php foreach($pets as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $selected_pet_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="metric" onchange="this.form.submit()">
                                <option value="Weight" <?= $selected_metric == 'Weight' ? 'selected' : '' ?>>Weight History</option>
                                <option value="Blood Sugar" <?= $selected_metric == 'Blood Sugar' ? 'selected' : '' ?>>Blood Sugar</option>
                                <option value="Insulin Dose" <?= $selected_metric == 'Insulin Dose' ? 'selected' : '' ?>>Insulin Dose</option>
                                <option value="Water Intake" <?= $selected_metric == 'Water Intake' ? 'selected' : '' ?>>Water Intake</option>
                            </select>
                        </form>
                    </div>
                    
                    <div style="height: 300px; position: relative;">
                        <?php if (empty($chart_labels)): ?>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #999; text-align: center;">
                                <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                                No data found for this pet's <?= strtolower($selected_metric) ?>.
                            </div>
                        <?php else: ?>
                            <canvas id="trendChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($chart_labels)): ?>
    <script>
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: '<?= htmlspecialchars($selected_metric) ?>',
                    data: <?= json_encode($chart_data) ?>,
                    borderColor: '#589A64',
                    backgroundColor: 'rgba(88, 154, 100, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#589A64',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: false, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>

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
    <?php endif; ?>
</body>
</html>