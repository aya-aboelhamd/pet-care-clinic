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
    $password = $_POST['password']; // حفظ الباسورد زي ما هو (بدون تشفير)
    $roleid = $_POST['roleid']; 

    $query = "INSERT INTO users (name, email, phone, password, roleid) VALUES (:name, :email, :phone, :password, :roleid)";
    $stmt = $db->prepare($query);
    
    // ربط المتغيرات بالـ Query
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':roleid', $roleid);

    // تنفيذ الإدخال
    if ($stmt->execute()) {
        header("Location: ../views/Auth/login.php?success=registered");
        exit();
    } else {
        echo "حدث خطأ أثناء التسجيل";
    }
}
?>