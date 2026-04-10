<?php
// Check booking availability for a given date
header('Content-Type: application/json');

require_once '../config/db.php';

$response = [
    'available' => true,
    'booked_hours' => 0,
    'is_fully_booked' => false,
    'available_slots' => [],
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = isset($_POST['date']) ? $_POST['date'] : null;
    $duration = isset($_POST['duration']) ? $_POST['duration'] : null;

    if (!$date || !$duration) {
        http_response_code(400);
        $response['message'] = 'Date and duration are required';
        echo json_encode($response);
        exit;
    }

    // Extract hours from duration (e.g., "2-hours" -> 2, "8-hours" -> 8)
    preg_match('/(\d+)/', $duration, $matches);
    $requested_hours = isset($matches[1]) ? (int)$matches[1] : 0;

    if ($requested_hours === 0) {
        http_response_code(400);
        $response['message'] = 'Invalid duration format';
        echo json_encode($response);
        exit;
    }

    try {
        // Get all bookings for this date
        $stmt = $conn->prepare("
            SELECT time_of_service, duration 
            FROM photo_bookings 
            WHERE event_date = ? AND status != 'cancelled' AND status != 'declined'
            ORDER BY time_of_service ASC
        ");
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        $total_booked_hours = 0;
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
            // Extract hours from duration
            preg_match('/(\d+)/', $row['duration'], $matches);
            $booked_hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $total_booked_hours += $booked_hours;
        }
        
        $response['booked_hours'] = $total_booked_hours;

        // Check if day is fully booked (any booking >= 8 hours or total >= 8 hours)
        $is_day_fully_booked = $total_booked_hours >= 8;
        foreach ($bookings as $booking) {
            preg_match('/(\d+)/', $booking['duration'], $matches);
            if (isset($matches[1]) && (int)$matches[1] >= 8) {
                $is_day_fully_booked = true;
                break;
            }
        }

        $response['is_fully_booked'] = $is_day_fully_booked;

        if ($is_day_fully_booked) {
            $response['available'] = false;
            $response['message'] = 'This day is fully booked';
            echo json_encode($response);
            exit;
        }

        // If requested duration is 8+ hours and there are already bookings
        if ($requested_hours >= 8 && count($bookings) > 0) {
            $response['available'] = false;
            $response['message'] = 'Cannot book 8 or more hours when there are existing bookings on this day. Maximum 2 bookings allowed with less than 8 hours each.';
            echo json_encode($response);
            exit;
        }

        // Check if we can fit 2 bookings
        if (count($bookings) >= 2) {
            $response['available'] = false;
            $response['message'] = 'Maximum 2 bookings per day reached';
            echo json_encode($response);
            exit;
        }

        // Generate available time slots
        $available_slots = generateAvailableSlots($bookings, $requested_hours);
        
        if (empty($available_slots)) {
            $response['available'] = false;
            $response['message'] = 'No available time slots for the requested duration';
            $response['available_slots'] = [];
        } else {
            $response['available'] = true;
            $response['available_slots'] = $available_slots;
            $response['message'] = 'Slots available';
        }

    } catch (Exception $e) {
        http_response_code(500);
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

echo json_encode($response);

/**
 * Generate available time slots based on existing bookings
 * Business hours: 8 AM to 8 PM (8:00 - 20:00)
 * Minimum 1-hour gap between bookings
 */
function generateAvailableSlots($bookings, $requested_hours) {
    $business_start = 8;    // 8 AM
    $business_end = 20;     // 8 PM
    $min_gap = 1;           // 1 hour minimum gap
    
    // Convert bookings to time ranges
    $booked_ranges = [];
    foreach ($bookings as $booking) {
        preg_match('/(\d+):(\d+)/', $booking['time_of_service'], $time_matches);
        $start_hour = isset($time_matches[1]) ? (int)$time_matches[1] : 8;
        
        preg_match('/(\d+)/', $booking['duration'], $duration_matches);
        $duration_hours = isset($duration_matches[1]) ? (int)$duration_matches[1] : 2;
        
        $end_hour = $start_hour + $duration_hours;
        
        $booked_ranges[] = [
            'start' => $start_hour,
            'end' => $end_hour
        ];
    }
    
    $available_slots = [];
    
    // Check each hour as a potential start time
    for ($hour = $business_start; $hour < $business_end; $hour++) {
        $slot_end = $hour + $requested_hours;
        
        // Check if slot extends beyond business hours
        if ($slot_end > $business_end) {
            continue;
        }
        
        // Check for conflicts with existing bookings (including 1-hour gap)
        $has_conflict = false;
        foreach ($booked_ranges as $range) {
            // Add buffer zone for minimum 1-hour gap
            $buffer_start = $range['start'] - $min_gap;
            $buffer_end = $range['end'] + $min_gap;
            
            // Check if new slot overlaps with buffered range
            if (!($slot_end <= $buffer_start || $hour >= $buffer_end)) {
                $has_conflict = true;
                break;
            }
        }
        
        if (!$has_conflict) {
            $available_slots[] = sprintf("%02d:00", $hour);
        }
    }
    
    return $available_slots;
}
?>
