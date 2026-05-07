<?php
session_start();
require_once '../models/Database.php';
require_once '../models/BookingModel.php';

$database = new Database();
$db = $database->getConnection();
$bookingModel = new BookingModel($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_booking'])) {
    $data = [
        'user_id' => $_SESSION['user_id'],
        'pet_id' => $_POST['pet_id'],
        'provider_id' => $_POST['provider_id'],
        'service' => $_POST['service_type'],
        'date' => $_POST['booking_date'],
        'time' => $_POST['booking_time'],
        'notes' => $_POST['notes']
    ];

    if ($bookingModel->createBooking($data)) {
        header("Location: ../views/petOwner/booking.php?success=1");
    } else {
        header("Location: ../views/petOwner/booking.php?error=1");
    }
    exit();
}