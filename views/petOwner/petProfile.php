<?php
session_start();

// التأكد إن اليوزر مسجل دخول
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../Auth/login.php");
    exit();
}

// التأكد من وجود ID الحيوان في الرابط
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: mypets.php");
    exit();
}

require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$pet_id = $_GET['id'];

// 1. جلب بيانات المستخدم للهيدر
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['name'] ?? 'User';
$user_email = $user['email'] ?? 'user@petlor.com';
$name_parts = explode(' ', trim($user_name));
$first_name = $name_parts[0];
$initials = strtoupper(substr($first_name, 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : substr($first_name, 1, 1)));

// 2. تحديث الوزن (إذا تم الضغط على زر Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_weight'])) {
    $new_weight = floatval($_POST['new_weight']);
    if ($new_weight > 0) {
        // تحديث الوزن في جدول pet
        $updateStmt = $db->prepare("UPDATE pet SET weight = ? WHERE id = ? AND user_id = ?");
        $updateStmt->execute([$new_weight, $pet_id, $user_id]);
        
        // تسجيل الحركة في جدول weightlog
        $logStmt = $db->prepare("INSERT INTO weightlog (pet_id, weight, logged_at) VALUES (?, ?, NOW())");
        $logStmt->execute([$pet_id, $new_weight]);
        
        // إعادة تحميل الصفحة لمنع تكرار الإرسال
        header("Location: petProfile.php?id=" . $pet_id . "&success=weight_updated");
        exit();
    }
}

