<?php
session_start();
require_once '../models/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/Auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_lost'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $pet_id = $_POST['pet_id'];
    
    $location = $_POST['location'];
    $date_lost = $_POST['date_lost'];

    $stmt = $db->prepare("UPDATE pet SET is_lost = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);

    $stmt_pet = $db->prepare("SELECT name FROM pet WHERE id = ?");
    $stmt_pet->execute([$pet_id]);
    $pet_name = $stmt_pet->fetchColumn() ?: 'a pet';

    $message = "🚨 URGENT: A pet named " . $pet_name . " was lost near " . $location . " on " . $date_lost . ". Please keep an eye out!";
    
    $stmt_users = $db->prepare("SELECT id FROM users WHERE id != ?");
    $stmt_users->execute([$user_id]);
    $all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    $notif_stmt = $db->prepare("INSERT INTO notification (user_id, type, message, is_read) VALUES (?, 'Lost Pet Alert', ?, 0)");
    
    foreach ($all_users as $u) {
        $notif_stmt->execute([$u['id'], $message]);
    }

    header("Location: ../views/petOwner/reportLostPet.php?success=1");
    exit();
}