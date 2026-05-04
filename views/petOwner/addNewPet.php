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
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .content-padding { padding: 2rem; max-width: 900px; margin: 0 auto; width: 100%; }

        /* --- Form Styling --- */
        .form-card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 2.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        
        .form-header { margin-bottom: 2rem; border-bottom: 1px solid #f0f0f0; padding-bottom: 1rem; }
        .form-header h2 { font-size: 1.5rem; color: var(--text-dark); }

        .photo-upload-section { display: flex; flex-direction: column; align-items: center; gap: 15px; margin-bottom: 2.5rem; }
        .photo-placeholder { width: 100px; height: 100px; background: #f0f4f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #999; border: 2px dashed var(--border-color); }
        .upload-btn { font-size: 0.8rem; color: var(--primary-green); font-weight: 600; cursor: pointer; text-decoration: underline; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; outline: none; transition: 0.2s;
        }
        .form-group input:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(88, 154, 100, 0.1); }

        .tags-input-area { background: #fcfcfc; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; display: flex; flex-wrap: wrap; gap: 8px; }
        .tag-pill { background: #eee; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; display: flex; align-items: center; gap: 5px; }

        .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 2rem; border-top: 1px solid #f0f0f0; padding-top: 2rem; }
        .btn { padding: 0.8rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
        .btn-cancel { background: #f5f5f5; color: var(--text-gray); }
        .btn-save { background: var(--primary-green); color: white; }
        .btn:hover { opacity: 0.9; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item active"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / My Pets / <strong>Add New Pet</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar-circle">SJ</div>
                <div><strong>Sarah Johnson</strong><br><span style="font-size: 0.75rem; color: #999;">owner@petlor.com</span></div>
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
        </header>

        <div class="content-padding">
            <div class="form-card">
                <div class="form-header">
                    <h2>Add a new pet</h2>
                    <p style="font-size: 0.85rem; color: var(--text-gray);">Tell us more about your furry friend.</p>
                </div>

                <form>
                    

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pet Name</label>
                            <input type="text" placeholder="Enter name">
                        </div>
                        <div class="form-group">
                            <label>Species</label>
                            <select>
                                <option>Dog</option>
                                <option>Cat</option>
                                <option>Bird</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Breed</label>
                            <input type="text" placeholder="e.g. Golden Retriever">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select>
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date">
                        </div>
                        <div class="form-group">
                            <label>Weight (kg)</label>
                            <input type="number" step="0.1" placeholder="0.0">
                        </div>

                        <div class="form-group full-width">
                            <label>Known Allergies</label>
                            <div class="tags-input-area">
                                <span class="tag-pill">Chicken <i class="fa-solid fa-xmark"></i></span>
                                <span class="tag-pill">Wheat <i class="fa-solid fa-xmark"></i></span>
                                <input type="text" placeholder="Add allergy..." style="border:none; width: 120px; padding: 4px;">
                            </div>
                            <small style="color: #999; font-size: 0.7rem; margin-top: 5px; display: block;">This will help us flag unsafe products in the Marketplace.</small>
                        </div>

                        <div class="form-group full-width">
                            <label>Medical Conditions / Notes</label>
                            <textarea placeholder="Any additional health information..."></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="window.location.href='mypets.php'">Cancel</button>
                        <button type="submit" class="btn btn-save">Save Pet Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>


