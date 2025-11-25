<?php
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// 1. Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Get data sent from JavaScript (JSON format)
$data = json_decode(file_get_contents('php://input'), true);

// Basic validation
if (!isset($data['task_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$task_id = $data['task_id'];
$new_status = $data['status'] === 'done' ? 'done' : 'pending'; // Ensure status is valid

// --- 3. Database Connection ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portal_db";
$port = 3307;

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 4. Update the task status ---
    // IMPORTANT: We also check user_id to make sure the user owns this task!
    $sql = "UPDATE tasks SET status = :status WHERE id = :task_id AND user_id = :user_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':user_id', $user_id);

    $stmt->execute();

    // Check if any row was actually updated
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        // This happens if the task ID doesn't exist or doesn't belong to the user
        echo json_encode(['success' => false, 'message' => 'Task not found or permission denied']);
    }

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
?>