<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/DiseaseAlertModel.php';

$database = new Database();
$db = $database->getConnection();
$alertModel = new DiseaseAlertModel($db);

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
    header("Location: diseasealert.php");
    exit();
}

$regions = $alertModel->getAvailableRegions();
$history = $alertModel->getAlertHistory($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Disease Outbreak Alerts</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --alert-red: #E53935; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; }
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
        .logout-icon { color: var(--text-gray); border: 1px solid var(--border-color); padding: 5px; border-radius: 5px; cursor: pointer; text-decoration: none;}

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
        .page-header { margin-bottom: 2rem; }
        .page-title { font-size: 1.8rem; margin-bottom: 5px; }
        .page-subtitle { color: var(--text-gray); font-size: 0.9rem; }
        .alerts-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; align-items: start; }
        .card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .card h3 { font-size: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.5rem; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; outline: none; }
        .broadcast-btn { background: var(--alert-red); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; margin-top: 1rem; transition: 0.3s; }
        .broadcast-btn:hover { background: #c62828; }
        
        .history-item { border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; margin-bottom: 1rem; position: relative; }
        .severity-badge { position: absolute; top: 10px; right: 10px; font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; font-weight: 700; }
        .severity-HIGH { background: #FFEBEE; color: #C62828; }
        .severity-MEDIUM { background: #FFF3E0; color: #E65100; }
        .severity-LOW { background: #E8F5E9; color: #2E7D32; }
        
        .history-item strong { display: block; font-size: 0.9rem; margin-bottom: 4px; }
        .history-item small { color: var(--text-gray); font-size: 0.75rem; }
        .history-item p { font-size: 0.8rem; color: #555; margin-top: 8px; line-height: 1.4; }
        .alert-success { background: #F0FDF4; color: #16A34A; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #BBF7D0; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> <div>Petlor</div></div>
            <a href="vetDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="createprescription.php" class="nav-item"><i class="fa-regular fa-file-lines"></i> New Prescription</a>
            <a href="labresults.php" class="nav-item"><i class="fa-solid fa-flask"></i> Lab Results</a>
            <a href="medicalnotes.php" class="nav-item"><i class="fa-regular fa-pen-to-square"></i> Medical Notes</a>
            <a href="diseasealert.php" class="nav-item active"><i class="fa-solid fa-bullhorn"></i> Disease Alerts</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Vet Portal / <strong>Disease Control</strong></div>
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
                <div><strong>Dr. <?= htmlspecialchars($user_name) ?></strong><br><span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span></div>
                <a href="../Auth/logout.php" class="logout-icon" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2 class="page-title">Disease Outbreak Alerts</h2>
                <p class="page-subtitle">Notify all registered owners in specific regions about health risks.</p>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert-success"><i class="fa-solid fa-check"></i> Alert broadcasted successfully to targeted users!</div>
            <?php elseif(isset($_GET['error'])): ?>
                <div class="alert-success" style="background: #FEF2F2; color: #DC2626; border-color: #FCA5A5;">
                    <i class="fa-solid fa-circle-exclamation"></i> An error occurred while sending the alert.
                </div>
            <?php endif; ?>

            <div class="alerts-grid">
                <div class="card">
                    <h3><i class="fa-solid fa-circle-exclamation" style="color: var(--alert-red);"></i> Issue New Alert</h3>
                    
                    <form method="POST" action="../../controllers/DiseaseAlertController.php">
                        <div class="form-group">
                            <label>Target City / Region</label>
                            <select name="region" required>
                                <option value="All Regions">All Regions</option>
                                <?php foreach($regions as $r): ?>
                                    <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Disease Name</label>
                                <input type="text" name="disease" placeholder="e.g. Parvovirus" required>
                            </div>
                            <div class="form-group">
                                <label>Severity Level</label>
                                <select name="severity" required>
                                    <option value="HIGH">High (Immediate Action)</option>
                                    <option value="MEDIUM">Medium (Watchful)</option>
                                    <option value="LOW">Low (Informational)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Message to Owners</label>
                            <textarea name="message" rows="5" placeholder="Describe the outbreak, symptoms to watch for, and preventive measures..." required></textarea>
                        </div>

                        <button type="submit" class="broadcast-btn" onclick="return confirm('Are you sure you want to broadcast this alert?');">
                            <i class="fa-solid fa-tower-broadcast"></i> Broadcast Alert
                        </button>
                    </form>
                </div>

                <div class="card">
                    <h3>Recent Notifications</h3>
                    
                    <?php if(empty($history)): ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No recent alerts issued.</p>
                    <?php else: ?>
                        <?php foreach($history as $h): ?>
                            <div class="history-item">
                                <span class="severity-badge severity-<?= htmlspecialchars($h['severity']) ?>"><?= htmlspecialchars($h['severity']) ?></span>
                                <strong><?= htmlspecialchars($h['disease_name']) ?></strong>
                                <small>Sent to: <?= htmlspecialchars($h['target_region']) ?> · <?= date('M j, Y H:i', strtotime($h['sent_at'])) ?></small>
                                <p><?= htmlspecialchars($h['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
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