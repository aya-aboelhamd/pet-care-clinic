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
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            flex-shrink: 0;
            justify-content: space-between;
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
            color: var(--text-dark);
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

        .demo-switch { padding: 1.5rem; border-top: 1px solid var(--border-color); }
        .demo-switch h4 { font-size: 0.65rem; color: #999; margin-bottom: 10px; }
        .role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
        .role-btn { padding: 5px; text-align: center; font-size: 0.75rem; border-radius: 20px; color: var(--text-gray); text-decoration: none; }
        .role-btn.active { background: #DFF0E2; color: var(--primary-green); font-weight: 600; }

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

        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .logout-icon { color: var(--text-gray); border: 1px solid var(--border-color); padding: 5px; border-radius: 5px; cursor: pointer; }

        /* Create Prescription Page Specific */
        .content-padding { padding: 2.5rem; max-width: 1000px; }
        .page-title { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .page-subtitle { color: var(--text-gray); margin-bottom: 2rem; }

        .form-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .section-label { font-weight: 600; font-size: 0.9rem; margin-bottom: 1rem; display: block; }

        /* Patient Selection */
        .patient-grid { display: flex; gap: 15px; margin-bottom: 2rem; }
        .patient-card {
            flex: 1;
            max-width: 250px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: 0.2s;
        }
        .patient-card.active { border-color: var(--primary-green); background-color: #f8faf8; }
        .patient-card img { width: 35px; height: 35px; border-radius: 50%; }
        .patient-info strong { font-size: 0.9rem; display: block; }
        .patient-info small { color: var(--text-gray); font-size: 0.75rem; }

        /* Medications Row */
        .med-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .add-btn { background: #f5f5f5; border: 1px solid var(--border-color); padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 500; }

        .med-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 40px;
            gap: 10px;
            padding: 12px;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        input, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.85rem;
            outline: none;
            color: var(--text-dark);
        }
        input::placeholder, textarea::placeholder { color: #aaa; }

        .trash-btn { color: #ff5252; text-align: center; cursor: pointer; }

        /* Notes */
        textarea { height: 120px; resize: none; margin-bottom: 2rem; }

        /* Submit Button */
        .submit-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 0.9rem;
        }

    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-logo">
                <i class="fa-solid fa-paw"></i> 
                <div>Petlor <br></div>
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
                <div class="avatar">DM</div>
                <div><strong>Dr. Mark Lee</strong><br><span style="font-size: 0.75rem; color: #999;">vet@petlor.com</span></div>
                <i class="fa-solid fa-right-from-bracket logout-icon"></i>
            </div>
        </header>

        <div class="content-padding">
            <h1 class="page-title">Create Prescription</h1>
            <p class="page-subtitle">Issue a new prescription for a patient.</p>

            <div class="form-card">
                <!-- Patient Section -->
                <label class="section-label">Patient</label>
                <div class="patient-grid">
                    <div class="patient-card active">
                        <div style="font-size: 1.5rem;">🐕</div> <!-- Placeholder for image -->
                        <div class="patient-info">
                            <strong>Max</strong>
                            <small>Golden Retriever</small>
                        </div>
                    </div>
                    <div class="patient-card">
                        <div style="font-size: 1.5rem;">🐈</div> <!-- Placeholder for image -->
                        <div class="patient-info">
                            <strong>Luna</strong>
                            <small>British Shorthair</small>
                        </div>
                    </div>
                </div>

                <!-- Medications Section -->
                <div class="med-header">
                    <label class="section-label" style="margin-bottom: 0;">Medications</label>
                    <button class="add-btn"><i class="fa-solid fa-plus"></i> Add</button>
                </div>
                <div class="med-row">
                    <input type="text" placeholder="Medication name">
                    <input type="text" placeholder="Dosage">
                    <input type="text" placeholder="Frequency">
                    <input type="text" placeholder="Duration">
                    <div class="trash-btn"><i class="fa-regular fa-trash-can"></i></div>
                </div>

                <!-- Notes Section -->
                <label class="section-label">Notes</label>
                <textarea placeholder="Additional instructions or warnings..."></textarea>

                <!-- Submit Button -->
                <button class="submit-btn">
                    <i class="fa-regular fa-file-lines"></i>
                    Issue prescription
                </button>
            </div>
        </div>
    </div>

</body>
</html>


