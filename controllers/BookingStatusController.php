<?php
session_start();
require_once '../models/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../views/Auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id']) && isset($_POST['action'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $booking_id = $_POST['booking_id'];
    $provider_id = $_SESSION['user_id'];
    $action = $_POST['action'];
    
    $new_status = ($action == 'accept') ? 'Confirmed' : 'Cancelled';

    $stmt = $db->prepare("UPDATE booking SET status = ? WHERE id = ? AND provider_id = ?");
    $stmt->execute([$new_status, $booking_id, $provider_id]);

    header("Location: ../views/serviceProvider/bookingRequests.php?success=1");
    exit();
}
?>