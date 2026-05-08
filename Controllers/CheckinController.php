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
        // 1. نجيب وقت الـ Check-in وبيانات الحجز
        $stmt = $db->prepare("SELECT user_id, service_type, checkin_time FROM booking WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking && $booking['checkin_time']) {
            $hourly_rate = 5.00; // سعر الساعة (ممكن تخليه ديناميكي بعدين من جدول الخدمات)
            
            $checkin_time = strtotime($booking['checkin_time']);
            $checkout_time = time(); // الوقت الحالي

            // 2. حساب الفرق بالساعات وتقريبه للأكبر (عشان لو قعد ثانية تتحسب بـ 5)
            $diff_seconds = $checkout_time - $checkin_time;
            $hours = ceil($diff_seconds / 3600);
            if ($hours < 1) $hours = 1; // أقل حسبة هي ساعة واحدة

            $total_price = $hours * $hourly_rate;

            // 3. تحديث الطلب وإضافة السعر الإجمالي
            $stmt_update = $db->prepare("UPDATE booking SET status = 'Completed', checkout_time = CURRENT_TIMESTAMP, total_price = ? WHERE id = ? AND provider_id = ?");
            $stmt_update->execute([$total_price, $booking_id, $provider_id]);

            // 4. إشعار الأونر إن الخدمة خلصت والسعر نزل
            $message = "Your " . $booking['service_type'] . " session is completed. Total duration: $hours hr(s). Amount due: $" . number_format($total_price, 2) . ". Please proceed to payment or raise a dispute.";
            $notif = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Service Completed', ?, 0)");
            $notif->execute([$booking['user_id'], $message]);
        }
    }

    header("Location: ../views/serviceProvider/qrCheckin.php?success=1");
    exit();
}
?>