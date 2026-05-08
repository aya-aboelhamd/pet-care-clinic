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
    
    // ---------------------------------------------------------
    // 1. إضافة منتج للسلة (مع فحص الروشتة والموانع الطبية)
    // ---------------------------------------------------------
    if (isset($_POST['action']) && $_POST['action'] == 'add_to_cart') {
        $product_id = $_POST['product_id'];
        $pet_id = $_POST['pet_id'] ?? '';
        
        $stmt = $db->prepare("SELECT name, requires_prescription, contraindicated_condition FROM product WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // أ. فحص الروشتة
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
        
        // ب. فحص الموانع الطبية (Contraindications)
        if (!empty($product['contraindicated_condition']) && !empty($pet_id) && $pet_id != 'all') {
            $condition = strtolower(trim($product['contraindicated_condition']));
            $is_contraindicated = false;

            $stmt_med = $db->prepare("SELECT diagnosis FROM medicalrecord WHERE pet_id = ?");
            $stmt_med->execute([$pet_id]);
            $records = $stmt_med->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($records as $r) {
                if (strpos(strtolower($r['diagnosis']), $condition) !== false) {
                    $is_contraindicated = true;
                    break;
                }
            }

            $stmt_pet = $db->prepare("SELECT allergies, medical_notes FROM pet WHERE id = ?");
            $stmt_pet->execute([$pet_id]);
            $pet_data = $stmt_pet->fetch(PDO::FETCH_ASSOC);
            if ($pet_data) {
                $pet_health = strtolower(($pet_data['allergies'] ?? '') . ' ' . ($pet_data['medical_notes'] ?? ''));
                if (strpos($pet_health, $condition) !== false) {
                    $is_contraindicated = true;
                }
            }

            if ($is_contraindicated) {
                $log_msg = "SYSTEM WARNING: Owner attempted to purchase contraindicated item (" . $product['name'] . "). Reason: " . ucfirst($condition) . " restriction.";
                $log_stmt = $db->prepare("INSERT INTO medicalrecord (pet_id, diagnosis, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
                $log_stmt->execute([$pet_id, $log_msg]);

                header("Location: ../views/petOwner/marketplace.php?error=contraindicated&pet_id=" . $pet_id);
                exit();
            }
        }
        
        // ج. إضافة المنتج للسلة فعلياً
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

    // ---------------------------------------------------------
    // 2. إزالة منتج من السلة
    // ---------------------------------------------------------
    if (isset($_POST['action']) && $_POST['action'] == 'remove_item') {
        $item_id = $_POST['item_id'];
        $stmt = $db->prepare("DELETE FROM cartitem WHERE id = ?");
        $stmt->execute([$item_id]);
        header("Location: ../views/petOwner/checkout.php");
        exit();
    }

    // ---------------------------------------------------------
    // 3. تأكيد الطلب والدفع (Checkout / Place Order)
    // ---------------------------------------------------------
    if (isset($_POST['action']) && $_POST['action'] == 'place_order') {
        try {
            $db->beginTransaction();

            // أ. جلب محتويات السلة الخاصة بالمستخدم
            $stmt_cart = $db->prepare("
                SELECT ci.product_id, ci.quantity, p.name, p.price 
                FROM cartitem ci 
                JOIN cart c ON ci.cart_id = c.id 
                JOIN product p ON ci.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt_cart->execute([$user_id]);
            $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

            if (empty($cart_items)) {
                header("Location: ../views/petOwner/checkout.php?error=empty_cart");
                exit();
            }

            // ب. حساب الإجمالي
            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += ($item['price'] * $item['quantity']);
            }

            // ج. إنشاء الطلب الرئيسي
            $delivery_date = date('Y-m-d', strtotime('+3 days')); // ميعاد التوصيل الافتراضي
            $stmt_order = $db->prepare("INSERT INTO orders (user_id, total_amount, status, order_date, delivery_date) VALUES (?, ?, 'Processing', CURRENT_DATE(), ?)");
            $stmt_order->execute([$user_id, $total_amount, $delivery_date]);
            $order_id = $db->lastInsertId();

            // د. حفظ عناصر الطلب (عشان تفيدنا في الـ Recall)
            $stmt_item = $db->prepare("INSERT INTO orderitem (order_id, product_id, name, price, quantity) VALUES (?, ?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $stmt_item->execute([$order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity']]);
            }

            // هـ. إفراغ السلة
            $stmt_clear = $db->prepare("DELETE FROM cartitem WHERE cart_id IN (SELECT id FROM cart WHERE user_id = ?)");
            $stmt_clear->execute([$user_id]);

            $db->commit();
            // نرجعه للماركت بليس برسالة نجاح
            header("Location: ../views/petOwner/marketplace.php?success=order_placed");
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            die("Order Error: " . $e->getMessage());
        }
    }
}
?>