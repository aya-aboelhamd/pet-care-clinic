<?php
session_start();
require_once '../../models/Database.php';
require_once '../../models/BookingModel.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../Auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$bookingModel = new BookingModel($db);

$provider_id = $_SESSION['user_id'];
$appointments = $bookingModel->getProviderBookings($provider_id);