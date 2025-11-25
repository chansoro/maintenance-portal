<?php
session_start();

// --- 1. ADMIN BOUNCER ---
if ( !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' ) {
    die("Access Denied");
}

// --- Database Connection ---
require 'db_connect.php';

if (isset($_GET['type'])) {
    $type = $_GET['type'];
    $filename = $type . "_report_" . date('Y-m-d') . ".csv";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // --- 3. Set Headers to Force Download ---
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Open the output stream
        $output = fopen('php://output', 'w');

        // =============================================
        // EXPORT 1: FEEDBACK & ASSIGNMENTS VIEW
        // =============================================
        if ($type == 'feedback') {
            // Headers match the "All Users Feedback & Assignments" table
            fputcsv($output, ['Submitted By', 'Details', 'Assigned To', 'User Rating/Feedback', 'Date Submitted']);
            
            // Query the TASKS table
            $sql = "SELECT u.username, t.details, t.assigned_worker, t.completion_feedback, t.submission_date 
                    FROM tasks t 
                    JOIN users u ON t.user_id = u.id 
                    ORDER BY t.submission_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Format fields
                $worker = !empty($row['assigned_worker']) ? $row['assigned_worker'] : 'Unassigned';
                $rating = !empty($row['completion_feedback']) ? $row['completion_feedback'] : 'Waiting for completion';

                fputcsv($output, [
                    $row['username'],
                    $row['details'],
                    $worker,
                    $rating,
                    $row['submission_date']
                ]);
            }

        // =============================================
        // EXPORT 2: MAINTENANCE SCHEDULE VIEW
        // =============================================
        } elseif ($type == 'tasks') {
            // Headers match the "All Users Maintenance Schedule" table
            // Note: "Action" is excluded because CSVs cannot contain buttons
            fputcsv($output, ['Submitted By', 'Category', 'Priority', 'Deadline', 'Details', 'Status', 'Date Submitted']);
            
            $sql = "SELECT u.username, t.maintenance_area, t.priority, t.deadline, t.details, t.status, t.submission_date 
                    FROM tasks t 
                    JOIN users u ON t.user_id = u.id 
                    ORDER BY t.submission_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                
                $deadline = !empty($row['deadline']) ? $row['deadline'] : 'No deadline set';
                $status = ucfirst($row['status']); // "done" -> "Done"

                fputcsv($output, [
                    $row['username'],
                    $row['maintenance_area'],
                    $row['priority'],
                    $deadline,
                    $row['details'],
                    $status,
                    $row['submission_date']
                ]);
            }

        // =============================================
        // EXPORT 3: USERS
        // =============================================
        } elseif ($type == 'users') {
            fputcsv($output, ['ID', 'Username', 'Email', 'Role', 'Date Joined']);
            
            $sql = "SELECT id, username, email, role, created_at FROM users ORDER BY id ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['username'],
                    $row['email'],
                    ucfirst($row['role']),
                    $row['created_at']
                ]);
            }
        }

        fclose($output);
        exit();

    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>