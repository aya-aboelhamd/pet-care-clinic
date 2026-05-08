<?php
// controllers/AuthController.php
session_start();
require_once '../models/Database.php';

$database = new Database();
$db = $database->getConnection();

// ==========================================
// 1. التعامل مع تسجيل الدخول (Login)
// ==========================================
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // البحث عن المستخدم باستخدام الإيميل
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // التحقق من الباسورد (بدون تشفير - مطابقة النص بالنص)
        if ($password === $user['password']) {
            
            // حفظ بيانات اليوزر في الجلسة (Session)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['roleid'];
            $_SESSION['user_name'] = $user['name']; 
            $_SESSION['profile_id']=$user['profile_id'];

            // التوجيه للداشبورد المناسبة بناءً على roleid
            switch ($user['roleid']) {
                case 1:
                    header("Location: ../views/admin/adminDashboard.php"); 
                    break;
                case 2:
                    header("Location: ../views/petOwner/petownerDashboard.php");
                    break;
                case 3:
                    header("Location: ../views/veterinarian/vetDashboard.php");
                    break;
                case 4:
                    header("Location: ../views/serviceProvider/providerDashboard.php");
                    break;
                default:
                    header("Location: ../views/Auth/login.php?error=invalid_role");
            }
            exit();
        } else {
            echo "كلمة المرور خاطئة"; 
        }
    } else {
        echo "هذا الحساب غير موجود";
    }
}

// ==========================================
// 2. التعامل مع إنشاء حساب جديد (Register)
// ==========================================
if (isset($_POST['register'])) {
    $name = $_POST['username']; 
    $email = $_POST['email'];
    $phone = $_POST['phone']; 
    $password = $_POST['password']; 
    $roleid = $_POST['roleid']; 

    $query = "INSERT INTO users (name, email, phone, password, roleid) VALUES (:name, :email, :phone, :password, :roleid)";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':roleid', $roleid);

    if ($stmt->execute()) {
        $new_user_id = $db->lastInsertId();

        if ($roleid == 4) {
            $stmtProfile = $db->prepare("INSERT INTO serviceproviderprofile (user_id) VALUES (:uid)");
            $stmtProfile->execute([':uid' => $new_user_id]);

            $new_profile_id = $db->lastInsertId();
            $updateUser = $db->prepare("UPDATE users SET profile_id = :pid WHERE id = :uid");
            $updateUser->execute([':pid' => $new_profile_id, ':uid' => $new_user_id]);
        }

        header("Location: ../views/Auth/login.php?success=registered");
        exit();
    } else {
        echo "حدث خطأ أثناء التسجيل";
    }
}
?>