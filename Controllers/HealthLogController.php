<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/Auth/login.php");
    exit();
}

require_once '../models/Database.php';
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['log_metric'])) {
    $pet_id = $_POST['pet_id'];
    $metric_name = $_POST['metric_name'];
    $metric_value = $_POST['metric_value'];
    
    // تحديد الوحدة بناءً على نوع القياس
    $unit = '';
    if ($metric_name == 'Blood Sugar') $unit = 'mg/dL';
    elseif ($metric_name == 'Water Intake') $unit = 'ml';
    elseif ($metric_name == 'Insulin Dose') $unit = 'Units';
    else $unit = 'kg'; // للوزن

    // Alt Course 1: التحقق من البيانات
    if (empty($pet_id) || empty($metric_name) || $metric_value === '') {
        header("Location: ../views/petOwner/healthLogs.php?error=missing_data");
        exit();
    }

    try {
        $db->beginTransaction();

        // حفظ السجل
        $stmt = $db->prepare("INSERT INTO health_metrics_log (pet_id, metric_name, metric_value, unit, logged_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$pet_id, $metric_name, $metric_value, $unit]);

        // Alt Course 2: اكتشاف القيم الخطيرة (Abnormal Values) وإرسال إنذار
        $is_critical = false;
        if ($metric_name == 'Blood Sugar' && ($metric_value > 250 || $metric_value < 70)) {
            $is_critical = true;
        }

        if ($is_critical) {
            // إرسال إنذار لصاحب الحيوان
            $owner_id = $_SESSION['user_id'];
            $alert_msg = "CRITICAL ALERT: Abnormal $metric_name reading ($metric_value $unit) recorded! Please consult your vet immediately.";
            $alert_stmt = $db->prepare("INSERT INTO notification (user_id, type, message, created_at) VALUES (?, 'Emergency', ?, NOW())");
            $alert_stmt->execute([$owner_id, $alert_msg]);
            
            // في الواقع الفعلي، هنا بنعمل Query نجيب الـ Vet_id المربوط بالحيوان ونبعتله هو كمان إشعار.
        }

        $db->commit();
        header("Location: ../views/petOwner/healthLogs.php?success=logged" . ($is_critical ? "&alert=critical" : ""));
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: ../views/petOwner/healthLogs.php?error=db_error");
        exit();
    }
}
?>