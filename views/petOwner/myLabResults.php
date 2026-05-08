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
    header("Location: myLabResults.php");
    exit();
}

// جلب حيوانات المستخدم
$stmt = $db->prepare("SELECT id, name FROM pet WHERE user_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// لو اليوزر اختار حيوان معين لعرض تحاليله
$selected_pet_id = $_GET['pet_id'] ?? ($pets[0]['id'] ?? 0);
$results = [];

if ($selected_pet_id) {
    $stmt = $db->prepare("
        SELECT l.*, u.name as vet_name 
        FROM lab_results l 
        JOIN users u ON l.vet_id = u.id 
        WHERE l.pet_id = ? 
        ORDER BY l.uploaded_at DESC
    ");
    $stmt->execute([$selected_pet_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Petlor - My Lab Results</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* نفس الستايل العام بتاع المالك */
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --border-color: #E0E0E0; --text-dark: #1A1A1A; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 240px; background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: #666; font-size: 0.9rem; font-weight: 500; }
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
        .content-padding { padding: 2.5rem; max-width: 900px; width: 100%; margin: 0 auto; }
        
        .filter-bar { margin-bottom: 2rem; display: flex; gap: 10px; }
        .filter-bar select { padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); outline: none; }
        .filter-bar button { padding: 10px 20px; background: var(--text-dark); color: white; border: none; border-radius: 8px; cursor: pointer; }

        .result-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; position: relative; }
        .result-card.critical { border-color: #FCA5A5; background: #FFF5F5; }
        .result-header { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem; }
        .insight-box { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; color: #166534; }
        .download-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 15px; background: #eee; color: #333; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
        .download-btn:hover { background: #e0e0e0; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="healthLogs.php" class="nav-item"><i class="fa-solid fa-notes-medical"></i> Health Logs</a>
        <!-- لينك التحاليل للمالك -->
        <a href="myLabResults.php" class="nav-item active"><i class="fa-solid fa-flask"></i> Lab Results</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
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
            <h1 style="margin-bottom: 5px;">My Lab Results</h1>
            <p style="color: #666; margin-bottom: 2rem;">Simplified insights translated by your veterinarian.</p>

            <form class="filter-bar" method="GET">
                <select name="pet_id">
                    <?php foreach($pets as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $selected_pet_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">View Results</button>
            </form>

            <?php if(empty($results)): ?>
                <div style="text-align:center; padding: 3rem; color: #999;">No lab results found for this pet.</div>
            <?php else: ?>
                <?php foreach($results as $res): ?>
                    <div class="result-card <?= $res['is_critical'] ? 'critical' : '' ?>">
                        <?php if($res['is_critical']): ?>
                            <div style="position: absolute; top: -10px; right: 20px; background: #DC2626; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold;">CRITICAL</div>
                        <?php endif; ?>
                        
                        <div class="result-header">
                            <div>
                                <h3 style="margin-bottom: 5px;"><?= htmlspecialchars($res['test_name']) ?></h3>
                                <small style="color: #666;"><i class="fa-regular fa-calendar"></i> <?= date("M j, Y", strtotime($res['uploaded_at'])) ?> • By <?= htmlspecialchars($res['vet_name']) ?></small>
                            </div>
                            <a href="../../<?= htmlspecialchars($res['file_path']) ?>" target="_blank" class="download-btn">
                                <i class="fa-solid fa-file-arrow-down"></i> View Original Report
                            </a>
                        </div>

                        <!-- عرض الشرح المبسط فقط (Typical Course 3) -->
                        <div class="insight-box">
                            <strong><i class="fa-solid fa-lightbulb"></i> Vet's Simplified Insight:</strong><br>
                            <?= nl2br(htmlspecialchars($res['simplified_insight'])) ?>
                        </div>

                        <?php if(!empty($res['vet_notes'])): ?>
                            <p style="font-size: 0.85rem; color: #444;"><strong>Notes/Next Steps:</strong> <?= htmlspecialchars($res['vet_notes']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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