<?php
session_start();
require_once '../models/Database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_dispute'])) {
    $booking_id = $_POST['booking_id'];
    $reason = $_POST['reason'];
    $owner_id = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT INTO disputes (owner_id, booking_id, reason, status, created_at) VALUES (?, ?, ?, 'Open', CURRENT_TIMESTAMP)");
    $stmt->execute([$owner_id, $booking_id, $reason]);

    header("Location: ../views/petOwner/booking.php?dispute_success=1");
    exit();
}
?>