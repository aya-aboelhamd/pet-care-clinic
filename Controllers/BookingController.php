<?php
session_start();
require_once '../models/Database.php';
require_once '../models/BookingModel.php';

$database = new Database();
$db = $database->getConnection();
$bookingModel = new BookingModel($db);

// --- 1. الانضمام لقائمة الانتظار (Waitlist) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join_waitlist'])) {
    $user_id = $_SESSION['user_id'];
    $pet_id = $_POST['pet_id'];
    $provider_id = $_POST['provider_id'];
    $service = $_POST['service_type'];
    $start_time = $_POST['booking_date'] . ' ' . $_POST['booking_time'];

    // هنسجل الحجز في الداتا بيز بس بحالة Waitlisted عشان ميحجزش مكان فعلي
    $stmt = $db->prepare("INSERT INTO booking (user_id, pet_id, provider_id, service_type, start_time, status, created_at) VALUES (?, ?, ?, ?, ?, 'Waitlisted', CURRENT_TIMESTAMP)");
    $stmt->execute([$user_id, $pet_id, $provider_id, $service, $start_time]);

    // مسح داتا التعارض من السيشن
    unset($_SESSION['conflict_data']);

    header("Location: ../views/petOwner/booking.php?waitlist_success=1");
    exit();
}

// --- 2. طلب حجز جديد (فحص التعارض وتسجيل السلوك) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_booking'])) {
    
    // --> الإضافة الجديدة: تحديث السلوك للأبد لو الأونر اختاره من النافذة <--
    if (!empty($_POST['new_behavior']) && !empty($_POST['pet_id'])) {
        $stmt_beh = $db->prepare("UPDATE pet SET behavioral_notes = ? WHERE id = ?");
        $stmt_beh->execute([$_POST['new_behavior'], $_POST['pet_id']]);
    }
    // -------------------------------------------------------------

    $provider_id = $_POST['provider_id'];
    $date = $_POST['booking_date'];
    $time = $_POST['booking_time'];
    $datetime = $date . ' ' . $time;

    // فحص التعارض: هل الدكتور ده عنده حجز مؤكد أو جاري في خلال 60 دقيقة من الميعاد ده؟
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM booking 
        WHERE provider_id = ? 
        AND DATE(start_time) = ? 
        AND ABS(TIMESTAMPDIFF(MINUTE, start_time, ?)) < 60
        AND status IN ('Pending', 'Confirmed', 'In Progress')
    ");
    $stmt->execute([$provider_id, $date, $datetime]);
    $conflict = $stmt->fetchColumn();

    if ($conflict > 0) {
        // لو في تعارض، نجيب الدكاترة التانيين اللي فاضيين في نفس الميعاد ده (الاقتراحات البديلة)
        $role_stmt = $db->prepare("SELECT role_id FROM users WHERE id = ?");
        $role_stmt->execute([$provider_id]);
        $role_id = $role_stmt->fetchColumn(); // بنعرف هو دكتور ولا مقدم خدمة

        $alt_stmt = $db->prepare("
            SELECT id, name FROM users 
            WHERE role_id = ? 
            AND id != ? 
            AND id NOT IN (
                SELECT provider_id FROM booking 
                WHERE DATE(start_time) = ? 
                AND ABS(TIMESTAMPDIFF(MINUTE, start_time, ?)) < 60
                AND status IN ('Pending', 'Confirmed', 'In Progress')
            )
        ");
        $alt_stmt->execute([$role_id, $provider_id, $date, $datetime]);
        $alternatives = $alt_stmt->fetchAll(PDO::FETCH_ASSOC);

        // بنحفظ الداتا في السيشن عشان نعرضها لليوزر في شاشة التعارض
        $_SESSION['conflict_data'] = [
            'pet_id' => $_POST['pet_id'],
            'provider_id' => $provider_id,
            'service_type' => $_POST['service_type'],
            'booking_date' => $date,
            'booking_time' => $time,
            'notes' => $_POST['notes'],
            'alternatives' => $alternatives
        ];

        header("Location: ../views/petOwner/booking.php?conflict=1");
        exit();
    }

    // لو مفيش تعارض، كمل الحجز عادي جداً
    $data = [
        'user_id' => $_SESSION['user_id'],
        'pet_id' => $_POST['pet_id'],
        'provider_id' => $provider_id,
        'service' => $_POST['service_type'],
        'date' => $date,
        'time' => $time,
        'notes' => $_POST['notes']
    ];

    if ($bookingModel->createBooking($data)) {
        header("Location: ../views/petOwner/booking.php?success=1");
    } else {
        header("Location: ../views/petOwner/booking.php?error=1");
    }
    exit();
}
?>