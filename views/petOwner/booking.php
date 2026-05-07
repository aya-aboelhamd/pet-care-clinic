<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/BookingModel.php';

$database = new Database();
$db = $database->getConnection();
$bookingModel = new BookingModel($db);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$pets = $bookingModel->getUserPets($user_id);
$vets = $bookingModel->getProvidersByRole(3);
$providers = $bookingModel->getProvidersByRole(4);
$myBookings = $bookingModel->getUserBookings($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Book a Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .content-padding { padding: 2rem; }
        .booking-grid { display: grid; grid-template-columns: 1.8fr 1fr; gap: 2rem; }
        .booking-form-card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 2rem; }
        .section-label { font-size: 0.9rem; font-weight: 700; margin-bottom: 1rem; display: block; }
        .service-selector { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 2rem; }
        .service-btn { border: 1px solid var(--border-color); background: white; padding: 1rem 0.5rem; border-radius: 12px; text-align: center; cursor: pointer; transition: 0.2s; }
        .service-btn i { display: block; font-size: 1.5rem; margin-bottom: 8px; color: var(--text-gray); }
        .service-btn span { font-size: 0.75rem; font-weight: 600; color: var(--text-gray); }
        .service-btn.active { border-color: var(--primary-green); background: #F0F7F1; }
        .service-btn.active i, .service-btn.active span { color: var(--primary-green); }
        .pet-selector { display: flex; gap: 15px; margin-bottom: 2rem; flex-wrap: wrap; }
        .pet-option { flex: 1; min-width: 150px; border: 1px solid var(--border-color); padding: 1rem; border-radius: 12px; display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .pet-option.active { border-color: var(--primary-green); background: #F0F7F1; }
        .inputs-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-gray); }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; outline: none; }
        .request-btn { background: var(--primary-green); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; }
        .bookings-sidebar { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; }
        .booking-item { padding: 1rem; border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 12px; display: flex; flex-direction: column; align-items: flex-start; }
        .status-badge { font-size: 0.7rem; padding: 3px 10px; border-radius: 20px; font-weight: 700; height: fit-content; }
        .status-confirmed { background: #DCFCE7; color: #16A34A; }
        .status-pending { background: #FFF4E5; color: #D97706; }
        .hidden { display: none; }
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
        <a href="booking.php" class="nav-item active"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Symptom Checker</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Welcome back, <?= htmlspecialchars($user_name) ?> 👋</strong></div>
            <div class="user-profile">
                <div class="avatar-circle"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
            </div>
        </header>

        <div class="content-padding">
            <h2 style="font-size: 1.8rem; margin-bottom: 2rem;">Book a Service</h2>
            
            <?php if(isset($_GET['success'])): ?>
                <div style="background: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; margin-bottom: 20px;">Booking request sent successfully!</div>
            <?php endif; ?>

            <div class="booking-grid">
                <div class="booking-form-card">
                    <form action="../../controllers/BookingController.php" method="POST">
                        <span class="section-label">Select a service</span>
                        <div class="service-selector">
                            <div class="service-btn" data-service="Walking" data-role="provider"><i class="fa-solid fa-person-walking"></i><span>Walking</span></div>
                            <div class="service-btn" data-service="Vet Visit" data-role="vet"><i class="fa-solid fa-stethoscope"></i><span>Vet Visit</span></div>
                        </div>
                        <input type="hidden" name="service_type" id="service_type" value="Grooming">

                        <span class="section-label">Pet</span>
                        <div class="pet-selector">
                            <?php foreach($pets as $pet): ?>
                                <div class="pet-option" data-id="<?= $pet['id'] ?>">
                                    <span class="icon"><?= (strtolower($pet['type']) == 'dog' || $pet['type'] == '🐶') ? '🐶' : '🐱' ?></span>
                                    <div><strong><?= htmlspecialchars($pet['name']) ?></strong><br><small><?= htmlspecialchars($pet['breed']) ?></small></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="pet_id" id="pet_id" required>

                        <div class="form-group">
                            <label>Select Provider / Doctor</label>
                            <select name="provider_id" id="provider_select" required>
                                <option value="">-- Choose --</option>
                                <optgroup label="Service Providers" id="opt-provider">
                                    <?php foreach($providers as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Veterinarians" id="opt-vet" class="hidden">
                                    <?php foreach($vets as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option><?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>

                        <div class="inputs-row">
                            <div class="form-group"><label>Date</label><input type="date" name="booking_date" required></div>
                            <div class="form-group"><label>Time</label><input type="time" name="booking_time" required></div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" placeholder="Any special instructions?"></textarea>
                        </div>

                        <button type="submit" name="request_booking" class="request-btn">Request booking</button>
                    </form>
                </div>

                <div class="bookings-sidebar">
                    <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem;">Recent bookings</h3>
                    
                    <?php if(isset($_GET['dispute_success'])): ?>
                        <div style="background: #FEE2E2; color: #DC2626; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem; border: 1px solid #FECACA;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Dispute sent to admin successfully.
                        </div>
                    <?php endif; ?>

                    <?php if(empty($myBookings)): ?>
                        <p style="color: #999; text-align: center;">No bookings yet.</p>
                    <?php else: ?>
                        <?php foreach($myBookings as $bk): ?>
                            <div class="booking-item">
                                <div style="display: flex; justify-content: space-between; width: 100%;">
                                    <div>
                                        <strong><?= htmlspecialchars($bk['service_type']) ?></strong><br>
                                        <small style="color: #888;"><?= htmlspecialchars($bk['pet_name']) ?> • <?= date('M d, H:i', strtotime($bk['start_time'])) ?></small>
                                    </div>
                                    <span class="status-badge <?= in_array(strtolower($bk['status']), ['confirmed', 'completed']) ? 'status-confirmed' : 'status-pending' ?>">
                                        <?= htmlspecialchars($bk['status']) ?>
                                    </span>
                                </div>
                                
                                <?php if(strtolower($bk['status']) == 'completed'): ?>
                                    <form action="../../controllers/DisputeController.php" method="POST" style="margin-top: 12px; width: 100%; display: flex; gap: 8px;">
                                        <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                                        <input type="text" name="reason" placeholder="Issue? Type reason..." required style="flex: 1; padding: 8px; font-size: 0.8rem; border: 1px solid var(--border-color); border-radius: 6px; outline: none;">
                                        <button type="submit" name="submit_dispute" style="background: #DC2626; color: white; border: none; padding: 8px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">Dispute</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.service-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('service_type').value = this.dataset.service;
                const role = this.dataset.role;
                document.getElementById('opt-provider').classList.toggle('hidden', role !== 'provider');
                document.getElementById('opt-vet').classList.toggle('hidden', role !== 'vet');
                document.getElementById('provider_select').value = "";
            });
        });

        document.querySelectorAll('.pet-option').forEach(opt => {
            opt.addEventListener('click', function() {
                document.querySelectorAll('.pet-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('pet_id').value = this.dataset.id;
            });
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const petId = document.getElementById('pet_id').value;
            if (!petId || petId === "") {
                e.preventDefault();
                alert("Please select a pet before requesting a booking.");
            }
        });
    </script>
</body>
</html>