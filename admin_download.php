<?php
// --- DEBUG MODE: ON ---
// This will force the server to show us the specific error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Check Login
if ( !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' ) {
    die("Access Denied: You are not logged in as Admin.");
}

// 2. Check if db_connect.php exists
if (!file_exists('db_connect.php')) {
    die("CRITICAL ERROR: The file 'db_connect.php' was not found in this folder. Did you upload it?");
}

// 3. Try to Require the file
require 'db_connect.php';

// 4. Check if connection works
if (!isset($conn)) {
    die("CRITICAL ERROR: Connected to db_connect.php, but the \$conn variable is missing. Check your db_connect.php file.");
}

if (isset($_GET['type'])) {
    $type = $_GET['type'];
    $filename = $type . "_report_" . date('Y-m-d') . ".csv";
    
    try {
        // Headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');

        // --- EXPORT LOGIC ---
        if ($type == 'feedback') {
            fputcsv($output, ['Submitted By', 'Details', 'Assigned To', 'User Rating/Feedback', 'Date Submitted']);
            $sql = "SELECT u.username, t.details, t.assigned_worker, t.completion_feedback, t.submission_date 
                    FROM tasks t JOIN users u ON t.user_id = u.id ORDER BY t.submission_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $worker = !empty($row['assigned_worker']) ? $row['assigned_worker'] : 'Unassigned';
                $rating = !empty($row['completion_feedback']) ? $row['completion_feedback'] : 'Waiting for completion';
                fputcsv($output, [$row['username'], $row['details'], $worker, $rating, $row['submission_date']]);
            }

        } elseif ($type == 'tasks') {
            fputcsv($output, ['Submitted By', 'Category', 'Priority', 'Deadline', 'Details', 'Status', 'Date Submitted']);
            $sql = "SELECT u.username, t.maintenance_area, t.priority, t.deadline, t.details, t.status, t.submission_date 
                    FROM tasks t JOIN users u ON t.user_id = u.id ORDER BY t.submission_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $deadline = !empty($row['deadline']) ? $row['deadline'] : 'No deadline set';
                $status = ucfirst($row['status']); 
                fputcsv($output, [$row['username'], $row['maintenance_area'], $row['priority'], $deadline, $row['details'], $status, $row['submission_date']]);
            }

        } elseif ($type == 'users') {
            fputcsv($output, ['ID', 'Username', 'Email', 'Role', 'Date Joined']);
            $sql = "SELECT id, username, email, role, created_at FROM users ORDER BY id ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [$row['id'], $row['username'], $row['email'], ucfirst($row['role']), $row['created_at']]);
            }
            
        } elseif ($type == 'general_feedback') {
            fputcsv($output, ['Submitted By', 'Subject', 'Message', 'Date Submitted']);
            $sql = "SELECT u.username, f.subject, f.details, f.submitted_at 
                    FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.submitted_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [$row['username'], $row['subject'], $row['details'], $row['submitted_at']]);
            }
        }

        fclose($output);
        exit();

    } catch(PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    echo "No download type specified.";
}
?>
