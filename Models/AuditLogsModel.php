<?php
class AuditLogsModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllLogs() {
        try {
            $stmt = $this->db->prepare("
                SELECT a.created_at, u.email as actor, a.action, a.target, a.ip_address 
                FROM activitylog a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function logAction($user_id, $action, $target) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $stmt = $this->db->prepare("INSERT INTO activitylog (user_id, action, target, ip_address) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$user_id, $action, $target, $ip_address]);
        } catch (Exception $e) {
            return false;
        }
    }
}
?>