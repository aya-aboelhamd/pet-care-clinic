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
$user_name = $_SESSION['user_name'];

$stmt = $db->prepare("SELECT id, name, species, breed FROM pet WHERE user_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Report Lost Pet</title>
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
            --warning-bg: #FFFBEB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }

        .main-content { flex-grow: 1; overflow-y: auto; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 10; }
        
        .content-padding { padding: 2rem; max-width: 1000px; margin: 0 auto; }

        .page-header { margin-bottom: 2rem; }
        .page-header h1 { color: var(--text-dark); font-size: 1.8rem; margin-bottom: 8px; }
        .page-header p { color: var(--text-gray); font-size: 0.95rem; }

        .report-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }
        
        .card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-title { font-weight: 700; font-size: 1rem; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 10px; color: var(--text-dark); }

        .form-group { margin-bottom: 1.2rem; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-dark); }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; font-size: 0.9rem; transition: 0.2s; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(88, 154, 100, 0.1); }
        .form-textarea { resize: none; height: 100px; }

        .photo-upload-zone { border: 2px dashed #CBD5E1; border-radius: 12px; padding: 2.5rem 1rem; text-align: center; cursor: pointer; transition: 0.2s; background: #FBFDFB; }
        .photo-upload-zone:hover { background: #F0F7F1; border-color: var(--primary-green); }
        .photo-upload-zone i { font-size: 2.5rem; color: var(--primary-green); margin-bottom: 12px; opacity: 0.7; }

        .btn-submit { background: #DC2626; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; font-size: 0.95rem; transition: 0.3s; }
        .btn-submit:hover { background: #B91C1C; transform: translateY(-1px); }

        .info-alert { background: #F0F7F1; border: 1px solid #D1E7D5; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; font-size: 0.85rem; color: #3E6D47; display: flex; gap: 12px; align-items: center; }
        .success-alert { background: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #BBF7D0; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
         <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="healthLogs.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Health Logs</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Symptom Checker</a>
        <a href="reportLostPet.php" class="nav-item active"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Emergency / <strong>Report Lost Pet</strong></div>
            <div class="user-profile" style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 32px; height: 32px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
                <div style="font-size: 0.85rem;"><strong><?= htmlspecialchars($user_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h1>Report a Lost Pet</h1>
                <p>Broadcasting this report will alert local pet owners and veterinary clinics.</p>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="success-alert">
                    <i class="fa-solid fa-check-circle"></i> Emergency alert has been broadcasted to all users successfully.
                </div>
            <?php endif; ?>

            <div class="info-alert">
                <i class="fa-solid fa-circle-info" style="font-size: 1.2rem;"></i>
                <p>Information provided here will be public on the "Found Pets" board. Please double-check your contact number.</p>
            </div>

            <form action="../../controllers/LostPetController.php" method="POST">
                <div class="report-grid">
                    <div class="form-col">
                        <div class="card">
                            <div class="card-title"><i class="fa-solid fa-paw"></i> Pet Details</div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Select Registered Pet</label>
                                    <select class="form-select" name="pet_id" required>
                                        <option value="" disabled selected>-- Choose Pet --</option>
                                        <?php foreach($pets as $pet): ?>
                                            <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['breed']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Collar / Harness Color</label>
                                    <input type="text" name="collar_color" class="form-input" placeholder="e.g. Red leather collar">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Distinctive Features</label>
                                <textarea name="features" class="form-textarea" placeholder="Describe unique marks, behavior, or medical needs..."></textarea>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-title"><i class="fa-solid fa-location-dot"></i> Last Seen Details</div>
                            <div class="form-group">
                                <label class="form-label">Exact Location / Area</label>
                                <input type="text" name="location" class="form-input" placeholder="e.g. Central Park West Entrance" required>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Date Lost</label>
                                    <input type="date" name="date_lost" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Approximate Time</label>
                                    <input type="time" name="time_lost" class="form-input" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-col">
                        <div class="card">
                            <div class="card-title"><i class="fa-solid fa-image"></i> Recent Photo</div>
                            <div class="photo-upload-zone">
                                <i class="fa-solid fa-camera"></i>
                                <p style="font-size: 0.85rem; font-weight: 500; color: var(--text-dark);">Upload Photo</p>
                                <p style="font-size: 0.75rem; color: var(--text-gray); margin-top: 5px;">Best for identification</p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-title"><i class="fa-solid fa-phone"></i> Contact Details</div>
                            <div class="form-group">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" name="contact_phone" class="form-input" required placeholder="e.g. +1 234 567 890">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Reward Amount (Optional)</label>
                                <div style="position: relative; display: flex; align-items: center;">
                                    <span style="position: absolute; left: 12px; font-weight: 700; color: var(--text-gray);">$</span>
                                    <input type="number" name="reward" class="form-input" style="padding-left: 28px;" placeholder="0.00">
                                </div>
                            </div>
                            <button type="submit" name="report_lost" class="btn-submit">Launch Alert Now</button>
                            <p style="text-align: center; font-size: 0.7rem; color: var(--text-gray); margin-top: 15px;">
                                <i class="fa-solid fa-shield-halved"></i> Secured by Petlor Emergency Response
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

</body>
</html>