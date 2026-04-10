<?php 
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "amuning_db_new";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        // Only show JSON error for AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $conn->connect_error
            ]);
            exit();
        } else {
            die("Database connection failed: " . $conn->connect_error);
        }
    }

    // Set charset to prevent encoding issues
    $conn->set_charset("utf8mb4");
?>