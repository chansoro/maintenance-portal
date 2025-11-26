<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) { die("Access Denied"); }

try {
    $stmt = $conn->prepare("INSERT INTO feedback (user_id, subject, details) VALUES (:uid, :sub, :det)");
    $stmt->execute([
        ':uid' => $_SESSION['user_id'],
        ':sub' => $_POST['subject'],
        ':det' => $_POST['details']
    ]);
    header("Location: index.php?status=feedback_success");
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
