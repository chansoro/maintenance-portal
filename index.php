<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in
// If 'user_id' is NOT set in the session, they are not logged in
if ( !isset($_SESSION['user_id']) ) {
    
    // Redirect them to the login page
    header("Location: login.html");
    exit; // Stop the rest of the page from loading
}

// If the script continues, the user is logged in!
// We can grab their username to display it.
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- Database Connection ---
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "portal_db";
$port = 3307;

$tasks = []; // Initialize an empty array

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 1. Get filter values from the URL (using GET) ---
    $search_term = $_GET['search'] ?? '';   // default to empty
    $category_filter = $_GET['category'] ?? ''; // default to empty

    // --- 2. Build the SQL Query ---
    
    // Base query always filters by user_id
    $sql = "SELECT * FROM tasks WHERE user_id = :user_id";
    $params = [':user_id' => $user_id]; // This array will hold our bindings

    // If the user typed something in the search box...
    if ( !empty($search_term) ) {
        $sql .= " AND details LIKE :search";
        $params[':search'] = '%' . $search_term . '%';
    }
    
    // If the user selected a category...
    if ( !empty($category_filter) ) {
        $sql .= " AND maintenance_area = :category";
        $params[':category'] = $category_filter;
    }

    // Finally, add the ordering
    $sql .= " ORDER BY submission_date DESC";

    // --- 3. Prepare and Execute the Query ---
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    // Fetch all results into our $tasks array
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Handle database errors gracefully
    $db_error = "Error connecting to database: " . $e->getMessage();
}
$conn = null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Suggestion Portal - Maintenance Scheduler</title>

    <link rel="stylesheet" href="style-kineme.css">

