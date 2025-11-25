<?php

// We MUST start a session at the very top of any page that needs to remember the user.
session_start();

// --- Database Connection ---
require 'db_connect.php';

try {
    // --- 2. Connect to the Database ---
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 3. Get Data from the Form ---
    $username_form = $_POST['username'];
    $password_form = $_POST['password'];

    // --- 4. Find the User in the Database ---
    $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username_form);
    $stmt->execute();

    // Fetch the user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- 5. Verify the User and Password ---
    if ($user && password_verify($password_form, $user['password'])) {
		
        // --- 6. Store User Info in the Session ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
	$_SESSION['role'] = $user['role'];
        
        // --- 7. Redirect to the Main Portal ---
        header("Location: index.php");
        exit();
        
    } else {
        header("Location: login.html?status=login_failed");
        exit();
    }

} catch(PDOException $e) {
    // Handle database errors
    echo "<h2>Error!</h2>";
    echo "Database error: " . $e->getMessage();
}

$conn = null;
?>
