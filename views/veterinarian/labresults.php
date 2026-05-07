<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/LabResultModel.php';

$database = new Database();
$db = $database->getConnection();
$labModel = new LabResultModel($db);

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
    header("Location: labresults.php");
    exit();
}

$stats = $labModel->getStats($user_id);
$results = $labModel->getAllResults($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Lab Results</title>
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

        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; justify-content: space-between; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .nav-item:hover:not(.active) { background: #f0f0f0; }

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
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }

        .upload-btn { background: var(--primary-green); color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; }

        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .stat-info p { font-size: 0.7rem; color: #999; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
        .stat-info h3 { font-size: 1.8rem; font-weight: 700; }
        .stat-card i { font-size: 1.5rem; }
        
        .icon-normal { color: #589A64; }
        .icon-abnormal { color: #FFB74D; }

        .table-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--text-gray); font-size: 0.8rem; font-weight: 600; padding: 12px 10px; border-bottom: 1px solid var(--border-color); }
        td { padding: 15px 10px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .pet-cell { display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-normal { background: #E8F5E9; color: #4CAF50; border: 1px solid #C8E6C9; }
        .badge-abnormal { background: #FFF3E0; color: #E65100; border: 1px solid #FFE0B2; }

        .view-btn { background: #F5F5F5; border: 1px solid var(--border-color); padding: 6px 15px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .view-btn:hover { background: #eeeeee; }
        .empty-state { text-align: center; color: #999; padding: 30px; font-size: 0.95rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo">
                <i class="fa-solid fa-paw"></i> 
                <div>Petlor <br></div>
            </div>
            <a href="vetDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="createprescription.php" class="nav-item"><i class="fa-regular fa-file-lines"></i> New Prescription</a>
            <a href="labresults.php" class="nav-item active"><i class="fa-solid fa-flask"></i> Lab Results</a>
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
                <div>
                    <h2>Lab Results</h2>
                    <p>Review and upload laboratory test results.</p>
                </div>
                <a href="uploadresult.php" class="upload-btn">
                    <i class="fa-solid fa-upload"></i> Upload result
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <p>Normal</p>
                        <h3><?= $stats['normal_count'] ?></h3>
                    </div>
                    <i class="fa-solid fa-flask icon-normal"></i>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <p>Abnormal</p>
                        <h3><?= $stats['abnormal_count'] ?></h3>
                    </div>
                    <i class="fa-solid fa-flask icon-abnormal"></i>
                </div>
            </div>

            <div class="table-card">
                <?php if (empty($results)): ?>
                    <div class="empty-state">No lab results found for your patients.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Pet</th>
                                <th>Test</th>
                                <th>Date</th>
                                <th>Result</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $res): ?>
                                <tr>
                                    <td>
                                        <div class="pet-cell">
                                            <?= (strtolower($res['species']) == 'cat') ? '🐈' : '🐕' ?> 
                                            <?= htmlspecialchars($res['pet_name']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($res['test_name']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($res['uploaded_at'])) ?></td>
                                    <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($res['technical_data']) ?></td>
                                    <td>
                                        <?php if ($res['is_critical'] == 1): ?>
                                            <span class="badge badge-abnormal">Abnormal</span>
                                        <?php else: ?>
                                            <span class="badge badge-normal">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
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