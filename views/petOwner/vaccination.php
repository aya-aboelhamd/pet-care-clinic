<?php
session_start();

// التأكد إن اليوزر مسجل دخول
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// التعامل مع زرار "Mark done"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_done'])) {
    $vac_id = $_POST['vac_id'];
    
    // تحديث تاريخ التطعيم للنهاردة، والميعاد القادم لكمان سنة
    $updateStmt = $db->prepare("
        UPDATE vaccination 
        SET date_administered = CURDATE(), 
            next_due_date = DATE_ADD(CURDATE(), INTERVAL 1 YEAR) 
        WHERE id = ?
    ");
    $updateStmt->execute([$vac_id]);
    
    // ريفريش للصفحة عشان الداتا الجديدة تظهر
    header("Location: vaccination.php?success=updated");
    exit();
}

// 1. جلب بيانات المستخدم للهيدر
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['name'] ?? 'User';
$user_email = $user['email'] ?? 'user@petlor.com';
$name_parts = explode(' ', trim($user_name));
$first_name = $name_parts[0];
$initials = strtoupper(substr($first_name, 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : substr($first_name, 1, 1)));

// 2. جلب كل التطعيمات الخاصة بحيوانات هذا المستخدم
$query = "
    SELECT v.id as vac_id, v.vaccine_name, v.date_administered, v.next_due_date,
           p.name as pet_name, p.species
    FROM vaccination v
    JOIN medicalrecord m ON v.record_id = m.id
    JOIN pet p ON m.pet_id = p.id
    WHERE p.user_id = ? AND (p.is_archived = 0 OR p.is_archived IS NULL)
    ORDER BY v.next_due_date ASC
";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. حساب الإحصائيات (الحالات)
$overdue_count = 0;
$due_soon_count = 0;
$up_to_date_count = 0;

$today = new DateTime('today');

// لف على كل التطعيمات لتحديد الحالة
foreach ($vaccinations as &$vac) {
    $due_date = new DateTime($vac['next_due_date']);
    $diff = $today->diff($due_date);
    $days_diff = (int)$diff->format('%R%a'); // لو الرقم سالب يبقى ميعاده عدى

    if ($days_diff < 0) {
        $vac['status_text'] = 'Overdue';
        $vac['status_class'] = 'status-overdue';
        $overdue_count++;
    } elseif ($days_diff <= 30) { // لو فاضل 30 يوم أو أقل
        $vac['status_text'] = 'Due Soon';
        $vac['status_class'] = 'status-due-soon';
        $due_soon_count++;
    } else {
        $vac['status_text'] = 'Up to date';
        $vac['status_class'] = 'status-up-to-date';
        $up_to_date_count++;
    }
}
unset($vac); // لفك ارتباط المتغير عشان ميعملش مشاكل

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Vaccination Schedule</title>
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
            --red-alert: #FEE2E2;
            --yellow-alert: #FEF3C7;
            --green-alert: #DCFCE7;
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
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase; }
        .logout-btn { color: var(--text-gray); transition: 0.2s; font-size: 1.1rem; }
        .logout-btn:hover { color: #d32f2f; }

        .content-padding { padding: 2rem; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; }
        .schedule-btn { background: var(--primary-green); color: white; border: none; padding: 0.7rem 1.2rem; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; }

        /* --- Summary Cards --- */
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .summary-card { padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); position: relative; background: white; }
        .summary-card.overdue { border-color: #FCA5A5; background: #FFF5F5; }
        .summary-card.due-soon { border-color: #FDE68A; background: #FFFBEB; }
        .summary-card.up-to-date { border-color: #BBF7D0; background: #F0FDF4; }
        .summary-card span { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .summary-card h3 { font-size: 2rem; }
        .summary-card i { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 2rem; opacity: 0.7; }
        .overdue i { color: #DC2626; }
        .due-soon i { color: #D97706; }
        .up-to-date i { color: #16A34A; }

        /* --- Table Styling --- */
        .table-container { background: white; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #FAFAFA; padding: 1rem; font-size: 0.85rem; color: var(--text-gray); font-weight: 500; border-bottom: 1px solid var(--border-color); }
        td { padding: 1.2rem 1rem; font-size: 0.9rem; border-bottom: 1px solid #F0F0F0; vertical-align: middle; }
        .pet-cell { display: flex; align-items: center; gap: 10px; }

        /* Status Badges */
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-up-to-date { background: #DCFCE7; color: #16A34A; }
        .status-due-soon { background: #FEF3C7; color: #D97706; }
        .status-overdue { background: #FEE2E2; color: #DC2626; }

        .mark-done-btn { padding: 6px 12px; border: 1px solid var(--border-color); background: white; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .mark-done-btn:hover { background: #F5F5F5; }
        .empty-state { text-align: center; padding: 3rem; color: #666; font-size: 1.1rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="healthLogs.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Health Logs</a>
        <a href="vaccination.php" class="nav-item active"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
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
                <a href="../Auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <div>
                    <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Vaccination Schedule</h2>
                    <p style="color: var(--text-gray);">Stay on top of your pets' immunizations.</p>
                </div>
                <button class="schedule-btn" onclick="window.location.href='vaccinationSchedule.php'">
                    <i class="fa-solid fa-plus"></i> Schedule new
                </button>
            </div>

            <!-- الكروت الديناميكية اللي بتعد أوتوماتيك -->
            <div class="summary-grid">
                <div class="summary-card overdue">
                    <span style="color: #DC2626;">Overdue</span>
                    <h3><?= $overdue_count ?></h3>
                    <i class="fa-solid fa-syringe"></i>
                </div>
                <div class="summary-card due-soon">
                    <span style="color: #D97706;">Due Soon</span>
                    <h3><?= $due_soon_count ?></h3>
                    <i class="fa-solid fa-syringe"></i>
                </div>
                <div class="summary-card up-to-date">
                    <span style="color: #16A34A;">Up to Date</span>
                    <h3><?= $up_to_date_count ?></h3>
                    <i class="fa-solid fa-syringe"></i>
                </div>
            </div>

            <div class="table-container">
                <?php if (count($vaccinations) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Pet</th>
                                <th>Vaccine</th>
                                <th>Last given</th>
                                <th>Next due</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vaccinations as $vac): ?>
                                <?php $icon = (strtolower($vac['species']) == 'cat' || strtolower($vac['species']) == 'قطه') ? '🐱' : '🐶'; ?>
                                <tr>
                                    <td>
                                        <div class="pet-cell">
                                            <span style="font-size: 1.2rem;"><?= $icon ?></span>
                                            <span><?= htmlspecialchars($vac['pet_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($vac['vaccine_name']) ?></td>
                                    <td><?= date("Y-m-d", strtotime($vac['date_administered'])) ?></td>
                                    <td><strong><?= date("Y-m-d", strtotime($vac['next_due_date'])) ?></strong></td>
                                    <td><span class="status-badge <?= $vac['status_class'] ?>"><?= $vac['status_text'] ?></span></td>
                                    <td style="text-align: right;">
                                        <!-- فورم صغير لزرار الـ Mark done -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="vac_id" value="<?= $vac['vac_id'] ?>">
                                            <button type="submit" name="mark_done" class="mark-done-btn">Mark done</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-syringe" style="font-size: 3rem; color: #E0E0E0; margin-bottom: 1rem;"></i><br>
                        No vaccination records found for your pets.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>