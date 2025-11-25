<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in
if ( !isset($_SESSION['user_id']) ) {
    header("Location: login.html");
    exit;
}

// Get user info
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// --- Database Connection ---
$servername = "sql207.infinityfree.com";
$db_username = "if0_40254173";
$db_password = "107MADAFAKA";
$dbname = "if0_40254173_portal";
$port = 3306;

$all_suggestions = [];

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Fetch ALL Suggestions for this user ---
    $sql = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY submission_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $all_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $db_error = "Error connecting to database: ". $e->getMessage();
}
$conn = null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Scheduler - Feedback Portal</title>
    <link rel="stylesheet" href="style-kineme.css">
    
    <style>
        .full-width-card {
            flex: 1;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        
        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>

    <header>
        <h1>🛠️ Full Maintenance Scheduler 🛠️</h1>
        
        <div style="background-color: #003a75; padding: 10px; border-radius: 5px; margin-top: 15px; display: inline-block;">
            Welcome, <strong><?php echo htmlspecialchars($username); ?>!</strong>
            <a href="logout.php" style="color: #ffb3b3; margin-left: 20px; text-decoration: underline;">Logout</a>
        </div>
    </header>

    <div class="container">
        
        <div class="card full-width-card">
            
            <a href="index.php" style="text-decoration: none; font-weight: bold; color: #007bff;">&larr; Back to Dashboard</a>
            
            <h2 style="color: #0099cc; margin-top: 20px;">All Submitted Tasks</h2>

            <?php if (isset($db_error)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($db_error); ?></p>
            <?php elseif (empty($all_tasks)): ?>
                <p style="text-align: center; color: #666; padding: 30px 0;">You have not submitted any maintenance tasks.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                           <th>Area / Category</th>
<th>Priority</th>
<th>Deadline</th>
<th>Details</th>
<th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php foreach ($all_tasks as $task): ?>
        <tr>
            <td><?php echo htmlspecialchars($task['maintenance_area']); ?></td>
            <td><?php echo htmlspecialchars($task['priority']); ?></td>
            <td><?php echo htmlspecialchars($task['deadline']); ?></td> <td><?php echo htmlspecialchars($task['details']); ?></td>
            <td><?php echo htmlspecialchars($task['submission_date']); ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
