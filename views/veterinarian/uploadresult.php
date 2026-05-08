<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../Auth/login.php");
    exit();
}
require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT id, name, species FROM pet WHERE is_archived = 0 OR is_archived IS NULL");
$stmt->execute();
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vet Portal - Upload Lab Results</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* (استخدم نفس الـ CSS بتاع صفحة createprescription بالظبط عشان نوفر مساحة هنا) */
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --border-color: #E0E0E0; --text-dark: #1A1A1A; --text-gray: #666; --sidebar-width: 240px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; color: var(--text-dark); }
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .content-padding { padding: 2.5rem; max-width: 900px; }
        .form-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; }
        textarea { height: 100px; resize: none; }
        .btn { background: var(--primary-green); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 15px; }
        .alert-error { background: #FEF2F2; color: #DC2626; border: 1px solid #FEE2E2; }
        .alert-success { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; background: #FFF5F5; padding: 15px; border-radius: 8px; border: 1px solid #FCA5A5; color: #DC2626; }
        .checkbox-group input { width: auto; transform: scale(1.2); cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="vetDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="createprescription.php" class="nav-item"><i class="fa-regular fa-file-lines"></i> New Prescription</a>
            <a href="labresults.php" class="nav-item active"><i class="fa-solid fa-flask"></i> Lab Results</a>
            <a href="medicalnotes.php" class="nav-item"><i class="fa-regular fa-pen-to-square"></i> Medical Notes</a>
            <a href="diseasealert.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Disease Alerts</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Vet Portal / <strong>Lab Results</strong></div>
        </header>

        <div class="content-padding">
            <h1 style="font-size: 2rem; margin-bottom: 5px;">Upload Lab Results</h1>
            <p style="color: #666; margin-bottom: 2rem;">Upload technical results and provide simplified insights for the pet owner.</p>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Lab result uploaded successfully.</div>
            <?php elseif(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> 
                    <?= $_GET['error'] == 'invalid_format' ? 'Invalid file format. Only PDF, JPG, PNG allowed.' : 'Upload failed. Please try again.' ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <!-- مهم جداً enctype="multipart/form-data" عشان رفع الملفات يشتغل -->
                <form method="POST" action="../../controllers/LabResultController.php" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Select Patient *</label>
                            <select name="pet_id" required>
                                <option value="" disabled selected>Choose a patient...</option>
                                <?php foreach($pets as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['species']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Test Name *</label>
                            <input type="text" name="test_name" placeholder="e.g. Complete Blood Count (CBC)" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Upload File (PDF/Image) *</label>
                        <input type="file" name="lab_file" accept=".pdf, .jpg, .jpeg, .png" required>
                    </div>

                    <!-- البيانات المعقدة للدكتور فقط -->
                    <div class="form-group">
                        <label>Technical Data (For Vet Eyes Only) <i class="fa-solid fa-lock" style="color:#999;"></i></label>
                        <textarea name="technical_data" placeholder="WBC: 15.2 x10^9/L (High), RBC: 6.5 x10^12/L..." required></textarea>
                    </div>

                    <!-- الشرح المبسط للمالك (الـ Insight اللي طلبته اليوز كيس) -->
                    <div class="form-group">
                        <label>Simplified Insight (For Pet Owner) <i class="fa-solid fa-eye" style="color:var(--primary-green);"></i></label>
                        <textarea name="simplified_insight" placeholder="e.g. Max has a slight infection, which explains his fever. The rest of his organs are perfectly healthy." required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Additional Notes / Next Steps</label>
                        <input type="text" name="vet_notes" placeholder="e.g. Start antibiotics, recheck in 2 weeks.">
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="is_critical" id="critical">
                        <label for="critical" style="margin:0; font-weight:bold; cursor:pointer;">CRITICAL RESULT: Alert the pet owner immediately!</label>
                    </div>

                    <button type="submit" name="upload_result" class="btn"><i class="fa-solid fa-cloud-arrow-up"></i> Upload & Notify Owner</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>