<?php
// controllers/BatchController.php
require_once __DIR__ . '/../models/Database.php';

class BatchController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function createBatch() {
        if (!$this->conn) return false;

        $sql = "SELECT * FROM orders WHERE batch_id IS NULL AND TRIM(LOWER(status)) = 'confirmed'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orders)) return false;

        $groups = [];
        foreach ($orders as $order) {
            $groups[$order['user_id']][] = $order;
        }

        $isUpdated = false;
        foreach ($groups as $user_id => $group) {
            if (count($group) > 1) {
                $insert = "INSERT INTO deliverybatch (scheduled_time, status, total_orders) 
                           VALUES (DATE_ADD(NOW(), INTERVAL 3 DAY), 'pending', :count)";
                $stmtBatch = $this->conn->prepare($insert);
                $stmtBatch->execute([':count' => count($group)]);
                $batch_id = $this->conn->lastInsertId();
                
                $update = "UPDATE orders SET batch_id = :batch_id WHERE id = :id";
                $stmtUpdate = $this->conn->prepare($update);
                foreach ($group as $order) {
                    $stmtUpdate->execute([':batch_id' => $batch_id, ':id' => $order['id']]);
                }
                $isUpdated = true;
            }
        }
        return $isUpdated;
    }
}