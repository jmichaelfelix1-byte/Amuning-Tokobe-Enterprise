<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: signin.php');
    exit();
}

$page_title = 'My Orders | Amuning Tokobe Enterprise';
$additional_css = ['orders.css'];

// Include database config
require_once 'includes/config.php';

// Fetch user's orders
$user_id = $_SESSION['user_id'];
$active_orders = [];
$archived_orders = [];

try {
    // Fetch active orders (not archived by user)
    // Use subquery to get only the LATEST payment per order to avoid duplicate payment records
    $stmt = $conn->prepare("
        SELECT po.id, po.service, po.size, po.paper_type, po.quantity, po.price, po.order_date, po.status, po.special_instruction, po.image_path, po.payment_method,
        CASE WHEN p.id IS NOT NULL THEN p.status ELSE 'Not Submitted' END as payment_status, COALESCE(po.user_archived, 0) as user_archived,
        CASE
            WHEN po.status = 'pending' THEN 'Order Not Yet Processed'
            WHEN po.status = 'validated' THEN 'Order Validated'
            WHEN po.status = 'processing' THEN 'Order Processing'
            WHEN po.status = 'ready_to_pickup' THEN 'Order Ready For Pick Up'
            WHEN po.status = 'completed' THEN 'Order Completed'
            WHEN po.status = 'cancelled' THEN 'Order Cancelled'
            WHEN po.status = 'declined' THEN 'Order Declined'
            ELSE po.status
        END as status_display
        FROM printing_orders po
        LEFT JOIN payments p ON po.id = p.reference_id AND p.payment_type = 'printing_order'
            AND p.id = (
                SELECT MAX(id) FROM payments 
                WHERE reference_id = po.id AND payment_type = 'printing_order'
            )
        WHERE po.user_id = ? AND COALESCE(po.user_archived, 0) = 0
        ORDER BY po.order_date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Decode JSON image paths (stored as JSON array in database)
        if (!empty($row['image_path'])) {
            $decoded_paths = json_decode($row['image_path'], true);
            // Get first file if it's an array, otherwise use as-is
            $row['image_path'] = is_array($decoded_paths) ? $decoded_paths[0] : $row['image_path'];
        }
        $active_orders[] = $row;
    }

    $stmt->close();
    
    // Fetch archived orders (archived by user)
    // Use subquery to get only the LATEST payment per order to avoid duplicate payment records
    $stmt = $conn->prepare("
        SELECT po.id, po.service, po.size, po.paper_type, po.quantity, po.price, po.order_date, po.status, po.special_instruction, po.image_path, po.payment_method,
        CASE WHEN p.id IS NOT NULL THEN p.status ELSE 'Not Submitted' END as payment_status, COALESCE(po.user_archived, 0) as user_archived,
        CASE
            WHEN po.status = 'pending' THEN 'Order Not Yet Processed'
            WHEN po.status = 'validated' THEN 'Order Validated'
            WHEN po.status = 'processing' THEN 'Order Processing'
            WHEN po.status = 'ready_to_pickup' THEN 'Order Ready For Pick Up'
            WHEN po.status = 'completed' THEN 'Order Completed'
            WHEN po.status = 'cancelled' THEN 'Order Cancelled'
            WHEN po.status = 'declined' THEN 'Order Declined'
            ELSE po.status
        END as status_display
        FROM printing_orders po
        LEFT JOIN payments p ON po.id = p.reference_id AND p.payment_type = 'printing_order'
            AND p.id = (
                SELECT MAX(id) FROM payments 
                WHERE reference_id = po.id AND payment_type = 'printing_order'
            )
        WHERE po.user_id = ? AND COALESCE(po.user_archived, 0) = 1
        ORDER BY po.order_date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Decode JSON image paths (stored as JSON array in database)
        if (!empty($row['image_path'])) {
            $decoded_paths = json_decode($row['image_path'], true);
            // Get first file if it's an array, otherwise use as-is
            $row['image_path'] = is_array($decoded_paths) ? $decoded_paths[0] : $row['image_path'];
        }
        $archived_orders[] = $row;
    }

    $stmt->close();
} catch (Exception $e) {
    // Handle error gracefully
    $active_orders = [];
    $archived_orders = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <!-- jQuery (required for DataTables) - Load FIRST -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link rel="stylesheet" href="assets/css/orders.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* Page-specific styles only */
        .orders-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .orders-table-container {
            margin-top: 20px;
            overflow-x: auto;
        }

        /* Order Tabs Styling */
        .order-tabs {
            display: flex;
            gap: 10px;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: #f5276c;
        }

        .tab-btn.active {
            color: #f5276c;
            border-bottom-color: #f5276c;
            font-weight: 600;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Specific column minimum widths for orders table - FIXED ALIGNMENT */
        #ordersTable, #archivedTable {
            table-layout: fixed !important;
        }
        
        #ordersTable th, #ordersTable td,
        #archivedTable th, #archivedTable td {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            vertical-align: middle;
        }
    </style>
    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <section class="banner">
    <div class="banner-content">
        <h1>My Orders</h1>
        <p>View and manage all your orders</p>
    </div>
  </section>
    <div class="orders-container">
        <div class="orders-table-container">
            <?php if (empty($active_orders) && empty($archived_orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any printing orders yet.</p>
                    <a href="print.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Place Your First Order</a>
                </div>
            <?php else: ?>
                <!-- Tabs for Active and Archived Orders -->
                <div class="order-tabs" style="margin-bottom: 20px; border-bottom: 2px solid #eee;">
                    <button class="tab-btn active" data-tab="active-orders" onclick="switchTab('active-orders', this)">
                        <i class="fas fa-inbox"></i> Active Orders <?php echo !empty($active_orders) ? '(' . count($active_orders) . ')' : ''; ?>
                    </button>
                    <button class="tab-btn" data-tab="archived-orders" onclick="switchTab('archived-orders', this)">
                        <i class="fas fa-archive"></i> Archived Orders <?php echo !empty($archived_orders) ? '(' . count($archived_orders) . ')' : ''; ?>
                    </button>
                </div>

                <!-- Active Orders Tab -->
                <div id="active-orders" class="tab-content active">
                    <?php if (empty($active_orders)): ?>
                        <div class="no-orders" style="padding: 40px 20px; text-align: center; color: #999;">
                            <p>No active orders. <a href="print.php">Place a new order</a></p>
                        </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table id="ordersTable" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Service</th>
                                    <th>Size</th>
                                    <th>Paper Type</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>File/Image</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Payment Method</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['service']); ?></td>
                                        <td><?php echo htmlspecialchars($order['size']); ?></td>
                                        <td><?php echo htmlspecialchars($order['paper_type']); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                        <td>₱<?php echo number_format((float)($order['price'] ?? 0), 2); ?></td>
                                        <td>
                                            <?php if (!empty($order['image_path'])): ?>
                                                <?php
                                                $file_path = $order['image_path'];
                                                $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                                                if (in_array($file_extension, $image_extensions) && file_exists($file_path)): ?>
                                                    <img src="<?php echo htmlspecialchars($file_path); ?>" alt="Order Image" style="max-width: 100%; max-height: 150px; border-radius: 4px; object-fit: cover; cursor: pointer;" class="clickable-preview" onclick="viewImage('<?php echo htmlspecialchars($file_path); ?>')" title="Click to view full image">
                                                <?php elseif ($file_extension === 'pdf'): ?>
                                                    <canvas id="pdf-preview-<?php echo $order['id']; ?>" style="max-width: 100%; max-height: 150px; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;" class="pdf-canvas clickable-preview" data-file="<?php echo htmlspecialchars($file_path); ?>" onclick="viewPDF('<?php echo htmlspecialchars($file_path); ?>')" title="Click to view PDF"></canvas>
                                                <?php else: ?>
                                                    <div style="padding: 10px; background: #f5f5f5; border-radius: 4px; text-align: center; max-width: 100%;">
                                                        <i class="fas fa-file" style="font-size: 24px; color: #999;"></i>
                                                        <p style="margin: 5px 0 0 0; font-size: 0.8rem; color: #666;">File: <?php echo htmlspecialchars(basename($file_path)); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #999; font-style: italic;">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status <?php echo strtolower($order['status'] ?: 'pending'); ?>">
                                                <?php echo htmlspecialchars($order['status_display'] ?? $order['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status <?php 
                                                $payment_status = $order['payment_status'] ?? 'Not Submitted';
                                                echo strtolower(str_replace(' ', '-', $payment_status));
                                            ?>">
                                                <?php echo htmlspecialchars($payment_status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="background: <?php echo ($order['payment_method'] === 'in_person' ? '#17a2b8' : '#0066cc'); ?>; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 500;">
                                                <i class="fas <?php echo ($order['payment_method'] === 'in_person' ? 'fa-handshake' : 'fa-credit-card'); ?>"></i>
                                                <?php echo ($order['payment_method'] === 'in_person' ? 'In-Person' : 'Online'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <button class="action-btn view" title="View Order Details" data-order-id="<?php echo $order['id']; ?>" data-type="printing_order">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit" title="<?php echo (in_array(strtolower($order['status']), ['declined', 'cancelled'])) ? 'Edit File' : 'Edit only available for declined/cancelled orders'; ?>" data-order-id="<?php echo $order['id']; ?>" <?php echo (!in_array(strtolower($order['status']), ['declined', 'cancelled'])) ? 'disabled' : ''; ?>>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn cancel" title="<?php echo (strtolower($order['status']) === 'pending' ? 'Cancel Order' : 'Can only cancel pending orders'); ?>" data-order-id="<?php echo $order['id']; ?>" data-type="printing_order" <?php echo (strtolower($order['status']) !== 'pending') ? 'disabled' : ''; ?>>
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                            <button class="action-btn archive" title="Archive Order" data-order-id="<?php echo $order['id']; ?>" data-type="printing_order" <?php echo (!in_array(strtolower($order['status']), ['completed', 'cancelled', 'declined'])) ? 'disabled' : ''; ?>>
                                                <i class="fas fa-archive"></i>
                                            </button>
                                            <?php
                                            $order_status = strtolower($order['status'] ?: 'pending');
                                            $payment_status = strtolower($order['payment_status'] ?: 'not submitted');
                                            $payment_method = $order['payment_method'] ?? 'online';

                                            // Payment button only enabled if order is validated and payment not submitted
                                            // Also hide for In Person orders (payment collected at pickup)
                                            $disable_payment = ($payment_status !== 'not submitted') || ($order_status !== 'validated') || ($payment_method === 'in_person');
                                            ?>

                                            <?php if (!$disable_payment): ?>
                                            <button class="action-btn pay" title="Pay Now" data-order-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-credit-card"></i>
                                            </button>
                                            <?php elseif ($payment_method === 'in_person'): ?>
                                            <button class="action-btn pay disabled" title="Payment collected at pickup" disabled>
                                                <i class="fas fa-cash-register"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="action-btn pay" title="<?php echo ($order_status !== 'validated') ? 'Order must be validated before payment' : 'Payment already processed'; ?>" disabled>
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>

                                            <?php 
                                            // Show download receipt for:
                                            // - Online payment with "processing" status or later
                                            // - In-person payment with "completed" status
                                            $show_receipt = false;
                                            if ($payment_method === 'online' && in_array($order_status, ['processing', 'ready_to_pickup', 'completed'])) {
                                                $show_receipt = true;
                                            } elseif ($payment_method === 'in_person' && $order_status === 'completed') {
                                                $show_receipt = true;
                                            }
                                            ?>

                                            <?php if ($show_receipt): ?>
                                            <a href="download_printing_receipt.php?order_id=<?php echo $order['id']; ?>" target="_blank" class="action-btn download" title="Download Receipt" style="text-decoration: none; background: #22c55e; color: white; display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Archived Orders Tab -->
                <div id="archived-orders" class="tab-content" style="display: none;">
                    <?php if (empty($archived_orders)): ?>
                        <div class="no-orders" style="padding: 40px 20px; text-align: center; color: #999;">
                            <p>No archived orders yet.</p>
                        </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table id="archivedTable" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Service</th>
                                    <th>Size</th>
                                    <th>Paper Type</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>File/Image</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Payment Method</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['service']); ?></td>
                                        <td><?php echo htmlspecialchars($order['size']); ?></td>
                                        <td><?php echo htmlspecialchars($order['paper_type']); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                        <td>₱<?php echo number_format((float)($order['price'] ?? 0), 2); ?></td>
                                        <td>
                                            <?php if (!empty($order['image_path'])): ?>
                                                <?php
                                                $file_path = $order['image_path'];
                                                $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                                                if (in_array($file_extension, $image_extensions) && file_exists($file_path)): ?>
                                                    <img src="<?php echo htmlspecialchars($file_path); ?>" alt="Order Image" style="max-width: 100%; max-height: 150px; border-radius: 4px; object-fit: cover; cursor: pointer;" class="clickable-preview" onclick="viewImage('<?php echo htmlspecialchars($file_path); ?>')" title="Click to view full image">
                                                <?php elseif ($file_extension === 'pdf'): ?>
                                                    <canvas id="pdf-preview-archive-<?php echo $order['id']; ?>" style="max-width: 100%; max-height: 150px; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;" class="pdf-canvas clickable-preview" data-file="<?php echo htmlspecialchars($file_path); ?>" onclick="viewPDF('<?php echo htmlspecialchars($file_path); ?>')" title="Click to view PDF"></canvas>
                                                <?php else: ?>
                                                    <div style="padding: 10px; background: #f5f5f5; border-radius: 4px; text-align: center; max-width: 100%;">
                                                        <i class="fas fa-file" style="font-size: 24px; color: #999;"></i>
                                                        <p style="margin: 5px 0 0 0; font-size: 0.8rem; color: #666;">File: <?php echo htmlspecialchars(basename($file_path)); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #999; font-style: italic;">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status <?php echo strtolower($order['status'] ?: 'pending'); ?>">
                                                <?php echo htmlspecialchars($order['status_display'] ?? $order['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status <?php 
                                                $payment_status = $order['payment_status'] ?? 'Not Submitted';
                                                echo strtolower(str_replace(' ', '-', $payment_status));
                                            ?>">
                                                <?php echo htmlspecialchars($payment_status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="background: <?php echo ($order['payment_method'] === 'in_person' ? '#17a2b8' : '#0066cc'); ?>; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 500;">
                                                <i class="fas <?php echo ($order['payment_method'] === 'in_person' ? 'fa-handshake' : 'fa-credit-card'); ?>"></i>
                                                <?php echo ($order['payment_method'] === 'in_person' ? 'In-Person' : 'Online'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <button class="action-btn view" title="View Order Details" data-order-id="<?php echo $order['id']; ?>" data-type="printing_order">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn unarchive" title="Restore to Active" data-order-id="<?php echo $order['id']; ?>" data-type="printing_order">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Buttons (optional but good to have) -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <!-- DataTables Responsive -->
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
    $(document).ready(function() {
        // Check for payment success/error messages in URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('payment_success') && urlParams.get('payment_success') === '1') {
            const message = urlParams.get('message') || 'Payment submitted successfully!';
            Swal.fire({
                icon: 'success',
                title: 'Payment Submitted',
                text: decodeURIComponent(message),
                confirmButtonColor: '#f5276c'
            });
            // Clean up URL
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (urlParams.has('payment_error') && urlParams.get('payment_error') === '1') {
            const message = urlParams.get('message') || 'Payment submission failed.';
            Swal.fire({
                icon: 'error',
                title: 'Payment Error',
                text: decodeURIComponent(message),
                confirmButtonColor: '#f5276c'
            });
            // Clean up URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Initialize DataTable with proper configuration
        const ordersTable = $('#ordersTable').DataTable({
            responsive: false,
            scrollX: false,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: '<"top"lf>rt<"bottom"ip><"clear">', // This ensures all controls are displayed
            language: {
                search: "Search orders:",
                lengthMenu: "Show _MENU_ orders per page",
                info: "Showing _START_ to _END_ of _TOTAL_ orders",
                infoEmpty: "No orders available",
                infoFiltered: "(filtered from _MAX_ total orders)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Prev"
                },
                zeroRecords: "No matching orders found"
            },
            columnDefs: [
                { width: "7%", targets: 0, className: "dt-left" },
                { width: "8%", targets: 1, className: "dt-left" },
                { width: "5%", targets: 2, className: "dt-center" },
                { width: "8%", targets: 3, className: "dt-left" },
                { width: "7%", targets: 4, className: "dt-center" },
                { width: "8%", targets: 5, className: "dt-right" },
                {
                    targets: 6,
                    orderable: false,
                    width: "8%",
                    className: "dt-center"
                },
                {
                    targets: 7,
                    width: "7%",
                    className: "dt-center"
                },
                {
                    targets: 8,
                    width: "8%",
                    className: "dt-center"
                },
                {
                    targets: 9,
                    width: "10%",
                    className: "dt-left"
                },
                {
                    targets: 10,
                    orderable: false,
                    searchable: false,
                    width: "14%",
                    className: "dt-center"
                }
            ],
            order: [[0, 'desc']],
            initComplete: function() {
                console.log('DataTable initialized successfully');
                // Force proper rendering
                setTimeout(() => {
                    ordersTable.columns.adjust().draw();
                    attachEventHandlers();
                    renderPDFPreviews();
                }, 100);
            },
            drawCallback: function() {
                // Add data-labels for mobile view
                $('#ordersTable thead th').each(function(i) {
                    $('td:nth-child(' + (i + 1) + ')', '#ordersTable tbody').attr('data-label', $(this).text());
                });

                // Re-attach event handlers after DataTable redraw
                attachEventHandlers();
                
                // Render PDFs to canvas
                renderPDFPreviews();
            }
        });

        // Initialize archived orders table if it exists
        if ($('#archivedTable').length) {
            const archivedTable = $('#archivedTable').DataTable({
                responsive: false,
                scrollX: false,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                dom: '<"top"lf>rt<"bottom"ip><"clear">',
                language: {
                    search: "Search orders:",
                    lengthMenu: "Show _MENU_ orders per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ orders",
                    infoEmpty: "No orders available",
                    infoFiltered: "(filtered from _MAX_ total orders)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Prev"
                    },
                    zeroRecords: "No matching orders found"
                },
                columnDefs: [
                    { width: "7%", targets: 0, className: "dt-left" },
                    { width: "8%", targets: 1, className: "dt-left" },
                    { width: "5%", targets: 2, className: "dt-center" },
                    { width: "8%", targets: 3, className: "dt-left" },
                    { width: "7%", targets: 4, className: "dt-center" },
                    { width: "8%", targets: 5, className: "dt-right" },
                    {
                        targets: 6,
                        orderable: false,
                        width: "8%",
                        className: "dt-center"
                    },
                    {
                        targets: 7,
                        width: "7%",
                        className: "dt-center"
                    },
                    {
                        targets: 8,
                        width: "8%",
                        className: "dt-center"
                    },
                    {
                        targets: 9,
                        width: "10%",
                        className: "dt-left"
                    },
                    {
                        targets: 10,
                        orderable: false,
                        searchable: false,
                        width: "14%",
                        className: "dt-center"
                    }
                ],
                order: [[0, 'desc']],
                initComplete: function() {
                    console.log('Archived DataTable initialized successfully');
                    setTimeout(() => {
                        archivedTable.columns.adjust().draw();
                        attachEventHandlers();
                        renderPDFPreviews();
                    }, 100);
                },
                drawCallback: function() {
                    $('#archivedTable thead th').each(function(i) {
                        $('td:nth-child(' + (i + 1) + ')', '#archivedTable tbody').attr('data-label', $(this).text());
                    });
                    attachEventHandlers();
                    // Render PDFs to canvas
                    renderPDFPreviews();
                }
            });
        }

        // Function to attach all event handlers
        function attachEventHandlers() {
            console.log('Attaching event handlers...');

            // File viewing - using event delegation so it works even after DataTable redraws
            $(document).off('click', '.file-view-link-item').on('click', '.file-view-link-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const filePath = $(this).data('file');
                const fileExtension = $(this).data('extension');
                console.log('File link clicked:', filePath, fileExtension);
                viewFile(filePath, fileExtension);
                return false;
            });

            // Edit order file functionality
            $(document).off('click', '.action-btn.edit').on('click', '.action-btn.edit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Edit button clicked');
                const orderId = $(this).data('order-id');

                // Create file upload modal
                const modalHtml = `
                    <div class="edit-order-modal">
                        <div class="modal-icon">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <h3>Replace Order File</h3>
                        <p>Upload a new file to replace the current one</p>
                        <form id="editFileForm" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="${orderId}">
                            <div class="file-input-wrapper">
                                <label for="newFile" class="file-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose File or Drag & Drop</span>
                                </label>
                                <input type="file" id="newFile" name="new_file" accept="image/*,.pdf,.psd,.ai" required>
                                <p class="file-hint">Accepted formats: JPG, PNG, GIF, PDF, PSD, AI (Max 50MB)</p>
                            </div>
                        </form>
                    </div>
                `;

                Swal.fire({
                    html: modalHtml,
                    showCancelButton: true,
                    confirmButtonText: 'Upload File',
                    confirmButtonColor: '#f5276c',
                    cancelButtonColor: '#6c757d',
                    didOpen: function() {
                        const fileInput = document.getElementById('newFile');
                        const fileLabel = document.querySelector('.file-label');
                        
                        // Drag and drop
                        fileLabel.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            fileLabel.style.background = '#fff3f5';
                            fileLabel.style.borderColor = '#f5276c';
                        });
                        
                        fileLabel.addEventListener('dragleave', () => {
                            fileLabel.style.background = '';
                            fileLabel.style.borderColor = '';
                        });
                        
                        fileLabel.addEventListener('drop', (e) => {
                            e.preventDefault();
                            fileInput.files = e.dataTransfer.files;
                            fileLabel.style.background = '';
                            fileLabel.style.borderColor = '';
                            fileLabel.querySelector('span').textContent = fileInput.files[0].name;
                        });

                        fileInput.addEventListener('change', (e) => {
                            if (fileInput.files.length > 0) {
                                fileLabel.querySelector('span').textContent = fileInput.files[0].name;
                            }
                        });
                    },
                    willClose: function() {
                        // Clean up
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const fileInput = document.getElementById('newFile');
                        if (fileInput.files.length === 0) {
                            Swal.fire('Error', 'Please select a file', 'error');
                            return;
                        }

                        const formData = new FormData(document.getElementById('editFileForm'));

                        Swal.fire({
                            title: 'Uploading...',
                            html: '<i class="fas fa-spinner fa-spin" style="font-size: 2em; color: #f5276c;"></i>',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: 'edit_order_file.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                console.log('Response:', response);
                                if (response.success) {
                                    Swal.fire(
                                        'Success!',
                                        'File updated successfully. Your order has been updated.',
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        response.message || 'Failed to update file',
                                        'error'
                                    );
                                }
                            },
                            error: function(xhr) {
                                console.log('Error response:', xhr);
                                Swal.fire(
                                    'Error!',
                                    'Failed to upload file. Please try again.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // Delete order functionality
            $(document).off('click', '.action-btn.delete').on('click', '.action-btn.delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Delete button clicked');
                const orderId = $(this).data('order-id');
                const row = $(this).closest('tr');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f5276c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // AJAX call to delete order
                        $.ajax({
                            url: 'delete_booking.php',
                            type: 'POST',
                            data: {
                                order_id: orderId,
                                type: 'printing'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Deleted!',
                                        'Your printing order has been deleted.',
                                        'success'
                                    );
                                    // Remove row from DataTable
                                    ordersTable.row(row).remove().draw();
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'Failed to delete order: ' + response.message,
                                        'error'
                                    );
                                }
                            },
                            error: function() {
                                Swal.fire(
                                    'Error!',
                                    'Failed to delete order. Please try again.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // View order functionality
            $(document).off('click', '.action-btn.view').on('click', '.action-btn.view', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('View button clicked');
                const orderId = $(this).data('order-id');
                const orderType = $(this).data('type');

                // Find the order data from the table row
                const row = $(this).closest('tr');
                const orderData = {
                    id: row.find('td:nth-child(1)').text().replace('#', ''),
                    service: row.find('td:nth-child(2)').text(),
                    size: row.find('td:nth-child(3)').text(),
                    paper_type: row.find('td:nth-child(4)').text(),
                    quantity: row.find('td:nth-child(5)').text(),
                    price: row.find('td:nth-child(6)').text(),
                    status: row.find('td:nth-child(8) .status').text(),
                    payment_status: row.find('td:nth-child(9) .status').text(),
                    date: row.find('td:nth-child(10)').text()
                };

                // Create user-friendly order view modal
                const modalContent = `
                    <div class="modal-order-content">
                        <div class="modal-header">
                            <h3><i class="fas fa-print"></i> Printing Order Details</h3>
                            <p>Order ID: #${orderData.id.padStart(4, '0')}</p>
                        </div>

                        <div class="modal-body">
                            <div class="modal-grid modal-grid-2col">
                                <div class="modal-card modal-card-primary">
                                    <h4><i class="fas fa-cog"></i> Order Specifications</h4>
                                    <p><strong>Service:</strong> <span>${orderData.service}</span></p>
                                    <p><strong>Size:</strong> <span>${orderData.size}</span></p>
                                    <p><strong>Paper Type:</strong> <span>${orderData.paper_type}</span></p>
                                    <p><strong>Quantity:</strong> <span>${orderData.quantity}</span></p>
                                </div>

                                <div class="modal-card modal-card-success">
                                    <h4><i class="fas fa-calendar-check"></i> Order Information</h4>
                                    <p><strong>Order Date:</strong> <span>${orderData.date}</span></p>
                                    <p><strong>Status:</strong> <span class="status ${orderData.status.toLowerCase()}">${orderData.status}</span></p>
                                </div>
                            </div>

                            <div class="modal-price-section">
                                <h4><i class="fas fa-tag"></i> Total Amount</h4>
                                <p>${orderData.price}</p>
                                <div class="modal-price-badge">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Ready for Payment</span>
                                </div>
                                <div class="modal-status-container">
                                    <span class="modal-status-badge">Order: ${orderData.status}</span>
                                    <span class="modal-status-badge">Payment: ${orderData.payment_status}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                Swal.fire({
                    html: modalContent,
                    showConfirmButton: true,
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#f5276c',
                    showCloseButton: true,
                    width: '600px',
                    customClass: {
                        popup: 'order-details-modal'
                    }
                });
            });

            console.log('Event handlers attached successfully');
        }

        // Cancel Order Handler
        $(document).off('click', '.action-btn.cancel').on('click', '.action-btn.cancel', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const orderId = $(this).data('order-id');
            const orderType = $(this).data('type') || 'printing_order';
            
            Swal.fire({
                title: 'Cancel Order?',
                text: 'Are you sure you want to cancel this order? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f5276c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel Order',
                cancelButtonText: 'No, Keep It'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/cancel_order.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            order_id: orderId,
                            order_type: orderType
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Order Cancelled',
                                    text: response.message || 'Your order has been cancelled successfully.',
                                    confirmButtonColor: '#f5276c'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to cancel order', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to cancel order. Please try again.', 'error');
                        }
                    });
                }
            });
        });

        // Pay for order functionality
        $(document).off('click', '.action-btn.pay').on('click', '.action-btn.pay', function() {
            const orderId = $(this).data('order-id');

            Swal.fire({
                title: 'Proceed to Payment',
                text: 'You will be redirected to the payment page for this order.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f5276c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Continue to Payment'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `payment.php?order_id=${orderId}`;
                }
            });
        });

        // Archive Order Handler
        $(document).off('click', '.action-btn.unarchive, .action-btn.archive').on('click', '.action-btn.unarchive, .action-btn.archive', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const orderId = $(this).data('order-id');
            const orderType = $(this).data('type') || 'printing_order';
            const isArchiving = $(this).hasClass('archive');
            
            const title = isArchiving ? 'Archive Order?' : 'Restore Order?';
            const text = isArchiving ? 
                'Are you sure you want to archive this order? You can find it in the Archived tab.' :
                'Are you sure you want to restore this order to active orders?';
            
            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f5276c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: isArchiving ? 'Yes, Archive It' : 'Yes, Restore It',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/archive_order.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            order_id: orderId,
                            order_type: orderType,
                            action: isArchiving ? 'archive' : 'unarchive'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: isArchiving ? 'Order Archived' : 'Order Restored',
                                    text: response.message || 'Operation completed successfully.',
                                    confirmButtonColor: '#f5276c'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to update order', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to update order. Please try again.', 'error');
                        }
                    });
                }
            });
        });
    });

    // Tab Switching Function
    function switchTab(tabName, buttonEl) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
            tab.style.display = 'none';
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show selected tab
        const selectedTab = document.getElementById(tabName);
        if (selectedTab) {
            selectedTab.classList.add('active');
            selectedTab.style.display = 'block';
            buttonEl.classList.add('active');
            
            // Reinitialize DataTables if it exists
            setTimeout(() => {
                if (tabName === 'active-orders' && $.fn.DataTable.isDataTable('#ordersTable')) {
                    $('#ordersTable').DataTable().columns.adjust().draw();
                } else if (tabName === 'archived-orders') {
                    if ($.fn.DataTable.isDataTable('#archivedTable')) {
                        $('#archivedTable').DataTable().columns.adjust().draw();
                    } else if ($('#archivedTable').length) {
                        // Initialize if not already initialized
                        $('#archivedTable').DataTable({
                            responsive: false,
                            scrollX: true,
                            autoWidth: false,
                            pageLength: 10,
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                            dom: '<"top"lf>rt<"bottom"ip><"clear">',
                            language: {
                                search: "Search orders:",
                                lengthMenu: "Show _MENU_ orders per page",
                                info: "Showing _START_ to _END_ of _TOTAL_ orders",
                                infoEmpty: "No orders available",
                                infoFiltered: "(filtered from _MAX_ total orders)",
                                paginate: { first: "First", last: "Last", next: "Next", previous: "Prev" },
                                zeroRecords: "No matching orders found"
                            },
                            columnDefs: [
                                { width: "8%", targets: 0 }, { width: "10%", targets: 1 }, { width: "6%", targets: 2 },
                                { width: "10%", targets: 3 }, { width: "8%", targets: 4 }, { width: "9%", targets: 5 },
                                { targets: 6, orderable: false, width: "9%" }, { targets: 7, width: "9%" },
                                { targets: 8, width: "10%" }, { targets: 9, width: "10%" },
                                { targets: 10, orderable: false, searchable: false, width: "11%" }
                            ],
                            order: [[0, 'desc']]
                        });
                    }
                }
            }, 100);
        }
    }

    // Function to render all PDF previews on the page
    function renderPDFPreviews() {
        const pdfCanvases = document.querySelectorAll('.pdf-canvas');
        pdfCanvases.forEach(canvas => {
            renderPDFToCanvas(canvas.dataset.file, canvas);
        });
    }

    // Function to render a single PDF file to a canvas element
    function renderPDFToCanvas(pdfPath, canvas) {
        if (!pdfPath || !canvas) return;
        
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
        
        const loadingTask = pdfjsLib.getDocument(pdfPath);
        loadingTask.promise.then(function(pdf) {
            // Get first page
            pdf.getPage(1).then(function(page) {
                const scale = 1.5;
                const viewport = page.getViewport({ scale: scale });
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                page.render(renderContext).promise.then(function() {
                    console.log('PDF rendered to canvas successfully');
                }).catch(function(error) {
                    console.error('Error rendering PDF page:', error);
                });
            }).catch(function(error) {
                console.error('Error getting PDF page:', error);
            });
        }).catch(function(error) {
            console.error('Error loading PDF:', error);
        });
    }

    // Function to view an image
    function viewImage(imagePath) {
        console.log('Viewing image:', imagePath);
        
        // Create an image element to detect its dimensions
        const img = new Image();
        img.onload = function() {
            const width = this.width;
            const height = this.height;
            const aspectRatio = width / height;
            
            // Calculate modal dimensions based on image size
            let modalWidth = '80vw';
            let modalHeight = 'auto';
            
            // If image is very large, cap at 80vw but maintain aspect ratio
            if (width > window.innerWidth * 0.8) {
                modalWidth = '80vw';
            } else {
                modalWidth = Math.min(width + 40, window.innerWidth - 40) + 'px';
            }
            
            Swal.fire({
                imageUrl: imagePath,
                imageAlt: 'Order Image',
                showConfirmButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: '#f5276c',
                width: modalWidth,
                padding: '0',
                didOpen: (modal) => {
                    // Remove extra padding from modal body
                    const htmlElement = modal.querySelector('.swal2-html-container');
                    if (htmlElement) {
                        htmlElement.style.margin = '0';
                        htmlElement.style.padding = '0';
                    }
                }
            });
        };
        img.src = imagePath;
    }

    // Function to view a PDF
    function viewPDF(pdfPath) {
        console.log('Viewing PDF:', pdfPath);
        const modalHtml = `
            <div style="width: 100%; height: 75vh; border-radius: 4px; overflow: hidden;">
                <iframe src="${pdfPath}#toolbar=0" style="width: 100%; height: 100%; border: none; display: block;"></iframe>
            </div>
        `;
        Swal.fire({
            title: 'PDF Viewer',
            html: modalHtml,
            width: '90vw',
            showConfirmButton: true,
            confirmButtonText: 'Close',
            confirmButtonColor: '#f5276c',
            padding: '1rem',
            didOpen: (modal) => {
                // Adjust modal to fit content better
                const htmlElement = modal.querySelector('.swal2-html-container');
                if (htmlElement) {
                    htmlElement.style.margin = '0';
                    htmlElement.style.padding = '0';
                }
            }
        });
    }

    // Function to view different file types
    function viewFile(filePath, fileExtension) {
        console.log('viewFile called with:', filePath, fileExtension);
        
        const pdfExtensions = ['pdf'];

        if (pdfExtensions.includes(fileExtension)) {
            console.log('Displaying PDF');
            // Display PDF in a modal with embedded viewer
            const modalHtml = `
                <div style="width: 100%; height: 600px;">
                    <iframe src="${filePath}#toolbar=0" style="width: 100%; height: 100%; border: none; border-radius: 4px;"></iframe>
                </div>
            `;
            Swal.fire({
                title: 'PDF Viewer',
                html: modalHtml,
                width: '90%',
                showConfirmButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: '#f5276c'
            });
        } else if (fileExtension === 'image') {
            console.log('Displaying image');
            // Display image in a modal
            const modalHtml = `<img src="${filePath}" style="max-width: 100%; max-height: 600px; border-radius: 4px;">`;
            Swal.fire({
                html: modalHtml,
                showConfirmButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: '#f5276c',
                width: 'auto'
            });
        } else {
            console.log('Opening file in new window');
            // For other file types, open in new window
            window.open(filePath, '_blank');
        }
        
        return false;
    }
    </script>
</body>
</html>