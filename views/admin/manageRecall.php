<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pet-care-clinic/views/Auth/login.php');
    exit();
}

require_once __DIR__ . '/../../models/ProductModel.php';
$productModel = new ProductModel();
$products = $productModel->getAllProducts();
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
        .page-header h2 { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.95rem; }

        .table-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #fff; padding: 18px 15px; font-size: 0.85rem; font-weight: 500; color: var(--text-gray); border-bottom: 1px solid var(--border-color); }
        td { padding: 18px 15px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        
        .pill { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .s-recalled { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }
        .s-available { background: #ECFDF5; color: #059669; border: 1px solid #D1FAE5; }

        .btn-action { background: white; border: 1px solid var(--border-color); padding: 6px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: 0.2s; }
        .btn-action:hover { background: #f9f9f9; }
        .btn-danger { background: #DC2626; color: white; border: none; padding: 6px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; }
        .btn-danger:hover { background: #B91C1C; }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 500px;
            max-width: 90%;
            padding: 24px;
        }
        .modal-content h3 { margin-bottom: 20px; }
        .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDispute.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageHealthAlert.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Health Alerts</a>
            <a href="manageRecall.php" class="nav-item active"><i class="fa-solid fa-box"></i> Recalls</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Welcome back, Admin 👋</strong></div>
            <div class="user-profile">
                <i class="fa-regular fa-bell"></i>
                <div class="avatar">A</div>
                <div><strong>Admin</strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Product Recalls</h2>
                <p>Manage product recalls and notify affected customers.</p>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">No products found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>#<?= $product['id'] ?></td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= $product['price'] ?> EGP</td>
                                    <td>
                                        <span class="pill <?= (isset($product['is_recalled']) && $product['is_recalled'] == 1) ? 's-recalled' : 's-available' ?>">
                                            <?= (isset($product['is_recalled']) && $product['is_recalled'] == 1) ? 'Recalled' : 'Available' ?>
                                        </span>
                                    </td>
                                    <td align="right">
                                        <?php if (!isset($product['is_recalled']) || $product['is_recalled'] == 0): ?>
                                            <button class="btn-danger" onclick="openRecallModal(<?= $product['id'] ?>)">Recall</button>
                                        <?php else: ?>
                                            <span class="text-muted">Already recalled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="recallModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Recall Product</h3>
            <form id="recallForm">
                <input type="hidden" id="product_id">
                <textarea id="reason" rows="3" placeholder="Recall reason..." required></textarea>
                <div class="modal-buttons">
                    <button type="button" class="btn-action" onclick="closeRecallModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Confirm Recall</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRecallModal(productId) {
            document.getElementById('product_id').value = productId;
            document.getElementById('recallModal').style.display = 'flex';
        }
        function closeRecallModal() {
            document.getElementById('recallModal').style.display = 'none';
        }
        document.getElementById('recallForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let productId = document.getElementById('product_id').value;
            let reason = document.getElementById('reason').value;
            fetch('/pet-care-clinic/controllers/MarketplaceController.php?action=recall_product', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'product_id=' + productId + '&reason=' + encodeURIComponent(reason)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product recalled and ' + data.affected_customers + ' customers notified');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        });
        window.onclick = function(event) {
            if (event.target == document.getElementById('recallModal')) closeRecallModal();
        }
    </script>
</body>
</html>