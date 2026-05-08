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
    $species = $_POST['species'] ?? '';
    $vaccine_name = $_POST['vaccine_name'] ?? '';
    $last_date = !empty($_POST['last_date']) ? $_POST['last_date'] : null;

    if (empty($pet_id) || empty($vaccine_name) || empty($species)) {
        header("Location: ../views/petOwner/vaccinationSchedule.php?error=missing_fields");
        exit();
    }

    $next_due_date = $vaccineModel->calculateNextDueDate($species, $vaccine_name, $last_date);

    if ($vaccineModel->saveSchedule($pet_id, $vaccine_name, $last_date, $next_due_date)) {
        header("Location: ../views/petOwner/vaccinationSchedule.php?success=scheduled&date=" . urlencode($next_due_date) . "&vac=" . urlencode($vaccine_name));
        exit();
    } else {
        header("Location: ../views/petOwner/vaccinationSchedule.php?error=db_error");
        exit();
    }
}
?>