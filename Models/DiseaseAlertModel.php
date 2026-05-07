<?php
class DiseaseAlertModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAvailableRegions() {
        return ['Cairo', 'Giza', 'Alexandria'];
    }

    public function getAlertHistory($vet_id) {
        $stmt = $this->conn->prepare("SELECT * FROM alert_history WHERE vet_id = ? ORDER BY sent_at DESC LIMIT 10");
        $stmt->execute([$vet_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function broadcastAlert($vet_id, $region, $disease, $severity, $message) {
        try {
            $this->conn->beginTransaction();

            if ($region == 'All Regions') {
                // إرسال لكل المستخدمين في السيستم ما عدا الدكتور اللي بيبعت
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE id != ?");
                $stmt->execute([$vet_id]);
            } else {
                // إرسال لكل المستخدمين في المحافظة دي ما عدا الدكتور اللي بيبعت
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE region = ? AND id != ?");
                $stmt->execute([$region, $vet_id]);
            }
            
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $full_message = "[$disease OUTBREAK] $message";

            if (!empty($users)) {
                $notify_stmt = $this->conn->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Alert', ?, 0)");
                foreach ($users as $uid) {
                    $notify_stmt->execute([$uid, $full_message]);
                }
            }

            $history_stmt = $this->conn->prepare("INSERT INTO alert_history (vet_id, target_region, disease_name, severity, message) VALUES (?, ?, ?, ?, ?)");
            $history_stmt->execute([$vet_id, $region, $disease, $severity, $message]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>