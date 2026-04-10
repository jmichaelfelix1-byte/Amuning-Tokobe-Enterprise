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
    $required_fields = ['service_name', 'basic_price', 'standard_price'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            exit();
        }
    }

    // Get form data
    $service_name = trim($_POST['service_name']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    // Only use basic and standard price now
    $basic_price = floatval($_POST['basic_price']);
    $standard_price = floatval($_POST['standard_price']);
    $is_available = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
        $new_file_name = 'photo_service_' . time() . '_' . uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'images/services_image/' . $new_file_name;
            
            // Delete old image if exists
            $sql_old = "SELECT image_path FROM photo_services WHERE id = ?";
            $stmt_old = $conn->prepare($sql_old);
            $stmt_old->bind_param("i", $service_id);
            $stmt_old->execute();
            $result = $stmt_old->get_result();
            if ($row = $result->fetch_assoc()) {
                $old_image = '../../assets/' . $row['image_path'];
                if (file_exists($old_image) && !empty($row['image_path'])) {
                    unlink($old_image);
                }
            }
            $stmt_old->close();
            
            // Update with new image
            $sql = "UPDATE photo_services SET 
                    service_name = ?, 
                    description = ?, 
                    image_path = ?, 
                    basic_price = ?, 
                    standard_price = ?, 
                    is_available = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssddii", 
                $service_name, 
                $description, 
                $image_path, 
                $basic_price, 
                $standard_price, 
                $is_available, 
                $service_id
            );
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image. Please try again.']);
            exit();
        }
    } else {
        // Update without changing image
        $sql = "UPDATE photo_services SET 
                service_name = ?, 
                description = ?, 
                basic_price = ?, 
                standard_price = ?, 
                is_available = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssddii", 
            $service_name, 
            $description, 
            $basic_price, 
            $standard_price, 
            $is_available, 
            $service_id
        );
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    if ($stmt->affected_rows > 0) {
        // Get old values for history
        $old_values_sql = "SELECT service_name, description, basic_price, standard_price, is_available FROM photo_services WHERE id = ?";
        $old_stmt = $conn->prepare($old_values_sql);
        $old_stmt->bind_param("i", $service_id);
        $old_stmt->execute();
        $old_result = $old_stmt->get_result();
        $old_row = $old_result->fetch_assoc();
        
        // Prepare old and new values JSON
        $old_data = [
            'service_name' => $old_row['service_name'],
            'description' => $old_row['description'],
            'basic_price' => $old_row['basic_price'],
            'standard_price' => $old_row['standard_price'],
            'is_available' => $old_row['is_available']
        ];
        
        $new_data = [
            'service_name' => $service_name,
            'description' => $description,
            'basic_price' => $basic_price,
            'standard_price' => $standard_price,
            'is_available' => $is_available
        ];
        
        if (isset($image_path)) {
            $old_data['image_path'] = $old_row['image_path'] ?? 'N/A';
            $new_data['image_path'] = $image_path;
        }
        
        $old_values = json_encode($old_data);
        $new_values = json_encode($new_data);
        $admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
        
        // Log to service_history
        $history_sql = "INSERT INTO service_history (service_id, service_type, action_type, old_values, new_values, changed_by) VALUES (?, ?, ?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_sql);
        $action = 'edited';
        $service_type = 'photo';
        $history_stmt->bind_param("isssss", $service_id, $service_type, $action, $old_values, $new_values, $admin_name);
        $history_stmt->execute();
        $history_stmt->close();
        $old_stmt->close();
    }
    
    if ($stmt->affected_rows === 0) {
        // Check if the service exists
        $check_sql = "SELECT id FROM photo_services WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $service_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception("Service not found.");
        }
        // If service exists but no rows affected, it means no changes were made
        // This is actually success
    }

    echo json_encode([
        'success' => true,
        'message' => 'Photo service updated successfully'
    ]);

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>