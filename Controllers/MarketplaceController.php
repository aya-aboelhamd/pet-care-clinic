<?php
// controllers/MarketplaceController.php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../models/ProductModel.php';

class MarketplaceController {
    private $productModel;
    
    public function __construct() {
        $this->productModel = new ProductModel();
    }
    
    // UC-13 & UC-15: Add to cart with validation
    public function addToCart() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Please login first']);
            return;
        }
        
        $productId = $_POST['product_id'] ?? null;
        $petId = $_POST['pet_id'] ?? null;
        
        if (!$productId || !$petId) {
            echo json_encode(['error' => 'Missing required data']);
            return;
        }
        
        // UC-15: Check allergens
        $allergyCheck = $this->productModel->checkAllergens($productId, $petId);
        if ($allergyCheck['safe'] === false) {
            echo json_encode([
                'error' => $allergyCheck['message'],
                'blocked' => true,
                'type' => 'allergy'
            ]);
            return;
        }
        
        // UC-13: Check prescription
        if (!$this->productModel->hasValidPrescription($petId, $productId)) {
            echo json_encode([
                'error' => 'This product requires a valid prescription from a veterinarian',
                'blocked' => true,
                'type' => 'prescription'
            ]);
            return;
        }
        
        // Add to cart
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $_SESSION['cart'][] = [
            'product_id' => $productId,
            'pet_id' => $petId,
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_count' => count($_SESSION['cart'])
        ]);
    }
    
    // UC-20: Recall product (admin only)
    public function recallProduct() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $productId = $_POST['product_id'] ?? null;
        $reason = $_POST['reason'] ?? null;
        
        if (!$productId || !$reason) {
            echo json_encode(['error' => 'Product ID and reason are required']);
            return;
        }
        
        $result = $this->productModel->recallProduct($productId, $reason);
        $customers = $this->productModel->getCustomersByProduct($productId);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Product recalled successfully',
                'affected_customers' => count($customers)
            ]);
        } else {
            echo json_encode(['error' => 'Failed to recall product']);
        }
    }
}

// Routing
if (isset($_GET['action'])) {
    $controller = new MarketplaceController();
    
    switch ($_GET['action']) {
        case 'add_to_cart':
            $controller->addToCart();
            break;
        case 'recall_product':
            $controller->recallProduct();
            break;
        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}
?>