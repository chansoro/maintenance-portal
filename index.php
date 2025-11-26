<?php
session_start();

if ( !isset($_SESSION['user_id']) ) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

require 'db_connect.php';

$tasks = []; 

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 1. Get filter values ---
    $search_term = $_GET['search'] ?? '';   
    $category_filter = $_GET['category'] ?? ''; 

    // --- 2. Build the SQL Query ---
    $sql = "SELECT * FROM tasks WHERE user_id = :user_id";
    $params = [':user_id' => $user_id]; 

    if ( !empty($search_term) ) {
        $sql .= " AND details LIKE :search";
        $params[':search'] = '%' . $search_term . '%';
    }
    
    if ( !empty($category_filter) ) {
        $sql .= " AND maintenance_area = :category";
        $params[':category'] = $category_filter;
    }

    $sql .= " ORDER BY submission_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Maintenance Scheduler</title>
    <link rel="stylesheet" href="style-kineme.css">
</head>
<body>

    <header>
        <h1>🛠️ Preventive Maintenance Scheduler 🛠️</h1>
        
        <div class="user-menu">
            <span>Welcome, <strong><?php echo htmlspecialchars($username); ?>!</strong></span>
            
            <span style="opacity: 0.5;">|</span>

            <button id="openFeedback" class="header-btn">Give Feedback</button>
            
            <a href="logout.php" class="header-btn logout-link">Logout</a>
            
            <?php if ($role === 'admin'): ?>
                <span style="opacity: 0.5;">|</span>
                <a href="admin.php" class="header-btn" style="color: #a7ffa7;">Admin Portal</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        
        <div class="card" style="background-color: #f9f9f9;">
            
            <h2>Maintenance Schedule</h2>
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
                if (isset($db_error)) {
                    echo '<p style="color: red;">' . htmlspecialchars($db_error) . '</p>';
                } elseif (empty($tasks)) {
                    echo '<p style="text-align: center; color: #666;">You have no tasks scheduled. Add one below!</p>';
                } else {
                    foreach ($tasks as $row) {
                        
                        $badge_class = 'badge-primary'; 
                        $priority_text = htmlspecialchars($row['priority']);
                        if ($row['priority'] == 'Urgent') { $badge_class = 'badge-danger'; } 
                        elseif ($row['priority'] == 'High') { $badge_class = 'badge-warning'; } 
                        elseif ($row['priority'] == 'Low') { $badge_class = 'badge-success'; }

                        $deadline_text = 'No deadline set';
                        if (!empty($row['deadline'])) {
                            try {
                                $date = new DateTime($row['deadline']);
                                $deadline_text = 'Due: ' . $date->format('M j, Y');
                            } catch (Exception $e) {}
                        }

                        $is_done = ($row['status'] === 'done');
                        $status_text = $is_done ? 'Done' : 'Pending';
                        $status_badge_class = $is_done ? 'badge-success' : 'badge-secondary';

                        echo '<div class="schedule-item" style="flex-direction: column; align-items: flex-start; gap: 8px; padding-bottom: 15px;">';
                            
                            echo '<div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">';
                                echo '<span style="font-weight: bold; font-size: 16px;">' . htmlspecialchars($row['details']) . '</span>';
                                echo '<span class="status-badge ' . $status_badge_class . '" style="font-size: 11px;">' . $status_text . '</span>';
                            echo '</div>';
                            
                            echo '<div style="display: flex; justify-content: space-between; width: 100%; margin-top: 5px;">';
                                echo '<span class="status-badge ' . $badge_class . '">' . $priority_text . '</span>';
                                echo '<span style="color: #666; font-size: 14px;">' . $deadline_text . '</span>';
                            echo '</div>';

                            if (!empty($row['assigned_worker'])) {
                                echo '<div style="font-size: 13px; color: #007bff; margin-top: 5px;">';
                                    echo '👷 Assigned to: <strong>' . htmlspecialchars($row['assigned_worker']) . '</strong>';
                                echo '</div>';
                            }

                            if ($is_done) {
                                echo '<div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; width: 100%;">';
                                
                                if (!empty($row['completion_feedback'])) {
                                    echo '<span style="font-size: 13px; color: green;">✔ Feedback sent: "' . htmlspecialchars($row['completion_feedback']) . '"</span>';
                                } else {
                                    echo '<form action="submit_completion_feedback.php" method="POST" style="display: flex; gap: 10px; width: 100%; align-items: center;">';
                                        
                                        echo '<input type="hidden" name="task_id" value="' . $row['id'] . '">';
                                        
                                        echo '<input type="text" name="feedback" placeholder="How was the work?" required style="flex-grow: 1; min-width: 0; height: 38px; padding: 0 10px; font-size: 13px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; margin: 0;">';
                                        
                                        echo '<button type="submit" style="width: auto; height: 38px; padding: 0 15px; background: #28a745; color: white; border: 1px solid #28a745; border-radius: 4px; cursor: pointer; font-size: 13px; white-space: nowrap; box-sizing: border-box; margin: 0;">Rate</button>';
                                    
                                    echo '</form>';
                                }
                                echo '</div>';
                            }

                        echo '</div>';
                    }
                }
                ?>
            </div> 
            
            <hr style="margin: 20px 0;">

            <h3 style="color: #007bff; margin-bottom: 15px;">Add New Maintenance Schedule</h3>
            <form action="submit_task.php" method="post">
                <label for="task_details">Maintenance Details:</label>
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

                <button type="submit" style="background-color: #007bff;">Schedule Now</button>
            </form>
            
            <div style="text-align: center; margin-top: 25px;">
                 <a href="scheduler.php" style="color: #0099cc; text-decoration: none; font-weight: bold;">View Full Scheduler Dashboard →</a>
            </div>
        </div>
        
    </div> 
	
    <div id="feedbackModal" class="modal-overlay">
    <div class="modal-content">
        <span id="closeFeedback" class="modal-close">&times;</span>
        <h2 style="color: #007bff; margin-top: 0;">Submit Feedback</h2>
        <p style="color: #666; font-size: 14px;">Have a suggestion for the facility? Let us know!</p>

        <form action="submit_feedback.php" method="post">
            <label>Subject:</label>
            <input type="text" name="subject" placeholder="e.g., Gym Lights" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;">

            <label>Details:</label>
            <textarea name="details" rows="4" placeholder="Describe your suggestion..." required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>

            <button type="submit" style="width: 100%; background-color: #007bff; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer;">Submit</button>
        </form>
    </div>
