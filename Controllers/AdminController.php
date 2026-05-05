<?php
// controllers/AdminController.php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../models/DisputeModel.php';
require_once __DIR__ . '/../models/AlertModel.php';

class AdminController {
    private $disputeModel;
    private $alertModel;
    
    public function __construct() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }
        $this->disputeModel = new DisputeModel();
        $this->alertModel = new AlertModel();
    }
    
    // UC-36: Resolve dispute
    public function resolveDispute() {
        header('Content-Type: application/json');
        
        $disputeId = $_POST['dispute_id'] ?? null;
        $decision = $_POST['decision'] ?? null;
        
        if (!$disputeId || !$decision) {
            echo json_encode(['error' => 'Missing required data']);
            return;
        }
        
        $result = $this->disputeModel->resolveDispute($disputeId, $decision);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Dispute resolved successfully']);
        } else {
            echo json_encode(['error' => 'Failed to resolve dispute']);
        }
    }
    
    // UC-39: Create health alert
    public function createHealthAlert() {
        header('Content-Type: application/json');
        
        $diseaseName = $_POST['disease_name'] ?? null;
        $description = $_POST['description'] ?? null;
        $severity = $_POST['severity'] ?? 'medium';
        
        if (!$diseaseName || !$description) {
            echo json_encode(['error' => 'Disease name and description are required']);
            return;
        }
        
        $result = $this->alertModel->createHealthAlert($diseaseName, $description, $severity);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Health alert created successfully']);
        } else {
            echo json_encode(['error' => 'Failed to create health alert']);
        }
    }
    
    // UC-39: Deactivate health alert
    public function deactivateAlert() {
        header('Content-Type: application/json');
        
        $alertId = $_POST['alert_id'] ?? null;
        
        if (!$alertId) {
            echo json_encode(['error' => 'Alert ID is required']);
            return;
        }
        
        $result = $this->alertModel->deactivateAlert($alertId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Alert deactivated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to deactivate alert']);
        }
    }
}

// Routing
if (isset($_GET['action'])) {
    $controller = new AdminController();
    
    switch ($_GET['action']) {
        case 'resolve_dispute':
            $controller->resolveDispute();
            break;
        case 'create_health_alert':
            $controller->createHealthAlert();
            break;
        case 'deactivate_alert':
            $controller->deactivateAlert();
            break;
        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}
?>