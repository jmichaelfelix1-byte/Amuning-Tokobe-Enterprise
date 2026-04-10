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
    // Fetch all print services
    $sql = "SELECT id, service_name, description, base_price, paper_types, sizes, stock_quantity, image_path, is_available
            FROM print_services
            ORDER BY id ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $printServices = [];

    while ($row = $result->fetch_assoc()) {
        $printServices[] = [
            'id' => $row['id'],
            'service_name' => $row['service_name'],
            'description' => $row['description'],
            'base_price' => $row['base_price'],
            'paper_types' => $row['paper_types'],
            'sizes' => $row['sizes'],
            'stock_quantity' => $row['stock_quantity'],
            'image_path' => $row['image_path'],
            'is_available' => (bool)$row['is_available']
        ];
    }

    echo json_encode(['success' => true, 'data' => $printServices]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
