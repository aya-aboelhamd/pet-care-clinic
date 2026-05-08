<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../Auth/login.php");
    exit();
}

// قراءة بيانات التعارض لو موجودة
$conflict_data = null;
if (isset($_GET['conflict']) && isset($_SESSION['conflict_data'])) {
    $conflict_data = $_SESSION['conflict_data'];
}

require_once '../../models/Database.php';
require_once '../../models/BookingModel.php';

$database = new Database();
$db = $database->getConnection();
$bookingModel = new BookingModel($db);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Owner';
$first_name = explode(' ', trim($user_name))[0];
$initials = strtoupper(substr($first_name, 0, 2));

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
        .booking-grid { display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 2rem; }
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
        .status-completed { background: #EFF6FF; color: #2563EB; }
        .hidden { display: none; }
        
        /* Payment & Dispute Styles */
        .price-tag { background: #f8f9fa; padding: 8px 12px; border-radius: 8px; font-weight: bold; font-size: 1.1rem; color: var(--text-dark); margin: 10px 0; display: inline-block; border: 1px solid #e0e0e0; }
        .action-row { display: flex; gap: 10px; width: 100%; margin-top: 10px; }
        .pay-btn { background: var(--primary-green); color: white; border: none; padding: 8px; border-radius: 6px; font-weight: bold; cursor: pointer; flex: 1; display: flex; justify-content: center; align-items: center; gap: 5px; }
        .dispute-input { flex: 2; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; outline: none; font-size: 0.8rem;}
        .dispute-btn { background: white; color: #DC2626; border: 1px solid #FCA5A5; padding: 8px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;}
        .dispute-btn:hover { background: #FEF2F2; }
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
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Welcome back, <?= htmlspecialchars($first_name) ?> 👋</strong></div>
            <div class="user-profile">
                <div class="avatar-circle"><?= $initials ?></div>
            </div>
        </header>

        <div class="content-padding">
            <h2 style="font-size: 1.8rem; margin-bottom: 2rem;">Book a Service</h2>
            
            <?php if(isset($_GET['success'])): ?>
                <div style="background: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; margin-bottom: 20px;">Booking request sent successfully!</div>
            <?php elseif(isset($_GET['payment_success'])): ?>
                <div style="background: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><i class="fa-solid fa-check-circle"></i> Payment successful! Provider has been notified.</div>
            <?php elseif(isset($_GET['dispute_success'])): ?>
                <div style="background: #FEE2E2; color: #DC2626; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><i class="fa-solid fa-triangle-exclamation"></i> Payment frozen. Dispute sent to admin & provider.</div>
            <?php elseif(isset($_GET['waitlist_success'])): ?>
                <div style="background: #FFF4E5; color: #D97706; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fa-solid fa-clock"></i> You have been added to the waitlist! We will notify you if the slot opens up.
                </div>
            <?php endif; ?>

            <!-- شاشة التعارض (Conflict Resolution & Alternatives) -->
            <?php if($conflict_data): ?>
                <div style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #DC2626; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                    <h3 style="margin-bottom: 10px; font-size: 1.1rem;"><i class="fa-solid fa-calendar-xmark"></i> Time Slot Unavailable</h3>
                    <p style="font-size: 0.9rem; margin-bottom: 15px;">The requested provider is already booked for this 1-hour slot (<?= htmlspecialchars($conflict_data['booking_date']) ?> at <?= htmlspecialchars($conflict_data['booking_time']) ?>).</p>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                        <!-- فورم الانضمام لقائمة الانتظار -->
                        <form action="../../controllers/BookingController.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="pet_id" value="<?= htmlspecialchars($conflict_data['pet_id']) ?>">
                            <input type="hidden" name="provider_id" value="<?= htmlspecialchars($conflict_data['provider_id']) ?>">
                            <input type="hidden" name="service_type" value="<?= htmlspecialchars($conflict_data['service_type']) ?>">
                            <input type="hidden" name="booking_date" value="<?= htmlspecialchars($conflict_data['booking_date']) ?>">
                            <input type="hidden" name="booking_time" value="<?= htmlspecialchars($conflict_data['booking_time']) ?>">
                            <button type="submit" name="join_waitlist" style="background: #D97706; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                <i class="fa-solid fa-list-ol"></i> Join Waitlist Instead
                            </button>
                        </form>
                        <span style="color: #991B1B; font-size: 0.85rem;">Or try booking with:</span>
                    </div>

                    <!-- اقتراح دكاترة بدلاء -->
                    <?php if(!empty($conflict_data['alternatives'])): ?>
                        <div style="margin-top: 15px; border-top: 1px dashed #FCA5A5; padding-top: 15px;">
                            <strong style="font-size: 0.85rem; color: #991B1B;">Available Providers at this exact time:</strong>
                            <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                                <?php foreach($conflict_data['alternatives'] as $alt): ?>
                                    <span style="background: white; border: 1px solid #FCA5A5; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; color: #DC2626; font-weight: bold;">
                                        <i class="fa-solid fa-user-doctor"></i> <?= htmlspecialchars($alt['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                         <div style="margin-top: 15px; font-size: 0.85rem; color: #991B1B;">No other providers are available at this time.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="booking-grid">
                <div class="booking-form-card">
                    <!-- تم تعديل الـ ID بتاع الفورم هنا -->
                    <form id="bookingForm" action="../../controllers/BookingController.php" method="POST">
                        <span class="section-label">Select a service</span>
                        <div class="service-selector">
                            <div class="service-btn" data-service="Walking" data-role="provider"><i class="fa-solid fa-person-walking"></i><span>Walking</span></div>
                            <div class="service-btn" data-service="Vet Visit" data-role="vet"><i class="fa-solid fa-stethoscope"></i><span>Vet Visit</span></div>
                        </div>
                        <input type="hidden" name="service_type" id="service_type" value="Walking">

                        <span class="section-label">Pet</span>
                        <div class="pet-selector">
                            <?php foreach($pets as $pet): ?>
                                <!-- تم إضافة الـ Behavior هنا -->
                                <div class="pet-option" data-id="<?= $pet['id'] ?>" data-behavior="<?= htmlspecialchars($pet['behavioral_notes'] ?? '') ?>">
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

                        <button type="submit" class="request-btn">Request booking</button>
                    </form>
                </div>

                <div class="bookings-sidebar">
                    <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem;">Recent bookings</h3>
                    
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
                                    <?php 
                                        $badge_class = 'status-pending';
                                        if (strtolower($bk['status']) == 'confirmed') $badge_class = 'status-confirmed';
                                        if (strtolower($bk['status']) == 'completed') $badge_class = 'status-completed';
                                    ?>
                                    <span class="status-badge <?= $badge_class ?>"><?= htmlspecialchars($bk['status']) ?></span>
                                </div>
                                
                                <?php if(strtolower($bk['status']) == 'completed'): ?>
                                    <div style="width: 100%; margin-top: 10px; border-top: 1px dashed #eee; padding-top: 10px;">
                                        <div class="price-tag">Amount Due: $<?= number_format($bk['total_price'] ?? 0, 2) ?></div>
                                        
                                        <form action="../../controllers/PaymentController.php" method="POST" style="margin-bottom: 8px;">
                                            <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                                            <input type="hidden" name="provider_id" value="<?= $bk['provider_id'] ?>">
                                            <input type="hidden" name="amount" value="<?= $bk['total_price'] ?>">
                                            <button type="submit" name="pay_booking" class="pay-btn"><i class="fa-solid fa-credit-card"></i> Pay Now</button>
                                        </form>

                                        <form action="../../controllers/DisputeController.php" method="POST" class="action-row">
                                            <input type="hidden" name="booking_id" value="<?= $bk['id'] ?>">
                                            <input type="hidden" name="provider_id" value="<?= $bk['provider_id'] ?>">
                                            <input type="text" name="reason" class="dispute-input" placeholder="Issue? Type reason..." required>
                                            <button type="submit" name="submit_dispute" class="dispute-btn">Dispute</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- نافذة السلوك المنبثقة (Behavior Modal) -->
    <!-- نافذة السلوك المنبثقة (Behavior Modal) -->
    <!-- التعديل هنا: خليناها display: none; بشكل افتراضي -->
    <div id="behaviorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; width: 400px; max-width: 90%;">
            <h3 style="margin-bottom: 10px;"><i class="fa-solid fa-shield-dog" style="color:var(--primary-green);"></i> Pet Behavior Info</h3>
            <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">For the safety of our providers, please select your pet's typical behavior. (This will be saved for future bookings).</p>
            
            <select id="behavior_select" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 15px; outline: none;">
                <option value="Friendly & Playful">Friendly & Playful 😊</option>
                <option value="Timid / Scared of loud noises">Timid / Scared of loud noises 🥺</option>
                <option value="Aggressive towards strangers">Aggressive towards strangers 😠</option>
                <option value="Hyperactive / Pulls on leash">Hyperactive / Pulls on leash ⚡</option>
                <option value="Requires Muzzle">Requires Muzzle 🐕‍🦺</option>
            </select>
            
            <button type="button" onclick="submitWithBehavior()" style="background: var(--primary-green); color: white; border: none; padding: 10px 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer;">Save & Continue Booking</button>
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

        const bookingForm = document.getElementById('bookingForm');
        bookingForm.addEventListener('submit', function(e) {
            const activePet = document.querySelector('.pet-option.active');
            
            if (!activePet) {
                e.preventDefault();
                alert("Please select a pet before requesting a booking.");
                return;
            }

            const behavior = activePet.dataset.behavior;

            // لو مفيش سلوك مسجل، وقف الفورم وأظهر النافذة
            if (!behavior || behavior.trim() === "") {
                e.preventDefault();
                document.getElementById('behaviorModal').style.display = 'flex'; // نظهرها هنا
            } else {
                let submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'request_booking';
                submitInput.value = '1';
                bookingForm.appendChild(submitInput);
            }
        });

        function submitWithBehavior() {
            const behaviorVal = document.getElementById('behavior_select').value;
            
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'new_behavior';
            hiddenInput.value = behaviorVal;
            bookingForm.appendChild(hiddenInput);

            document.getElementById('behaviorModal').style.display = 'none'; // نخفيها بعد ما يدوس Save
            
            document.querySelector('.pet-option.active').dataset.behavior = behaviorVal;
            
            let submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'request_booking';
            submitInput.value = '1';
            bookingForm.appendChild(submitInput);

            bookingForm.submit();
        }
    </script>
</body>
</html>