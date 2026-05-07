<?php
session_start();
require_once '../models/Database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'recall_product') {
    $product_id = $_POST['product_id'];

    $stmt = $db->prepare("UPDATE product SET is_recalled = 1 WHERE id = ?");
    $stmt->execute([$product_id]);

    $stmt = $db->prepare("SELECT name FROM product WHERE id = ?");
    $stmt->execute([$product_id]);
    $product_name = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT DISTINCT o.user_id FROM orders o JOIN orderitem oi ON o.id = oi.order_id WHERE oi.product_id = ?");
    $stmt->execute([$product_id]);
    $affected_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($affected_users)) {
        $stmt = $db->prepare("INSERT INTO activitylog (action, details, created_at) VALUES ('Product Recall', ?, NOW())");
        $stmt->execute(["Product ID {$product_id} recalled. No affected users found."]);
    } else {
        $notification_msg = "URGENT RECALL: The product '{$product_name}' you previously purchased has been recalled by the manufacturer. Please stop using it immediately and contact the clinic for return instructions.";

        foreach ($affected_users as $uid) {
            $delivery_success = false;
            $attempts = 0;

            while (!$delivery_success && $attempts < 3) {
                try {
                    $stmt = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Recall', ?, 0)");
                    $stmt->execute([$uid, $notification_msg]);
                    $delivery_success = true;
                } catch (Exception $e) {
                    $attempts++;
                    if ($attempts >= 3) {
                        $stmt_log = $db->prepare("INSERT INTO activitylog (action, details, created_at) VALUES ('Notification Delivery Failed', ?, NOW())");
                        $stmt_log->execute(["Failed to send recall alert to User ID {$uid} after 3 attempts."]);
                    }
                }
            }
        }
    }

    header("Location: ../views/admin/productRecalls.php?success=1");
    exit();
}
?>