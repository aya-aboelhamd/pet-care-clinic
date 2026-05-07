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
    header("Location: bookingRequests.php");
    exit();
}

$stmt = $db->prepare("
    SELECT b.id as booking_id, b.service_type, b.start_time, b.status, 
           p.name as pet_name, p.species, p.allergies, p.medical_notes 
    FROM booking b 
    JOIN pet p ON b.pet_id = p.id 
    WHERE b.provider_id = ? 
    ORDER BY b.start_time DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Booking Requests</title>
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
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }

        .table-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--text-gray); font-size: 0.8rem; font-weight: 600; padding: 12px 10px; border-bottom: 1px solid var(--border-color); }
        td { padding: 15px 10px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .pet-cell { display: flex; align-items: center; gap: 10px; font-weight: 500; }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-confirmed { background: #E8F5E9; color: #4CAF50; border: 1px solid #C8E6C9; }
        .badge-pending { background: #FFF3E0; color: #E65100; border: 1px solid #FFE0B2; }
        .badge-cancelled { background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; }

        .action-cell { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-action {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: white;
            transition: 0.2s;
        }
        .btn-accept:hover { background: #f8faf8; border-color: var(--primary-green); color: var(--primary-green); }
        .btn-decline:hover { background: #fff8f8; border-color: #ff5252; color: #ff5252; }
        .empty-state { text-align: center; padding: 3rem; color: #999; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo">
                <i class="fa-solid fa-paw"></i> 
                <div>Petlor <br></div>
            </div>
            <a href="providerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="bookingRequests.php" class="nav-item active"><i class="fa-regular fa-calendar-check"></i> Booking Requests</a>
            <a href="qrCheckin.php" class="nav-item"><i class="fa-solid fa-qrcode"></i> QR Check-in</a>
            <a href="walkTracker.php" class="nav-item"><i class="fa-solid fa-shoe-prints"></i> Walk Tracker</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Provider Portal / <strong>Booking Requests</strong></div>
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
                <h2>Booking Requests</h2>
                <p>Accept or decline incoming requests from pet owners.</p>
            </div>

            <div class="table-card">
                <?php if (count($bookings) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Pet</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <?php 
                                    $icon = (strtolower($b['species']) == 'cat' || strtolower($b['species']) == 'قطه') ? '🐈' : '🐕';
                                    
                                    $notes_arr = [];
                                    if (!empty($b['allergies'])) $notes_arr[] = "Allergies: " . $b['allergies'];
                                    if (!empty($b['medical_notes'])) $notes_arr[] = $b['medical_notes'];
                                    $notes = empty($notes_arr) ? '—' : implode(' | ', $notes_arr);

                                    $status_class = 'badge-pending';
                                    if (strtolower($b['status']) == 'confirmed') $status_class = 'badge-confirmed';
                                    if (strtolower($b['status']) == 'cancelled' || strtolower($b['status']) == 'declined') $status_class = 'badge-cancelled';
                                ?>
                                <tr>
                                    <td><div class="pet-cell"><?= $icon ?> <?= htmlspecialchars($b['pet_name']) ?></div></td>
                                    <td><?= htmlspecialchars($b['service_type']) ?></td>
                                    <td><?= date('Y-m-d · H:i', strtotime($b['start_time'])) ?></td>
                                    <td style="max-width: 200px; color: #d32f2f; font-size: 0.8rem;"><?= htmlspecialchars($notes) ?></td>
                                    <td><span class="badge <?= $status_class ?>"><?= ucfirst(htmlspecialchars($b['status'])) ?></span></td>
                                    <td>
                                        <div class="action-cell">
                                            <?php if (strtolower($b['status']) == 'pending'): ?>
                                                <form method="POST" action="../../controllers/BookingStatusController.php" style="display:inline;">
                                                    <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                                    <input type="hidden" name="action" value="accept">
                                                    <button type="submit" class="btn-action btn-accept"><i class="fa-solid fa-check"></i> Accept</button>
                                                </form>
                                                <form method="POST" action="../../controllers/BookingStatusController.php" style="display:inline;">
                                                    <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                                    <input type="hidden" name="action" value="decline">
                                                    <button type="submit" class="btn-action btn-decline"><i class="fa-solid fa-xmark"></i> Decline</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 0.85rem; font-style: italic;">No actions available</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open" style="font-size: 3rem; margin-bottom: 15px; color: #ccc;"></i><br>
                        No booking requests found for your account.
                    </div>
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