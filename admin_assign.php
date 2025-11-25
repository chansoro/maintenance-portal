<?php
session_start();

// Check if Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

// --- Database Connection ---
require 'db_connect.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['task_id']) && isset($_POST['worker'])) {
        $sql = "UPDATE tasks SET assigned_worker = :worker WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':worker', $_POST['worker']);
        $stmt->bindParam(':id', $_POST['task_id']);
        $stmt->execute();
    }

    header("Location: admin.php?msg=assigned");

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>