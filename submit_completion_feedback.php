<?php
session_start();

if (!isset($_SESSION['user_id'])) { die("Access Denied"); }

// --- Database Connection ---
require 'db_connect.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['task_id']) && isset($_POST['feedback'])) {
        // Ensure user owns the task for security
        $sql = "UPDATE tasks SET completion_feedback = :feedback WHERE id = :id AND user_id = :uid";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':feedback', $_POST['feedback']);
        $stmt->bindParam(':id', $_POST['task_id']);
        $stmt->bindParam(':uid', $_SESSION['user_id']);
        $stmt->execute();
    }

    header("Location: index.php?msg=feedback_sent");

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>