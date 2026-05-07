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
    header("Location: qrCheckin.php");
    exit();
}

$stmt = $db->prepare("
    SELECT b.id, p.name as pet_name, b.service_type, b.status 
    FROM booking b 
    JOIN pet p ON b.pet_id = p.id 
    WHERE b.provider_id = ? AND b.status IN ('Pending', 'Confirmed', 'In Progress') AND DATE(b.start_time) = CURDATE()
");
$stmt->execute([$user_id]);
$active_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
    SELECT b.id, p.name as pet_name, b.checkin_time, b.checkout_time, b.status 
    FROM booking b 
    JOIN pet p ON b.pet_id = p.id 
    WHERE b.provider_id = ? AND (DATE(b.checkin_time) = CURDATE() OR DATE(b.checkout_time) = CURDATE())
    ORDER BY GREATEST(COALESCE(b.checkout_time, '2000-01-01'), COALESCE(b.checkin_time, '2000-01-01')) DESC
");
$stmt->execute([$user_id]);
$todays_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - QR Check-in</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }
        .qr-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 400px; }
        .qr-scanner-frame { width: 250px; height: 250px; border: 2px dashed #C8E6C9; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; background: #F1F8F1; }
        .qr-scanner-frame i { font-size: 8rem; color: var(--primary-green); opacity: 0.8; }
        .scanner-hint { color: var(--text-gray); font-size: 0.85rem; margin-bottom: 1.5rem; text-align: center;}
        .btn-group { display: flex; gap: 10px; width: 100%; justify-content: center;}
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: 0.2s; border: 1px solid transparent; width: 100%; max-width: 200px;}
        .btn-primary { background: var(--primary-green); color: white; }
        .btn-primary:disabled { background: #ccc; cursor: not-allowed; }
        .btn-danger { background: #DC2626; color: white; }
        .activity-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; height: fit-content;}
        .activity-card h4 { margin-bottom: 1.5rem; font-size: 1rem; }
        .activity-item { border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .activity-info strong { display: block; font-size: 0.9rem; }
        .activity-info span { font-size: 0.75rem; color: #999; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .badge-checkin { background: #E8F5E9; color: #4CAF50; border: 1px solid #C8E6C9; }
        .badge-checkout { background: #E3F2FD; color: #2196F3; border: 1px solid #BBDEFB; }
        .badge-duration { background: #FFF3E0; color: #E65100; border: 1px solid #FFE0B2; margin-top: 5px; display: inline-block; font-size: 0.65rem;}
        select { width: 80%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 20px; font-size: 1rem; outline: none; }
        .alert-success { background: #F0FDF4; color: #16A34A; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; border: 1px solid #BBF7D0; width: 100%;}
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo">
                <i class="fa-solid fa-paw"></i> 
                <div>Petlor</div>
            </div>
            <a href="providerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="bookingRequests.php" class="nav-item"><i class="fa-regular fa-calendar-check"></i> Booking Requests</a>
            <a href="qrCheckin.php" class="nav-item active"><i class="fa-solid fa-qrcode"></i> QR Check-in</a>
            <a href="walkTracker.php" class="nav-item"><i class="fa-solid fa-shoe-prints"></i> Walk Tracker</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Provider Portal / <strong>Welcome back 👋</strong></div>
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
                <div><strong><?= htmlspecialchars($user_name) ?></strong></div>
                <a href="../Auth/logout.php" style="color: #ccc; margin-left: 10px; border: 1px solid #eee; padding: 5px; border-radius: 5px;"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Service Check-in / Check-out</h2>
                <p>Select a confirmed booking to start, or an active session to end and calculate duration.</p>
            </div>

            <div class="qr-grid">
                <div class="card">
                    <?php if(isset($_GET['success'])): ?>
                        <div class="alert-success"><i class="fa-solid fa-check"></i> Action recorded successfully!</div>
                    <?php endif; ?>

                    <div class="qr-scanner-frame"><i class="fa-solid fa-qrcode"></i></div>
                    
                    <form method="POST" action="../../controllers/CheckinController.php" style="width: 100%; text-align: center;">
                        <p class="scanner-hint">Simulate QR scan by selecting the booking manually below:</p>
                        
                        <select name="booking_id" id="booking_select" onchange="updateAction()" required>
                            <option value="" disabled selected>-- Select a booking --</option>
                            <?php foreach($active_bookings as $ab): ?>
                                <option value="<?= $ab['id'] ?>" data-status="<?= $ab['status'] ?>">
                                    <?= htmlspecialchars($ab['pet_name']) ?> - <?= htmlspecialchars($ab['service_type']) ?> (<?= htmlspecialchars($ab['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="hidden" name="action" id="action_input" value="">

                        <div class="btn-group">
                            <button type="submit" id="submit_btn" class="btn btn-primary" disabled>Select booking first</button>
                        </div>
                    </form>
                </div>

                <div class="activity-card">
                    <h4>Today's activity</h4>
                    <?php if(empty($todays_activity)): ?>
                        <div style="text-align: center; color: #999; padding: 20px;">No check-ins logged today.</div>
                    <?php else: ?>
                        <?php foreach($todays_activity as $act): ?>
                            <?php if(!empty($act['checkin_time'])): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <strong><?= htmlspecialchars($act['pet_name']) ?></strong>
                                        <span><?= date('H:i', strtotime($act['checkin_time'])) ?></span>
                                    </div>
                                    <span class="badge badge-checkin">Check-in</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($act['checkout_time'])): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <strong><?= htmlspecialchars($act['pet_name']) ?></strong>
                                        <span><?= date('H:i', strtotime($act['checkout_time'])) ?></span>
                                        <?php 
                                            if(!empty($act['checkin_time'])) {
                                                $in = new DateTime($act['checkin_time']);
                                                $out = new DateTime($act['checkout_time']);
                                                $diff = $in->diff($out);
                                                $duration = $diff->format('%h hr %i min');
                                                echo "<div class='badge badge-duration'>Duration: $duration</div>";
                                            }
                                        ?>
                                    </div>
                                    <span class="badge badge-checkout">Check-out</span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateAction() {
            var select = document.getElementById('booking_select');
            var selectedOption = select.options[select.selectedIndex];
            var status = selectedOption.getAttribute('data-status');
            var btn = document.getElementById('submit_btn');
            var actionInput = document.getElementById('action_input');

            btn.disabled = false;

            if (status === 'Confirmed' || status === 'Pending') {
                btn.className = 'btn btn-primary';
                btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Session (Check-in)';
                actionInput.value = 'checkin';
            } else if (status === 'In Progress') {
                btn.className = 'btn btn-danger';
                btn.innerHTML = '<i class="fa-solid fa-stop"></i> End Session (Check-out)';
                actionInput.value = 'checkout';
            }
        }

        function toggleNotif(event) { event.stopPropagation(); document.getElementById('notifDropdown').classList.toggle('show'); }
        window.onclick = function(event) { if (!event.target.closest('.bell-wrapper')) { var dropdowns = document.getElementsByClassName("notif-dropdown"); for (var i = 0; i < dropdowns.length; i++) { if (dropdowns[i].classList.contains('show')) dropdowns[i].classList.remove('show'); } } }
    </script>
</body>
</html>