</div>
    
    <div id="chat-bubble">💬</div>

    <div id="chat-window">
        <div id="chat-header">
            <h3>Chat Assistant</h3>
            <button id="chat-close">&times;</button>
        </div>
        <div id="chat-log">
            <div class="chat-message bot-message">
                <p>Hello! How can I help you today? You can ask me about 'maintenance' or 'account'.</p>
            </div>
        </div>
        <form id="chat-form">
            <input type="text" id="chat-input" placeholder="Type your message..." autocomplete="off">
            <button type="submit">Send</button>
        </form>
    </div>

    <script>
        const chatBubble = document.getElementById('chat-bubble');
        const chatWindow = document.getElementById('chat-window');
        const chatClose = document.getElementById('chat-close');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const chatLog = document.getElementById('chat-log');

        chatBubble.addEventListener('click', () => { chatWindow.classList.toggle('open'); });
        chatClose.addEventListener('click', () => { chatWindow.classList.remove('open'); });

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

        function addMessage(text, sender) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('chat-message');
            messageElement.classList.add(sender + '-message');
            messageElement.innerHTML = `<p>${text}</p>`; 
            chatLog.appendChild(messageElement);
            chatLog.scrollTop = chatLog.scrollHeight;
        }

        function getBotResponse(input) {
            const text = input.toLowerCase();
            if (text.includes('hello') || text.includes('hi')) { return "Hello there! How can I assist you?"; }
            if (text.includes('maintenance') || text.includes('task')) { return "You can add a new task below and view your list above."; }
            if (text.includes('account') || text.includes('logout')) { return "You can log out using the link in the header."; }
            return "I'm sorry, I don't understand. Try asking about 'maintenance' or 'account'.";
        }
        
const modal = document.getElementById('feedbackModal');
const btn = document.getElementById('openFeedback');
const span = document.getElementById('closeFeedback');

btn.onclick = function() {
    modal.style.display = "flex";
}

span.onclick = function() {
    modal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
    </script>
</body>
</html>
