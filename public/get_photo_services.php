<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once 'includes/db_connect.php';

try {
    // Get only available services
    $stmt = $conn->prepare("SELECT * FROM photo_services ORDER BY id");
    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        // Only expose basic and standard packages. Use basic_price as the displayed base price.
        $services[] = [
            'id' => $row['id'],
            'service_name' => $row['service_name'],
            'description' => $row['description'],
            'image_path' => $row['image_path'],
            'basic_price' => $row['basic_price'],
            'standard_price' => $row['standard_price'],
            'is_available' => (bool)$row['is_available'],
            'packages' => [
                'basic' => ['price' => $row['basic_price'], 'multiplier' => 1.0],
                'standard' => ['price' => $row['standard_price'], 'multiplier' => 1.5]
            ]
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
