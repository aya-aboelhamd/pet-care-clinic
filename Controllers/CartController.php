<?php
session_start();
require_once '../models/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/Auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] == 'add_to_cart') {
        $product_id = $_POST['product_id'];
        $pet_id = $_POST['pet_id'] ?? '';
        
        $stmt = $db->prepare("SELECT name, requires_prescription FROM product WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product['requires_prescription'] == 1) {
            if (empty($pet_id) || $pet_id == 'all') {
                header("Location: ../views/petOwner/marketplace.php?error=select_pet_first");
                exit();
            }

            $stmt = $db->prepare("
                SELECT p.id 
                FROM prescription p 
                JOIN medicalrecord m ON p.record_id = m.id 
                WHERE m.pet_id = ? 
                AND p.medication_name LIKE ? 
                AND (p.expiry_date >= CURDATE() OR p.expiry_date IS NULL)
            ");
            $stmt->execute([$pet_id, '%' . $product['name'] . '%']);
            $valid_prescription = $stmt->fetch();

            if (!$valid_prescription) {
                header("Location: ../views/petOwner/marketplace.php?error=no_prescription&pet_id=" . $pet_id);
                exit();
            }
        }
        
        $stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch();
        
        if (!$cart) {
            $stmt = $db->prepare("INSERT INTO cart (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $cart_id = $db->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        $stmt = $db->prepare("SELECT id FROM cartitem WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cart_id, $product_id]);
        $item = $stmt->fetch();

        if ($item) {
            $stmt = $db->prepare("UPDATE cartitem SET quantity = quantity + 1 WHERE id = ?");
            $stmt->execute([$item['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO cartitem (cart_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$cart_id, $product_id]);
        }

        header("Location: ../views/petOwner/marketplace.php?success=added&pet_id=" . $pet_id);
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] == 'remove_item') {
        $item_id = $_POST['item_id'];
        $stmt = $db->prepare("DELETE FROM cartitem WHERE id = ?");
        $stmt->execute([$item_id]);
        header("Location: ../views/petOwner/checkout.php");
        exit();
    }
}
?>