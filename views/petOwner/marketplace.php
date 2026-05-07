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
$first_name = explode(' ', trim($user_name))[0];
$initials = strtoupper(substr($first_name, 0, 1));

$stmt = $db->prepare("SELECT id, name, medical_notes, allergies FROM pet WHERE user_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_pet_id = $_GET['pet_id'] ?? 'all';
$current_pet = null;
if ($current_pet_id != 'all') {
    foreach ($pets as $p) {
        if ($p['id'] == $current_pet_id) {
            $current_pet = $p;
            break;
        }
    }
}

$stmt = $db->prepare("SELECT * FROM product");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Petlor - Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; --safe-green: #DCFCE7; --safe-text: #16A34A; --danger-red: #FEE2E2; --danger-text: #DC2626; }
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
        .market-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .view-cart-btn { background: white; border: 1px solid var(--border-color); padding: 0.6rem 1rem; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .pet-selector { background: white; padding: 15px 20px; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 2rem; display: flex; align-items: center; gap: 15px; }
        .pet-selector select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 0.9rem; outline: none; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.5rem; }
        .product-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; position: relative;}
        .product-img-box { height: 160px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f0f4f0; font-size: 4rem; }
        .product-info { padding: 1.2rem; display: flex; flex-direction: column; flex-grow: 1; }
        .product-name { font-weight: 700; font-size: 1rem; margin-bottom: 8px; color: var(--text-dark); }
        .safety-badge { padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 6px; }
        .safe { background: var(--safe-green); color: var(--safe-text); }
        .unsafe { background: var(--danger-red); color: var(--danger-text); }
        .neutral { background: #f0f0f0; color: #666; }
        .price-row { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
        .price { font-weight: 700; font-size: 1.1rem; }
        .add-btn { background: var(--primary-green); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .add-btn:disabled { background: #ccc; cursor: not-allowed; }
        .alert-success { background: #F0FDF4; color: #16A34A; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEF2F2; color: #DC2626; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #FCA5A5; display: flex; flex-direction: column; gap: 10px;}
        .prescription-badge { position: absolute; top: 10px; right: 10px; background: #3B82F6; color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: bold; display: flex; align-items: center; gap: 4px;}
        .upload-btn { background: #DC2626; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.8rem; width: fit-content;}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="petownerDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
        <a href="mypets.php" class="nav-item"><i class="fa-solid fa-paw"></i> My Pets</a>
        <a href="healthLogs.php" class="nav-item"><i class="fa-solid fa-notes-medical"></i> Health Logs</a>
        <a href="vaccination.php" class="nav-item"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="marketplace.php" class="nav-item active"><i class="fa-solid fa-store"></i> Marketplace</a>
        <a href="checkout.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</a>
        <a href="booking.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> Book a Service</a>
        <a href="symptomChecker.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Symptom Checker</a>
        <a href="reportLostPet.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Report Lost Pet</a>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Owner Portal / <strong>Welcome back, <?= htmlspecialchars($first_name) ?> 👋</strong></div>
            <div class="user-profile">
                <div class="avatar-circle"><?= $initials ?></div>
                <div><strong><?= htmlspecialchars($user_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="market-top">
                <div>
                    <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Marketplace</h2>
                    <p style="color: var(--text-gray);">Allergy-aware shopping tailored to your pets.</p>
                </div>
                <button class="view-cart-btn" onclick="window.location.href='checkout.php'">
                    <i class="fa-solid fa-cart-shopping"></i> View cart
                </button>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert-success"><i class="fa-solid fa-check"></i> Item added to your cart successfully!</div>
            <?php endif; ?>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'no_prescription'): ?>
                <div class="alert-error">
                    <div><strong><i class="fa-solid fa-ban"></i> Action Blocked: Prescription Required</strong></div>
                    <div style="font-size: 0.85rem;">This therapeutic product requires a valid digital prescription. No active prescription was found in your pet's medical record.</div>
                    <button class="upload-btn" onclick="alert('Redirecting to document verification system...')"><i class="fa-solid fa-file-arrow-up"></i> Upload Prescription Document</button>
                </div>
            <?php elseif(isset($_GET['error']) && $_GET['error'] == 'select_pet_first'): ?>
                <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> Please select a specific pet from the dropdown before adding prescription items.</div>
            <?php endif; ?>

            <div class="pet-selector">
                <strong><i class="fa-solid fa-dog"></i> Who are you shopping for today?</strong>
                <select id="shopping_for_pet" onchange="window.location.href='?pet_id='+this.value">
                    <option value="all" <?= $current_pet_id == 'all' ? 'selected' : '' ?>>Browsing for all (No safety checks)</option>
                    <?php foreach($pets as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $current_pet_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="products-grid">
                <?php foreach($products as $prod): ?>
                    <?php
                        $is_safe = true;
                        $warning_msg = "";
                        
                        if ($current_pet && !empty($prod['contraindicated_condition'])) {
                            $condition = strtolower($prod['contraindicated_condition']);
                            $pet_health = strtolower($current_pet['allergies'] . ' ' . $current_pet['medical_notes']);
                            
                            if (strpos($pet_health, $condition) !== false) {
                                $is_safe = false;
                                $warning_msg = "Contains " . ucfirst($condition) . " — Not safe for " . htmlspecialchars($current_pet['name']);
                            }
                        }
                    ?>
                    <div class="product-card">
                        <?php if($prod['requires_prescription'] == 1): ?>
                            <div class="prescription-badge"><i class="fa-solid fa-file-prescription"></i> Rx Only</div>
                        <?php endif; ?>
                        
                        <div class="product-img-box">
                            <?= $prod['icon'] ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($prod['name']) ?></h3>
                            <p style="font-size: 0.8rem; color: #666; margin-bottom: 10px;"><?= htmlspecialchars($prod['description']) ?></p>
                            
                            <?php if ($current_pet_id == 'all'): ?>
                                <div class="safety-badge neutral"><i class="fa-solid fa-circle-info"></i> Select a pet to check safety</div>
                            <?php elseif ($is_safe): ?>
                                <div class="safety-badge safe"><i class="fa-solid fa-check-circle"></i> Safe for <?= htmlspecialchars($current_pet['name']) ?></div>
                            <?php else: ?>
                                <div class="safety-badge unsafe"><i class="fa-solid fa-triangle-exclamation"></i> <?= $warning_msg ?></div>
                            <?php endif; ?>

                            <div class="price-row">
                                <span class="price">$<?= number_format($prod['price'], 2) ?></span>
                                <form method="POST" action="../../controllers/CartController.php" onsubmit="return checkSafety(<?= $is_safe ? 'true' : 'false' ?>)">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                    <input type="hidden" name="pet_id" value="<?= htmlspecialchars($current_pet_id) ?>">
                                    <button type="submit" class="add-btn" <?= !$is_safe ? 'disabled title="Blocked due to medical risk"' : '' ?>>Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        function checkSafety(isSafe) {
            if (!isSafe) {
                alert("WARNING: This item is medically contraindicated for your selected pet. Action blocked.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>