<?php
class LabResultModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function saveResult($pet_id, $vet_id, $test_name, $file_path, $technical_data, $simplified_insight, $vet_notes, $is_critical) {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO lab_results (pet_id, vet_id, test_name, file_path, technical_data, simplified_insight, vet_notes, is_critical) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$pet_id, $vet_id, $test_name, $file_path, $technical_data, $simplified_insight, $vet_notes, $is_critical]);

            if ($is_critical == 1) {
                $stmt_owner = $this->conn->prepare("SELECT user_id FROM pet WHERE id = ?");
                $stmt_owner->execute([$pet_id]);
                $owner_id = $stmt_owner->fetchColumn();

                if ($owner_id) {
                    $alert_msg = "CRITICAL ALERT: Abnormal lab results uploaded for your pet ($test_name). Please check your portal and contact the vet.";
                    $stmt_notify = $this->conn->prepare("INSERT INTO notification (user_id, type, message, created_at, is_read) VALUES (?, 'Emergency', ?, NOW(), 0)");
                    $stmt_notify->execute([$owner_id, $alert_msg]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getResultsForPet($pet_id) {
        $stmt = $this->conn->prepare("
            SELECT l.*, u.name as vet_name 
            FROM lab_results l 
            JOIN users u ON l.vet_id = u.id 
            WHERE l.pet_id = ? 
            ORDER BY l.uploaded_at DESC
        ");
        $stmt->execute([$pet_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats($vet_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN is_critical = 0 THEN 1 ELSE 0 END), 0) as normal_count, 
                COALESCE(SUM(CASE WHEN is_critical = 1 THEN 1 ELSE 0 END), 0) as abnormal_count 
            FROM lab_results 
            WHERE vet_id = ?
        ");
        $stmt->execute([$vet_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllResults($vet_id) {
        $stmt = $this->conn->prepare("
            SELECT l.id, l.test_name, l.technical_data, l.is_critical, l.uploaded_at, p.name as pet_name, p.species 
            FROM lab_results l 
            JOIN pet p ON l.pet_id = p.id 
            WHERE l.vet_id = ? 
            ORDER BY l.uploaded_at DESC
        ");
        $stmt->execute([$vet_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>