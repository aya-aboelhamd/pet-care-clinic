<?php
session_start();
require_once '../models/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/Auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_booking'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $booking_id = $_POST['booking_id'];
    $provider_id = $_POST['provider_id'];
    $amount = $_POST['amount'];
    $user_name = $_SESSION['user_name'] ?? 'The Owner';

    try {
        $db->beginTransaction();

        // 1. تحديث حالة الحجز لـ مدفوع
        $stmt = $db->prepare("UPDATE booking SET status = 'Paid' WHERE id = ?");
        $stmt->execute([$booking_id]);

        // 2. إشعار لمقدم الخدمة
        $msg = "Payment Received: $user_name has paid $" . number_format($amount, 2) . " for booking #" . $booking_id . ".";
        $notif = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Payment Successful', ?, 0)");
        $notif->execute([$provider_id, $msg]);

        $db->commit();
        header("Location: ../views/petOwner/booking.php?payment_success=1");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        die("Payment Error: " . $e->getMessage());
    }
}
?>