// 3. جلب بيانات الحيوان
$stmt = $db->prepare("SELECT * FROM pet WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

// لو الحيوان مش موجود أو مش بتاع اليوزر ده، نرجعه لصفحة mypets
if (!$pet) {
    header("Location: mypets.php");
    exit();
}

// دالة حساب العمر
function calculateAge($dob) {
    if (!$dob) return 'Unknown';
    $bday = new DateTime($dob);
    $today = new DateTime('today');
    $diff = $today->diff($bday);
    if ($diff->y > 0) return $diff->y . ' years';
    if ($diff->m > 0) return $diff->m . ' months';
    return $diff->d . ' days';
}

$pet_age = calculateAge($pet['date_of_birth']);
$pet_icon = (strtolower($pet['species']) == 'cat' || strtolower($pet['species']) == 'قطه') ? '🐱' : '🐶';

// تحويل نص الحساسية إلى مصفوفة (Array)
$allergies = array_filter(array_map('trim', explode(',', $pet['allergies'])));

// 4. جلب آخر تحديثات الوزن (Recent Activity)
$stmt = $db->prepare("SELECT weight, logged_at FROM weightlog WHERE pet_id = ? ORDER BY logged_at DESC LIMIT 3");
$stmt->execute([$pet_id]);
$weight_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. جلب التطعيمات القادمة (بناءً على الربط بجدول medicalrecord)
$stmt = $db->prepare("
    SELECT v.vaccine_name, v.next_due_date 
    FROM vaccination v 
    JOIN medicalrecord m ON v.record_id = m.id 
    WHERE m.pet_id = ? AND v.next_due_date >= CURDATE() 
    ORDER BY v.next_due_date ASC LIMIT 3
");
$stmt->execute([$pet_id]);
$upcoming_vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. جلب الروشتات الفعالة
$stmt = $db->prepare("
    SELECT p.medication_name, p.dosage, p.instructions, p.expiry_date 
    FROM prescription p 
    JOIN medicalrecord m ON p.record_id = m.id 
    WHERE m.pet_id = ? AND (p.expiry_date >= CURDATE() OR p.expiry_date IS NULL)
    ORDER BY p.issued_date DESC LIMIT 2
");
$stmt->execute([$pet_id]);
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - <?= htmlspecialchars($pet['name']) ?>'s Profile</title>
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
            --danger-red: #FEE2E2;
            --danger-text: #DC2626;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar --- */
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .nav-item:hover:not(.active) { background: #f0f0f0; }

        /* --- Main Content --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase;}
        .logout-btn { color: var(--text-gray); transition: 0.2s; font-size: 1.1rem; }
        .logout-btn:hover { color: #d32f2f; }

        .content-padding { padding: 2rem; }

        /* --- Profile Header --- */
        .profile-banner { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 2rem; display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem; }
        .pet-avatar-large { width: 120px; height: 120px; background: #F0F7F1; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 4rem; color:#333; }
        .pet-main-info h2 { font-size: 2rem; margin-bottom: 5px; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-right: 5px; display: inline-block; margin-bottom: 5px;}
        .badge-allergy { background: #FFF4E5; color: #D97706; border: 1px solid #FFEBCD; }
        .badge-condition { background: var(--danger-red); color: var(--danger-text); border: 1px solid #FDCACA; }

        /* --- Stats Grid --- */
        .profile-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }

        .card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-title { font-weight: 700; font-size: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }

        /* --- Weight Update Section --- */
        .metric-row { display: flex; align-items: center; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #F5F5F5; }
        .metric-label { font-size: 0.9rem; color: var(--text-gray); }
        .metric-value { font-weight: 700; color: var(--text-dark); }

        .weight-input-group { display: flex; align-items: center; gap: 10px; }
        .weight-input { width: 80px; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 700; text-align: center; outline: none; }
        .weight-input:focus { border-color: var(--primary-green); }
        .save-weight-btn { background: var(--primary-green); color: white; border: none; padding: 8px 15px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; }

        /* --- History List --- */
        .history-item { display: flex; gap: 15px; padding: 10px 0; border-bottom: 1px solid #F9F9F9; }
        .history-item:last-child { border-bottom: none; }
        .history-icon { width: 35px; height: 35px; border-radius: 50%; background: #F0F4F0; display: flex; align-items: center; justify-content: center; color: var(--primary-green); font-size: 0.8rem; }
        .history-text { font-size: 0.85rem; }
        .history-date { font-size: 0.75rem; color: #999; margin-top: 3px; }

        /* --- Prescription Styles --- */
        .presc-box { background: #F0F7FF; border: 1px solid #D1E9FF; border-radius: 10px; padding: 12px; margin-top: 10px; }
        .presc-title { font-weight: 700; color: #1E40AF; font-size: 0.85rem; margin-bottom: 4px; display: block; }
        .presc-sub { font-size: 0.75rem; color: #4B5563; line-height: 1.4; }
        
        .empty-state-small { font-size: 0.85rem; color: #999; font-style: italic; text-align: center; padding: 1rem 0; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item active"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">
                <a href="mypets.php" style="color: #666; text-decoration: none;">My Pets</a> / 
                <strong><?= htmlspecialchars($pet['name']) ?>'s Profile</strong>
            </div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <div><strong><?= htmlspecialchars($user_name) ?></strong><br><span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span></div>
                <a href="../Auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <!-- الهيدر الخاص بالحيوان -->
            <div class="profile-banner">
                <div class="pet-avatar-large"><?= $pet_icon ?></div>
                <div class="pet-main-info">
                    <h2><?= htmlspecialchars($pet['name']) ?></h2>
                    <p style="color: var(--text-gray); margin-bottom: 12px;">
                        <?= htmlspecialchars($pet['breed']) ?> • <?= $pet_age ?> • <?= htmlspecialchars($pet['gender']) ?>
                    </p>
                    <div>
                        <!-- طباعة الحساسية -->
                        <?php foreach($allergies as $allergy): ?>
                            <span class="badge badge-allergy">⚠️ <?= htmlspecialchars($allergy) ?> Allergy</span>
                        <?php endforeach; ?>
                        
                        <!-- طباعة الحالة الطبية -->
                        <?php if (!empty($pet['medical_notes'])): ?>
                            <span class="badge badge-condition">🚫 <?= htmlspecialchars($pet['medical_notes']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="profile-grid">
                <div class="main-stats">
                    <div class="card">
                        <div class="card-title">Health Metrics <i class="fa-solid fa-heart-pulse" style="color: var(--primary-green);"></i></div>
                        
                        <!-- فورم تحديث الوزن -->
                        <form method="POST" action="">
                            <div class="metric-row">
                                <span class="metric-label">Current Weight</span>
                                <div class="weight-input-group">
                                    <input type="hidden" name="update_weight" value="1">
                                    <input type="number" name="new_weight" class="weight-input" value="<?= htmlspecialchars($pet['weight']) ?>" step="0.1" required>
                                    <span style="font-weight: 600; color: var(--text-gray);">kg</span>
                                    <button type="submit" class="save-weight-btn">Update</button>
                                </div>
                            </div>
                        </form>

                        <div class="metric-row">
                            <span class="metric-label">Date of Birth</span>
                            <span class="metric-value"><?= date("M j, Y", strtotime($pet['date_of_birth'])) ?></span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title">Upcoming Vaccinations</div>
                        <?php if (count($upcoming_vaccines) > 0): ?>
                            <?php foreach ($upcoming_vaccines as $vac): ?>
                                <?php 
                                    $due_date = new DateTime($vac['next_due_date']);
                                    $today = new DateTime('today');
                                    $days_left = $today->diff($due_date)->days;
                                    $is_urgent = ($days_left <= 14);
                                ?>
                                <div class="metric-row">
                                    <span><?= htmlspecialchars($vac['vaccine_name']) ?></span>
                                    <span style="<?= $is_urgent ? 'color: #D97706; font-weight: 600;' : 'color: #666;' ?>">
                                        <?= $is_urgent ? "Due in $days_left days" : "Due " . date("Y-m-d", strtotime($vac['next_due_date'])) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state-small">No upcoming vaccinations found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="activity-sidebar">
                    <div class="card">
                        <div class="card-title">Recent Weight Updates</div>
                        
                        <?php if (count($weight_history) > 0): ?>
                            <?php foreach ($weight_history as $log): ?>
                                <div class="history-item">
                                    <div class="history-icon"><i class="fa-solid fa-weight-scale"></i></div>
                                    <div>
                                        <p class="history-text">Weight updated to <strong><?= htmlspecialchars($log['weight']) ?> kg</strong></p>
                                        <p class="history-date"><?= date("M j, g:i A", strtotime($log['logged_at'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state-small">No weight history yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <div class="card-title">Active Prescriptions <i class="fa-solid fa-prescription-bottle-medical" style="color: #3B82F6;"></i></div>
                        
                        <?php if (count($prescriptions) > 0): ?>
                            <?php foreach ($prescriptions as $presc): ?>
                                <div class="presc-box">
                                    <span class="presc-title"><?= htmlspecialchars($presc['medication_name']) ?> <?= htmlspecialchars($presc['dosage']) ?></span>
                                    <p class="presc-sub"><?= htmlspecialchars($presc['instructions']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state-small">No active prescriptions.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>