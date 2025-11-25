<?php

// --- Database Connection ---
require 'db_connect.php';

try {
    // --- 2. Connect to the Database ---
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 3. Get Data from the Form ---
    $username_form = $_POST['username'];
    $email_form = $_POST['email'];
    $password_form = $_POST['password'];

    // --- 4. SECURE THE PASSWORD ---
    $hashed_password = password_hash($password_form, PASSWORD_DEFAULT);

    // --- 5. Prepare and Execute the SQL Query ---
    $sql = "INSERT INTO users (username, email, password) 
            VALUES (:username, :email, :password)";
    
    $stmt = $conn->prepare($sql);

    // Bind the HASHED password, not the original one
    $stmt->bindParam(':username', $username_form);
    $stmt->bindParam(':email', $email_form);
    $stmt->bindParam(':password', $hashed_password);
    
    $stmt->execute();

    // --- 6. Redirect to Login Page on Success ---
    header("Location: login.html?status=reg_success");
    exit();

} catch(PDOException $e) {
    // --- 7. Handle Errors ---
    
    // Check if the error is a "duplicate entry" (error code 23000)
    // This happens if the username is already taken.
    if ($e->getCode() == 23000) {
        echo "<h2>Error!</h2>";
        echo "That username is already taken. Please go back and try another one.";
    } else {
        // Handle other errors
        echo "<h2>Error!</h2>";
        echo "Could not register: " . $e->getMessage();
    }
}

$conn = null;
?>
