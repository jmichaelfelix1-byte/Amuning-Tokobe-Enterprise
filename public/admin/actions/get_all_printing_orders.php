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
    // Fetch all printing orders with separate payment and order status
    // Use a subquery to get only the LATEST payment for each order to avoid duplicate/old payment records
    $sql = "SELECT po.id, po.full_name, po.contact_number, po.service, po.size, po.paper_type, po.color_type,
                   po.quantity, po.price, po.image_path, po.page_count, po.page_counts, po.order_date, po.status as order_status, po.user_archived, po.admin_archived, po.payment_method,
                   CASE
                       WHEN p.id IS NULL THEN 'Payment Not Submitted'
                       WHEN p.status = 'paid' THEN 'Paid'
                       ELSE 'Payment Not Submitted'
                   END as payment_status_display,
                   CASE
                       WHEN po.status = 'pending' THEN 'Order not yet processed'
                       WHEN po.status = 'validated' THEN 'Order Validated'
                       WHEN po.status = 'processing' THEN 'Order processing'
                       WHEN po.status = 'ready_to_pickup' THEN 'Ready to Pick-Up'
                       WHEN po.status = 'completed' THEN 'Order Completed'
                       WHEN po.status = 'cancelled' THEN 'Order Cancelled'
                       WHEN po.status = 'declined' THEN 'Order Declined'
                       ELSE 'Order not yet processed'
                   END as order_status_display,
                   CASE WHEN p.id IS NULL THEN 'unpaid' ELSE p.status END as payment_status_raw
            FROM printing_orders po
            LEFT JOIN payments p ON p.reference_id = po.id AND p.payment_type = 'printing_order'
                AND p.id = (
                    SELECT MAX(id) FROM payments 
                    WHERE reference_id = po.id AND payment_type = 'printing_order'
                )
            ORDER BY po.id DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $printingOrders = [];

    while ($row = $result->fetch_assoc()) {
        // Count files from JSON image_path (preferred)
        $fileCount = 0;
        if (!empty($row['image_path'])) {
            $files = json_decode($row['image_path'], true);
            $fileCount = is_array($files) ? count($files) : 1;
        }

        // Attempt to determine page count robustly:
        // 1) Prefer explicit numeric `page_count` column if set
        // 2) Otherwise, try to sum pages from `page_counts` JSON column if available
        $pageCount = null;
        if (isset($row['page_count']) && $row['page_count'] !== '') {
            $pageCount = is_numeric($row['page_count']) ? (int) $row['page_count'] : $row['page_count'];
        } elseif (!empty($row['page_counts'])) {
            $decodedPageCounts = json_decode($row['page_counts'], true);
            if (is_array($decodedPageCounts)) {
                // `page_counts` might be an array of objects like [{"file":"...","pages":1}, ...]
                $sumPages = 0;
                foreach ($decodedPageCounts as $pc) {
                    if (is_array($pc) && isset($pc['pages']) && is_numeric($pc['pages'])) {
                        $sumPages += (int) $pc['pages'];
                    } elseif (is_numeric($pc)) {
                        $sumPages += (int) $pc;
                    }
                }
                if ($sumPages > 0) {
                    $pageCount = $sumPages;
                }
                // If fileCount wasn't determined from image_path, fallback to page_counts length
                if ($fileCount === 0) {
                    $fileCount = count($decodedPageCounts);
                }
            }
        }

        $printingOrders[] = [
            'id' => $row['id'],
            'full_name' => $row['full_name'],
            'contact_number' => $row['contact_number'],
            'service' => $row['service'],
            'size' => $row['size'],
            'paper_type' => $row['paper_type'],
            'color_type' => $row['color_type'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'image_path' => $row['image_path'],
            'page_count' => $pageCount,
            'file_count' => $fileCount,
            'order_date' => $row['order_date'],
            'status' => $row['order_status'],
            'payment_status' => $row['payment_status_raw'],
            'payment_status_display' => $row['payment_status_display'],
            'order_status_display' => $row['order_status_display'],
            'payment_method' => $row['payment_method'],
            'user_archived' => (int)$row['user_archived'],
            'admin_archived' => (int)$row['admin_archived']
        ];
    }

    echo json_encode(['success' => true, 'data' => $printingOrders]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
