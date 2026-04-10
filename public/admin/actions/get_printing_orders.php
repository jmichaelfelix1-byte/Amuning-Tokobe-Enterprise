<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Fetch recent printing orders with payment status (last 10)
    $sql = "SELECT po.id, po.full_name, po.contact_number, po.service, po.order_date, po.status,
                   COALESCE(p.status, 'unpaid') as payment_status
            FROM printing_orders po
            LEFT JOIN payments p ON p.reference_id = po.id AND p.payment_type = 'printing_order'
            ORDER BY po.order_date DESC
            LIMIT 10";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $printingOrders = [];

    while ($row = $result->fetch_assoc()) {
        // Map status to display text (same as in get_all_printing_orders.php)
        $status = $row['status'];
        $order_status_display = 'Order not yet process';
        
        if ($status === 'completed') {
            $order_status_display = 'ORDER COMPLETED';
        } else if ($status === 'declined') {
            $order_status_display = 'ORDER DECLINED';
        } else if ($status === 'processing') {
            $order_status_display = 'ORDER PROCESSING';
        }

        $printingOrders[] = [
            'id' => $row['id'],
            'full_name' => $row['full_name'],
            'contact_number' => $row['contact_number'],
            'service' => $row['service'],
            'order_date' => date('M d, Y', strtotime($row['order_date'])),
            'order_date_raw' => $row['order_date'],
            'status' => $row['status'], // Changed from 'order_status' to 'status'
            'order_status_display' => $order_status_display, // Add this field
            'payment_status' => $row['payment_status']
        ];
    }

    echo json_encode(['success' => true, 'data' => $printingOrders]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>