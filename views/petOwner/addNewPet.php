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
$error = '';
$success = '';

// جلب بيانات المستخدم للهيدر
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['name'] ?? 'User';
$user_email = $user['email'] ?? 'user@petlor.com';
$name_parts = explode(' ', trim($user_name));
$first_name = $name_parts[0];
$initials = strtoupper(substr($first_name, 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : substr($first_name, 1, 1)));

// استقبال البيانات عند الضغط على Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $species = $_POST['species'];
    $breed = trim($_POST['breed']);
    $gender = $_POST['gender'];
    $dob = $_POST['date_of_birth'];
    $weight = $_POST['weight'];
    $allergies = $_POST['allergies']; // جاية من الحقل المخفي
    $medical_notes = trim($_POST['medical_notes']);

    if (!empty($name) && !empty($species)) {
        $query = "INSERT INTO pet (user_id, name, species, breed, gender, date_of_birth, weight, allergies, medical_notes, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$user_id, $name, $species, $breed, $gender, $dob, $weight, $allergies, $medical_notes])) {
            // توجيه لصفحة My Pets بعد النجاح
            header("Location: mypets.php?success=pet_added");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    } else {
        $error = "Pet name and species are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Add New Pet</title>
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
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            flex-shrink: 0;
        }

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

        .content-padding { padding: 2rem; max-width: 900px; margin: 0 auto; width: 100%; }

        /* --- Form Styling --- */
        .form-card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 2.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        
        .form-header { margin-bottom: 2rem; border-bottom: 1px solid #f0f0f0; padding-bottom: 1rem; }
        .form-header h2 { font-size: 1.5rem; color: var(--text-dark); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; outline: none; transition: 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(88, 154, 100, 0.1); }

        .tags-input-area { background: #fcfcfc; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;}
        .tag-pill { background: #eee; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; display: flex; align-items: center; gap: 5px; }
        .tag-pill i { cursor: pointer; color: #999; }
        .tag-pill i:hover { color: #d32f2f; }

        .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 2rem; border-top: 1px solid #f0f0f0; padding-top: 2rem; }
        .btn { padding: 0.8rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
        .btn-cancel { background: #f5f5f5; color: var(--text-gray); text-decoration: none; display: inline-block; text-align: center;}
        .btn-save { background: var(--primary-green); color: white; }
        .btn:hover { opacity: 0.9; }

        .alert-error { background: #ffebee; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #ef9a9a;}
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
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / My Pets / <strong>Add New Pet</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <div><strong><?= htmlspecialchars($user_name) ?></strong><br><span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span></div>
                <a href="../Auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="form-card">
                <div class="form-header">
                    <h2>Add a new pet</h2>
                    <p style="font-size: 0.85rem; color: var(--text-gray);">Tell us more about your furry friend.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert-error"><?= $error ?></div>
                <?php endif; ?>

                <!-- تم إضافة method POST -->
                <form method="POST" action="">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pet Name *</label>
                            <input type="text" name="name" placeholder="Enter name" required>
                        </div>
                        <div class="form-group">
                            <label>Species *</label>
                            <select name="species" required>
                                <option value="" disabled selected>Select species</option>
                                <option value="Dog">Dog</option>
                                <option value="Cat">Cat</option>
                                <option value="Bird">Bird</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Breed</label>
                            <input type="text" name="breed" placeholder="e.g. Golden Retriever">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth">
                        </div>
                        <div class="form-group">
                            <label>Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" placeholder="0.0">
                        </div>

                        <div class="form-group full-width">
                            <label>Known Allergies</label>
                            <div class="tags-input-area" id="tagsContainer">
                                <!-- التاجز هتظهر هنا بالـ JS -->
                                <input type="text" id="allergyInput" placeholder="Type and press Enter..." style="border:none; width: 160px; padding: 4px; outline:none; background:transparent;">
                            </div>
                            <small style="color: #999; font-size: 0.7rem; margin-top: 5px; display: block;">Press Enter after typing each allergy. This will help us flag unsafe products.</small>
                            <!-- حقل مخفي هنخزن فيه الكلمات عشان تتبعت للداتا بيز -->
                            <input type="hidden" name="allergies" id="hiddenAllergiesInput">
                        </div>

                        <div class="form-group full-width">
                            <label>Medical Conditions / Notes</label>
                            <textarea name="medical_notes" rows="3" placeholder="Any additional health information..."></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="mypets.php" class="btn btn-cancel">Cancel</a>
                        <button type="submit" class="btn btn-save">Save Pet Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- سكريبت بسيط عشان يشغل نظام الـ Tags بتاع الحساسية -->
    <script>
        const allergyInput = document.getElementById('allergyInput');
        const tagsContainer = document.getElementById('tagsContainer');
        const hiddenAllergiesInput = document.getElementById('hiddenAllergiesInput');
        let allergiesArray = [];

        allergyInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // منع الفورم من الإرسال عند الضغط على انتر
                const value = allergyInput.value.trim();
                
                if (value !== '' && !allergiesArray.includes(value)) {
                    allergiesArray.push(value);
                    renderTags();
                    allergyInput.value = '';
                }
            }
        });

        function removeTag(index) {
            allergiesArray.splice(index, 1);
            renderTags();
        }

        function renderTags() {
            // نمسح التاجز القديمة ونسيب الانبوت بس
            const tags = tagsContainer.querySelectorAll('.tag-pill');
            tags.forEach(tag => tag.remove());

            // نرسم التاجز الجديدة
            allergiesArray.slice().reverse().forEach((allergy, index) => {
                const actualIndex = allergiesArray.length - 1 - index;
                const span = document.createElement('span');
                span.className = 'tag-pill';
                span.innerHTML = `${allergy} <i class="fa-solid fa-xmark" onclick="removeTag(${actualIndex})"></i>`;
                tagsContainer.insertBefore(span, allergyInput);
            });

            // نحدث قيمة الحقل المخفي عشان تروح للداتا بيز مفصولة بفاصلة
            hiddenAllergiesInput.value = allergiesArray.join(',');
        }
    </script>
</body>
</html>