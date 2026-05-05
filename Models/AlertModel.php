<?php
// models/AlertModel.php

require_once __DIR__ . '/Database.php';

class AlertModel {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // UC-39: Get all active health alerts
    public function getActiveHealthAlerts() {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM health_alerts 
                WHERE is_active = 1
                ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low'), created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // UC-39: Create new health alert
    public function createHealthAlert($diseaseName, $description, $severity) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO health_alerts (disease_name, description, severity, is_active, created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            return $stmt->execute([$diseaseName, $description, $severity]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // UC-39: Deactivate health alert
    public function deactivateAlert($alertId) {
        try {
            $stmt = $this->conn->prepare("UPDATE health_alerts SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$alertId]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>