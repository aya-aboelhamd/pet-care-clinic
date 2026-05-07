<?php
session_start();

// التأكد إن اليوزر مسجل دخول وإن الرول بتاعه Pet Owner (رقم 2)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// 1. جلب بيانات المستخدم الأساسية (للهيدر)
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

// 2. جلب الحيوانات الخاصة باليوزر من جدول pet
$stmt = $db->prepare("SELECT * FROM pet WHERE user_id = ? AND (is_archived = 0 OR is_archived IS NULL)");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// دالة لحساب العمر من تاريخ الميلاد
function calculateAge($dob) {
    if (!$dob) return 'Unknown';
    $bday = new DateTime($dob);
    $today = new DateTime('today');
    $diff = $today->diff($bday);
    if ($diff->y > 0) return $diff->y . ' yrs';
    if ($diff->m > 0) return $diff->m . ' mos';
    return $diff->d . ' days';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - My Pets</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-green: #589A64;
            --light-green: #E8F5E9;
            --bg-light: #F8FAF8;
            --sidebar-width: 240px;
            --text-dark: #1A1A1A;
            --text-gray: #666;
            --border-color: #E0E0E0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            flex-shrink: 0;
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
            color: var(--text-gray);
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

        .nav-item:hover:not(.active) { background: #f0f0f0; }

        /* --- Main Content --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }

        header {
            background: white;
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase;}

        .content-padding { padding: 2rem; }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .add-new-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* --- Pets Cards Grid --- */
        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .pet-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
        }

        .pet-card:hover { transform: translateY(-5px); }

        .pet-card-header {
            background-color: var(--primary-green);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .pet-icon-bg {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .pet-info-body {
            padding: 1.5rem;
        }

        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .stat-item span { display: block; font-size: 0.75rem; color: var(--text-gray); margin-bottom: 4px; }
        .stat-item strong { font-size: 1rem; color: var(--text-dark); }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 1.5rem;
        }

        .tag {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .view-profile-btn {
            width: 100%;
            padding: 0.6rem;
            background: #F8FAF8;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-dark);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .view-profile-btn:hover { background: #eee; }
        .empty-state { text-align: center; padding: 3rem; color: #666; font-size: 1.1rem; width: 100%; grid-column: 1 / -1; }
        .logout-btn { color: var(--text-gray); transition: 0.2s; font-size: 1.1rem; }
        .logout-btn:hover { color: #d32f2f; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item active"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="healthLogs.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Health Logs</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Symptom Checker</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
        
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Welcome back, <?= htmlspecialchars($first_name) ?> 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <strong><?= htmlspecialchars($user_name) ?></strong><br>
                    <span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span>
                </div>
                <!-- زرار الخروج -->
                <a href="../Auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <div>
                    <h2 style="font-size: 1.8rem; margin-bottom: 5px;">My Pets</h2>
                    <p style="color: var(--text-gray);">Manage profiles, allergies and medical conditions.</p>
                </div>
                <button class="add-new-btn" onclick="window.location.href='addNewPet.php'">
                    <i class="fa-solid fa-plus"></i> Add new pet
                </button>
            </div>

            <div class="pets-grid">
                
                <?php if (count($pets) > 0): ?>
                    <?php foreach ($pets as $pet): ?>
                        <div class="pet-card">
                            <div class="pet-card-header">
                                <div class="pet-icon-bg">
                                    <!-- تغيير الأيقونة بناءً على الفصيلة -->
                                    <?= (strtolower($pet['species']) == 'cat' || strtolower($pet['species']) == 'قطه') ? '🐱' : '🐶' ?>
                                </div>
                                <div>
                                    <h3 style="font-size: 1.2rem;"><?= htmlspecialchars($pet['name']) ?></h3>
                                    <p style="font-size: 0.85rem; opacity: 0.9;"><?= htmlspecialchars($pet['breed']) ?></p>
                                </div>
                            </div>
                            <div class="pet-info-body">
                                <div class="stats-row">
                                    <div class="stat-item"><span>Age</span><strong><?= calculateAge($pet['date_of_birth']) ?></strong></div>
                                    <div class="stat-item"><span>Weight</span><strong><?= htmlspecialchars($pet['weight']) ?> kg</strong></div>
                                </div>
                                
                                <button class="view-profile-btn" onclick="window.location.href='petProfile.php?id=<?= $pet['id'] ?>'">
                                    View profile
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-paw" style="font-size: 3rem; color: #E0E0E0; margin-bottom: 1rem;"></i><br>
                        You haven't added any pets yet.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>