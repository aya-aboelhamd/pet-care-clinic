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
$initials = strtoupper(substr($first_name, 0, 1));
if (isset($name_parts[1])) {
    $initials .= strtoupper(substr($name_parts[1], 0, 1));
} else {
    $initials .= strtoupper(substr($first_name, 1, 1) ?: '');
}

$stmt = $db->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$dropdown_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = 0;
foreach ($dropdown_notifications as $n) {
    if ($n['is_read'] == 0) $unread_count++;
}

if (isset($_GET['mark_read'])) {
    $stmt = $db->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: petProfile.php?id=" . $pet_id);
    exit();
}

// 2. تحديث الوزن 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_weight'])) {
    $new_weight = floatval($_POST['new_weight']);
    if ($new_weight > 0) {
        $updateStmt = $db->prepare("UPDATE pet SET weight = ? WHERE id = ? AND user_id = ?");
        $updateStmt->execute([$new_weight, $pet_id, $user_id]);
        
        $logStmt = $db->prepare("INSERT INTO weightlog (pet_id, weight, logged_at) VALUES (?, ?, NOW())");
        $logStmt->execute([$pet_id, $new_weight]);
        
        header("Location: petProfile.php?id=" . $pet_id . "&success=weight_updated");
        exit();
    }
}

// 3. جلب بيانات الحيوان
$stmt = $db->prepare("SELECT * FROM pet WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    header("Location: mypets.php");
    exit();
}

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
$allergies = array_filter(array_map('trim', explode(',', $pet['allergies'])));

