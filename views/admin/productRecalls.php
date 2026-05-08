<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// جلب بيانات الأدمن للهيدر
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin_data['name'] ?? 'Admin';
$admin_email = $admin_data['email'] ?? 'admin@petlor.com';

// جلب المنتجات الفعالة
$stmt = $db->prepare("SELECT * FROM product WHERE is_recalled = 0 OR is_recalled IS NULL ORDER BY id DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Product Recalls & Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; --alert-red: #E53935; --edit-blue: #3B82F6;}
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; color: var(--text-dark); }
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; justify-content: space-between; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .nav-item.active { background-color: var(--primary-green); color: white; margin: 0 10px; border-radius: 8px; }
        .main-content { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
        header { background: white; padding: 0.8rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .user-profile { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
        .avatar { width: 35px; height: 35px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .content-padding { padding: 2rem; max-width: 1200px; margin: 0 auto; width: 100%;}
        
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;}
        .alert-success { background: #DFF0E2; color: var(--primary-green); border: 1px solid #BBF7D0;}
        .alert-danger { background: #FFEBEE; color: var(--alert-red); border: 1px solid #FCA5A5;}

        .grid-layout { display: grid; grid-template-columns: 1fr 2.5fr; gap: 2rem; align-items: start; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .card-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px;}
        
        /* Form Styling */
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--text-gray); }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; font-size: 0.9rem; transition: 0.2s;}
        input:focus, select:focus, textarea:focus { border-color: var(--primary-green); }
        .btn-submit { background: var(--primary-green); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.2s;}
        .btn-submit:hover { opacity: 0.9; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 0.85rem; color: var(--text-gray); border-bottom: 2px solid #f0f0f0; }
        td { padding: 12px; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; vertical-align: middle; }
        
        /* Action Buttons */
        .actions { display: flex; gap: 8px; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-edit { background: #EFF6FF; color: var(--edit-blue); border: 1px solid #BFDBFE; }
        .btn-edit:hover { background: #DBEAFE; }
        .btn-recall { background: #FEF2F2; color: var(--alert-red); border: 1px solid #FECACA; }
        .btn-recall:hover { background: #FEE2E2; }
        
        .badge { background: #f0f0f0; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
            <a href="productRecalls.php" class="nav-item active"><i class="fa-solid fa-rotate-left"></i> Product Recalls</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Welcome back 👋</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div><strong><?= htmlspecialchars($admin_name) ?></strong><br><span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($admin_email) ?></span></div>
                <a href="../Auth/logout.php" style="color: #ccc; margin-left: 10px; text-decoration: none;"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Marketplace & Recalls</h2>
                <p>Manage products and issue urgent health recalls.</p>
            </div>

            <?php if(isset($_GET['success']) && $_GET['success'] == 'added'): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Product added successfully to the marketplace!</div>
            <?php elseif(isset($_GET['success']) && $_GET['success'] == 'updated'): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Product price updated successfully!</div>
            <?php elseif(isset($_GET['success']) && $_GET['success'] == 'recalled'): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Product recalled successfully! <?= htmlspecialchars($_GET['count']) ?> previous buyers have been notified.</div>
            <?php elseif(isset($_GET['error']) && $_GET['error'] == 'invalid_price'): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> Invalid price entered. Update failed.</div>
            <?php endif; ?>

            <div class="grid-layout">
                <div class="card">
                    <div class="card-title">Add New Product</div>
                    <form method="POST" action="../../controllers/AdminProductController.php">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" placeholder="e.g. Royal Canin Adult" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" required>
                                <option value="" disabled selected>Select Category</option>
                                <option value="Food & Nutrition">Food & Nutrition</option>
                                <option value="Medicine & Pharmacy">Medicine & Pharmacy</option>
                                <option value="Supplements">Supplements</option>
                                <option value="Toys & Accessories">Toys & Accessories</option>
                                <option value="Grooming">Grooming</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Price ($)</label>
                            <input type="number" step="0.01" name="price" placeholder="0.00" required>
                        </div>

                        <div class="form-group">
                            <label>Medical Contraindication (If any)</label>
                            <select name="contraindicated_condition">
                                <option value="">None (Safe for all)</option>
                                <option value="Chicken">Contains Chicken (Allergy Alert)</option>
                                <option value="Beef">Contains Beef (Allergy Alert)</option>
                                <option value="Grain">Contains Grain/Wheat</option>
                                <option value="Dairy">Contains Dairy</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-dark);">
                                <input type="checkbox" name="requires_prescription" style="width: auto;"> Requires Vet Prescription?
                            </label>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" placeholder="Short product description..." rows="3" required></textarea>
                        </div>

                        <button type="submit" class="btn-submit">Add to Marketplace</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-title">Active Marketplace Products</div>
                    <?php if(empty($products)): ?>
                        <div style="text-align: center; color: #999; padding: 2rem 0;">No active products in the marketplace.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td><span class="badge"><?= htmlspecialchars($p['category']) ?></span></td>
                                    <td><strong>$<?= number_format($p['price'], 2) ?></strong></td>
                                    <td>
                                        <?php if($p['requires_prescription']): ?>
                                            <span style="color: #3B82F6; font-size: 0.75rem; font-weight: bold;"><i class="fa-solid fa-file-prescription"></i> Rx Only</span>
                                        <?php else: ?>
                                            <span style="color: var(--primary-green); font-size: 0.75rem; font-weight: bold;">OTC</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn-action btn-edit" onclick="editPrice(<?= $p['id'] ?>, <?= $p['price'] ?>)">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </button>
                                            
                                            <form id="editForm_<?= $p['id'] ?>" method="POST" action="../../controllers/AdminProductController.php" style="display:none;">
                                                <input type="hidden" name="action" value="edit_price">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="new_price" id="newPrice_<?= $p['id'] ?>">
                                            </form>

                                            <form method="POST" action="../../controllers/AdminProductController.php" onsubmit="return confirm('URGENT: Are you sure you want to recall this product? ALL past buyers will receive an emergency notification.');">
                                                <input type="hidden" name="action" value="recall_product">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn-action btn-recall">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> Recall
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editPrice(productId, currentPrice) {
            let newPrice = prompt("Enter new price for this product ($):", currentPrice);
            
            if (newPrice !== null && newPrice.trim() !== "") {
                if(!isNaN(newPrice) && newPrice > 0) {
                    document.getElementById('newPrice_' + productId).value = newPrice;
                    document.getElementById('editForm_' + productId).submit();
                } else {
                    alert("Please enter a valid positive number for the price.");
                }
            }
        }
    </script>
</body>
</html>