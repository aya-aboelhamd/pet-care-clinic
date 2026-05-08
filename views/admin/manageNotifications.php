<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/ManageNotificationsModel.php';

$database = new Database();
$db = $database->getConnection();
$notifModel = new ManageNotificationsModel($db);

$admin_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_broadcast'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    
    if (!empty($title) && !empty($message)) {
        if ($notifModel->broadcastToAll($title, $message)) {
            $success_msg = "Broadcast sent successfully to all users.";
        } else {
            $error_msg = "Failed to send broadcast.";
        }
    }
}

$stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_name = $stmt->fetchColumn() ?: 'Admin';

$recent_broadcasts = $notifModel->getRecentBroadcasts();

function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - Notifications</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; --alert-red: #DC2626; --alert-blue: #0284C7; --alert-orange: #D97706; }
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
        .notif-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem; align-items: start; }
        .card { background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .card h4 { margin-bottom: 1.5rem; font-size: 1rem; font-weight: 600; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; }
        .form-group input, .form-group textarea { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; font-size: 0.9rem; outline: none; transition: border 0.2s; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--primary-green); }
        .form-group textarea { height: 120px; resize: none; }
        .btn-send { width: 100%; background: var(--primary-green); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 1rem; transition: 0.2s; }
        .btn-send:hover { background: #4a8254; }
        .notif-item { border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; margin-bottom: 12px; display: flex; align-items: flex-start; gap: 15px; position: relative; }
        .notif-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; background: #f5f5f5;}
        .icon-red { color: var(--alert-red); background: #FEF2F2;}
        .icon-green { color: var(--primary-green); background: #F0FDF4;}
        .icon-blue { color: var(--alert-blue); background: #F0F9FF;}
        .notif-content { flex-grow: 1; }
        .notif-content strong { display: block; font-size: 0.95rem; margin-bottom: 2px; }
        .notif-content p { font-size: 0.85rem; color: var(--text-gray); }
        .notif-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
        .time-stamp { font-size: 0.75rem; color: #999; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 500; }
        .alert-success { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item active"><i class="fa-solid fa-bullhorn"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
            <a href="productRecalls.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Product Recalls</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>Welcome back 👋</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div><strong><?= htmlspecialchars($admin_name) ?></strong></div>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <h2>Notifications</h2>
                <p>Broadcast announcements and review recent system messages.</p>
            </div>

            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $success_msg ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $error_msg ?></div>
            <?php endif; ?>

            <div class="notif-grid">
                <div class="card">
                    <h4>Broadcast new</h4>
                    <form method="POST">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" placeholder="Scheduled maintenance" required>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" placeholder="Petlor will be undergoing maintenance..." required></textarea>
                        </div>
                        <button type="submit" name="send_broadcast" class="btn-send"><i class="fa-solid fa-paper-plane"></i> Send</button>
                    </form>
                </div>

                <div class="card">
                    <h4>Recent broadcasts</h4>
                    
                    <?php if(empty($recent_broadcasts)): ?>
                        <div style="text-align: center; color: #999; padding: 20px;">No recent broadcasts.</div>
                    <?php else: ?>
                        <?php foreach($recent_broadcasts as $b): 
                            preg_match('/\[(.*?)\] (.*)/s', $b['message'], $matches);
                            $b_title = $matches[1] ?? 'System Broadcast';
                            $b_text = $matches[2] ?? $b['message'];
                        ?>
                            <div class="notif-item">
                                <div class="notif-icon icon-blue"><i class="fa-solid fa-bullhorn"></i></div>
                                <div class="notif-content">
                                    <strong><?= htmlspecialchars($b_title) ?></strong>
                                    <p><?= nl2br(htmlspecialchars($b_text)) ?></p>
                                </div>
                                <div class="notif-meta">
                                    <span class="time-stamp"><?= time_elapsed_string($b['created_at']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>