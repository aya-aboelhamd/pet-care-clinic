<?php
class AdminDashboardModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getTotalUsers() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getOpenDisputesCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM disputes WHERE status = 'Open'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0; 
        }
    }

   
    public function getNotificationsTodayCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notification WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    
    public function getRecentAlerts() {
        try {
            $stmt = $this->db->prepare("
                SELECT a.disease_name, a.target_region, a.severity, a.sent_at, u.name as vet_name 
                FROM alert_history a 
                JOIN users u ON a.vet_id = u.id 
                ORDER BY a.sent_at DESC LIMIT 5
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

   
    public function getRecentLogs() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM activitylog ORDER BY created_at DESC LIMIT 4");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>