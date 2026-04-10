<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    // Get date range from query parameters
    $start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-1 month'));
    $end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));

    // Query photobooth bookings - only show 'booked' status
    $query = "SELECT id, name, event_date, time_of_service, event_type, status 
              FROM photo_bookings 
              WHERE event_date BETWEEN ? AND ? AND status = 'booked'
              ORDER BY event_date ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $statusColor = match($row['status']) {
            'confirmed' => '#10b981',
            'pending' => '#3b82f6',
            'cancelled' => '#ef4444',
            'completed' => '#8b5cf6',
            default => '#6b7280'
        };

        $events[] = [
            'id' => 'event-' . $row['id'],
            'title' => $row['name'] . ' - ' . $row['event_type'],
            'start' => $row['event_date'] . 'T' . $row['time_of_service'],
            'backgroundColor' => $statusColor,
            'borderColor' => $statusColor,
            'extendedProps' => [
                'bookingId' => $row['id'],
                'status' => $row['status']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'events' => $events
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading calendar events: ' . $e->getMessage()
    ]);
}
?>