</head>
<body>

    <header>
        <h1>🛠️ Feedback & Suggestion Portal With Preventive & Maintenance Scheduler 🛠️</h1>
        
        <div style="background-color: #003a75; padding: 10px; border-radius: 5px; margin-top: 15px; display: block; width: fit-content; margin-left: auto; margin-right: auto;">
            
            Welcome, <strong><?php echo htmlspecialchars($username); ?>!</strong>
            <a href="logout.php" style="color: #ffb3b3; margin-left: 20px; text-decoration: underline;">Logout</a>
            
            <?php
            // --- START: Admin-Only Link ---
            if ($role === 'admin') {
                echo '<a href="admin.php" style="color: #a7ffa7; margin-left: 20px; text-decoration: underline;">Admin Portal</a>';
            }
            // --- END: Admin-Only Link ---
            ?>

        </div>
        
    </header>

    <div class="container">
        
	<?php if ($role !== 'admin'): ?>

        <div class="card">
            <h2 style="color: #007bff;">Submit Feedback</h2>
            <p style="color: #666;">Your ideas help us automate and optimize our maintenance schedules.</p>

            <form action="submit_feedback.php" method="post">
                
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" placeholder="e.g., Chatbot Idea" required>

                <label for="details">Feedback / Suggestion:</label>
                <textarea id="details" name="details" rows="5" placeholder="Describe your idea or feedback for the admins..." required></textarea>

                <button type="submit">Submit Feedback</button>

            </form>
        </div>
	<?php endif; ?>

        <div class="card" style="background-color: #f9f9f9;">
            
            <h2>Maintenance List & Schedule</h2>
            <p style="color: #666;">Optimized tasks and automated checks from the maintenance engine.</p>

            <form action="index.php" method="GET" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="search" placeholder="Search details..." style="width: 60%; margin-bottom: 0;">
                <select name="category" style="width: 35%; margin-bottom: 0;">
                    <option value="">All Categories</option>
                    <option value="HVAC">HVAC System</option>
                    <option value="Electrical">Electrical Wiring</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="IT">IT Infrastructure</option>
                    <option value="General">General Facility</option>
                </select>
                <button type="submit" style="width: 100px; margin: 0;">Filter</button>
            </form>
            <div class="schedule-list">
                
                <?php
                // Check if there was a database error
                if (isset($db_error)) {
                    echo '<p style="color: red;">' . htmlspecialchars($db_error) . '</p>';
                } 
                // Check if the $tasks array is empty
                elseif (empty($tasks)) {
                    echo '<p style="text-align: center; color: #666;">You have no tasks scheduled. Add one below!</p>';
                } 
                // If we have tasks, loop through and display them
                else {
                    foreach ($tasks as $row) {
                        
                        // --- 1. Priority Badge ---
                        $badge_class = 'badge-primary'; // Default
                        $priority_text = htmlspecialchars($row['priority']);
                        
                        if ($row['priority'] == 'Urgent') { $badge_class = 'badge-danger'; } 
                        elseif ($row['priority'] == 'High') { $badge_class = 'badge-warning'; } 
                        elseif ($row['priority'] == 'Low') { $badge_class = 'badge-success'; }

                        // --- 2. Deadline Text ---
                        $deadline_text = 'No deadline set';
                        if (!empty($row['deadline'])) {
                            $date = new DateTime($row['deadline']);
                            $deadline_text = 'Due: ' . $date->format('M j, Y');
                        }

                        // --- 3. Status and Checkbox ---
                        $is_done = ($row['status'] === 'done');
                        $status_text = $is_done ? 'Done' : 'Pending';
                        $status_badge_class = $is_done ? 'badge-success' : 'badge-secondary'; // Using a gray badge for pending
                        $checked_attribute = $is_done ? 'checked' : '';

                        // --- 4. Print the HTML ---
                        echo '<div class="schedule-item" style="flex-direction: column; align-items: flex-start; gap: 8px; padding-bottom: 10px;">'; // Added padding
                            
                            // Top row: Details and Status Badge
                            echo '<div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">';
                                echo '<span style="font-weight: bold; font-size: 16px;">' . htmlspecialchars($row['details']) . '</span>';
                                echo '<span class="status-badge ' . $status_badge_class . '" style="font-size: 11px;">' . $status_text . '</span>'; // This line generates the status badge
                            echo '</div>';
                            
                            // Middle row: Priority and Deadline
                            echo '<div style="display: flex; justify-content: space-between; width: 100%; margin-top: 5px;">';
                                echo '<span class="status-badge ' . $badge_class . '">' . $priority_text . '</span>';
                                echo '<span style="color: #666; font-size: 14px;">' . $deadline_text . '</span>';
                            echo '</div>';

                            // Bottom row: Checkbox
                            echo '<div style="margin-top: 8px;">';
                                echo '<label style="font-size: 14px; cursor: pointer; display: inline-flex; align-items: center;">';
                                    echo '<input type="checkbox" class="task-status-checkbox" data-task-id="' . $row['id'] . '" ' . $checked_attribute . ' style="margin-right: 5px; cursor: pointer;">';
                                    echo 'Mark as Done';
                                echo '</label>';
                            echo '</div>';

                        echo '</div>'; // End schedule-item
                    } // End foreach
                } // End else
                ?>

            </div> <hr style="margin: 20px 0;">

            <h3 style="color: #007bff; margin-bottom: 15px;">Add a New Task</h3>
            <form action="submit_task.php" method="post">
                <label for="task_details">Task Details:</label>
                <input type="text" id="task_details" name="details" placeholder="e.g., Clean A/C filter" required>

                <label for="task_area">Category:</label>
                <select id="task_area" name="area" required>
                    <option value="" disabled selected>Select an area</option>
                    <option value="HVAC">HVAC System</option>
                    <option value="Electrical">Electrical Wiring</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="IT">IT Infrastructure</option>
                    <option value="General">General Facility</option>
                </select>

                <label for="task_priority">Priority:</label>
                <select id="task_priority" name="priority" required>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                </select>

                <label for="task_deadline">Deadline (Optional):</label>
                <input type="date" id="task_deadline" name="deadline" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">

                <button type="submit" style="background-color: #007bff;">Add Task to Schedule</button>
            </form>
            <div style="text-align: center; margin-top: 25px;">
                 <a href="scheduler.php" style="color: #0099cc; text-decoration: none; font-weight: bold;">View Full Scheduler Dashboard →</a>
            </div>
        </div>
        
    </div> <div id="chat-bubble">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: white;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    </div>

    <div id="chat-window">
        <div id="chat-header">
            <h3>Chat Assistant</h3>
            <button id="chat-close">&times;</button>
        </div>
        <div id="chat-log">
            <div class="chat-message bot-message">
                <p>Hello! How can I help you today? You can ask me about 'feedback', 'maintenance', or 'account'.</p>
            </div>
        </div>
        <form id="chat-form">
            <input type="text" id="chat-input" placeholder="Type your message..." autocomplete="off">
            <button type="submit">Send</button>
        </form>
    </div>
    <script>
        // Get all the HTML elements we need
        const chatBubble = document.getElementById('chat-bubble');
        const chatWindow = document.getElementById('chat-window');
        const chatClose = document.getElementById('chat-close');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const chatLog = document.getElementById('chat-log');

        // --- 1. Toggle Chat Window ---
        chatBubble.addEventListener('click', () => {
            chatWindow.classList.toggle('open');
        });
        chatClose.addEventListener('click', () => {
            chatWindow.classList.remove('open');
        });

        // --- 2. Handle Form Submission ---
        chatForm.addEventListener('submit', (event) => {
            event.preventDefault(); 
            const userMessage = chatInput.value.trim();
            if (userMessage === "") return; 
            addMessage(userMessage, 'user');
            chatInput.value = "";
            setTimeout(() => {
                const botResponse = getBotResponse(userMessage);
                addMessage(botResponse, 'bot');
            }, 500);
        });

        // --- 3. Add a Message to the Chat Log ---
        function addMessage(text, sender) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('chat-message');
            messageElement.classList.add(sender + '-message');
            messageElement.innerHTML = `<p>${text}</p>`; 
            chatLog.appendChild(messageElement);
            chatLog.scrollTop = chatLog.scrollHeight;
        }

        // --- 4. The Bot's "Brain" ---
        function getBotResponse(input) {
            const text = input.toLowerCase();
            if (text.includes('hello') || text.includes('hi')) {
                return "Hello there! How can I assist you?";
            }
            if (text.includes('feedback') || text.includes('suggestion')) {
                return "You can submit feedback using the 'Submit Feedback' form on the left.";
            }
            if (text.includes('maintenance') || text.includes('task') || text.includes('schedule')) {
                return "You can add a new task using the 'Add a New Task' form, and view your list above it. You can also search them by category.";
            }
            if (text.inscludes('search') || text.includes('category')) {
                return "Use the search bar and category dropdown above your task list to filter your tasks.";
            }
            if (text.includes('account') || text.includes('password') || text.includes('logout')) {
                return "You can log out by clicking the 'Logout' link in the header. For other account issues, please contact an administrator.";
            }
            if (text.includes('help')) {
                return "I can answer questions about: <br> • Feedback <br> • My Task List <br> • My Account";
            }
            return "I'm sorry, I don't understand that. Please try asking about 'feedback', 'task', or 'account'.";
        }
    </script>
<script>
        // Find all the checkboxes
        const checkboxes = document.querySelectorAll('.task-status-checkbox');

        // Add an event listener to each checkbox
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const taskId = this.getAttribute('data-task-id');
                const newStatus = this.checked ? 'done' : 'pending';

                // Send the update to the server
                updateTaskStatus(taskId, newStatus);
            });
        });

        // Function to send the update via Fetch API
        async function updateTaskStatus(id, status) {
            try {
                const response = await fetch('update_task_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ task_id: id, status: status })
                });

                const result = await response.json();

                if (result.success) {
                    // Optionally: Reload the page to see the updated status badge
                    // Or you could update the badge text directly with more JS
                    location.reload(); 
                } else {
                    alert('Error updating task: ' + result.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('An error occurred while updating the task.');
            }
        }
    </script>
</body>
</html>