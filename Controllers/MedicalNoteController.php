<?php
session_start();
require_once '../models/Database.php';

// التأكد من تسجيل الدخول كطبيب بيطري
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../views/Auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$vet_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pet_id = $_POST['pet_id'];
    $title = trim($_POST['title']);
    $note_content = trim($_POST['note_content']);

    if (!empty($pet_id) && !empty($title) && !empty($note_content)) {
        try {
            $db->beginTransaction();

            // دمج العنوان والمحتوى معاً
            $full_note = $title . "\n" . $note_content;

            // 1. إضافة الريكورد في السجل الطبي (medicalrecord) كمرجع تاريخي للزيارة
            $stmt = $db->prepare("INSERT INTO medicalrecord (pet_id, vet_id, diagnosis, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$pet_id, $vet_id, $full_note]);

            // 2. تحديث جدول الحيوان نفسه (pet) عشان يظهر لصاحبه في الـ Profile مباشرة
            // بنضيف النوت الجديدة على الملاحظات القديمة لو موجودة
            $append_text = "\n[Dr. Note - " . date('Y-m-d') . "]: " . $full_note;
            $stmt_pet = $db->prepare("UPDATE pet SET medical_notes = CONCAT(IFNULL(medical_notes, ''), ?) WHERE id = ?");
            $stmt_pet->execute([$append_text, $pet_id]);

            // 3. إرسال إشعار لصاحب الحيوان
            $stmt_owner = $db->prepare("SELECT user_id, name FROM pet WHERE id = ?");
            $stmt_owner->execute([$pet_id]);
            $pet_data = $stmt_owner->fetch(PDO::FETCH_ASSOC);

            if ($pet_data && $pet_data['user_id']) {
                $owner_id = $pet_data['user_id'];
                $pet_name = $pet_data['name'];
                
                $msg = "Your veterinarian has added a new medical note ($title) to $pet_name's profile.";
                $notif = $db->prepare("INSERT INTO notification (user_id, type, message, created_at, is_read) VALUES (?, 'Medical Update', ?, NOW(), 0)");
                $notif->execute([$owner_id, $msg]);
            }

            $db->commit();
            header("Location: ../views/veterinarian/medicalnotes.php?success=1");
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            die("Database Error: " . $e->getMessage());
        }
    }
}
?>