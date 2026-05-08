<?php
session_start();
require_once '../models/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../views/Auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- 1. إضافة منتج جديد ---
    if (isset($_POST['action']) && $_POST['action'] == 'add_product') {
        $name = $_POST['name'];
        $category = $_POST['category']; // دلوقتي بقت بتيجي من Dropdown
        $price = $_POST['price'];
        $description = $_POST['description'];
        $icon = $_POST['icon'] ?? '📦'; 
        $requires_prescription = isset($_POST['requires_prescription']) ? 1 : 0;
        $contraindicated_condition = trim($_POST['contraindicated_condition']) ?: NULL;
        $stock_quantity = $_POST['stock_quantity'] ?? 100;

        $stmt = $db->prepare("INSERT INTO product (name, category, price, description, icon, requires_prescription, contraindicated_condition, stock_quantity, is_recalled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$name, $category, $price, $description, $icon, $requires_prescription, $contraindicated_condition, $stock_quantity]);

        header("Location: ../views/admin/productRecalls.php?success=added");
        exit();
    }

    // --- 2. تعديل سعر المنتج (Edit) ---
    if (isset($_POST['action']) && $_POST['action'] == 'edit_price') {
        $product_id = $_POST['product_id'];
        $new_price = $_POST['new_price'];

        if (is_numeric($new_price) && $new_price > 0) {
            $stmt = $db->prepare("UPDATE product SET price = ? WHERE id = ?");
            $stmt->execute([$new_price, $product_id]);
            header("Location: ../views/admin/productRecalls.php?success=updated");
        } else {
            header("Location: ../views/admin/productRecalls.php?error=invalid_price");
        }
        exit();
    }

    // --- 3. سحب منتج (Recall) وإشعار المشترين ---
    if (isset($_POST['action']) && $_POST['action'] == 'recall_product') {
        $product_id = $_POST['product_id'];
        
        try {
            $db->beginTransaction();

            $stmt_prod = $db->prepare("SELECT name FROM product WHERE id = ?");
            $stmt_prod->execute([$product_id]);
            $product_name = $stmt_prod->fetchColumn();

            $stmt_update = $db->prepare("UPDATE product SET is_recalled = 1 WHERE id = ?");
            $stmt_update->execute([$product_id]);

            $stmt_buyers = $db->prepare("
                SELECT DISTINCT o.user_id 
                FROM orders o 
                JOIN orderitem oi ON o.id = oi.order_id 
                WHERE oi.product_id = ?
            ");
            $stmt_buyers->execute([$product_id]);
            $buyers = $stmt_buyers->fetchAll(PDO::FETCH_ASSOC);

            if ($buyers) {
                $alert_msg = "URGENT RECALL: The product you previously purchased ('$product_name') has been recalled from our marketplace due to safety concerns. Please stop using it immediately and contact support.";
                $stmt_notif = $db->prepare("INSERT INTO notification (user_id, type, message, created_at, is_read) VALUES (?, 'Product Recall', ?, NOW(), 0)");
                
                foreach ($buyers as $buyer) {
                    $stmt_notif->execute([$buyer['user_id'], $alert_msg]);
                }
            }

            $db->commit();
            header("Location: ../views/admin/productRecalls.php?success=recalled&count=" . count($buyers));
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            die("Error: " . $e->getMessage());
        }
    }
}
?>