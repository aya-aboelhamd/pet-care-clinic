<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Book a Service</title>
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
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .content-padding { padding: 2rem; }

        /* --- Booking Layout --- */
        .booking-grid { display: grid; grid-template-columns: 1.8fr 1fr; gap: 2rem; }

        .booking-form-card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 2rem; }
        .section-label { font-size: 0.9rem; font-weight: 700; margin-bottom: 1rem; display: block; }

        /* Service Selection */
        .service-selector { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 2rem; }
        .service-btn {
            border: 1px solid var(--border-color);
            background: white;
            padding: 1rem 0.5rem;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
        }
        .service-btn i { display: block; font-size: 1.5rem; margin-bottom: 8px; color: var(--text-gray); }
        .service-btn span { font-size: 0.75rem; font-weight: 600; color: var(--text-gray); }
        .service-btn.active { border-color: var(--primary-green); background: #F0F7F1; }
        .service-btn.active i, .service-btn.active span { color: var(--primary-green); }

        /* Pet Selection */
        .pet-selector { display: flex; gap: 15px; margin-bottom: 2rem; }
        .pet-option {
            flex: 1;
            border: 1px solid var(--border-color);
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }
        .pet-option.active { border-color: var(--primary-green); background: #F0F7F1; }
        .pet-option .icon { font-size: 1.5rem; }

        /* Form Fields */
        .inputs-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-gray); }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
        }
        textarea { height: 80px; resize: none; }

        .request-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center; gap: 8px;
        }

        /* --- Right Sidebar: Bookings --- */
        .bookings-sidebar { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; }
        .booking-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .status-badge { font-size: 0.7rem; padding: 3px 10px; border-radius: 20px; font-weight: 700; }
        .status-confirmed { background: #DCFCE7; color: #16A34A; }
        .status-pending { background: #FFF4E5; color: #D97706; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item active"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Welcome back, Sarah 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar-circle">SJ</div>
                <div><strong>Sarah Johnson</strong><br><span style="font-size: 0.75rem; color: #999;">owner@petlor.com</span></div>
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
        </header>

        <div class="content-padding">
            <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Book a Service</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem;">Pick a service, time and provider.</p>

            <div class="booking-grid">
                <div class="booking-form-card">
                    <span class="section-label">Select a service</span>
                    <div class="service-selector">
                        <div class="service-btn active"><i class="fa-solid fa-scissors"></i><span>Grooming</span></div>
                        <div class="service-btn"><i class="fa-solid fa-person-walking"></i><span>Walking</span></div>
                        <div class="service-btn"><i class="fa-solid fa-house-chimney"></i><span>Boarding</span></div>
                        <div class="service-btn"><i class="fa-solid fa-graduation-cap"></i><span>Training</span></div>
                        <div class="service-btn"><i class="fa-solid fa-stethoscope"></i><span>Vet Visit</span></div>
                    </div>

                    <span class="section-label">Pet</span>
                    <div class="pet-selector">
                        <div class="pet-option active">
                            <span class="icon">🐶</span>
                            <div><strong>Max</strong><br><small>Golden Retriever</small></div>
                        </div>
                        <div class="pet-option">
                            <span class="icon">🐱</span>
                            <div><strong>Luna</strong><br><small>British Shorthair</small></div>
                        </div>
                    </div>

                    <div class="inputs-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" value="2026-04-27">
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Notes (allergies, special instructions)</label>
                        <textarea placeholder="e.g. Max is allergic to chicken."></textarea>
                    </div>

                    <button class="request-btn"><i class="fa-solid fa-calendar-check"></i> Request booking</button>
                </div>

                <div class="bookings-sidebar">
                    <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem;">Your bookings</h3>
                    
                    <div class="booking-item">
                        <div>
                            <strong>Walking</strong><br>
                            <small style="color: #888;">Max • 2026-04-28 08:30</small>
                        </div>
                        <span class="status-badge status-confirmed">Confirmed</span>
                    </div>

                    <div class="booking-item">
                        <div>
                            <strong>Grooming</strong><br>
                            <small style="color: #888;">Luna • 2026-05-02 14:00</small>
                        </div>
                        <span class="status-badge status-pending">Pending</span>
                    </div>

                    <div class="booking-item">
                        <div>
                            <strong>Boarding</strong><br>
                            <small style="color: #888;">Max • 2026-05-15 10:00</small>
                        </div>
                        <span class="status-badge status-pending">Pending</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

