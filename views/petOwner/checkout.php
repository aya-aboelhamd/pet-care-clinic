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

// جلب بيانات اليوزر
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user['name'] ?? 'User';
$first_name = explode(' ', trim($user_name))[0];
$initials = strtoupper(substr($first_name, 0, 1));


$stmt = $db->prepare("
    SELECT ci.id as item_id, ci.quantity, p.id as product_id, p.name, p.price, p.icon 
    FROM cartitem ci 
    JOIN cart c ON ci.cart_id = c.id 
    JOIN product p ON ci.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_price = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Petlor - Checkout</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-gray); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; }
        .avatar-circle { width: 35px; height: 35px; background: #4CAF50; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .content-padding { padding: 2rem; }
        .checkout-container { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; align-items: start; }
        .cart-item { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; display: flex; gap: 1.5rem; align-items: center; }
        .item-img { width: 80px; height: 80px; background: #F0F4F0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; }        
        .item-img img { width: 100%; height: 100%; object-fit: contain; }
        .item-details { flex-grow: 1; }
        .item-details h4 { font-size: 1.1rem; margin-bottom: 4px; }
        .qty-control { margin-top: 10px; font-size: 0.9rem; color: #666;}
        .price-col { text-align: right; }
        .item-price { font-weight: 700; font-size: 1.1rem; display: block; margin-bottom: 10px; }
        .remove-btn { color: #d32f2f; font-size: 0.85rem; border: none; background: none; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .summary-card { background: white; border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; }
        .summary-total { border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 12px; font-weight: 700; font-size: 1.1rem; }
        .place-order-btn { width: 100%; background: var(--primary-green); color: white; border: none; padding: 1rem; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 1rem; transition: 0.2s; }
        .place-order-btn:hover { background: #488252; }
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
        <a href="checkout.php" class="nav-item active"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Symptom Checker</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Cart</strong></div>
            <div class="user-profile">
                <div class="avatar-circle"><?= $initials ?></div>
                <div><strong><?= htmlspecialchars($user_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Cart & Checkout</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem;">Review your items and complete the order.</p>

            <div class="checkout-container">
                <div class="cart-items-list">
                    <?php if(empty($cart_items)): ?>
                        <div style="text-align: center; color: #999; padding: 2rem; background: white; border-radius: 12px; border: 1px solid var(--border-color);">
                            Your cart is empty. <br><br>
                            <a href="marketplace.php" style="color: var(--primary-green); text-decoration: none; font-weight: bold;">Browse Marketplace</a>
                        </div>
                    <?php else: ?>
                        <?php foreach($cart_items as $item): ?>
                            <?php $item_total = $item['price'] * $item['quantity']; $total_price += $item_total; ?>
                            <div class="cart-item">
                                <div class="item-img"><?= $item['icon'] ?></div>
                                <div class="item-details">
                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                    <div class="qty-control">Qty: <?= $item['quantity'] ?></div>
                                </div>
                                <div class="price-col">
                                    <span class="item-price">$<?= number_format($item_total, 2) ?></span>
                                    <form method="POST" action="../../controllers/CartController.php">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                        <button type="submit" class="remove-btn"><i class="fa-regular fa-trash-can"></i> Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="summary-card">
                    <h3>Order Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span>$<?= number_format($total_price, 2) ?></span></div>
                    <div class="summary-row"><span>Shipping</span><span style="color: var(--primary-green);">Free</span></div>
                    <div class="summary-row summary-total"><span>Total</span><span>$<?= number_format($total_price, 2) ?></span></div>
                    
                    <form method="POST" action="../../controllers/CartController.php">
                        <input type="hidden" name="action" value="place_order">
                        <button type="submit" class="place-order-btn" <?= empty($cart_items) ? 'disabled style="background:#ccc; cursor:not-allowed;"' : '' ?>>
                            Proceed to Checkout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>