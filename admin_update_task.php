<?php
session_start();

// --- 1. ADMIN BOUNCER ---
if ( !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' ) {
    die("Access Denied: Admins only.");
}

// --- Database Connection ---
require 'db_connect.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $task_id = $_GET['id'];
    $action = $_GET['action'];
    
    $new_status = ($action == 'done') ? 'done' : 'pending';

    try {
        $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "UPDATE tasks SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $task_id);
        $stmt->execute();

        header("Location: admin.php?msg=updated");
        exit();

    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: admin.php");
    exit();
}
?>
