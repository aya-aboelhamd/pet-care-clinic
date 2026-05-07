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

// جلب بيانات المستخدم
$stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user['name'] ?? 'User';
$initials = strtoupper(substr($user_name, 0, 1));

// جلب حيوانات المستخدم
$stmt = $db->prepare("SELECT id, name, species FROM pet WHERE user_id = ? AND (is_archived = 0 OR is_archived IS NULL)");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب بيانات الجراف (مثال: قياسات السكر) لآخر 30 يوم
$chart_labels = [];
$chart_data = [];

// هنجيب الداتا بتاعت أول حيوان كإفتراضي لعرضها في الجراف
$selected_pet_id = $_GET['pet_id'] ?? ($pets[0]['id'] ?? 0);
$selected_metric = $_GET['metric'] ?? 'Blood Sugar';

if ($selected_pet_id) {
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(logged_at, '%b %d') as log_date, AVG(metric_value) as avg_value 
        FROM health_metrics_log 
        WHERE pet_id = ? AND metric_name = ? AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(logged_at)
        ORDER BY DATE(logged_at) ASC
    ");
    $stmt->execute([$selected_pet_id, $selected_metric]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($logs as $log) {
        $chart_labels[] = $log['log_date'];
        $chart_data[] = $log['avg_value'];
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

        /* Sidebar basics */
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .nav-item:hover:not(.active) { background: #f0f0f0; }

        /* Main */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .avatar { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;}
        .content-padding { padding: 2.5rem; max-width: 1000px; width: 100%; margin: 0 auto;}
        
        .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; }
        select, input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; outline: none; }
        select:focus, input:focus { border-color: var(--primary-green); }
        .btn { background: var(--primary-green); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600;}
        .alert-danger { background: #FEF2F2; color: #DC2626; border: 1px solid #FEE2E2; }
        .alert-success { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
        
        /* Filter form */
        .filter-form { display: flex; gap: 10px; margin-bottom: 1rem; }
        .filter-form select { padding: 6px 10px; font-size: 0.8rem; width: auto; }
        .filter-form button { padding: 6px 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;}
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
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Chronic Condition Tracker</strong></div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="avatar"><?= $initials ?></div>
                <strong><?= htmlspecialchars($user_name) ?></strong>
            </div>
        </header>

        <div class="content-padding">
            <h2 style="margin-bottom: 5px;">Daily Health Logs</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem;">Track chronic conditions like Diabetes or Kidney Disease.</p>

            <?php if(isset($_GET['alert']) && $_GET['alert'] == 'critical'): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> CRITICAL ALERT: Abnormal value recorded! Your vet has been notified.</div>
            <?php elseif(isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Log saved successfully.</div>
            <?php endif; ?>

            <div class="grid-layout">
                <!-- فورم إدخال البيانات -->
                <div class="card">
                    <h3 style="margin-bottom: 1.5rem;">Add Daily Log</h3>
                    <form method="POST" action="../../controllers/HealthLogController.php">
                        <div class="form-group">
                            <label>Select Pet</label>
                            <select name="pet_id" required>
                                <?php foreach($pets as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Metric Type</label>
                            <select name="metric_name" required>
                                <option value="Blood Sugar">Blood Sugar (Diabetes)</option>
                                <option value="Insulin Dose">Insulin Dose (Diabetes)</option>
                                <option value="Water Intake">Water Intake (Kidney Disease)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Value</label>
                            <input type="number" step="0.1" name="metric_value" placeholder="e.g. 120" required>
                        </div>
                        <button type="submit" name="log_metric" class="btn">Save Log</button>
                    </form>
                </div>

                <!-- الجراف (Trend Chart) -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3>Monthly Trends</h3>
                        <!-- فلتر لعرض الجراف -->
                        <form class="filter-form" method="GET">
                            <select name="pet_id">
                                <?php foreach($pets as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $selected_pet_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="metric">
                                <option value="Blood Sugar" <?= $selected_metric == 'Blood Sugar' ? 'selected' : '' ?>>Blood Sugar</option>
                                <option value="Water Intake" <?= $selected_metric == 'Water Intake' ? 'selected' : '' ?>>Water Intake</option>
                            </select>
                            <button type="submit">Filter</button>
                        </form>
                    </div>
                    
                    <?php if (empty($chart_labels)): ?>
                        <div style="text-align: center; color: #999; padding: 3rem 0;">No data recorded for this metric yet.</div>
                    <?php else: ?>
                        <canvas id="trendChart" height="150"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- سكربت الجراف -->
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
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true }
        });
    </script>
    <?php endif; ?>
</body>
</html>