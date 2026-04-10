<?php
require_once 'includes/config.php';

try {
    // Add travel_fee column to photo_bookings if it doesn't exist
    $sql = "ALTER TABLE photo_bookings ADD COLUMN travel_fee VARCHAR(20) DEFAULT '0.00' AFTER estimated_price;";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Travel fee column added successfully']);
    } else {
        // Check if column already exists
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo json_encode(['success' => true, 'message' => 'Column already exists']);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
