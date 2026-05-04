<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Cart & Checkout</title>
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
            --danger-red: #FEE2E2;
            --danger-text: #DC2626;
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

        /* --- Layout Grid --- */
        .checkout-container { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; align-items: start; }

        /* --- Cart Items --- */
        .cart-item {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .item-img { width: 80px; height: 80px; background: #F0F4F0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; }
        .item-details { flex-grow: 1; }
        .item-details h4 { font-size: 1.1rem; margin-bottom: 4px; }
        .item-details p { font-size: 0.85rem; color: var(--text-gray); margin-bottom: 8px; }

        .qty-control { display: flex; align-items: center; gap: 15px; margin-top: 10px; }
        .qty-btn { width: 28px; height: 28px; border: 1px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer; }
        
        .price-col { text-align: right; }
        .item-price { font-weight: 700; font-size: 1.1rem; display: block; margin-bottom: 10px; }
        .remove-btn { color: #d32f2f; font-size: 0.85rem; border: none; background: none; cursor: pointer; display: flex; align-items: center; gap: 5px; }

        .safety-warning {
            background: var(--danger-red);
            color: var(--danger-text);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center; gap: 6px;
            margin-top: 5px;
        }

        /* --- Order Summary Card --- */
        .summary-card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; }
        .summary-card h3 { margin-bottom: 1.5rem; font-size: 1.2rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; }
        .summary-total { border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 12px; font-weight: 700; font-size: 1.1rem; }

        .shipping-form { margin-top: 1.5rem; }
        .shipping-form label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 5px; color: var(--text-gray); }
        .shipping-form input { width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 12px; }

        .place-order-btn {
            width: 100%;
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1rem;
            transition: 0.2s;
        }
        .place-order-btn:hover { background: #488252; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item active"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
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
            <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Cart & Checkout</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem;">Review your items and complete the order.</p>

            <div class="checkout-container">
                <div class="cart-items-list">
                    
                    <div class="cart-item">
                        <div class="item-img">🍖</div>
                        <div class="item-details">
                            <h4>Grain-Free Beef Chunks</h4>
                            <p>NatureBowl</p>
                            <div class="qty-control">
                                <button class="qty-btn">-</button>
                                <span>1</span>
                                <button class="qty-btn">+</button>
                            </div>
                        </div>
                        <div class="price-col">
                            <span class="item-price">$38.50</span>
                            <button class="remove-btn"><i class="fa-regular fa-trash-can"></i> Remove</button>
                        </div>
                    </div>

                    <div class="cart-item">
                        <div class="item-img">🛏️</div>
                        <div class="item-details">
                            <h4>Calming Bed</h4>
                            <p>DreamPet</p>
                            <div class="qty-control">
                                <button class="qty-btn">-</button>
                                <span>1</span>
                                <button class="qty-btn">+</button>
                            </div>
                        </div>
                        <div class="price-col">
                            <span class="item-price">$64.00</span>
                            <button class="remove-btn"><i class="fa-regular fa-trash-can"></i> Remove</button>
                        </div>
                    </div>

                    <div class="cart-item">
                        <div class="item-img">🍗</div>
                        <div class="item-details">
                            <h4>Chicken Treats</h4>
                            <p>PetGold</p>
                            <div class="safety-warning">
                                <i class="fa-solid fa-circle-exclamation"></i> Contains Chicken — not safe for Max
                            </div>
                            <div class="qty-control">
                                <button class="qty-btn">-</button>
                                <span>2</span>
                                <button class="qty-btn">+</button>
                            </div>
                        </div>
                        <div class="price-col">
                            <span class="item-price">$19.98</span>
                            <button class="remove-btn"><i class="fa-regular fa-trash-can"></i> Remove</button>
                        </div>
                    </div>

                </div>

                <div class="summary-card">
                    <h3>Order Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span>$122.48</span></div>
                    <div class="summary-row"><span>Shipping</span><span style="color: var(--primary-green);">Free</span></div>
                    <div class="summary-row summary-total"><span>Total</span><span>$122.48</span></div>

                    <div class="shipping-form">
                        <label>Shipping Address</label>
                        <input type="text" value="123 Pet Lane">
                        
                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;">
                                <label>City</label>
                                <input type="text" value="Springfield">
                            </div>
                            <div style="flex: 1;">
                                <label>ZIP</label>
                                <input type="text" value="12345">
                            </div>
                        </div>
                    </div>

                    <button class="place-order-btn">Place order</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

