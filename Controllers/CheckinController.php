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

    if ($action == 'checkin') {
        $stmt = $db->prepare("UPDATE booking SET status = 'In Progress', checkin_time = CURRENT_TIMESTAMP WHERE id = ? AND provider_id = ?");
        $stmt->execute([$booking_id, $provider_id]);
    } elseif ($action == 'checkout') {
        $stmt = $db->prepare("UPDATE booking SET status = 'Completed', checkout_time = CURRENT_TIMESTAMP WHERE id = ? AND provider_id = ?");
        $stmt->execute([$booking_id, $provider_id]);

        $stmt = $db->prepare("SELECT user_id, service_type FROM booking WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            $message = "Your " . $booking['service_type'] . " session has been completed. You can now review it or raise a dispute if needed.";
            $notif = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Service Completed', ?, 0)");
            $notif->execute([$booking['user_id'], $message]);
        }
    }

    header("Location: ../views/serviceProvider/qrCheckin.php?success=1");
    exit();
}
?>