// 4. جلب آخر تحديثات الوزن
$stmt = $db->prepare("SELECT weight, logged_at FROM weightlog WHERE pet_id = ? ORDER BY logged_at DESC LIMIT 3");
$stmt->execute([$pet_id]);
$weight_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. جلب التطعيمات القادمة للبروفايل
$stmt = $db->prepare("
    SELECT v.vaccine_name, v.next_due_date 
    FROM vaccination v 
    JOIN medicalrecord m ON v.record_id = m.id 
    WHERE m.pet_id = ? AND v.next_due_date >= CURDATE() 
    ORDER BY v.next_due_date ASC LIMIT 3
");
$stmt->execute([$pet_id]);
$upcoming_vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- UC-10: جلب كل التطعيمات لملف السفر (السابقة والقادمة) للتأكد من الشروط ---
$stmt = $db->prepare("
    SELECT v.vaccine_name, v.date_administered, v.next_due_date 
    FROM vaccination v 
    JOIN medicalrecord m ON v.record_id = m.id 
    WHERE m.pet_id = ? 
    ORDER BY v.date_administered DESC
");
$stmt->execute([$pet_id]);
$all_vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// التحقق من شرط السفر: هل يوجد تطعيم سعار (Rabies) ساري؟ (Alt Course 1)
$has_valid_rabies = false;
foreach ($all_vaccines as $vac) {
    if (strtolower($vac['vaccine_name']) == 'rabies' && strtotime($vac['next_due_date']) >= time()) {
        $has_valid_rabies = true;
        break;
    }
}

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

// 7. جلب نتائج التحاليل
$stmt = $db->prepare("
    SELECT l.*, u.name as vet_name 
    FROM lab_results l 
    JOIN users u ON l.vet_id = u.id 
    WHERE l.pet_id = ? 
    ORDER BY l.uploaded_at DESC
");
$stmt->execute([$pet_id]);
$lab_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - <?= htmlspecialchars($pet['name']) ?>'s Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- استدعاء مكتبة html2pdf لتوليد الـ PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; --danger-red: #FEE2E2; --danger-text: #DC2626; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase;}
        
        .bell-wrapper { position: relative; cursor: pointer; display: flex; align-items: center; font-size: 1.1rem; }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: #DC2626; color: white; font-size: 0.6rem; font-weight: bold; padding: 2px 5px; border-radius: 50%; }
        .notif-dropdown { display: none; position: absolute; top: 35px; right: 0; width: 320px; background: white; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; max-height: 400px; overflow-y: auto; cursor: default;}
        .notif-dropdown.show { display: block; animation: fadeIn 0.2s ease; }
        .dropdown-header { padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-weight: bold; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;}
        .dropdown-header a { font-size: 0.75rem; color: var(--primary-green); text-decoration: none; font-weight: normal; }
        .dropdown-item { padding: 12px 15px; border-bottom: 1px solid #F5F5F5; font-size: 0.8rem; line-height: 1.4; color: var(--text-dark); }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item.unread { background: #F0FDF4; border-left: 3px solid var(--primary-green); }
        .dropdown-time { font-size: 0.7rem; color: #999; margin-top: 5px; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .logout-btn { color: var(--text-gray); transition: 0.2s; font-size: 1.1rem; }
        .logout-btn:hover { color: #d32f2f; }
        
        .content-padding { padding: 2rem; }

        .profile-banner { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 2rem; display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem; position: relative;}
        .pet-avatar-large { width: 120px; height: 120px; background: #F0F7F1; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 4rem; color:#333; }
        .pet-main-info h2 { font-size: 2rem; margin-bottom: 5px; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-right: 5px; display: inline-block; margin-bottom: 5px;}
        .badge-allergy { background: #FFF4E5; color: #D97706; border: 1px solid #FFEBCD; }
        .badge-condition { background: var(--danger-red); color: var(--danger-text); border: 1px solid #FDCACA; }

        /* Travel Passport Button */
        .travel-btn-container { margin-left: auto; text-align: right; }
        .btn-travel { background: #1E40AF; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-travel:hover { background: #1e3a8a; }
        .travel-warning { color: var(--danger-text); font-size: 0.8rem; font-weight: 600; margin-top: 8px; display: flex; align-items: center; justify-content: flex-end; gap: 5px;}

        .profile-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-title { font-weight: 700; font-size: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }

        .metric-row { display: flex; align-items: center; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #F5F5F5; }
        .metric-label { font-size: 0.9rem; color: var(--text-gray); }
        .metric-value { font-weight: 700; color: var(--text-dark); }
        .weight-input-group { display: flex; align-items: center; gap: 10px; }
        .weight-input { width: 80px; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 700; text-align: center; outline: none; }
        .save-weight-btn { background: var(--primary-green); color: white; border: none; padding: 8px 15px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; }

        .history-item { display: flex; gap: 15px; padding: 10px 0; border-bottom: 1px solid #F9F9F9; }
        .history-icon { width: 35px; height: 35px; border-radius: 50%; background: #F0F4F0; display: flex; align-items: center; justify-content: center; color: var(--primary-green); font-size: 0.8rem; }
        .history-text { font-size: 0.85rem; }
        .history-date { font-size: 0.75rem; color: #999; margin-top: 3px; }

        .presc-box { background: #F0F7FF; border: 1px solid #D1E9FF; border-radius: 10px; padding: 12px; margin-top: 10px; }
        .presc-title { font-weight: 700; color: #1E40AF; font-size: 0.85rem; margin-bottom: 4px; display: block; }
        .presc-sub { font-size: 0.75rem; color: #4B5563; line-height: 1.4; }
        
        .empty-state-small { font-size: 0.85rem; color: #999; font-style: italic; text-align: center; padding: 1rem 0; }

        .lab-result-card { border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; margin-bottom: 1rem; position: relative; }
        .lab-result-card.critical { border-color: #FCA5A5; background: #FFF5F5; }
        .lab-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .insight-box { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 10px; border-radius: 8px; margin-top: 10px; color: #166534; font-size: 0.85rem;}
        .download-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; background: #eee; color: #333; text-decoration: none; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .critical-badge { position: absolute; top: -10px; right: 15px; background: #DC2626; color: white; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }

        /* ستايل الـ PDF المخفي اللي هيتصور (Travel Passport) */
        #passport-document {
            position: absolute; left: -9999px; top: 0; width: 800px; padding: 40px; 
            background: white; color: black; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        .passport-header { text-align: center; border-bottom: 3px solid #1E40AF; padding-bottom: 20px; margin-bottom: 30px; }
        .passport-header h1 { color: #1E40AF; margin-bottom: 5px; font-size: 28px; text-transform: uppercase; letter-spacing: 2px;}
        .passport-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .passport-table th, .passport-table td { border: 1px solid #E5E7EB; padding: 12px; text-align: left; }
        .passport-table th { background: #F3F4F6; color: #374151; width: 30%; font-weight: bold;}
        .passport-section-title { color: #1E40AF; font-size: 18px; border-bottom: 2px solid #E5E7EB; padding-bottom: 5px; margin-bottom: 15px; }
        .verified-stamp { border: 3px solid #16A34A; color: #16A34A; padding: 10px 20px; display: inline-block; font-weight: bold; font-size: 20px; text-transform: uppercase; border-radius: 10px; transform: rotate(-5deg); margin-top: 30px;}
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item active"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="healthLogs.php" class="nav-item"><i class="fa-solid fa-notes-medical"></i> Health Logs</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item"><i class="fa-solid fa-stethoscope"></i> Symptom Checker</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">
                <a href="mypets.php" style="color: #666; text-decoration: none;">My Pets</a> / 
                <strong>Welcome back, <?= htmlspecialchars($first_name) ?> 👋</strong>
            </div>
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
                        <?php if(empty($dropdown_notifications)): ?>
                            <div class="dropdown-item" style="text-align: center; color: #999; padding: 20px;">No new notifications</div>
                        <?php else: ?>
                            <?php foreach($dropdown_notifications as $dn): ?>
                                <div class="dropdown-item <?= $dn['is_read'] == 0 ? 'unread' : '' ?>">
                                    <strong style="<?= $dn['type'] == 'Recall' ? 'color:#DC2626;' : '' ?>"><?= htmlspecialchars($dn['type']) ?> Alert</strong><br>
                                    <?= htmlspecialchars($dn['message']) ?>
                                    <div class="dropdown-time"><?= date('M j, Y g:i A', strtotime($dn['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <strong><?= htmlspecialchars($user_name) ?></strong><br>
                    <span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span>
                </div>
                <a href="../Auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="profile-banner">
                <div class="pet-avatar-large"><?= $pet_icon ?></div>
                <div class="pet-main-info">
                    <h2><?= htmlspecialchars($pet['name']) ?></h2>
                    <p style="color: var(--text-gray); margin-bottom: 12px;">
                        <?= htmlspecialchars($pet['breed']) ?> • <?= $pet_age ?> • <?= htmlspecialchars($pet['gender']) ?>
                    </p>
                    <div>
                        <?php foreach($allergies as $allergy): ?>
                            <span class="badge badge-allergy">⚠️ <?= htmlspecialchars($allergy) ?> Allergy</span>
                        <?php endforeach; ?>
                        <?php if (!empty($pet['medical_notes'])): ?>
                            <span class="badge badge-condition">🚫 <?= htmlspecialchars($pet['medical_notes']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- UC-10: Travel Passport Button -->
                <div class="travel-btn-container">
                    <button class="btn-travel" onclick="generatePassport()">
                        <i class="fa-solid fa-plane-departure"></i> Generate Travel Passport
                    </button>
                    <!-- Alternate Course 1: Missing requirement warning -->
                    <?php if (!$has_valid_rabies): ?>
                        <div class="travel-warning"><i class="fa-solid fa-circle-exclamation"></i> Action Required: Rabies Vaccine missing/expired.</div>
                    <?php else: ?>
                        <div style="color: #16A34A; font-size: 0.8rem; font-weight: 600; margin-top: 8px; display: flex; justify-content: flex-end; gap: 5px;"><i class="fa-solid fa-check"></i> Cleared for International Travel</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-grid">
                <!-- Column 1: Main Stats & Lab Results -->
                <div class="main-stats">
                    <div class="card">
                        <div class="card-title">Health Metrics <i class="fa-solid fa-heart-pulse" style="color: var(--primary-green);"></i></div>
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
                        <div class="card-title">Lab Results & Insights <i class="fa-solid fa-flask" style="color: #9333EA;"></i></div>
                        <?php if (count($lab_results) > 0): ?>
                            <?php foreach ($lab_results as $res): ?>
                                <div class="lab-result-card <?= $res['is_critical'] ? 'critical' : '' ?>">
                                    <?php if($res['is_critical']): ?>
                                        <div class="critical-badge">CRITICAL</div>
                                    <?php endif; ?>
                                    <div class="lab-header">
                                        <div>
                                            <strong style="font-size: 1rem;"><?= htmlspecialchars($res['test_name']) ?></strong><br>
                                            <span style="font-size: 0.75rem; color: #666;"><i class="fa-regular fa-calendar"></i> <?= date("M j, Y", strtotime($res['uploaded_at'])) ?> • By <?= htmlspecialchars($res['vet_name']) ?></span>
                                        </div>
                                        <a href="../../<?= htmlspecialchars($res['file_path']) ?>" target="_blank" class="download-btn">
                                            <i class="fa-solid fa-file-arrow-down"></i> Report
                                        </a>
                                    </div>
                                    <div class="insight-box">
                                        <strong><i class="fa-solid fa-lightbulb"></i> Vet's Insight:</strong><br>
                                        <?= nl2br(htmlspecialchars($res['simplified_insight'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state-small">No lab results found for this pet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 2: Sidebars -->
                <div class="activity-sidebar">
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
                        <div class="card-title">Active Prescriptions</div>
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


    <div id="passport-document">
        <div class="passport-header">
            <h1>International Pet Travel Passport</h1>
            <p style="color: #6B7280; font-size: 14px;">Document ID: PTLR-<?= strtoupper(uniqid()) ?> | Issued: <?= date('d M Y') ?></p>
        </div>

        <h2 class="passport-section-title">Pet Identification Details</h2>
        <table class="passport-table">
            <tr><th>Pet Name</th><td><?= htmlspecialchars($pet['name']) ?></td></tr>
            <tr><th>Species / Breed</th><td><?= htmlspecialchars($pet['species']) ?> / <?= htmlspecialchars($pet['breed']) ?></td></tr>
            <tr><th>Date of Birth</th><td><?= date("d M Y", strtotime($pet['date_of_birth'])) ?> (Age: <?= $pet_age ?>)</td></tr>
            <tr><th>Gender</th><td><?= htmlspecialchars($pet['gender']) ?></td></tr>
            <tr><th>Weight</th><td><?= htmlspecialchars($pet['weight']) ?> kg</td></tr>
            <tr><th>Owner Name</th><td><?= htmlspecialchars($user_name) ?></td></tr>
        </table>

        <h2 class="passport-section-title">Verified Vaccination History</h2>
        <table class="passport-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Vaccine Type</th>
                    <th style="width: 30%;">Administered Date</th>
                    <th style="width: 30%;">Valid Until (Next Due)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($all_vaccines) > 0): ?>
                    <?php foreach($all_vaccines as $vac): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($vac['vaccine_name']) ?></strong></td>
                            <td><?= date("d M Y", strtotime($vac['date_administered'])) ?></td>
                            <td><?= date("d M Y", strtotime($vac['next_due_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center;">No vaccination records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2 class="passport-section-title">Health Declarations</h2>
        <table class="passport-table">
            <tr>
                <th>Known Allergies</th>
                <td><?= empty($pet['allergies']) ? 'None Recorded' : htmlspecialchars($pet['allergies']) ?></td>
            </tr>
            <tr>
                <th>Chronic Conditions</th>
                <td><?= empty($pet['medical_notes']) ? 'Clinically Healthy. No chronic conditions noted.' : htmlspecialchars($pet['medical_notes']) ?></td>
            </tr>
        </table>

        <div style="text-align: center;">
            <div class="verified-stamp">SYSTEM VERIFIED - APPROVED FOR TRAVEL</div>
            <p style="margin-top: 20px; font-size: 12px; color: #9CA3AF;">This document is digitally generated by the Petlor Veterinary Management System. Contains all requisite medical information extracted from verified clinic records.</p>
        </div>
    </div>

    <!-- JavaScript to handle PDF generation -->
    <script>
        function generatePassport() {
            const hasRabies = <?= $has_valid_rabies ? 'true' : 'false' ?>;
            if (!hasRabies) {
                alert("ERROR: Cannot generate Travel Passport.\n\nAccording to international travel regulations, your pet must have a valid Rabies vaccination. Please visit the clinic to update records before generating this document.");
                return;
            }

   
            const element = document.getElementById('passport-document');
            element.style.left = '0';
            element.style.position = 'relative';

            const opt = {
                margin:       0.5,
                filename:     '<?= htmlspecialchars($pet['name']) ?>_Travel_Passport.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                element.style.position = 'absolute';
                element.style.left = '-9999px';
            });
        }
    </script>

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