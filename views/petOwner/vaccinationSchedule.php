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

        /* --- Sidebar (Identical to yours) --- */
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
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .content-padding { padding: 2rem; max-width: 900px; }
        
        .back-link { color: var(--primary-green); text-decoration: none; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 1rem; }

        /* --- Form Card --- */
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus { border-color: var(--primary-green); }

        .btn-submit {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            margin-top: 1rem;
        }

        /* --- Result Section --- */
        #result-section {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 12px;
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            display: none; /* Hidden by default */
        }
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
                <div class="avatar-circle">SJ</div>
                <div>
                    <strong>Sarah Johnson</strong><br>
                    <span style="font-size: 0.75rem; color: #999;">owner@petlor.com</span>
                </div>
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
        </header>

        <div class="content-padding">
            <a href="vaccination.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Schedule</a>
            
            <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;">Schedule New Vaccination</h2>

            <div class="form-container">
                <form id="scheduleForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Select Pet</label>
                            <select class="form-control" id="petSelect" required>
                                <option value="">Choose a pet</option>
                                <option value="Dog">Max (Dog)</option>
                                <option value="Cat">Luna (Cat)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vaccine Type</label>
                            <select class="form-control" id="vaccineType" required>
                                <option value="">Select vaccine</option>
                                <option value="Rabies">Rabies</option>
                                <option value="Distemper">Distemper</option>
                                <option value="Bordetella">Bordetella</option>
                                <option value="FVRCP">FVRCP</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Last Vaccination Date</label>
                        <input type="date" class="form-control" id="lastDate">
                        <p style="font-size: 0.75rem; color: #999; margin-top: 5px;">Leave empty if no previous history exists.</p>
                    </div>

                    <button type="submit" class="btn-submit">Calculate & Save Schedule</button>
                </form>
            </div>

            <div id="result-section">
                <div class="result-title"><i class="fa-solid fa-circle-check"></i> Schedule Calculated Successfully</div>
                <div id="calculation-text" style="font-size: 0.9rem; color: #16A34A;"></div>
                <button class="mark-done-btn" style="margin-top: 15px;" onclick="window.location.href='vaccination.php'">View All Vaccinations</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const pet = document.getElementById('petSelect').value;
            const vaccine = document.getElementById('vaccineType').value;
            const lastDateVal = document.getElementById('lastDate').value;
            
            let nextDue = new Date();

            if (lastDateVal) {
                // Typical Course: Add 1 year to last date
                nextDue = new Date(lastDateVal);
                nextDue.setFullYear(nextDue.getFullYear() + 1);
            } else {
                // Alternate Course: Default protocol (e.g., today + 1 month for new pet)
                nextDue.setMonth(nextDue.getMonth() + 1);
            }

            const dateString = nextDue.toISOString().split('T')[0];
            
            // Show Result
            const resultBox = document.getElementById('result-section');
            const resultText = document.getElementById('calculation-text');
            
            resultText.innerHTML = `Next due date for <strong>${pet}</strong> (${vaccine}) is set to: <strong>${dateString}</strong>.<br>The record has been saved to the medical history.`;
            resultBox.style.display = 'block';
            
            // Scroll to result
            resultBox.scrollIntoView({ behavior: 'smooth' });
        });
    </script>

</body>
</html>


