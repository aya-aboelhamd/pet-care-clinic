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

$vet_id = $_SESSION['user_id'];

// جلب بيانات الطبيب للهيدر
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$vet_id]);
$vet = $stmt->fetch(PDO::FETCH_ASSOC);

$vet_name = $vet['name'] ?? 'Veterinarian';
$vet_email = $vet['email'] ?? 'vet@petlor.com';

$name_parts = explode(' ', trim($vet_name));
$first_name = $name_parts[0];
$initials = strtoupper(substr($first_name, 0, 1));
if (isset($name_parts[1])) {
    $initials .= strtoupper(substr($name_parts[1], 0, 1));
}

// جلب قائمة الحيوانات اللي ليها حجز مع الدكتور ده
$stmt_pets = $db->prepare("
    SELECT DISTINCT p.id, p.name, p.species, p.breed
    FROM booking b
    JOIN pet p ON b.pet_id = p.id
    WHERE b.provider_id = ? 
    ORDER BY p.name ASC
");
$stmt_pets->execute([$vet_id]);
$booked_pets = $stmt_pets->fetchAll(PDO::FETCH_ASSOC);

// جلب آخر النوتس اللي الدكتور ده كتبها لعرضها في الجنب
$stmt_notes = $db->prepare("
    SELECT m.diagnosis, m.created_at, p.name as pet_name, p.species 
    FROM medicalrecord m 
    JOIN pet p ON m.pet_id = p.id 
    WHERE m.vet_id = ? 
    ORDER BY m.created_at DESC LIMIT 5
");
$stmt_notes->execute([$vet_id]);
$recent_notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Medical Notes</title>
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

        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; justify-content: space-between; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .nav-item:hover:not(.active) { background: #f0f0f0; }

        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .logout-btn { display: inline-flex; align-items: center; gap: 8px; color: #DC2626; background: #FEF2F2; border: 1px solid #FCA5A5; padding: 8px 12px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: 0.2s; margin-left: 10px; }
        .logout-btn:hover { background: #DC2626; color: white; }

        .content-padding { padding: 2rem; }
        .page-title { font-size: 1.8rem; margin-bottom: 5px; }
        .page-subtitle { color: var(--text-gray); font-size: 0.9rem; margin-bottom: 2rem; }

        .notes-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start; }
        .card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 1.5rem; }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; outline: none; color: var(--text-dark); background: #fff; }
        textarea { height: 200px; resize: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary-green); }

        .save-btn { background: var(--primary-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; margin-top: 1rem; transition: 0.2s;}
        .save-btn:hover { background: #488252; }

        .recent-note-item { border: 1px solid var(--border-color); border-radius: 10px; padding: 1.2rem; margin-bottom: 1rem; }
        .note-header { display: flex; align-items: center; gap: 10px; margin-bottom: 5px; color: var(--primary-green); }
        .note-header strong { color: var(--text-dark); font-size: 0.95rem; }
        .recent-note-item small { color: var(--text-gray); display: block; margin-bottom: 10px; font-size: 0.8rem; }
        .recent-note-item p { font-size: 0.85rem; color: var(--text-gray); line-height: 1.5; white-space: pre-wrap;}
        .alert-success { background: #F0FDF4; color: #16A34A; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #BBF7D0; font-size: 0.9rem; font-weight: 600;}
        .empty-state { text-align: center; color: #999; padding: 20px 0; font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo">
                <i class="fa-solid fa-paw"></i> 
                <div>Petlor</div>
            </div>
            <a href="vetDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="createprescription.php" class="nav-item"><i class="fa-regular fa-file-lines"></i> New Prescription</a>
            <a href="labresults.php" class="nav-item"><i class="fa-solid fa-flask"></i> Lab Results</a>
            <a href="medicalnotes.php" class="nav-item active"><i class="fa-regular fa-pen-to-square"></i> Medical Notes</a>
            <a href="diseasealert.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Disease Alerts</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Vet Portal / <strong>Welcome back, Dr. <?= htmlspecialchars($first_name) ?> 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <strong>Dr. <?= htmlspecialchars($vet_name) ?></strong><br>
                    <span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($vet_email) ?></span>
                </div>
                <a href="../Auth/logout.php" class="logout-btn" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </header>

        <div class="content-padding">
            <h2 class="page-title">Medical Notes</h2>
            <p class="page-subtitle">Select a booked patient and document examinations and observations.</p>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert-success"><i class="fa-solid fa-circle-check"></i> Note saved successfully and updated in the pet's profile.</div>
            <?php endif; ?>

            <div class="notes-grid">
                <div class="card">
                    <h3>New note</h3>
                    <form method="POST" action="../../controllers/MedicalNoteController.php">
                        <div class="form-group">
                            <label>Select Patient (From your appointments) *</label>
                            <select name="pet_id" required>
                                <option value="" disabled selected>Select a booked pet...</option>
                                <?php foreach($booked_pets as $bp): ?>
                                    <option value="<?= $bp['id'] ?>">
                                        <?= htmlspecialchars($bp['name']) ?> (<?= htmlspecialchars($bp['species']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note Title *</label>
                            <input type="text" name="title" placeholder="e.g. Annual check-up, Post-surgery review" required>
                        </div>
                        <div class="form-group">
                            <label>Medical Observations / Notes *</label>
                            <textarea name="note_content" placeholder="Examination findings, observations, recommendations..." required></textarea>
                        </div>
                        <button type="submit" class="save-btn">
                            <i class="fa-regular fa-floppy-disk"></i> Save Note to Profile
                        </button>
                    </form>
                </div>

                <div class="card">
                    <h3>Your Recent Notes</h3>
                    <?php if(empty($recent_notes)): ?>
                        <div class="empty-state">No medical notes recorded yet.</div>
                    <?php else: ?>
                        <?php foreach($recent_notes as $note): 
                            // فصل العنوان عن المحتوى إذا كانا مدمجين
                            $parts = explode("\n", $note['diagnosis'], 2);
                            $note_title = $parts[0] ?? 'Medical Note';
                            $note_body = $parts[1] ?? $note['diagnosis'];
                        ?>
                            <div class="recent-note-item">
                                <div class="note-header">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                    <strong><?= htmlspecialchars($note_title) ?></strong>
                                </div>
                                <small><?= htmlspecialchars($note['pet_name']) ?> (<?= htmlspecialchars($note['species']) ?>) · <?= date('Y-m-d', strtotime($note['created_at'])) ?></small>
                                <p><?= nl2br(htmlspecialchars(trim($note_body))) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>