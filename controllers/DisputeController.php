<?php
session_start();
require_once '../models/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/Auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// =======================================================
// 1. الجزء الخاص بصاحب الحيوان (إنشاء المشكلة وتجميد الدفع)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_dispute'])) {
    $booking_id = $_POST['booking_id'];
    $provider_id = $_POST['provider_id'];
    $reason = trim($_POST['reason']);
    $owner_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'The Owner';

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO disputes (owner_id, provider_id, booking_id, reason, status, created_at) VALUES (?, ?, ?, ?, 'Open', CURRENT_TIMESTAMP)");
        $stmt->execute([$owner_id, $provider_id, $booking_id, $reason]);

        $update_booking = $db->prepare("UPDATE booking SET status = 'Disputed' WHERE id = ?");
        $update_booking->execute([$booking_id]);

        $msg_provider = "URGENT: $user_name has opened a dispute for booking #" . $booking_id . ". Payment is frozen until resolved. Reason: " . $reason;
        $notif_provider = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Dispute Opened', ?, 0)");
        $notif_provider->execute([$provider_id, $msg_provider]);
        
        $msg_admin = "New Dispute (Booking #$booking_id) from $user_name: $reason";
        $notif_admin = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (1, 'Dispute Alert', ?, 0)");
        $notif_admin->execute([$msg_admin]);

        $db->commit();
        header("Location: ../views/petOwner/booking.php?dispute_success=1");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        die("Dispute Error: " . $e->getMessage());
    }
}

// =======================================================
// 2. الجزء الخاص بالأدمن (حل المشكلة وتحويل الفلوس)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_dispute'])) {
    
    if ($_SESSION['role_id'] != 1) {
        die("Unauthorized Access");
    }

    $dispute_id = $_POST['dispute_id'];
    $new_status = $_POST['new_status'];

    try {
        $db->beginTransaction();

        // 1. تحديث حالة النزاع
        $stmt = $db->prepare("UPDATE disputes SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $dispute_id]);

        // 2. نجيب رقم الحجز من المشكلة
        $stmt_booking_id = $db->prepare("SELECT booking_id FROM disputes WHERE id = ?");
        $stmt_booking_id->execute([$dispute_id]);
        $booking_id = $stmt_booking_id->fetchColumn();

        // 3. ⭐️ هنجيب صاحب الحيوان ومقدم الخدمة من "الحجز نفسه" مش المشكلة (عشان نضمن الصح)
        $stmt_users = $db->prepare("SELECT user_id, provider_id FROM booking WHERE id = ?");
        $stmt_users->execute([$booking_id]);
        $booking_users = $stmt_users->fetch(PDO::FETCH_ASSOC);

        $actual_owner_id = $booking_users['user_id']; // ده صاحب الحيوان الأصلي 100%
        $actual_provider_id = $booking_users['provider_id'];

        if ($new_status == 'Resolved') {
            
            // تحويل الحجز لـ Paid
            $stmt_bk = $db->prepare("UPDATE booking SET status = 'Paid' WHERE id = ?");
            $stmt_bk->execute([$booking_id]);

            // إشعار صاحب الحيوان (هيوصل لليوزر الأصلي اللي عمل الحجز)
            $msg_owner = "Dispute Resolved: Your case for booking #$booking_id has been closed by the admin.";
            $notif_owner = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Dispute Update', ?, 0)");
            $notif_owner->execute([$actual_owner_id, $msg_owner]);

            // إشعار مقدم الخدمة
            $msg_provider = "Dispute Resolved: The issue for booking #$booking_id is closed. Funds released to your account.";
            $notif_provider = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Funds Released', ?, 0)");
            $notif_provider->execute([$actual_provider_id, $msg_provider]);
            
        } else if ($new_status == 'Investigating') {
            $msg = "Admin is currently investigating the dispute for booking #$booking_id.";
            $notif = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Investigation', ?, 0)");
            $notif->execute([$actual_owner_id, $msg]);
            $notif->execute([$actual_provider_id, $msg]);
        }

        $db->commit();
        header("Location: ../views/admin/manageDisputes.php?success=1");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        die("Update Error: " . $e->getMessage());
    }
}
?>