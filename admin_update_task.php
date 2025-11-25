<?php
session_start();

// --- 1. ADMIN BOUNCER ---
// Only allow admins to run this script
if ( !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' ) {
    die("Access Denied: Admins only.");
}

// --- Database Connection ---
require 'db_connect.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $task_id = $_GET['id'];
    $action = $_GET['action'];
    
    // Determine the new status string
    $new_status = ($action == 'done') ? 'done' : 'pending';

    try {
        $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Update the task status
        // Note: We don't check user_id here because Admins can edit ANY task
        $sql = "UPDATE tasks SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $task_id);
        $stmt->execute();

        // Redirect back to the Admin Portal
        header("Location: admin.php?msg=updated");
        exit();

    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    // If someone tries to open this file without clicking a button
    header("Location: admin.php");
    exit();
}
?>