<?php
class ManageDisputesModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllDisputes() {
        try {
            $stmt = $this->db->prepare("
                SELECT d.id, d.reason, d.status, d.created_at, u.name as owner_name 
                FROM disputes d
                JOIN users u ON d.owner_id = u.id
                ORDER BY d.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>