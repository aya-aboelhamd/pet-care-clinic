<?php
session_start();

// التأكد إن اليوزر طبيب بيطري (رقم 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// جلب بيانات الطبيب للهيدر
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user['name'] ?? 'Dr. Vet';
$user_email = $user['email'] ?? 'vet@petlor.com';
$initials = strtoupper(substr($user_name, 0, 2));

// جلب قائمة الحيوانات المسجلة لكي يختار منها الطبيب
$stmt = $db->prepare("SELECT id, name, species, breed FROM pet WHERE is_archived = 0 OR is_archived IS NULL");
$stmt->execute();
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Create Prescription</title>
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
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; color: var(--text-dark); }

        /* --- Sidebar --- */
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .nav-item:hover:not(.active) { background: #f0f0f0; }

        /* --- Main Content --- */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase;}
        .logout-icon { color: var(--text-gray); transition: 0.2s; font-size: 1.1rem; }
        .logout-icon:hover { color: #d32f2f; }

        .content-padding { padding: 2.5rem; max-width: 1000px; }
        .page-title { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .page-subtitle { color: var(--text-gray); margin-bottom: 2rem; }

        .form-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .section-label { font-weight: 600; font-size: 0.9rem; margin-bottom: 1rem; display: block; }

        .form-group { margin-bottom: 1.5rem; }
        select, input, textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; outline: none; color: var(--text-dark); transition: border-color 0.2s; }
        select:focus, input:focus, textarea:focus { border-color: var(--primary-green); }

        .med-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .add-btn { background: #f5f5f5; border: 1px solid var(--border-color); padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 500; transition: 0.2s; }
        .add-btn:hover { background: #e0e0e0; }
        
        .check-btn { background: #FFF4E5; border: 1px solid #FDE68A; color: #D97706; padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 600; transition: 0.2s; }
        .check-btn:hover { background: #FEF3C7; }

        .med-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 40px; gap: 10px; padding: 12px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; align-items: center; margin-bottom: 1rem; }
        .trash-btn { color: #ff5252; text-align: center; cursor: pointer; transition: 0.2s; }
        .trash-btn:hover { color: #d32f2f; }

        textarea { height: 100px; resize: none; margin-bottom: 1rem; }

        .submit-btn { background: var(--primary-green); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 0.9rem; transition: 0.2s; width: 100%; justify-content: center;}
        .submit-btn:hover { background: #488252; }

        /* Alerts Container */
        #alerts-container { margin-bottom: 1.5rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 10px; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; }
        .alert-warning { background: #FEF2F2; color: #DC2626; border: 1px solid #FEE2E2; }
        .alert-safe { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-logo">
                <i class="fa-solid fa-paw"></i> 
                <div>Petlor</div>
            </div>
            <a href="vetDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="createprescription.php" class="nav-item active"><i class="fa-regular fa-file-lines"></i> New Prescription</a>
            <a href="labresults.php" class="nav-item"><i class="fa-solid fa-flask"></i> Lab Results</a>
            <a href="medicalnotes.php" class="nav-item"><i class="fa-regular fa-pen-to-square"></i> Medical Notes</a>
            <a href="diseasealert.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Disease Alerts</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Vet Portal / <strong>Welcome back, Dr. 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell" style="position: relative;"><span style="position: absolute; top: -2px; right: -2px; width: 7px; height: 7px; background: red; border-radius: 50%;"></span></i>
                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div><strong>Dr.<?= htmlspecialchars($user_name) ?></strong><br><span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($user_email) ?></span></div>
                <a href="../Auth/logout.php" style="color: inherit;"><i class="fa-solid fa-right-from-bracket logout-icon"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <h1 class="page-title">Create Prescription</h1>
            <p class="page-subtitle">Issue a new prescription with automated safety checks.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> Failed to save prescription.</div>
            <?php endif; ?>

            <div class="form-card">
                <form id="prescriptionForm" method="POST" action="../../controllers/PrescriptionController.php">
                    <input type="hidden" name="action" value="save_prescription">
                    
                    <!-- Patient Selection -->
                    <div class="form-group">
                        <label class="section-label">Select Patient *</label>
                        <select name="pet_id" id="pet_id" required>
                            <option value="" disabled selected>Choose a patient from the database...</option>
                            <?php foreach ($pets as $pet): ?>
                                <option value="<?= $pet['id'] ?>">
                                    <?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['species']) ?> - <?= htmlspecialchars($pet['breed']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Diagnosis -->
                    <div class="form-group">
                        <label class="section-label">Diagnosis *</label>
                        <input type="text" name="diagnosis" placeholder="e.g. Ear Infection, Allergies..." required>
                    </div>

                    <!-- Alerts Container for AJAX -->
                    <div id="alerts-container"></div>

                    <!-- Medications Section -->
                    <div class="med-header">
                        <label class="section-label" style="margin-bottom: 0;">Medications</label>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="check-btn" onclick="checkSafety()"><i class="fa-solid fa-shield-cat"></i> Check Safety</button>
                            <button type="button" class="add-btn" onclick="addMedRow()"><i class="fa-solid fa-plus"></i> Add Row</button>
                        </div>
                    </div>
                    
                    <div id="medications-container">
                        <!-- Initial Row -->
                        <div class="med-row">
                            <input type="text" name="med_names[]" class="med-name" placeholder="Medication name" required>
                            <input type="text" name="med_dosages[]" placeholder="Dosage (e.g. 10mg)" required>
                            <input type="text" name="duration" placeholder="Duration (e.g. 7 days)" required>
                            <input type="text" name="frequency" placeholder="Frequency (e.g. 2x/day)" required>
                            <div class="trash-btn" onclick="removeMedRow(this)"><i class="fa-regular fa-trash-can"></i></div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="form-group" style="margin-top: 2rem;">
                        <label class="section-label">Additional Instructions / Notes</label>
                        <textarea name="instructions" placeholder="Take with food, avoid sunlight, etc..."></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">
                        <i class="fa-regular fa-file-lines"></i>
                        Issue Prescription
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for Dynamic Rows & AJAX Checking -->
    <script>
        function addMedRow() {
            const container = document.getElementById('medications-container');
            const row = document.createElement('div');
            row.className = 'med-row';
            row.innerHTML = `
                <input type="text" name="med_names[]" class="med-name" placeholder="Medication name" required>
                <input type="text" name="med_dosages[]" placeholder="Dosage (e.g. 10mg)" required>
                <input type="text" name="duration" placeholder="Duration" required>
                <input type="text" name="frequency" placeholder="Frequency" required>
                <div class="trash-btn" onclick="removeMedRow(this)"><i class="fa-regular fa-trash-can"></i></div>
            `;
            container.appendChild(row);
        }

        function removeMedRow(element) {
            const rowCount = document.querySelectorAll('.med-row').length;
            if (rowCount > 1) {
                element.parentElement.remove();
            } else {
                alert('You must have at least one medication row.');
            }
        }

        function checkSafety() {
            const petId = document.getElementById('pet_id').value;
            const alertsContainer = document.getElementById('alerts-container');
            alertsContainer.innerHTML = ''; // Clear old alerts

            if (!petId) {
                alert('Please select a patient first.');
                return;
            }

            // Gather all medication names
            const medInputs = document.querySelectorAll('.med-name');
            const meds = Array.from(medInputs).map(input => input.value.trim()).filter(val => val !== '');

            if (meds.length === 0) {
                alert('Please enter at least one medication name to check.');
                return;
            }

            // Prepare Data for AJAX
            const formData = new URLSearchParams();
            formData.append('action', 'validate_drugs');
            formData.append('pet_id', petId);
            meds.forEach(med => formData.append('medications[]', med));

            // Change button icon to spinner
            const checkBtn = document.querySelector('.check-btn');
            const originalHtml = checkBtn.innerHTML;
            checkBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';

            // Send AJAX Request to Controller
            fetch('../../controllers/PrescriptionController.php', {
                method: 'POST',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                checkBtn.innerHTML = originalHtml;
                
                if (data.status === 'warning') {
                    data.warnings.forEach(warn => {
                        alertsContainer.innerHTML += `<div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> <strong>ALERT:</strong> ${warn}</div>`;
                    });
                } else if (data.status === 'safe') {
                    alertsContainer.innerHTML = `<div class="alert alert-safe"><i class="fa-solid fa-circle-check"></i> <strong>SAFE:</strong> No known allergies or severe interactions detected.</div>`;
                }
            })
            .catch(error => {
                checkBtn.innerHTML = originalHtml;
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>