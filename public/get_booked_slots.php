<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
include 'includes/config.php';

// Get all booked dates and times where status is 'booked' only
$sql = "SELECT event_date, time_of_service, duration FROM photo_bookings WHERE status = 'booked' ORDER BY event_date, time_of_service";

$result = $conn->query($sql);

$booked_slots = array();
$fully_booked_days = array(); // Track days with 8+ hours booked

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $date = $row['event_date'];
        $time = $row['time_of_service'];
        $duration = $row['duration'];

        if (!isset($booked_slots[$date])) {
            $booked_slots[$date] = array();
        }

        $booked_slots[$date][] = array(
            'time' => $time,
            'duration' => $duration
        );
        
        // Check if any single booking is 8+ hours or total is 8+ hours
        preg_match('/(\d+)/', $duration, $matches);
        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
        
        if ($hours >= 8) {
            $fully_booked_days[$date] = true;
        }
    }
    
    // Calculate total hours for each day
    foreach ($booked_slots as $date => $bookings) {
        $total_hours = 0;
        foreach ($bookings as $booking) {
            preg_match('/(\d+)/', $booking['duration'], $matches);
            $hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $total_hours += $hours;
        }
        if ($total_hours >= 8) {
            $fully_booked_days[$date] = true;
        }
    }
}

echo json_encode([
    'success' => true,
    'booked_slots' => $booked_slots,
    'fully_booked_days' => array_keys($fully_booked_days)
]);

$conn->close();
?>
