<?php
session_start();
require_once '../models/Database.php';
require_once '../models/PrescriptionModel.php';

$database = new Database();
$db = $database->getConnection();
$prescModel = new PrescriptionModel($db);

// التأكد إن اليوزر طبيب بيطري (role_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. استقبال الـ API Check (الفحص الآلي قبل الحفظ)
    if (isset($_POST['action']) && $_POST['action'] == 'validate_drugs') {
        $pet_id = $_POST['pet_id'];
        $meds_array = $_POST['medications']; // Array of drug names
        
        $warnings = [];

        // فحص الحساسية لكل دواء
        foreach ($meds_array as $med) {
            if ($prescModel->checkAllergy($pet_id, $med)) {
                $warnings[] = "ALLERGY ALERT: Pet is allergic to $med!";
            }
        }

        // فحص التفاعلات الدوائية
        $interaction = $prescModel->checkInteraction($meds_array);
        if ($interaction) {
            $warnings[] = "DRUG INTERACTION: " . $interaction;
        }

        if (!empty($warnings)) {
            echo json_encode(['status' => 'warning', 'warnings' => $warnings]);
        } else {
            echo json_encode(['status' => 'safe']);
        }
        exit();
    }

    // 2. الحفظ الفعلي للروشتة
    if (isset($_POST['action']) && $_POST['action'] == 'save_prescription') {
        $vet_id = $_SESSION['user_id'];
        $pet_id = $_POST['pet_id'];
        $diagnosis = $_POST['diagnosis'];
        
        $medications = [
            'names' => $_POST['med_names'], // Array
            'dosages' => $_POST['med_dosages'], // Array
            'duration' => $_POST['duration'],
            'instructions' => $_POST['instructions']
        ];

        // في الواقع المفروض نفحص تاني هنا للتأكيد (Backend Validation)
        $code = $prescModel->savePrescription($vet_id, $pet_id, $diagnosis, $medications);

        if ($code) {
            header("Location: ../views/veterinarian/vetDashboard.php?success=presc_created&code=" . $code);
        } else {
            header("Location: ../views/veterinarian/createprescription.php?error=failed");
        }
        exit();
    }
}
?>