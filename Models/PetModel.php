<?php
// models/PetModel.php

require_once __DIR__ . '/Database.php';

class PetModel {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Get all pets for a specific owner
    public function getPetsByOwner($ownerId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, species, breed, birth_date, weight_kg, is_active 
                FROM pets 
                WHERE owner_id = ? AND is_active = 1
                ORDER BY name
            ");
            $stmt->execute([$ownerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get single pet by ID
    public function getPetById($petId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, u.full_name as owner_name 
                FROM pets p
                LEFT JOIN users u ON p.owner_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$petId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Add new pet
    public function addPet($ownerId, $name, $species, $breed, $birthDate, $weight) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO pets (owner_id, name, species, breed, birth_date, weight_kg, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            return $stmt->execute([$ownerId, $name, $species, $breed, $birthDate, $weight]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Update pet info
    public function updatePet($petId, $name, $species, $breed, $birthDate, $weight) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE pets 
                SET name = ?, species = ?, breed = ?, birth_date = ?, weight_kg = ?
                WHERE id = ?
            ");
            return $stmt->execute([$name, $species, $breed, $birthDate, $weight, $petId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Delete pet (soft delete)
    public function deletePet($petId) {
        try {
            $stmt = $this->conn->prepare("UPDATE pets SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$petId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get pet allergy list (for display)
    public function getPetAllergiesList($petId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT allergen_name, severity 
                FROM pet_allergies 
                WHERE pet_id = ?
            ");
            $stmt->execute([$petId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>