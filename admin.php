<?php
// Start the session to access session variables
session_start();

// --- 1. ADMIN-ONLY Bouncer ---
// Check if user is logged in AND if their role is 'admin'
if ( !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' ) {
    // If not logged in or not an admin, send them to the login page
    header("Location: login.html?status=admin_required");
    exit;
}

// If we are here, the user is an admin.
$username = $_SESSION['username'];

// --- 2. Database Connection ---
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "portal_db";
$port = 3307;

$all_suggestions = [];
$all_users = [];

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 3. Fetch ALL Suggestions (with user info) ---
    // We use a JOIN to connect 'suggestions' and 'users' tables
    $sql_suggestions = "SELECT feedback.*, users.username 
                        FROM feedback 
                        JOIN users ON feedback.user_id = users.id 
                        ORDER BY feedback.submitted_at DESC";
    
    $stmt_suggestions = $conn->prepare($sql_suggestions);
    $stmt_suggestions->execute();
    $all_suggestions = $stmt_suggestions->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. Fetch ALL Users ---
    $sql_users = "SELECT id, username, email, role, created_at FROM users ORDER BY id ASC";
    $stmt_users = $conn->prepare($sql_users);
    $stmt_users->execute();
    $all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $db_error = "Error connecting to database: " . $e->getMessage();
}
$conn = null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN PORTAL</title>
    <link rel="stylesheet" href="style-kineme.css">
    
    <style>
        .full-width-card { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f9f9f9; font-weight: bold; }
        tr:nth-child(even) { background-color: #fdfdfd; }
    </style>
</head>
<body>

    <header>
        <h1>🛠️ ADMIN PORTAL</h1>
        
        <div style="background-color: #003a75; padding: 10px; border-radius: 5px; margin-top: 15px; display: inline-block;">
            Welcome, Admin <strong><?php echo htmlspecialchars($username); ?>!</strong>
            <a href="logout.php" style="color: #ffb3b3; margin-left: 20px; text-decoration: underline;">Logout</a>
        </div>
    </header>

    <div class="container" style="flex-direction: column; gap: 30px;">
        
        <a href="index.php" style="text-decoration: none; font-weight: bold; color: #007bff;">&larr; Back to Main Portal</a>

        <div class="card full-width-card">
            <h2 style="color: #0099cc;">All Users Feedback</h2>
            
            <?php if (isset($db_error)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($db_error); ?></p>
            <?php elseif (empty($all_suggestions)): ?>
                <p>No suggestions submitted by any user yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Submitted By</th>
                            <th>Subject</th>
                            <th>Details</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_suggestions as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['username']); ?></td>
                                <td><?php echo htmlspecialchars($task['subject']); ?></td>
                                <td><?php echo htmlspecialchars($task['details']); ?></td>
                                <td><?php echo htmlspecialchars($task['submitted_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card full-width-card">
            <h2 style="color: #0099cc;">All User Accounts</h2>
            
            <?php if (isset($db_error)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($db_error); ?></p>
            <?php elseif (empty($all_users)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Date Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>