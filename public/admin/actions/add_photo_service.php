<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

try {
    // Validate required fields
    $required_fields = ['service_name', 'basic_price', 'standard_price'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            echo json_encode(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            exit();
        }
    }

    // Get form data
    $service_name = trim($_POST['service_name']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    // Only accept basic and standard price now
    $basic_price = floatval($_POST['basic_price']);
    $standard_price = floatval($_POST['standard_price']);
    $is_available = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;

    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/services_image/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = basename($_FILES['image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png'];

        if (!in_array($file_ext, $allowed_exts)) {
            echo json_encode(['error' => 'Invalid image format. Only JPG, and PNG are allowed.']);
            exit();
        }

        // Generate unique filename
        $new_file_name = 'photo_service_' . time() . '_' . uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'images/services_image/' . $new_file_name;
        } else {
            echo json_encode(['error' => 'Failed to upload image']);
            exit();
        }
    } else {
        // Default image if no image uploaded
        $image_path = 'images/services_image/default_service.jpg';
    }

    // Insert new service
    $sql = "INSERT INTO photo_services (service_name, description, image_path, basic_price, standard_price, is_available, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssddi",
        $service_name,
        $description,
        $image_path,
        $basic_price,
        $standard_price,
        $is_available
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Photo service added successfully',
            'service_id' => $conn->insert_id
        ]);
    } else {
        throw new Exception("Failed to add service: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>