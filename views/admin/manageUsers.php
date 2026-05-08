<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/ManageUsersModel.php';

$database = new Database();
$db = $database->getConnection();
$userModel = new ManageUsersModel($db);

$admin_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status_id'])) {
    $target_id = $_POST['toggle_status_id'];
    $new_status = $_POST['new_status'];
    
    if ($target_id == $admin_id) {
        $error_msg = "You cannot deactivate your own admin account!";
    } else {
        if ($userModel->toggleUserStatus($target_id, $new_status)) {
            $success_msg = "User status has been updated to $new_status.";
        } else {
            $error_msg = "An error occurred while updating the status.";
        }
    }
}

$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin_data['name'] ?? 'Admin';
$admin_email = $admin_data['email'] ?? 'admin@petlor.com';

$users = $userModel->getAllUsers();

function getRoleDetails($roleid) {
    switch ($roleid) {
        case 1: return ['name' => 'admin', 'class' => 'role-admin'];
        case 2: return ['name' => 'owner', 'class' => 'role-owner'];
        case 3: return ['name' => 'vet', 'class' => 'role-vet'];
        case 4: return ['name' => 'provider', 'class' => 'role-provider'];
        default: return ['name' => 'user', 'class' => 'role-owner'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor Admin - User Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-green: #589A64; --bg-light: #F8FAF8; --sidebar-width: 240px; --text-dark: #1A1A1A; --text-gray: #666; --border-color: #E0E0E0; --status-active: #E8F5E9; --status-active-text: #4CAF50; --danger-red: #E53935; }
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.8rem; margin-bottom: 5px; }
        .page-header p { color: var(--text-gray); font-size: 0.9rem; }
        .search-container { position: relative; margin-bottom: 2rem; }
        .search-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        .search-bar { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid var(--border-color); border-radius: 10px; outline: none; font-size: 0.9rem; }
        .table-card { background: white; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #fff; padding: 15px; font-size: 0.85rem; font-weight: 600; color: var(--text-gray); border-bottom: 1px solid var(--border-color); }
        td { padding: 15px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); vertical-align: middle;}
        .badge-role { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; text-transform: lowercase; }
        .role-owner { background: #E3F2FD; color: #1976D2; }
        .role-vet { background: #F3E5F5; color: #7B1FA2; }
        .role-provider { background: #E0F2F1; color: #00796B; }
        .role-admin { background: #ECEFF1; color: #455A64; }
        .status-pill { background: var(--status-active); color: var(--status-active-text); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-deactivated { background: #FFEBEE; color: #C62828; }
        .action-cell { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-edit { background: none; border: 1px solid var(--border-color); padding: 5px 15px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 500; color: var(--text-dark); text-decoration: none;}
        .btn-delete { background: none; border: 1px solid #FFEBEE; color: var(--danger-red); padding: 5px 15px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 500; }
        .btn-activate { background: none; border: 1px solid #E8F5E9; color: #2E7D32; padding: 5px 15px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 500; }
        .btn-activate:hover { background: #E8F5E9; }
        .btn-delete:hover { background: #FFEBEE; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; font-weight: 500; }
        .alert-success { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-logo"><i class="fa-solid fa-paw"></i> Petlor</div>
            <a href="adminDashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            <a href="manageUsers.php" class="nav-item active"><i class="fa-solid fa-users"></i> Users</a>
            <a href="manageDisputes.php" class="nav-item"><i class="fa-solid fa-shield-halved"></i> Disputes</a>
            <a href="manageNotifications.php" class="nav-item"><i class="fa-solid fa-bullhorn"></i> Notifications</a>
            <a href="auditLogs.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Audit Logs</a>
            <a href="productRecalls.php" class="nav-item"><i class="fa-solid fa-file-contract"></i> Product Recalls</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div style="color: #666; font-size: 0.8rem;">Admin Portal / <strong>User Management</strong></div>
            <div class="user-profile">
                <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div><strong><?= htmlspecialchars($admin_name) ?></strong><br><span style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($admin_email) ?></span></div>
                <a href="../Auth/logout.php" style="color: #ccc; margin-left: 10px; text-decoration: none;"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="content-padding">
            <div class="page-header">
                <div>
                    <h2>User Management</h2>
                    <p>View, edit or deactivate platform users.</p>
                </div>
            </div>

            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $success_msg ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $error_msg ?></div>
            <?php endif; ?>

            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" class="search-bar" placeholder="Search by name or email..." onkeyup="filterUsers()">
            </div>

            <div class="table-card">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="text-align: right; padding-right: 40px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): 
                            $roleInfo = getRoleDetails($u['roleid']);
                        ?>
                            <tr>
                                <td>#<?= $u['id'] ?></td>
                                <td class="user-name"><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                                <td class="user-email"><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="badge-role <?= $roleInfo['class'] ?>"><?= $roleInfo['name'] ?></span></td>
                                <td>
                                    <?php if($u['status'] == 'Deactivated'): ?>
                                        <span class="status-pill status-deactivated">Deactivated</span>
                                    <?php else: ?>
                                        <span class="status-pill">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td align="right">
                                    <div class="action-cell">
                                        
                                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to change this user\'s status?');">
                                            <input type="hidden" name="toggle_status_id" value="<?= $u['id'] ?>">
                                            <?php if($u['status'] == 'Deactivated'): ?>
                                                <input type="hidden" name="new_status" value="Active">
                                                <button type="submit" class="btn-activate"><i class="fa-solid fa-check"></i> Activate</button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_status" value="Deactivated">
                                                <button type="submit" class="btn-delete"><i class="fa-solid fa-ban"></i> Deactivate</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($users)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px; color: #999;">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterUsers() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let table = document.getElementById("usersTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let nameTd = tr[i].getElementsByClassName("user-name")[0];
                let emailTd = tr[i].getElementsByClassName("user-email")[0];
                
                if (nameTd || emailTd) {
                    let nameTxt = nameTd.textContent || nameTd.innerText;
                    let emailTxt = emailTd.textContent || emailTd.innerText;
                    
                    if (nameTxt.toLowerCase().indexOf(input) > -1 || emailTxt.toLowerCase().indexOf(input) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>