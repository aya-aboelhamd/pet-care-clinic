<?php
require_once '../../models/Database.php';
require_once '../../models/ServiceZone.php';

session_start();

if (!isset($_SESSION['profile_id'])) {
    header("Location: ../Auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id'])) {
    header("Location: myServiceZones.php");
    exit();
}

$zone_id = $_GET['id'];

$query = "SELECT * FROM servicezone WHERE zone_id = :id AND profile_id = :profile_id";
$stmt = $conn->prepare($query);
$stmt->execute([
    'id'   => $zone_id, 
    'profile_id' => $_SESSION['profile_id'] 
]);
$zone = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$zone) {
    die("Zone not found or access denied.");
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $radius = $_POST['radius'];

    $updateQuery = "UPDATE servicezone SET name = :name, lat = :lat, lng = :lng, radius = :radius WHERE zone_id = :id";
    $updateStmt = $conn->prepare($updateQuery);
    
    if ($updateStmt->execute([
        'name'   => $name, 
        'lat'    => $lat, 
        'lng'    => $lng, 
        'radius' => $radius, 
        'id'     => $zone_id
    ])) {
        header("Location: myServiceZones.php?msg=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Edit Service Zone</title>
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

        /* Form Styling */
        .content-padding { padding: 2rem; }
        .edit-card { 
            background: white; 
            max-width: 600px; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            border: 1px solid var(--border-color);
        }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; color: var(--text-gray); }
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            font-size: 1rem;
            outline: none;
        }
        input:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(88, 154, 100, 0.1); }
        
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn-save { background: var(--primary-green); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; flex: 2; transition: 0.3s; }
        .btn-save:hover { opacity: 0.9; }
        .btn-cancel { background: #f0f0f0; color: #666; text-decoration: none; padding: 12px 20px; border-radius: 8px; text-align: center; flex: 1; font-weight: 500; }
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
            <div style="color: #666; font-size: 0.8rem;">Provider Portal / <strong>Edit Service Zone</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar">BW</div>
                <div><strong>Bella Walks Co.</strong></div>
            </div>
        </header>

        <div class="content-padding">
            <h2 style="margin-bottom: 20px; font-weight: 700;">Edit Service Zone</h2>
            
            <div class="edit-card">
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fa-solid fa-tag"></i> Zone Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($zone['name']) ?>" required>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label><i class="fa-solid fa-location-crosshairs"></i> Latitude</label>
                            <input type="number" step="any" name="lat" value="<?= $zone['lat'] ?>" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label><i class="fa-solid fa-location-dot"></i> Longitude</label>
                            <input type="number" step="any" name="lng" value="<?= $zone['lng'] ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fa-solid fa-circle-nodes"></i> Radius (KM)</label>
                        <input type="number" name="radius" value="<?= $zone['radius'] ?>" required>
                    </div>
                    
                    <div class="btn-group">
                        <a href="myServiceZones.php" class="btn-cancel">Cancel</a>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>