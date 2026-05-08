<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Add Service Zone</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        :root {
            --primary-green: #589A64;
            --bg-light: #F8FAF8;
            --sidebar-width: 240px;
            --text-dark: #1A1A1A;
            --text-gray: #666;
            --border-color: #E0E0E0;
            --alert-red: #E53935;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; color: var(--text-dark); }

        /* --- Sidebar --- */
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; justify-content: space-between; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }

        /* --- Main Content --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .content-padding { padding: 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; }
        .status-pill { background: #EEF2EE; color: #589A64; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; border: 1px solid #DCE5DC; }

        /* Main */
        .main-content {
            flex: 1;
            overflow-y: auto;
        }

        header {
            background: white;
            padding: 0.8rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
        }

        .content-padding {
            padding: 2rem;
        }

        .page-header h2 {
            font-size: 1.8rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 1rem;
        }

        .input-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        input {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            outline: none;
        }

        #map {
            height: 420px;
            margin: 1rem 0;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .btn-save {
            width: 100%;
            padding: 12px;
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        #coords-display {
            font-size: 0.75rem;
            color: var(--text-gray);
        }
        
    </style>
</head>

<body>

<!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-logo">
                <i class="fa-solid fa-paw"></i> 
                <div>Petlor <br></div>
            </div>
            <a href="providerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="bookingRequests.php" class="nav-item "><i class="fa-regular fa-calendar-check"></i> Booking Requests</a>
            <a href="qrCheckin.php" class="nav-item"><i class="fa-solid fa-qrcode"></i> QR Check-in</a>
            <a href="walkTracker.php" class="nav-item"><i class="fa-solid fa-shoe-prints"></i> Walk Tracker</a>
            <a href="addServiceZone.php" class="nav-item active"><i class="fa-solid fa-map-location-dot"></i>Add Service Zone</a>
            <a href="myServiceZones.php" class="nav-item "><i class="fa-solid fa-location-dot"></i>My Service Zones</a>
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
                    <h2 style="font-size: 2rem; font-weight: 700;">Set Service Zone</h2>
                    <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Define Service Area.</p>
                </div>
            </div>

<div class="card">

<form action="/petCare/pet-care-clinic-main/controllers/ServiceZoneController.php?action=save" method="POST">

    <div class="form-row">
        <div class="input-group">
            <label>Zone Name</label>
            <input type="text" name="zone_name" required>
        </div>

        <div class="input-group">
            <label>Radius (KM)</label>
            <input type="number" id="radiusInput" name="radius" value="5" min="1">
        </div>
    </div>

    <input type="hidden" id="lat" name="lat" required>
    <input type="hidden" id="lng" name="lng" required>

    <div id="map"></div>

    <div id="coords-display">Click on map...</div>

    <button type="submit" class="btn-save">
        Save Service Zone
    </button>

</form>

</div>

</div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map = L.map('map').setView([30.0444, 31.2357], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

let marker, circle;

map.on('click', function(e){

    let lat = e.latlng.lat;
    let lng = e.latlng.lng;
    let radius = document.getElementById('radiusInput').value;

    document.getElementById('lat').value = lat;
    document.getElementById('lng').value = lng;

    document.getElementById('coords-display').innerText =
        lat.toFixed(5) + ", " + lng.toFixed(5);

    if(marker) map.removeLayer(marker);
    if(circle) map.removeLayer(circle);

    marker = L.marker([lat, lng]).addTo(map);

    circle = L.circle([lat, lng], {
        radius: radius * 1000,
        color: "#589A64",
        fillOpacity: 0.2
    }).addTo(map);
});

document.getElementById('radiusInput').addEventListener('input', function(){
    if(circle){
        circle.setRadius(this.value * 1000);
    }
});
</script>

</body>
</html>