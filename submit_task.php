<?php
    session_start(); 
    
    if ( !isset($_SESSION['user_id']) ) {
        die("You must be logged in to add a task.");
    }
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "portal_db";
    $port = 3307;
    
    try {
        $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        // Get Data
        $user_id = $_SESSION['user_id'];
        $area = $_POST['area'];
        $priority = $_POST['priority'];
        $details = $_POST['details'];
        
        $deadline = null;
        if ( !empty($_POST['deadline']) ) {
            $deadline = $_POST['deadline'];
        }
    
        // Save to 'tasks' table
        $sql = "INSERT INTO tasks (user_id, maintenance_area, priority, deadline, details) 
                VALUES (:user_id, :area, :priority, :deadline, :details)";
    
        $stmt = $conn->prepare($sql);
    
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':area', $area);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':deadline', $deadline);
        $stmt->bindParam(':details', $details);
        
        $stmt->execute();
    
        header("Location: index.php?status=task_added");
        exit();
    
    } catch(Exception $e) {
        die("Error: " . $e->getMessage());
    }
    $conn = null;
    ?>