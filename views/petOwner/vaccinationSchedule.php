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

// 1. جلب بيانات المستخدم للهيدر
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['name'] ?? 'User';
$user_email = $user['email'] ?? 'user@petlor.com';
$name_parts = explode(' ', trim($user_name));
$first_name = $name_parts[0];
$initials = strtoupper(substr($first_name, 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : substr($first_name, 1, 1)));

// 2. جلب حيوانات المستخدم لقائمة الاختيار (Dropdown)
$stmt = $db->prepare("SELECT id, name, species FROM pet WHERE user_id = ? AND (is_archived = 0 OR is_archived IS NULL)");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Schedule New Vaccination</title>
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

        .content-padding { padding: 2rem; max-width: 900px; }
        .back-link { color: var(--primary-green); text-decoration: none; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 1rem; }

        /* --- Form Card --- */
        .form-container { background: white; padding: 2rem; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.02); }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
        
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; outline: none; transition: border-color 0.2s; }
        .form-control:focus { border-color: var(--primary-green); }

        .btn-submit { background: var(--primary-green); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 1rem; margin-top: 1rem; transition: 0.2s; }
        .btn-submit:hover { background: #488252; }

        .mark-done-btn { padding: 6px 12px; border: 1px solid var(--border-color); background: white; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .mark-done-btn:hover { background: #F5F5F5; }

        /* --- Result Section --- */
        #result-section { margin-top: 2rem; padding: 1.5rem; border-radius: 12px; background: #F0FDF4; border: 1px solid #BBF7D0; }
        .result-title { color: #16A34A; font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="vaccination.php" class="nav-item active"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / Vaccinations / <strong>Schedule New</strong></div>
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
            <a href="vaccination.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Schedule</a>
            
            <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;">Schedule New Vaccination</h2>

            <?php if (isset($_GET['error'])): ?>
                <div style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 8px; background: #FFF5F5; border: 1px solid #FCA5A5; color: #DC2626;">
                    <i class="fa-solid fa-circle-exclamation"></i> 
                    <?php 
                        if ($_GET['error'] == 'missing_fields') echo "Please fill in all required fields.";
                        else echo "Failed to save schedule. Please try again.";
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <!-- تم توجيه الفورم للكنترولر -->
                <form id="scheduleForm" method="POST" action="../../controllers/VaccineController.php">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Select Pet</label>
                            <!-- يتم استدعاء دالة setSpecies عند التغيير لتحديث الحقل المخفي -->
                            <select class="form-control" name="pet_id" id="petSelect" required onchange="setSpecies()">
                                <option value="" disabled selected>Choose a pet</option>
                                <?php foreach($pets as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-species="<?= htmlspecialchars($p['species']) ?>">
                                        <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['species']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <!-- حقل مخفي لارسال الفصيلة للكنترولر -->
                            <input type="hidden" name="species" id="speciesInput">
                        </div>
                        <div class="form-group">
                            <label>Vaccine Type</label>
                            <select class="form-control" name="vaccine_name" required>
                                <option value="" disabled selected>Select vaccine</option>
                                <option value="Rabies">Rabies</option>
                                <option value="Distemper">Distemper</option>
                                <option value="Bordetella">Bordetella</option>
                                <option value="FVRCP">FVRCP</option>
                                <option value="Leptospirosis">Leptospirosis</option>
                                <option value="Lyme Disease">Lyme Disease</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Last Vaccination Date</label>
                        <input type="date" class="form-control" name="last_date" max="<?= date('Y-m-d') ?>">
                        <p style="font-size: 0.75rem; color: #999; margin-top: 5px;">Leave empty if no previous history exists.</p>
                    </div>

                    <button type="submit" name="schedule_vaccine" class="btn-submit">Calculate & Save Schedule</button>
                </form>
            </div>

            <!-- عرض النتيجة لو الكنترولر رجعنا بنجاح -->
            <?php if (isset($_GET['success']) && $_GET['success'] == 'scheduled'): ?>
                <div id="result-section" style="display: block;">
                    <div class="result-title"><i class="fa-solid fa-circle-check"></i> Schedule Calculated Successfully</div>
                    <div id="calculation-text" style="font-size: 0.9rem; color: #16A34A;">
                        Next due date for the selected pet (<strong><?= htmlspecialchars($_GET['vac']) ?></strong>) is set to: <strong><?= htmlspecialchars($_GET['date']) ?></strong>.<br>The record has been saved to the medical history.
                    </div>
                    <button class="mark-done-btn" style="margin-top: 15px;" onclick="window.location.href='vaccination.php'">View All Vaccinations</button>
                </div>
                
                <script>
                    window.onload = function() {
                        document.getElementById('result-section').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    };
                </script>
            <?php endif; ?>

        </div>
    </div>

    <!-- سكريبت لتحديث الفصيلة في الحقل المخفي عشان الكنترولر يعرف يحسب البروتوكول -->
    <script>
        function setSpecies() {
            var select = document.getElementById('petSelect');
            var selectedOption = select.options[select.selectedIndex];
            var species = selectedOption.getAttribute('data-species');
            document.getElementById('speciesInput').value = species;
        }
    </script>

</body>
</html>