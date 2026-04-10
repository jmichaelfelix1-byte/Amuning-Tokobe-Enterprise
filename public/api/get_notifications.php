<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get unread count
if ($action === 'get_unread_count') {
    $query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode(['unread_count' => $data['unread']]);
    exit();
}

// Get recent notifications
if ($action === 'get_notifications') {
    $limit = intval($_GET['limit'] ?? 10);
    $offset = intval($_GET['offset'] ?? 0);
    
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['notifications' => $notifications]);
    exit();
}

// Get unread notifications only (for real-time popup)
if ($action === 'get_new_notifications') {
    $since = $_GET['since'] ?? null;
    
    $query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 5";
    $params = [$user_id];
    $types = "i";
    
    if ($since) {
        $query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE AND created_at > ? ORDER BY created_at DESC LIMIT 5";
        $params = [$user_id, $since];
        $types = "is";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['notifications' => $notifications]);
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>
