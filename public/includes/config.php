<?php 
// Database configuration for Render + Aiven
// Use environment variables for security (set in Render dashboard)
$servername = getenv('DB_HOST') ?: 'mysql-3b92f127-amuningtokobeenterprise.b.aivencloud.com';
$port = getenv('DB_PORT') ?: '19194';
$username = getenv('DB_USER') ?: 'avnadmin';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'amuning_db_new';

// Append port to servername for MySQLi
$servername_with_port = $servername . ':' . $port;

// Create connection with SSL (required for Aiven)
$conn = mysqli_init();

// Enable SSL for secure connection to Aiven
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Connect with SSL
if (!mysqli_real_connect($conn, $servername, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    $error = mysqli_connect_error();
    
    // Only show JSON error for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $error
        ]);
        exit();
    } else {
        die("Database connection failed: " . $error);
    }
}

// Set charset to prevent encoding issues
mysqli_set_charset($conn, "utf8mb4");

// For debugging locally (remove in production)
// echo "Connected successfully!";
?>
