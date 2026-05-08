<?php
require_once '../../models/Database.php';
require_once '../../models/ServiceZone.php';

session_start();

if (!isset($_SESSION['profile_id'])) {
    header("Location: ../Auth/login.php");
    exit();
}

$db = new Database();
$zoneModel = new ServiceZone($db->getConnection());
$zones = $zoneModel->getZonesByProvider($_SESSION['profile_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - My Service Zones</title>
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

        /* --- Sidebar --- */
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; justify-content: space-between; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }

        /* --- Main Content Layout --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); flex-shrink: 0; }
        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .content-padding { padding: 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h2 { font-size: 2rem; font-weight: 700; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; margin-top: 5px; }

        /* Card & Table */
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); padding: 25px; }
        
        .btn-add { background-color: var(--primary-green); color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; }
        .btn-add:hover { opacity: 0.9; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; color: var(--text-gray); border-bottom: 2px solid var(--border-color); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid var(--border-color); color: var(--text-dark); font-size: 0.95rem; }

        /* --- Action Buttons Styling --- */
        .edit-btn, .delete-btn {
            font-size: 1.1rem;
            transition: 0.2s;
            text-decoration: none;
            display: inline-block; 
        }

        .edit-btn {
            color: var(--text-gray);
            margin-right: 20px; 
        }

        .edit-btn:hover {
            color: var(--primary-green);
            transform: scale(1.1);
        }

        .delete-btn {
            color: #E53935;
        }

        .delete-btn:hover {
            transform: scale(1.1);
            color: #b71c1c;
        }

        .badge-km { background: #EEF2EE; color: var(--primary-green); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; border: 1px solid #DCE5DC; }
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
            <a href="qrCheckin.php" class="nav-item"><i class="fa-solid fa-qrcode"></i> QR Check-in</a>
            <a href="walkTracker.php" class="nav-item"><i class="fa-solid fa-shoe-prints"></i> Walk Tracker</a>
            <a href="addServiceZone.php" class="nav-item"><i class="fa-solid fa-map-location-dot"></i> Add Service Zone</a>
            <a href="myServiceZones.php" class="nav-item active"><i class="fa-solid fa-map-location-dot"></i> My Service Zones</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Provider Portal / <strong>Welcome back, Bella 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar">BW</div>
                <div><strong>Bella Walks Co.</strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <div>
                    <h2>My Service Zones</h2>
                    <p>Manage the geographic areas where you provide your services.</p>
                </div>
                <a href="addServiceZone.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i> Add New Zone
                </a>
            </div>

            <div class="card">
                <?php if (empty($zones)): ?>
                    <div style="text-align: center; color: #999; padding: 60px;">
                        <i class="fa-solid fa-map-marked-alt" style="font-size: 3rem; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                        No zones found. Start by adding your first service area!
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Zone Name</th>
                                <th>Coordinates</th>
                                <th>Radius</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($zones as $zone): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($zone['name']) ?></strong></td>
                                    <td style="color: var(--text-gray); font-family: monospace;">
                                        <?= round($zone['lat'], 4) ?>, <?= round($zone['lng'], 4) ?>
                                    </td>
                                    <td><span class="badge-km"><?= $zone['radius'] ?> KM</span></td>
                                    <td style="text-align: center;">
                                        <a href="editServiceZone.php?id=<?= $zone['zone_id'] ?>" class="edit-btn" title="Edit Zone">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <a href="../../controllers/ServiceZoneController.php?action=delete&id=<?= $zone['zone_id'] ?>"
                                            class="delete-btn" title="Delete Zone"
                                            onclick="return confirm('Are you sure you want to delete this zone?')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>