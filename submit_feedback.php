<?php
    session_start(); 
    
    // --- 1. Check if logged in ---
    if ( !isset($_SESSION['user_id']) ) {
        die("You must be logged in to submit feedback.");
    }
    
    // --- 2. Database Connection ---
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "portal_db";
    $port = 3307;
    
    try {
        $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        // --- 3. Get Data from Form & Session ---
        $user_id = $_SESSION['user_id'];
        $subject = $_POST['subject'];
        $details = $_POST['details'];
    
        // --- 4. Save to 'feedback' table ---
        $sql = "INSERT INTO feedback (user_id, subject, details) 
                VALUES (:user_id, :subject, :details)";
    
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':details', $details);
        $stmt->execute();
    
        // --- 5. Redirect back ---
        header("Location: index.php?status=feedback_success");
        exit();
    
    } catch(Exception $e) {
        die("Error: " . $e->getMessage());
    }
    $conn = null;
    ?>