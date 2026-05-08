<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    header("Location: manageDisputes.php");
    exit();
}

$dispute_id = $_GET['id'];
$admin_id = $_SESSION['user_id'];

// جلب تفاصيل النزاع
$stmt = $db->prepare("
    SELECT d.*, 
           u_owner.name as owner_name, 
           u_provider.name as provider_name,
           b.service_type, b.total_price, b.start_time
    FROM disputes d
    JOIN users u_owner ON d.owner_id = u_owner.id
    JOIN users u_provider ON d.provider_id = u_provider.id
    JOIN booking b ON d.booking_id = b.id
    WHERE d.id = ?
");
$stmt->execute([$dispute_id]);
$dispute = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dispute) {
    die("Dispute not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - View Dispute</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --border-color: #E0E0E0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; color: var(--text-dark); }
        .sidebar { width: var(--sidebar-width); background: white; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 1.5rem 2rem; font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .nav-item { padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); font-size: 0.9rem; font-weight: 500; }
        .main-content { flex-grow: 1; padding: 2rem; overflow-y: auto; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem; max-width: 800px; }
        .info-group { margin-bottom: 15px; }
        .info-group label { font-size: 0.8rem; color: #666; font-weight: 600; display: block; margin-bottom: 5px; }
        .info-group p { font-size: 0.95rem; background: #f9f9f9; padding: 10px; border-radius: 8px; border: 1px solid #eee; }
        .action-form { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 1.5rem; border-radius: 12px; margin-top: 2rem; }
        select { padding: 10px; border-radius: 8px; border: 1px solid #ccc; width: 100%; margin-bottom: 15px; font-size: 0.95rem; outline: none; }
        .btn-update { background: var(--primary-green); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #666; text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { color: var(--primary-green); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
        <a href="manageDisputes.php" class="nav-item"><i class="fa-solid fa-arrow-left"></i> Back to Disputes</a>
    </div>

    <div class="main-content">
        <a href="manageDisputes.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
        <h2 style="margin-bottom: 20px;">Review Dispute #<?= $dispute['id'] ?></h2>

        <div class="card">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="info-group">
                    <label>Pet Owner (Complainant)</label>
                    <p><i class="fa-regular fa-user"></i> <?= htmlspecialchars($dispute['owner_name']) ?></p>
                </div>
                <div class="info-group">
                    <label>Service Provider</label>
                    <p><i class="fa-solid fa-user-doctor"></i> <?= htmlspecialchars($dispute['provider_name']) ?></p>
                </div>
                <div class="info-group">
                    <label>Service Detail</label>
                    <p><?= htmlspecialchars($dispute['service_type']) ?> on <?= date('M d, Y', strtotime($dispute['start_time'])) ?></p>
                </div>
                <div class="info-group">
                    <label>Disputed Amount</label>
                    <p style="color: #DC2626; font-weight: bold;">$<?= number_format($dispute['total_price'], 2) ?></p>
                </div>
            </div>

            <div class="info-group" style="margin-top: 20px;">
                <label>Owner's Reason for Dispute</label>
                <p style="min-height: 80px;"><?= nl2br(htmlspecialchars($dispute['reason'])) ?></p>
            </div>

            <!-- فورم قرار الأدمن (بيوجه للكنترولر الموحد) -->
            <div class="action-form">
                <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #166534;">Admin Action</h3>
                <form action="../../controllers/DisputeController.php" method="POST">
                    <input type="hidden" name="dispute_id" value="<?= $dispute['id'] ?>">
                    <input type="hidden" name="owner_id" value="<?= $dispute['owner_id'] ?>">
                    <input type="hidden" name="provider_id" value="<?= $dispute['provider_id'] ?>">
                    <input type="hidden" name="booking_id" value="<?= $dispute['booking_id'] ?>">
                    
                    <label style="font-size: 0.85rem; font-weight: bold; margin-bottom: 8px; display: block;">Change Status:</label>
                    <select name="new_status">
                        <option value="Open" <?= $dispute['status'] == 'Open' ? 'selected' : '' ?>>Open (Pending Review)</option>
                        <option value="Investigating" <?= $dispute['status'] == 'Investigating' ? 'selected' : '' ?>>Investigating (Contacting Parties)</option>
                        <option value="Resolved" <?= $dispute['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved (Release Funds)</option>
                    </select>
                    
                    <button type="submit" name="update_dispute" class="btn-update">Update Status & Resolve</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>