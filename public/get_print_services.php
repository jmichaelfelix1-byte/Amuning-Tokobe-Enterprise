<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once 'includes/db_connect.php';

try {
    // Get only available services
    $stmt = $conn->prepare("SELECT * FROM print_services ORDER BY id");
    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'id' => $row['id'],
            'service_name' => $row['service_name'],
            'description' => $row['description'],
            'image_path' => $row['image_path'],
            'base_price' => $row['base_price'],
            'is_available' => (bool)$row['is_available'],
            'paper_types' => json_decode($row['paper_types'], true),
            'sizes' => json_decode($row['sizes'], true),
            'stock_quantity' => $row['stock_quantity']
        ];
    }

    echo json_encode([
        'success' => true,
        'services' => $services
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
