<?php
session_start();
require_once '../models/Database.php';

$database = new Database();
$db = $database->getConnection();

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (isset($user['status']) && $user['status'] == 'Deactivated') {
            echo "This account is deactivated. Please contact the admin.";
            exit();
        }
        
        if (password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['roleid'];
            $_SESSION['user_name'] = $user['name']; 

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
            echo "Incorrect password."; 
        }
    } else {
        echo "This account does not exist.";
    }
}

if (isset($_POST['register'])) {
    $name = $_POST['username']; 
    $email = $_POST['email'];
    $phone = $_POST['phone']; 
    $password = $_POST['password']; 
    $roleid = $_POST['roleid']; 

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (name, email, phone, password, roleid) VALUES (:name, :email, :phone, :password, :roleid)";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':roleid', $roleid);

    if ($stmt->execute()) {
        header("Location: ../views/Auth/login.php?success=registered");
        exit();
    } else {
        echo "An error occurred during registration.";
    }
}
?>