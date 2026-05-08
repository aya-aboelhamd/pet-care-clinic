<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../views/Auth/login.php");
    exit();
}

require_once '../models/Database.php';
require_once '../models/LabResultModel.php';

$database = new Database();
$db = $database->getConnection();
$labModel = new LabResultModel($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_result'])) {
    
    $vet_id = $_SESSION['user_id'];
    $pet_id = $_POST['pet_id'];
    $test_name = trim($_POST['test_name']);
    $technical_data = trim($_POST['technical_data']);
    $simplified_insight = trim($_POST['simplified_insight']);
    $vet_notes = trim($_POST['vet_notes']);
    $is_critical = isset($_POST['is_critical']) ? 1 : 0;

    if (isset($_FILES['lab_file']) && $_FILES['lab_file']['error'] == 0) {
        $target_dir = "../uploads/lab_results/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = basename($_FILES["lab_file"]["name"]);
        // تنظيف اسم الملف عشان ميحصلش مشاكل
        $safe_filename = preg_replace("/[^a-zA-Z0-9.]/", "_", time() . "_" . $file_name);
        $target_file = $target_dir . $safe_filename;
        
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = array("pdf", "jpg", "jpeg", "png");

        if (!in_array($file_type, $allowed_types)) {
            header("Location: ../views/veterinarian/labresults.php?error=invalid_format");
            exit();
        }

        if (move_uploaded_file($_FILES["lab_file"]["tmp_name"], $target_file)) {
            $db_file_path = "uploads/lab_results/" . $safe_filename;
            
            if ($labModel->saveResult($pet_id, $vet_id, $test_name, $db_file_path, $technical_data, $simplified_insight, $vet_notes, $is_critical)) {
                header("Location: ../views/veterinarian/labresults.php?success=uploaded");
                exit();
            } else {
                header("Location: ../views/veterinarian/labresults.php?error=db_error");
                exit();
            }
        } else {
            header("Location: ../views/veterinarian/labresults.php?error=upload_failed");
            exit();
        }
    } else {
        header("Location: ../views/veterinarian/labresults.php?error=no_file");
        exit();
    }
}
?>