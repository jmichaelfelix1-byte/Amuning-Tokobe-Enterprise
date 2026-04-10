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
    // Fetch all photo services
        $sql = "SELECT id, service_name, description, basic_price, standard_price, image_path, is_available
            FROM photo_services
            ORDER BY id ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $photoServices = [];

    while ($row = $result->fetch_assoc()) {
        $photoServices[] = [
            'id' => $row['id'],
            'service_name' => $row['service_name'],
            'description' => $row['description'],
            'basic_price' => $row['basic_price'],
            'standard_price' => $row['standard_price'],
            'image_path' => $row['image_path'],
            'is_available' => (bool)$row['is_available']
        ];
    }

    echo json_encode(['success' => true, 'data' => $photoServices]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
