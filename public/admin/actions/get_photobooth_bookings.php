<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    // Query all photobooth bookings
    $query = "SELECT id, name, email, mobile, event_type, product, duration, 
                     package_type, event_date, time_of_service, venue, street_address,
                     city, region, postal_code, country, remarks, estimated_price, travel_fee,
                     booking_date, status 
              FROM photo_bookings 
              ORDER BY event_date DESC, time_of_service DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $bookings,
        'count' => count($bookings)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading bookings: ' . $e->getMessage()
    ]);
}
?>
