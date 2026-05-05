<?php
// models/DisputeModel.php

require_once __DIR__ . '/Database.php';

class DisputeModel {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // UC-36: Get all disputes
    public function getAllDisputes() {
        try {
            $stmt = $this->conn->prepare("
                SELECT d.*, u.full_name as user_name
                FROM disputes d
                LEFT JOIN users u ON d.raised_by = u.id
                ORDER BY d.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // UC-36: Get dispute by ID
    public function getDisputeById($disputeId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT d.*, u.full_name as user_name
                FROM disputes d
                LEFT JOIN users u ON d.raised_by = u.id
                WHERE d.id = ?
            ");
            $stmt->execute([$disputeId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // UC-36: Create new dispute
    public function createDispute($bookingId, $raisedBy, $description) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO disputes (booking_id, raised_by, description, status, created_at)
                VALUES (?, ?, ?, 'open', NOW())
            ");
            return $stmt->execute([$bookingId, $raisedBy, $description]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // UC-36: Resolve dispute
    public function resolveDispute($disputeId, $decision) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE disputes 
                SET status = 'resolved', decision = ?, resolved_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$decision, $disputeId]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>