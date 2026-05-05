<?php
// models/ProductModel.php

require_once __DIR__ . '/Database.php';

class ProductModel {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // UC-13: Check if product requires prescription
    public function requiresPrescription($productId) {
        try {
            $stmt = $this->conn->prepare("SELECT requires_prescription FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['requires_prescription'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // UC-13: Check if pet has valid prescription
    public function hasValidPrescription($petId, $productId) {
        if (!$this->requiresPrescription($productId)) {
            return true;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT id FROM prescriptions 
                WHERE pet_id = ? AND is_valid = 1 AND expiry_date > NOW()
                LIMIT 1
            ");
            $stmt->execute([$petId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // UC-15: Get pet allergies
    public function getPetAllergies($petId) {
        try {
            $stmt = $this->conn->prepare("SELECT allergen_name, severity FROM pet_allergies WHERE pet_id = ?");
            $stmt->execute([$petId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // UC-15: Get product ingredients
    public function getProductIngredients($productId) {
        try {
            $stmt = $this->conn->prepare("SELECT ingredient_name FROM product_ingredients WHERE product_id = ?");
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // UC-15: Check allergens in product
    public function checkAllergens($productId, $petId) {
        $allergies = $this->getPetAllergies($petId);
        $ingredients = $this->getProductIngredients($productId);
        
        if (empty($allergies)) {
            return ['safe' => true, 'message' => 'No allergies recorded for this pet'];
        }
        
        if (empty($ingredients)) {
            return ['safe' => null, 'message' => 'Ingredients not available for this product'];
        }
        
        foreach ($allergies as $allergy) {
            foreach ($ingredients as $ingredient) {
                if (stripos($ingredient, $allergy['allergen_name']) !== false) {
                    return [
                        'safe' => false,
                        'message' => 'Product contains ' . $allergy['allergen_name'] . ' which your pet is allergic to'
                    ];
                }
            }
        }
        
        return ['safe' => true, 'message' => 'No allergens found'];
    }
    
    // UC-20: Recall a product
    public function recallProduct($productId, $reason) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE products 
                SET is_recalled = 1, recall_reason = ?, recalled_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$reason, $productId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // UC-20: Get customers who purchased a product
    public function getCustomersByProduct($productId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT u.id, u.email, u.full_name 
                FROM users u
                JOIN orders o ON o.user_id = u.id
                JOIN order_items oi ON oi.order_id = o.id
                WHERE oi.product_id = ?
            ");
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get product by ID
    public function getProductById($productId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Get all products
    public function getAllProducts() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM products ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>