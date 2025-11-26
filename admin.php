<?php
session_start();

// --- 1. ADMIN-ONLY Bouncer ---
if ( !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' ) {
    header("Location: login.html?status=admin_required");
    exit;
}

$username = $_SESSION['username'];

// --- 2. Database Connection ---
require 'db_connect.php';

$all_tasks = [];
$all_users = [];
$all_general_feedback = [];

try {
    // --- 3. Fetch ALL Tasks ---
    $sql_tasks = "SELECT tasks.*, users.username 
                  FROM tasks 
                  JOIN users ON tasks.user_id = users.id 
                  ORDER BY tasks.submission_date DESC";
    $stmt_tasks = $conn->prepare($sql_tasks);
    $stmt_tasks->execute();
    $all_tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. Fetch ALL Users ---
    $sql_users = "SELECT id, username, email, role, created_at FROM users ORDER BY id ASC";
    $stmt_users = $conn->prepare($sql_users);
    $stmt_users->execute();
    $all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    // --- 5. Fetch General Feedback ---
    $sql_feedback = "SELECT feedback.*, users.username 
                     FROM feedback 
                     JOIN users ON feedback.user_id = users.id 
                     ORDER BY feedback.submitted_at DESC";
    $stmt_feedback = $conn->prepare($sql_feedback);
    $stmt_feedback->execute();
    $all_general_feedback = $stmt_feedback->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $db_error = "Error connecting to database: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal</title>
    <link rel="stylesheet" href="style-kineme.css">
    
    <style>
        .full-width-card { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: middle; }
        th { background-color: #f9f9f9; font-weight: bold; font-size: 14px; }
        tr:nth-child(even) { background-color: #fdfdfd; }
        td { font-size: 13px; }
    </style>
</head>
<body>

    <header>
        <h1>🛠️ Admin Portal 🛠️</h1>
        
        <div class="user-menu">
        <span>Welcome, Admin <strong><?php echo htmlspecialchars($username); ?>!</strong></span>

        <a href="logout.php" class="header-btn logout-link">Logout</a>
    </div>
    </header>

    <div class="container" style="flex-direction: column; gap: 30px;">
        
        <a href="index.php" style="text-decoration: none; font-weight: bold; color: #007bff;">&larr; Back to Main Portal</a>

        <div class="card full-width-card" style="border-left: 5px solid #007bff;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="color: #007bff; margin: 0;">General Facility Suggestions</h2>
                <a href="admin_download.php?type=general_feedback" style="background-color: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;">Download CSV</a>
            </div>
            <p style="color: #666; font-size: 14px;">Ideas and suggestions submitted via the "Give Feedback" button.</p>
            
            <?php if (empty($all_general_feedback)): ?>
                <p>No general suggestions submitted yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Submitted By</th>
                            <th>Subject</th>
                            <th>Details / Message</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_general_feedback as $item): ?>
                            <tr>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($item['username']); ?></td>
                                <td><?php echo htmlspecialchars($item['subject']); ?></td>
                                <td><?php echo htmlspecialchars($item['details']); ?></td>
                                <td><?php echo htmlspecialchars($item['submitted_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card full-width-card" style="border-left: 5px solid #007bff;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="color: #007bff; margin: 0;">All Users Feedback & Assignments</h2>
                <a href="admin_download.php?type=feedback" style="background-color: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;">Download CSV</a>
            </div>
            <p style="color: #666; font-size: 14px;">Manage worker assignments and view user feedback upon completion.</p>
            
            <?php if (isset($db_error)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($db_error); ?></p>
            <?php elseif (empty($all_tasks)): ?>
                <p>No data available.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Submitted By</th>
                            <th style="width: 30%;">Details</th>
                            <th>Assigned To</th>
                            <th>User Rating/Feedback</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tasks as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['username']); ?></td>
                                <td><?php echo htmlspecialchars($task['details']); ?></td>
                                
                                <td>
                                    <form action="admin_assign.php" method="POST" style="display:flex; gap:5px; align-items: center;">
                                        
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        
                                        <input type="text" name="worker" 
                                               value="<?php echo htmlspecialchars($task['assigned_worker'] ?? ''); ?>" 
                                               placeholder="Enter name" 
                                               style="width: 120px; height: 30px; padding: 0 8px; font-size: 12px; border: 1px solid #ccc; border-radius: 3px; margin: 0; box-sizing: border-box;">
                                        
                                        <button type="submit" style="height: 30px; padding: 0 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; margin: 0; font-size: 12px; box-sizing: border-box;">Save</button>
                                    
                                    </form>
                                </td>

                                <td>
                                    <?php 
                                    if (!empty($task['completion_feedback'])) {
                                        echo '<span style="color: #28a745; font-weight: bold;">★</span> <span style="color: #555; font-style: italic;">"' . htmlspecialchars($task['completion_feedback']) . '"</span>';
                                    } else {
                                        echo '<span style="color: #ccc; font-size: 12px;">(Waiting for completion)</span>';
                                    }
                                    ?>
                                </td>

                                <td><?php echo htmlspecialchars($task['submission_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card full-width-card" style="border-left: 5px solid #007bff;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="color: #007bff; margin: 0;">All Users Maintenance Schedule</h2>
                <a href="admin_download.php?type=tasks" style="background-color: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;">Download CSV</a>
            </div>
            <p style="color: #666; font-size: 14px;">Track priorities, deadlines, and update task status.</p>
            
            <?php if (empty($all_tasks)): ?>
                <p>No tasks available.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Submitted By</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Deadline</th>
                            <th style="width: 30%;">Details</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tasks as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['username']); ?></td>
                                <td><?php echo htmlspecialchars($task['maintenance_area']); ?></td>
                                
                                <td>
                                    <?php
                                    $p_color = 'black';
                                    if($task['priority'] == 'Urgent') $p_color = '#dc3545';
                                    if($task['priority'] == 'High') $p_color = '#ffc107';
                                    if($task['priority'] == 'Low') $p_color = '#28a745';
                                    echo '<span style="color:'.$p_color.'; font-weight:bold;">'.htmlspecialchars($task['priority']).'</span>';
                                    ?>
                                </td>

                                <td><?php echo htmlspecialchars($task['deadline'] ?? 'N/A'); ?></td> 
                                <td><?php echo htmlspecialchars($task['details']); ?></td>
                                
                                <td>
                                    <?php 
                                    if ($task['status'] == 'done') {
                                        echo '<span style="background-color: #28a745; color: white; padding: 3px 8px; border-radius: 10px; font-size: 11px;">Done</span>';
                                    } else {
                                        echo '<span style="background-color: #6c757d; color: white; padding: 3px 8px; border-radius: 10px; font-size: 11px;">Pending</span>';
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php if ($task['status'] == 'pending'): ?>
                                        <a href="admin_update_task.php?id=<?php echo $task['id']; ?>&action=done" 
                                           style="background-color: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; white-space: nowrap;">
                                           Mark Done
                                        </a>
                                    <?php else: ?>
                                        <a href="admin_update_task.php?id=<?php echo $task['id']; ?>&action=pending" 
                                           style="background-color: #6c757d; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; white-space: nowrap;">
                                           Re-open
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($task['submission_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card full-width-card" style="border-left: 5px solid #007bff;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="color: #007bff; margin: 0;">All User Accounts</h2>
                <a href="admin_download.php?type=users" style="background-color: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;">Download CSV</a>
            </div>
            
            <?php if (empty($all_users)): ?>
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
