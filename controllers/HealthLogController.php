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

        // 1. إذا كان المقياس هو الوزن، نحفظه في جدول weightlog الخاص باليوز كيس UC-8
        if ($metric_name == 'Weight') {
            $stmt = $db->prepare("INSERT INTO weightlog (pet_id, weight, logged_at) VALUES (?, ?, CURRENT_DATE())");
            $stmt->execute([$pet_id, $metric_value]);
            
            // تحديث الوزن الحالي في جدول الحيوان الأساسي
            $update_pet = $db->prepare("UPDATE pet SET weight = ? WHERE id = ?");
            $update_pet->execute([$metric_value, $pet_id]);

            // --- تطبيق الـ UC-8: فحص التغير المفاجئ في الوزن (>10%) ---
            // جلب آخر قراءة قبل الحالية
            $stmt_prev = $db->prepare("
                SELECT weight, logged_at 
                FROM weightlog 
                WHERE pet_id = ? 
                ORDER BY logged_at DESC, id DESC 
                LIMIT 1 OFFSET 1
            ");
            $stmt_prev->execute([$pet_id]);
            $prev_record = $stmt_prev->fetch(PDO::FETCH_ASSOC);

            $is_critical = false;
            
            if ($prev_record) {
                $prev_weight = $prev_record['weight'];
                // التأكد من عدم القسمة على صفر
                if ($prev_weight > 0) {
                    $weight_diff = abs($metric_value - $prev_weight);
                    $percent_change = ($weight_diff / $prev_weight) * 100;
                    
                    // حساب فرق الأيام بين القراءتين
                    $days_diff = (strtotime(date('Y-m-d')) - strtotime($prev_record['logged_at'])) / (60 * 60 * 24);

                    // إذا كان التغير أكثر من 10% خلال 14 يوم أو أقل (أسبوعين)
                    if ($percent_change > 10 && $days_diff <= 14) {
                        $is_critical = true;
                        
                        // إرسال إنذار للأونر
                        $owner_id = $_SESSION['user_id'];
                        $alert_msg = "CRITICAL ALERT: Sudden weight change detected (" . number_format($percent_change, 1) . "% in " . $days_diff . " days). Please consult your vet.";
                        $alert_stmt = $db->prepare("INSERT INTO notification (user_id, type, message, created_at, is_read) VALUES (?, 'Weight Alert', ?, NOW(), 0)");
                        $alert_stmt->execute([$owner_id, $alert_msg]);
                        
                        // تسجيلها في السجل الطبي لكي يراها الطبيب (Vet Review)
                        $log_msg = "Abnormal Weight Change: Dropped/Gained from {$prev_weight}kg to {$metric_value}kg.";
                        $log_stmt = $db->prepare("INSERT INTO medicalrecord (pet_id, diagnosis, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
                        $log_stmt->execute([$pet_id, $log_msg]);
                    }
                }
            }

            $db->commit();
            header("Location: ../views/petOwner/healthLogs.php?success=logged" . ($is_critical ? "&alert=weight_critical" : ""));
            exit();

        } else {
            // 2. المقاييس الأخرى (السكر، المياه، إلخ) نحفظها في health_metrics_log القديم
            $stmt = $db->prepare("INSERT INTO health_metrics_log (pet_id, metric_name, metric_value, unit, logged_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$pet_id, $metric_name, $metric_value, $unit]);

            // اكتشاف القيم الخطيرة للمقاييس الأخرى
            $is_critical = false;
            if ($metric_name == 'Blood Sugar' && ($metric_value > 250 || $metric_value < 70)) {
                $is_critical = true;
            }

            if ($is_critical) {
                $owner_id = $_SESSION['user_id'];
                $alert_msg = "CRITICAL ALERT: Abnormal $metric_name reading ($metric_value $unit) recorded! Please consult your vet immediately.";
                $alert_stmt = $db->prepare("INSERT INTO notification (user_id, type, message, created_at, is_read) VALUES (?, 'Emergency', ?, NOW(), 0)");
                $alert_stmt->execute([$owner_id, $alert_msg]);
            }

            $db->commit();
            header("Location: ../views/petOwner/healthLogs.php?success=logged" . ($is_critical ? "&alert=critical" : ""));
            exit();
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        die("Database Error: " . $e->getMessage());
    }
}
?>