<?php
// views/admin/viewBatches.php
require_once '../../models/Database.php';

$db = new Database();
$conn = $db->getConnection();
$sql = "SELECT * FROM deliverybatch ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Petlor - View Batches</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F8FAF8; padding: 40px; }
        .container { background: white; padding: 30px; border-radius: 12px; border: 1px solid #E0E0E0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #589A64; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; background: #DFF0E2; color: #589A64; }
        .back-btn { text-decoration: none; color: #589A64; font-weight: bold; margin-bottom: 20px; display: inline-block; }
    </style>
</head>
<body>
    <a href="adminDashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>

    <div class="container">
        <h2>📦 Delivery Batches Records</h2>
        <?php if (isset($_GET['success']) && $_GET['success'] == "1"): ?>

    <div class="alert">
        ✅ New batches generated successfully!
    </div>

<?php elseif (isset($_GET['success']) && $_GET['success'] == "0"): ?>

    <div class="alert" style="background:#FFE9E9;color:#D63031;">
        ⚠ No eligible confirmed orders found.
    </div>

<?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>Status</th>
                    <th>Total Items</th>
                    <th>Delivery Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $batch): ?>
                <tr>
                    <td>#<?= $batch['id'] ?></td>
                    <td><?= strtoupper($batch['status']) ?></td>
                    <td><?= $batch['total_orders'] ?> Orders</td>
                    <td><?= $batch['scheduled_time'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>