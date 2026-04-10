<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Fetch all customers (users) excluding passwords
    $sql = "SELECT id, email, full_name, mobile, address, user_type, register_as, profile, created_at
            FROM users
            WHERE user_type = 'user'
            ORDER BY created_at DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $customers = [];

    while ($row = $result->fetch_assoc()) {
        $customers[] = [
            'id' => $row['id'],
            'email' => $row['email'],
            'full_name' => $row['full_name'],
            'mobile' => $row['mobile'],
            'address' => $row['address'],
            'user_type' => $row['user_type'],
            'register_as' => $row['register_as'],
            'profile' => $row['profile'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode(['success' => true, 'data' => $customers]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
