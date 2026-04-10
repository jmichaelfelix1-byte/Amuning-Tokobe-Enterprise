<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Service ID is required']);
    exit();
}

try {
    $service_id = intval($_POST['id']);

    // Validate required fields
    $required_fields = ['service_name', 'base_price', 'paper_types', 'sizes', 'stock_quantity'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            exit();
        }
    }

    // Get form data
    $service_name = trim($_POST['service_name']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $base_price = floatval($_POST['base_price']);
    $paper_types = trim($_POST['paper_types']);
    $sizes = trim($_POST['sizes']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $is_available = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;

    // Convert comma-separated strings to JSON arrays
    $paper_types_array = array_map('trim', explode(',', $paper_types));
    $sizes_array = array_map('trim', explode(',', $sizes));
    $paper_types_json = json_encode($paper_types_array);
    $sizes_json = json_encode($sizes_array);

    // Get current image path
    $current_image_sql = "SELECT image_path FROM print_services WHERE id = ?";
    $stmt = $conn->prepare($current_image_sql);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
        exit();
    }
    
    $row = $result->fetch_assoc();
    $current_image = $row['image_path'] ?? 'images/services_image/default_service.jpg';
    $stmt->close();

    // Handle image upload
    $image_path = $current_image; // Keep current image by default
    
    // Only process image upload if a file was actually selected and uploaded successfully
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['image']['name'])) {
        $upload_dir = '../../assets/images/services_image/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = basename($_FILES['image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'];

        if (!in_array($file_ext, $allowed_exts)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format. Only image files are allowed (JPG, PNG, GIF, WEBP, BMP, SVG, ICO).']);
            exit();
        }

        // Generate unique filename
        $new_file_name = 'print_service_' . time() . '_' . uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'images/services_image/' . $new_file_name;

            // Delete old image if it exists and is not the default
            if ($current_image && $current_image !== 'images/services_image/default_service.jpg' && file_exists('../../assets/' . $current_image)) {
                unlink('../../assets/' . $current_image);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image. Please try again.']);
            exit();
        }
    }
    // If no file was uploaded or file upload failed, keep the current image

    // Final validation: ensure image_path is never empty
    if (empty($image_path)) {
        $image_path = 'images/services_image/default_service.jpg';
    }

    // Update service
    $sql = "UPDATE print_services SET
            service_name = ?,
            description = ?,
            base_price = ?,
            paper_types = ?,
            sizes = ?,
            stock_quantity = ?,
            image_path = ?,
            is_available = ?,
            updated_at = NOW()
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssdssisii",
        $service_name,
        $description,
        $base_price,
        $paper_types_json,
        $sizes_json,
        $stock_quantity,
        $image_path,
        $is_available,
        $service_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    if ($stmt->affected_rows > 0) {
        // Get old values for history
        $old_values_sql = "SELECT service_name, description, base_price, stock_quantity, is_available FROM print_services WHERE id = ?";
        $old_stmt = $conn->prepare($old_values_sql);
        $old_stmt->bind_param("i", $service_id);
        $old_stmt->execute();
        $old_result = $old_stmt->get_result();
        $old_row = $old_result->fetch_assoc();
        
        // Prepare old and new values JSON
        $old_data = [
            'service_name' => $old_row['service_name'],
            'description' => $old_row['description'],
            'base_price' => $old_row['base_price'],
            'paper_types' => json_decode($old_row['paper_types'] ?? '[]'),
            'sizes' => json_decode($old_row['sizes'] ?? '[]'),
            'stock_quantity' => $old_row['stock_quantity'],
            'is_available' => $old_row['is_available']
        ];
        
        $new_data = [
            'service_name' => $service_name,
            'description' => $description,
            'base_price' => $base_price,
            'paper_types' => $paper_types_array,
            'sizes' => $sizes_array,
            'stock_quantity' => $stock_quantity,
            'is_available' => $is_available
        ];
        
        if ($image_path !== $current_image) {
            $old_data['image_path'] = $current_image;
            $new_data['image_path'] = $image_path;
        }
        
        $old_values = json_encode($old_data);
        $new_values = json_encode($new_data);
        $admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
        
        // Log to service_history
        $history_sql = "INSERT INTO service_history (service_id, service_type, action_type, old_values, new_values, changed_by) VALUES (?, ?, ?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_sql);
        $action = 'edited';
        $service_type = 'print';
        $history_stmt->bind_param("isssss", $service_id, $service_type, $action, $old_values, $new_values, $admin_name);
        $history_stmt->execute();
        $history_stmt->close();
        $old_stmt->close();
    }
    
    if ($stmt->affected_rows === 0) {
        // Check if the service exists
        $check_sql = "SELECT id FROM print_services WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $service_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception("Service not found.");
        }
        $check_stmt->close();
        // If service exists but no rows affected, it means no changes were made
        // This is actually success
    }

    echo json_encode([
        'success' => true,
        'message' => 'Print service updated successfully'
    ]);

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>