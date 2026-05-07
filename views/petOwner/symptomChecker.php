<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['name'] ?? 'User';
$name_parts = explode(' ', trim($user_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Symptom Checker</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .nav-item:hover:not(.active) { background: #f0f0f0; }

        /* Main Content */
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase;}
        
        .content-padding { padding: 2.5rem; max-width: 800px; margin: 0 auto; width: 100%; }
        
        /* Triage Box */
        .triage-container { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .step-box { display: none; }
        .step-box.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        h2 { font-size: 1.5rem; margin-bottom: 10px; color: var(--text-dark); }
        p { color: var(--text-gray); margin-bottom: 20px; font-size: 0.95rem; line-height: 1.5; }

        /* تم تعديل ستايل قائمة الاختيارات */
        select.symptom-select { width: 100%; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; outline: none; margin-bottom: 20px; color: var(--text-dark); cursor: pointer; transition: border-color 0.2s; }
        select.symptom-select:focus { border-color: var(--primary-green); }

        .btn { background: var(--primary-green); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: 0.2s; }
        .btn:hover { background: #488252; }
        
        .btn-outline { background: white; color: var(--text-dark); border: 1px solid var(--border-color); }
        .btn-outline:hover { background: #f5f5f5; }

        .button-group { display: flex; gap: 10px; }

        /* Recommendation Box */
        .recommendation-box { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 20px; border-radius: 10px; text-align: center; }
        .recommendation-box h3 { color: #16A34A; font-size: 1.8rem; margin: 10px 0; }
        .rationale { background: white; padding: 15px; border-radius: 8px; margin-top: 15px; font-size: 0.9rem; color: #4B5563; text-align: left; border: 1px solid #E5E7EB;}
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
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item active"><i class="fa-solid fa-stethoscope"></i> Symptom Checker</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Symptom Triage</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div><strong><?= htmlspecialchars($user_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="triage-container">
                
                <!-- Step 1: Select Symptoms -->
                <div id="step-1" class="step-box active">
                    <h2><i class="fa-solid fa-robot" style="color: var(--primary-green);"></i> Symptom Triage Engine</h2>
                    <p>Select what your pet is experiencing. Our logic-gate system will analyze the symptoms and recommend the right specialist.</p>
                    
                    <!-- تم استبدال الـ textarea بالـ select -->
                    <select id="symptom-input" class="symptom-select">
                        <option value="" disabled selected>-- Please select a primary symptom --</option>
                        <option value="lump">Found a lump, mass, or unexplained swelling</option>
                        <option value="limp">Limping, difficulty walking, or apparent leg pain</option>
                        <option value="aggressive">Sudden aggression, biting, or extreme fearfulness</option>
                        <option value="vomiting">Vomiting, diarrhea, or loss of appetite</option>
                        <option value="coughing">Coughing, sneezing, or breathing difficulty</option>
                        <option value="itching">Excessive scratching, hair loss, or red skin</option>
                    </select>

                    <button class="btn" onclick="analyzeSymptom()">Analyze Symptoms</button>
                </div>

                <!-- Step 2: Follow-up Question -->
                <div id="step-2" class="step-box">
                    <h2>Clarification Needed</h2>
                    <p id="follow-up-text" style="font-weight: 600; color: #1A1A1A; font-size: 1.1rem;"></p>
                    <div class="button-group">
                        <button class="btn" onclick="submitFollowUp('yes')">Yes</button>
                        <button class="btn btn-outline" onclick="submitFollowUp('no')">No</button>
                    </div>
                </div>

                <!-- Step 3: Recommendation -->
                <div id="step-3" class="step-box">
                    <div class="recommendation-box">
                        <i class="fa-solid fa-user-doctor" style="font-size: 3rem; color: #16A34A;"></i>
                        <p style="margin-top: 10px; color: #666;">Recommended Specialist:</p>
                        <h3 id="specialist-name"></h3>
                        <div class="rationale">
                            <strong>Rationale:</strong> <span id="rationale-text"></span>
                        </div>
                        <button class="btn" style="margin-top: 20px;" onclick="window.location.href='booking.php'">Book Appointment Now</button>
                        <button class="btn btn-outline" style="margin-top: 20px;" onclick="location.reload()">Start Over</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        let currentContext = '';

        function analyzeSymptom() {
            const symptom = document.getElementById('symptom-input').value;
            if(!symptom) { alert('Please select a symptom from the list first.'); return; }

            const formData = new URLSearchParams();
            formData.append('action', 'analyze_symptom');
            formData.append('symptom', symptom);

            fetch('../../controllers/TriageController.php', {
                method: 'POST',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('step-1').classList.remove('active');
                
                if(data.step === 'follow_up') {
                    currentContext = data.context;
                    document.getElementById('follow-up-text').innerText = data.question;
                    document.getElementById('step-2').classList.add('active');
                } else if (data.step === 'result') {
                    showResult(data.specialist, data.rationale);
                }
            });
        }

        function submitFollowUp(answer) {
            const formData = new URLSearchParams();
            formData.append('action', 'follow_up');
            formData.append('context', currentContext);
            formData.append('answer', answer);

            fetch('../../controllers/TriageController.php', {
                method: 'POST',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('step-2').classList.remove('active');
                showResult(data.specialist, data.rationale);
            });
        }

        function showResult(specialist, rationale) {
            document.getElementById('specialist-name').innerText = specialist;
            document.getElementById('rationale-text').innerText = rationale;
            document.getElementById('step-3').classList.add('active');
        }
    </script>
</body>
</html>