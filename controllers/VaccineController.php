<?php
// controllers/VaccineController.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/Auth/login.php");
    exit();
}

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/VaccineModel.php';

$database = new Database();
$db = $database->getConnection();
$vaccineModel = new VaccineModel($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule_vaccine'])) {
    
    $pet_id = $_POST['pet_id'] ?? '';
    $species = $_POST['species'] ?? ''; // هنبعتها هيدن من الفورم
    $vaccine_name = $_POST['vaccine_name'] ?? '';
    $last_date = !empty($_POST['last_date']) ? $_POST['last_date'] : null;

    // Alternate Course 1 & 2: User enters incomplete/invalid data -> Validation Error
    if (empty($pet_id) || empty($vaccine_name) || empty($species)) {
        header("Location: ../views/petOwner/vaccinationSchedule.php?error=missing_fields");
        exit();
    }

    // حساب الميعاد الجاي من الموديل
    $next_due_date = $vaccineModel->calculateNextDueDate($species, $vaccine_name, $last_date);

    // حفظ الجدول في الداتا بيز
    if ($vaccineModel->saveSchedule($pet_id, $vaccine_name, $last_date, $next_due_date)) {
        // Post-condition: Schedule is generated and saved
        header("Location: ../views/petOwner/vaccinationSchedule.php?success=scheduled&date=" . urlencode($next_due_date) . "&vac=" . urlencode($vaccine_name));
        exit();
    } else {
        header("Location: ../views/petOwner/vaccinationSchedule.php?error=db_error");
        exit();
    }
}
?>