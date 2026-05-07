<?php
class ManageNotificationsModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function broadcastToAll($title, $message) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT id FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($users)) {
                $full_message = "[$title] $message";
                
                $insertStmt = $this->db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Admin Broadcast', ?, 0)");
                
                foreach ($users as $uid) {
                    $insertStmt->execute([$uid, $full_message]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getRecentBroadcasts() {
        try {
            $stmt = $this->db->prepare("
                SELECT type, message, MAX(created_at) as created_at 
                FROM notification 
                WHERE type = 'Admin Broadcast' 
                GROUP BY type, message 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>