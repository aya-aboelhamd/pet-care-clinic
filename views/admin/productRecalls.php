<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';

$database = new Database();
$db = $database->getConnection();

$admin_id = $_SESSION['user_id'];
$stmt_admin = $db->prepare("SELECT name FROM users WHERE id = ?");
$stmt_admin->execute([$admin_id]);
$admin_name = $stmt_admin->fetchColumn() ?: 'Admin';

$stmt = $db->prepare("SELECT * FROM product WHERE is_recalled = 0");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Product Recalls</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; --alert-red: #DC2626; }
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
        .content-padding { padding: 2rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 2rem; font-weight: 700; color: var(--alert-red); margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.95rem; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .product-item { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 10px; margin-bottom: 12px; transition: 0.2s; }
        .product-item:hover { border-color: var(--alert-red); background: #FFFBFB; }
        .product-info strong { display: block; font-size: 1rem; margin-bottom: 4px; }
        .product-info span { font-size: 0.8rem; color: var(--text-gray); }
        .recall-btn { background: var(--alert-red); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .recall-btn:hover { background: #b91c1c; transform: translateY(-1px); }
        .alert-success { background: #DCFCE7; color: #16A34A; padding: 1rem; border-radius: 10px; border: 1px solid #BBF7D0; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
            <a href="productRecalls.php" class="nav-item active"><i class="fa-solid fa-rotate-left"></i> Product Recalls</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Safety Management</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div><strong><?= htmlspecialchars($admin_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Product Recall Center</h2>
                <p>Urgent: Select products to recall and notify all customers immediately.</p>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    Product has been recalled and affected users have been notified.
                </div>
            <?php endif; ?>

            <div class="card">
                <?php if(empty($products)): ?>
                    <div style="text-align: center; color: #999; padding: 40px;">
                        <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No active products available for recall.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($products as $prod): ?>
                        <div class="product-item">
                            <div class="product-info">
                                <strong><?= htmlspecialchars($prod['name']) ?></strong>
                                <span><i class="fa-solid fa-tag"></i> ID: <?= $prod['id'] ?> | <i class="fa-solid fa-layer-group"></i> Category: <?= htmlspecialchars($prod['category']) ?></span>
                            </div>
                            <form method="POST" action="../../controllers/RecallController.php" onsubmit="return confirm('🚨 CRITICAL ACTION: Are you sure you want to recall this product? Every user who purchased this item will receive an emergency notification.');">
                                <input type="hidden" name="action" value="recall_product">
                                <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                <button type="submit" class="recall-btn">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Recall Product
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>