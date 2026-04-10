<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Service ID is required']);
    exit();
}

$serviceId = intval($_GET['id']);

try {
        // Fetch single photo service by ID (only basic and standard prices)
        $sql = "SELECT id, service_name, description, basic_price, standard_price, image_path, is_available
            FROM photo_services
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Service not found']);
        exit();
    }

    $service = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $service['id'],
            'service_name' => $service['service_name'],
            'description' => $service['description'],
            'basic_price' => $service['basic_price'],
            'standard_price' => $service['standard_price'],
            'image_path' => $service['image_path'],
            'is_available' => (bool)$service['is_available']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
