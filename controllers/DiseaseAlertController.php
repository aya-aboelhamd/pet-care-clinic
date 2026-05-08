<?php
session_start();
require_once '../models/Database.php';
require_once '../models/DiseaseAlertModel.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../views/Auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $alertModel = new DiseaseAlertModel($db);

    $vet_id = $_SESSION['user_id'];
    $region = $_POST['region'];
    $disease = $_POST['disease'];
    $severity = $_POST['severity'];
    $message = $_POST['message'];

    if ($alertModel->broadcastAlert($vet_id, $region, $disease, $severity, $message)) {
        header("Location: ../views/veterinarian/diseasealert.php?success=1");
    } else {
        header("Location: ../views/veterinarian/diseasealert.php?error=1");
    }
    exit();
}
?>