<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: signin.php');
    exit();
}

$page_title = 'My Photo Bookings | Amuning Tokobe Enterprise';
$additional_css = ['orders.css'];

// Include database config
require_once 'includes/config.php';

// Fetch user's photo bookings
$user_email = $_SESSION['user_email'];
$active_bookings = [];
$archived_bookings = [];

try {
    // Fetch active bookings (not archived by user)
    // Use subquery to get only the LATEST payment per booking to avoid duplicate payment records
    $stmt = $conn->prepare("
        SELECT pb.id, pb.name, pb.email, pb.mobile, pb.event_type, pb.product, pb.duration, pb.package_type, pb.event_date, pb.time_of_service, pb.venue, pb.street_address, pb.city, pb.region, pb.postal_code, pb.country, pb.estimated_price, pb.travel_fee, pb.status, pb.booking_date,
        COALESCE(p.status, 'Not Submitted') as payment_status, COALESCE(pb.user_archived, 0) as user_archived,
        CASE
            WHEN pb.status = 'pending' THEN 'Booking Not Yet Processed'
            WHEN pb.status = 'validated' THEN 'Booking Validated'
            WHEN pb.status = 'booked' THEN 'Booked'
            WHEN pb.status = 'processing' THEN 'Booking Processing'
            WHEN pb.status = 'completed' THEN 'Booking Completed'
            WHEN pb.status = 'cancelled' THEN 'Booking Cancelled'
            WHEN pb.status = 'declined' THEN 'Booking Declined'
            ELSE pb.status
        END as status_display
        FROM photo_bookings pb
        LEFT JOIN payments p ON pb.id = p.reference_id AND p.payment_type = 'photo_booking'
            AND p.id = (
                SELECT id FROM payments 
                WHERE reference_id = pb.id AND payment_type = 'photo_booking'
                ORDER BY created_at DESC
                LIMIT 1
            )
        WHERE pb.email = ? AND COALESCE(pb.user_archived, 0) = 0
        ORDER BY pb.event_date DESC
    ");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $active_bookings[] = $row;
    }

    $stmt->close();
    
    // Fetch archived bookings (archived by user)
    // Use subquery to get only the LATEST payment per booking to avoid duplicate payment records
    $stmt = $conn->prepare("
        SELECT pb.id, pb.name, pb.email, pb.mobile, pb.event_type, pb.product, pb.duration, pb.package_type, pb.event_date, pb.time_of_service, pb.venue, pb.street_address, pb.city, pb.region, pb.postal_code, pb.country, pb.estimated_price, pb.travel_fee, pb.status, pb.booking_date,
        COALESCE(p.status, 'Not Submitted') as payment_status, COALESCE(pb.user_archived, 0) as user_archived,
        CASE
            WHEN pb.status = 'pending' THEN 'Booking Not Yet Processed'
            WHEN pb.status = 'validated' THEN 'Booking Validated'
            WHEN pb.status = 'booked' THEN 'Booked'
            WHEN pb.status = 'processing' THEN 'Booking Processing'
            WHEN pb.status = 'completed' THEN 'Booking Completed'
            WHEN pb.status = 'cancelled' THEN 'Booking Cancelled'
            WHEN pb.status = 'declined' THEN 'Booking Declined'
            ELSE pb.status
        END as status_display
        FROM photo_bookings pb
        LEFT JOIN payments p ON pb.id = p.reference_id AND p.payment_type = 'photo_booking'
            AND p.id = (
                SELECT id FROM payments 
                WHERE reference_id = pb.id AND payment_type = 'photo_booking'
                ORDER BY created_at DESC
                LIMIT 1
            )
        WHERE pb.email = ? AND COALESCE(pb.user_archived, 0) = 1
        ORDER BY pb.event_date DESC
    ");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $archived_bookings[] = $row;
    }

    $stmt->close();
} catch (Exception $e) {
    // Handle error gracefully
    $active_bookings = [];
    $archived_bookings = [];
    $error_message = $e->getMessage();
    error_log("Database error in user_bookings.php: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link rel="stylesheet" href="assets/css/orders.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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

        /* Specific column minimum widths for bookings table */
        #bookingsTable, #archivedTable {
            table-layout: fixed !important;
        }
        
        #bookingsTable th, #bookingsTable td,
        #archivedTable th, #archivedTable td {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            vertical-align: middle;
        }

        /* Ensure action buttons stay on one line */
        #bookingsTable td:last-child, 
        #archivedTable td:last-child {
            white-space: nowrap;
            text-align: center;
            padding: 8px 4px !important;
        }

        #bookingsTable td:last-child .action-btn,
        #archivedTable td:last-child .action-btn {
            display: inline-block;
            margin: 0 3px;
            padding: 6px 8px;
            font-size: 14px;
        }
    </style>
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
            <?php if (empty($active_bookings) && empty($archived_bookings)): ?>
                <div class="no-orders">
                    <i class="fas fa-camera"></i>
                    <h3>No Photo Bookings Yet</h3>
                    <p>You haven't made any photo bookings yet.</p>
                    <a href="photo.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Book Your First Session</a>
                </div>
            <?php else: ?>
                <!-- Tabs for Active and Archived Bookings -->
                <div class="order-tabs" style="margin-bottom: 20px; border-bottom: 2px solid #eee;">
                    <button class="tab-btn active" data-tab="active-bookings" onclick="switchTab('active-bookings', this)">
                        <i class="fas fa-calendar-check"></i> Active Bookings <?php echo !empty($active_bookings) ? '(' . count($active_bookings) . ')' : ''; ?>
                    </button>
                    <button class="tab-btn" data-tab="archived-bookings" onclick="switchTab('archived-bookings', this)">
                        <i class="fas fa-archive"></i> Archived Bookings <?php echo !empty($archived_bookings) ? '(' . count($archived_bookings) . ')' : ''; ?>
                    </button>
                </div>

                <!-- Active Bookings Tab -->
                <div id="active-bookings" class="tab-content active">
                    <?php if (empty($active_bookings)): ?>
                        <div class="no-orders" style="padding: 40px 20px; text-align: center; color: #999;">
                            <p>No active bookings. <a href="photo.php">Make a new booking</a></p>
                        </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table id="bookingsTable" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Type</th>
                                    <th>Product</th>
                                    <th>Package</th>
                                    <th>Duration</th>
                                    <th>Event Date</th>
                                    <th>Venue</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php foreach ($active_bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($booking['event_type']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['product']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['package_type']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['duration']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($booking['event_date'])); ?><br>
                                        <small style="color: #666;"><?php echo date('h:i A', strtotime($booking['time_of_service'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['venue']); ?></td>
                                    <td>
                                        <?php
                                            $price_raw = $booking['estimated_price'] ?? '0';
                                            $price_clean = str_replace(['₱', ',', ' '], '', $price_raw);
                                            $price_numeric = (float) $price_clean;
                                            
                                            $travel_fee_raw = $booking['travel_fee'] ?? '0';
                                            $travel_fee_clean = str_replace(['₱', ',', ' '], '', $travel_fee_raw);
                                            $travel_fee_numeric = (float) $travel_fee_clean;
                                            
                                            echo '₱' . number_format($price_numeric, 2);
                                            if ($travel_fee_numeric > 0) {
                                                echo ' +₱' . number_format($travel_fee_numeric, 2) . ' (Travel Fee)';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            $total = $price_numeric + $travel_fee_numeric;
                                            echo '₱' . number_format($total, 2);
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status <?php echo strtolower($booking['status'] ?: 'pending'); ?>">
                                            <?php echo htmlspecialchars($booking['status_display'] ?? $booking['status'] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status <?php 
                                            $payment_status = $booking['payment_status'] ?? 'Not Submitted';
                                            echo strtolower(str_replace(' ', '-', $payment_status));
                                        ?>">
                                            <?php echo htmlspecialchars($payment_status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn view" title="View Booking Details" data-booking-id="<?php echo $booking['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn edit" title="<?php echo (in_array(strtolower($booking['status']), ['validated', 'booked', 'processing', 'completed'])) ? 'Cannot edit after validation' : 'Edit Booking'; ?>" data-booking-id="<?php echo $booking['id']; ?>" <?php echo (in_array(strtolower($booking['status']), ['validated', 'booked', 'processing', 'completed'])) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn cancel" title="<?php echo (strtolower($booking['status']) === 'pending' ? 'Cancel Booking' : 'Can only cancel pending bookings'); ?>" data-booking-id="<?php echo $booking['id']; ?>" data-type="photo_booking" <?php echo (strtolower($booking['status']) !== 'pending') ? 'disabled' : ''; ?>>
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                        <button class="action-btn archive" title="Archive Booking" data-booking-id="<?php echo $booking['id']; ?>" data-type="photo_booking" <?php echo (!in_array(strtolower($booking['status']), ['completed', 'cancelled', 'declined'])) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-archive"></i>
                                        </button>
                                        <?php
                                        $booking_status = strtolower($booking['status'] ?: 'pending');
                                        $payment_status = strtolower($booking['payment_status'] ?: 'not submitted');

                                        // Payment button only enabled if booking is validated and payment not submitted
                                        $disable_payment = ($payment_status !== 'not submitted') || ($booking_status !== 'validated');
                                        ?>

                                        <?php if (!$disable_payment): ?>
                                        <button class="action-btn pay" title="Pay Now" data-booking-id="<?php echo $booking['id']; ?>">
                                            <i class="fas fa-credit-card"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="action-btn pay" title="<?php echo ($booking_status !== 'validated') ? 'Booking must be validated before payment' : 'Payment already processed'; ?>" disabled>
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if ($booking_status === 'booked'): ?>
                                        <a href="download_booking_receipt.php?booking_id=<?php echo $booking['id']; ?>" target="_blank" class="action-btn download" title="Download Receipt" style="text-decoration: none; background: #22c55e; color: white; display: inline-flex; align-items: center; justify-content: center;">
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

                <!-- Archived Bookings Tab -->
                <div id="archived-bookings" class="tab-content" style="display: none;">
                    <?php if (empty($archived_bookings)): ?>
                        <div class="no-orders" style="padding: 40px 20px; text-align: center; color: #999;">
                            <p>No archived bookings yet.</p>
                        </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table id="archivedTable" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Type</th>
                                    <th>Product</th>
                                    <th>Package</th>
                                    <th>Duration</th>
                                    <th>Event Date</th>
                                    <th>Venue</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived_bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($booking['event_type']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['product']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['package_type']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['duration']); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($booking['event_date'])); ?><br>
                                            <small style="color: #666;"><?php echo date('h:i A', strtotime($booking['time_of_service'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['venue']); ?></td>
                                        <td>
                                            <?php
                                                $price_raw = $booking['estimated_price'] ?? '0';
                                                $price_clean = str_replace(['₱', ',', ' '], '', $price_raw);
                                                $price_numeric = (float) $price_clean;
                                                
                                                $travel_fee_raw = $booking['travel_fee'] ?? '0';
                                                $travel_fee_clean = str_replace(['₱', ',', ' '], '', $travel_fee_raw);
                                                $travel_fee_numeric = (float) $travel_fee_clean;
                                                
                                                echo '₱' . number_format($price_numeric, 2);
                                                if ($travel_fee_numeric > 0) {
                                                    echo ' +₱' . number_format($travel_fee_numeric, 2) . ' (Travel Fee)';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $total = $price_numeric + $travel_fee_numeric;
                                                echo '₱' . number_format($total, 2);
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status <?php echo strtolower($booking['status'] ?: 'pending'); ?>">
                                                <?php echo htmlspecialchars($booking['status_display'] ?? $booking['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status <?php 
                                                $payment_status = $booking['payment_status'] ?? 'Not Submitted';
                                                echo strtolower(str_replace(' ', '-', $payment_status));
                                            ?>">
                                                <?php echo htmlspecialchars($payment_status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="action-btn view" title="View Booking Details" data-booking-id="<?php echo $booking['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn unarchive" title="Restore to Active" data-booking-id="<?php echo $booking['id']; ?>" data-type="photo_booking">
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

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
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

        // Initialize DataTable with proper scrolling configuration
        const bookingsTable = $('#bookingsTable').DataTable({
            responsive: false, // Disable DataTables responsive for custom mobile handling
            scrollX: false, // Disable DataTables scroll to prevent header/body misalignment
            autoWidth: false, // Prevent auto width calculation issues
            paging: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "Search bookings:",
                lengthMenu: "Show _MENU_ bookings per page",
                info: "Showing _START_ to _END_ of _TOTAL_ bookings",
                infoEmpty: "No bookings available",
                infoFiltered: "(filtered from _MAX_ total bookings)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                },
                zeroRecords: "No matching bookings found"
            },
            columnDefs: [
                { width: "4%", targets: 0 }, // ID
                { width: "8%", targets: 1 }, // Event Type
                { width: "8%", targets: 2 }, // Product
                { width: "7%", targets: 3 }, // Package
                { width: "7%", targets: 4 }, // Duration
                { width: "9%", targets: 5 }, // Event Date
                { width: "8%", targets: 6 }, // Venue
                { width: "9%", targets: 7 }, // Price
                { width: "8%", targets: 8 }, // Total
                { width: "7%", targets: 9 }, // Status
                { width: "7%", targets: 10 }, // Payment
                {
                    targets: 11, // Actions column
                    orderable: false,
                    searchable: false,
                    width: "11%",
                    className: "text-center"
                }
            ],
            order: [[0, 'desc']], // Sort by ID (newest first)
            initComplete: function() {
                console.log('DataTable initialized successfully');
                // Force redraw to ensure proper column rendering
                setTimeout(() => {
                    bookingsTable.columns.adjust().draw();
                }, 100);

                // Attach event handlers after DataTable is fully initialized
                attachEventHandlers();
            },
            drawCallback: function() {
                // Add data-labels for mobile view
                $('#bookingsTable thead th').each(function(i) {
                    $('td:nth-child(' + (i + 1) + ')', '#bookingsTable tbody').attr('data-label', $(this).text());
                });

                // Re-attach event handlers after DataTable redraw
                attachEventHandlers();
            }
        });

        // Initialize archived bookings table if it exists
        if ($('#archivedTable').length) {
            const archivedTable = $('#archivedTable').DataTable({
                responsive: false,
                scrollX: false,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                language: {
                    search: "Search bookings:",
                    lengthMenu: "Show _MENU_ bookings per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ bookings",
                    infoEmpty: "No bookings available",
                    infoFiltered: "(filtered from _MAX_ total bookings)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    zeroRecords: "No matching bookings found"
                },
                columnDefs: [
                    { width: "4%", targets: 0 }, // ID
                    { width: "8%", targets: 1 }, // Event Type
                    { width: "8%", targets: 2 }, // Product
                    { width: "7%", targets: 3 }, // Package
                    { width: "7%", targets: 4 }, // Duration
                    { width: "9%", targets: 5 }, // Event Date
                    { width: "8%", targets: 6 }, // Venue
                    { width: "9%", targets: 7 }, // Price
                    { width: "8%", targets: 8 }, // Total
                    { width: "7%", targets: 9 }, // Status
                    { width: "7%", targets: 10 }, // Payment
                    {
                        targets: 11, // Actions column
                        orderable: false,
                        searchable: false,
                        width: "11%",
                        className: "text-center"
                    }
                ],
                order: [[0, 'desc']],
                initComplete: function() {
                    console.log('Archived DataTable initialized successfully');
                    setTimeout(() => {
                        archivedTable.columns.adjust().draw();
                        attachEventHandlers();
                    }, 100);
                },
                drawCallback: function() {
                    $('#archivedTable thead th').each(function(i) {
                        $('td:nth-child(' + (i + 1) + ')', '#archivedTable tbody').attr('data-label', $(this).text());
                    });
                    attachEventHandlers();
                }
            });
        }

        // Auto-update user's validated+paid bookings to booked status
        function autoUpdateUserValidatedPaidBookings() {
            // Check each row in the active bookings table
            const rows = $('#bookingsTable tbody tr');
            rows.each(function() {
                const row = $(this);
                const bookingId = row.find('td:first').text().trim();
                
                // Get the status and payment columns (9th and 10th columns)
                const statusCell = row.find('td:eq(9)');
                const paymentCell = row.find('td:eq(10)');
                
                const statusText = statusCell.text().trim().toLowerCase();
                const paymentText = paymentCell.text().trim().toLowerCase();
                
                console.log(`Booking #${bookingId}: status="${statusText}", payment="${paymentText}"`);
                
                // Check if status is Validated and payment is Paid
                if (statusText.includes('validated') && paymentText === 'paid') {
                    console.log(`Auto-updating booking #${bookingId} from Validated to Booked`);
                    
                    // Make AJAX call to update status to booked
                    $.ajax({
                        url: 'admin/actions/update_booth_order_status.php',
                        type: 'POST',
                        data: {
                            id: bookingId,
                            status: 'booked'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                console.log(`Booking #${bookingId} successfully updated to Booked`);
                                // Reload to show updated status
                                location.reload();
                            } else {
                                console.error(`Failed to update booking #${bookingId}: ${response.message}`);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(`Error updating booking #${bookingId}: ${error}`);
                        }
                    });
                }
            });
        }

        // Run auto-update after DataTable is initialized
        setTimeout(() => {
            autoUpdateUserValidatedPaidBookings();
        }, 500);

        // Auto-refresh every 8 seconds to check for validated+paid bookings that need updating
        setInterval(function() {
            console.log('Auto-checking user bookings for validated+paid status...');
            autoUpdateUserValidatedPaidBookings();
        }, 8000); // 8 seconds

        // Function to attach all event handlers
        // Helper function to calculate total price
        function calculateTotal(priceStr, feeStr) {
            // Extract numeric values from price and fee strings
            const priceNum = parseFloat((priceStr || '₱0').toString().replace(/[₱$,\s]/g, ''));
            const feeNum = parseFloat((feeStr || '₱0').toString().replace(/[₱$,\s]/g, ''));
            const total = (priceNum + feeNum).toFixed(2);
            return '₱' + parseFloat(total).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Function to attach all event handlers
        function attachEventHandlers() {
            console.log('Attaching event handlers...');

            // View booking functionality
            $(document).off('click', '.action-btn.view').on('click', '.action-btn.view', function() {
                const bookingId = $(this).data('booking-id');
                console.log('View booking:', bookingId);

                // Get full booking data from the PHP array
                <?php foreach ($active_bookings as $booking): ?>
                if (bookingId == '<?php echo $booking["id"]; ?>') {
                    const bookingData = {
                        id: '<?php echo $booking["id"]; ?>',
                        name: '<?php echo addslashes($booking["name"]); ?>',
                        email: '<?php echo addslashes($booking["email"]); ?>',
                        mobile: '<?php echo addslashes($booking["mobile"] ?? "N/A"); ?>',
                        event_type: '<?php echo addslashes($booking["event_type"]); ?>',
                        product: '<?php echo addslashes($booking["product"]); ?>',
                        package_type: '<?php echo addslashes($booking["package_type"]); ?>',
                        duration: '<?php echo addslashes($booking["duration"]); ?>',
                        event_date: '<?php echo date('M d, Y', strtotime($booking["event_date"])); ?>',
                        time_of_service: '<?php echo date('h:i A', strtotime($booking["time_of_service"])); ?>',
                        venue: '<?php echo addslashes($booking["venue"]); ?>',
                        street_address: '<?php echo addslashes($booking["street_address"] ?? "N/A"); ?>',
                        city: '<?php echo addslashes($booking["city"] ?? "N/A"); ?>',
                        region: '<?php echo addslashes($booking["region"] ?? "N/A"); ?>',
                        postal_code: '<?php echo addslashes($booking["postal_code"] ?? "N/A"); ?>',
                        country: '<?php echo addslashes($booking["country"] ?? "N/A"); ?>',
                        estimated_price: '₱<?php
                            $price_raw = $booking["estimated_price"] ?? "0";
                            $price_clean = str_replace(["₱", ",", " "], "", $price_raw);
                            $price_numeric = (float) $price_clean;
                            echo number_format($price_numeric, 2);
                        ?>',
                        travel_fee: '₱<?php
                            $travel_fee_raw = $booking["travel_fee"] ?? "0";
                            $travel_fee_clean = str_replace(["₱", ",", " "], "", $travel_fee_raw);
                            $travel_fee_numeric = (float) $travel_fee_clean;
                            echo number_format($travel_fee_numeric, 2);
                        ?>',
                        status: '<?php echo addslashes($booking["status"] ?? "Pending"); ?>',
                        payment_status: '<?php echo addslashes($booking["payment_status"] ?? "Not Submitted"); ?>',
                        booking_date: '<?php echo date("M d, Y", strtotime($booking["booking_date"] ?? $booking["event_date"])); ?>'
                    };

                    // Create comprehensive view modal
                    const modalContent = `
                        <div class="modal-booking-content">
                            <div class="modal-header">
                                <h3><i class="fas fa-calendar-check"></i> Complete Booking Details</h3>
                                <p>Booking ID: #${String(bookingData.id).padStart(4, '0')}</p>
                            </div>

                            <div class="modal-body">
                                <div class="modal-grid modal-grid-2col">
                                    <div class="modal-card modal-card-primary">
                                        <h4><i class="fas fa-camera"></i> Event Details</h4>
                                        <p><strong>Type:</strong> ${bookingData.event_type}</p>
                                        <p><strong>Product:</strong> ${bookingData.product}</p>
                                        <p><strong>Package:</strong> ${bookingData.package_type}</p>
                                        <p><strong>Duration:</strong> ${bookingData.duration}</p>
                                    </div>

                                    <div class="modal-card modal-card-success">
                                        <h4><i class="fas fa-clock"></i> Schedule</h4>
                                        <p><strong>Date:</strong> ${bookingData.event_date}</p>
                                        <p><strong>Time:</strong> ${bookingData.time_of_service}</p>
                                        <p><strong>Venue:</strong> ${bookingData.venue}</p>
                                    </div>

                                    <div class="modal-card modal-card-warning">
                                        <h4><i class="fas fa-user"></i> Contact Information</h4>
                                        <p><strong>Name:</strong> ${bookingData.name}</p>
                                        <p><strong>Email:</strong> ${bookingData.email}</p>
                                        <p><strong>Mobile:</strong> ${bookingData.mobile}</p>
                                    </div>

                                    <div class="modal-card modal-card-danger">
                                        <h4><i class="fas fa-map-marker-alt"></i> Location</h4>
                                        <p><strong>Address:</strong> ${bookingData.street_address}</p>
                                        <p><strong>City:</strong> ${bookingData.city}</p>
                                        <p><strong>Region:</strong> ${bookingData.region}</p>
                                        <p><strong>Postal:</strong> ${bookingData.postal_code}</p>
                                        <p><strong>Country:</strong> ${bookingData.country}</p>
                                    </div>
                                </div>

                                <div class="modal-price-section">
                                    <h4><i class="fas fa-tag"></i> Price & Status</h4>
                                    <div class="price-breakdown">
                                        <p><strong>Price:</strong> ${bookingData.estimated_price}</p>
                                        <p><strong>Travel Fee:</strong> ${bookingData.travel_fee || '₱0.00'}</p>
                                        <hr style="border: none; border-top: 1px solid #ddd; margin: 8px 0;">
                                        <p style="font-weight: bold; font-size: 16px;"><strong>Total:</strong> ${calculateTotal(bookingData.estimated_price, bookingData.travel_fee)}</p>
                                    </div>
                                    <div class="modal-status-container">
                                        <span class="modal-status-badge">Booking: ${bookingData.status}</span>
                                        <span class="modal-status-badge">Payment: ${bookingData.payment_status}</span>
                                    </div>
                                    <p class="modal-booked-date">Booked on: ${bookingData.booking_date}</p>
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
                        width: '700px',
                        customClass: {
                            popup: 'booking-details-modal'
                        }
                    });

                    return;
                }
                <?php endforeach; ?>

                // Check archived bookings
                <?php foreach ($archived_bookings as $booking): ?>
                if (bookingId == '<?php echo $booking["id"]; ?>') {
                    const bookingData = {
                        id: '<?php echo $booking["id"]; ?>',
                        name: '<?php echo addslashes($booking["name"]); ?>',
                        email: '<?php echo addslashes($booking["email"]); ?>',
                        mobile: '<?php echo addslashes($booking["mobile"] ?? "N/A"); ?>',
                        event_type: '<?php echo addslashes($booking["event_type"]); ?>',
                        product: '<?php echo addslashes($booking["product"]); ?>',
                        package_type: '<?php echo addslashes($booking["package_type"]); ?>',
                        duration: '<?php echo addslashes($booking["duration"]); ?>',
                        event_date: '<?php echo date('M d, Y', strtotime($booking["event_date"])); ?>',
                        time_of_service: '<?php echo date('h:i A', strtotime($booking["time_of_service"])); ?>',
                        venue: '<?php echo addslashes($booking["venue"]); ?>',
                        street_address: '<?php echo addslashes($booking["street_address"] ?? "N/A"); ?>',
                        city: '<?php echo addslashes($booking["city"] ?? "N/A"); ?>',
                        region: '<?php echo addslashes($booking["region"] ?? "N/A"); ?>',
                        postal_code: '<?php echo addslashes($booking["postal_code"] ?? "N/A"); ?>',
                        country: '<?php echo addslashes($booking["country"] ?? "N/A"); ?>',
                        estimated_price: '₱<?php
                            $price_raw = $booking["estimated_price"] ?? "0";
                            $price_clean = str_replace(["₱", ",", " "], "", $price_raw);
                            $price_numeric = (float) $price_clean;
                            echo number_format($price_numeric, 2);
                        ?>',
                        travel_fee: '₱<?php
                            $travel_fee_raw = $booking["travel_fee"] ?? "0";
                            $travel_fee_clean = str_replace(["₱", ",", " "], "", $travel_fee_raw);
                            $travel_fee_numeric = (float) $travel_fee_clean;
                            echo number_format($travel_fee_numeric, 2);
                        ?>',
                        status: '<?php echo addslashes($booking["status"] ?? "Pending"); ?>',
                        payment_status: '<?php echo addslashes($booking["payment_status"] ?? "Not Submitted"); ?>',
                        booking_date: '<?php echo date("M d, Y", strtotime($booking["booking_date"] ?? $booking["event_date"])); ?>'
                    };

                    const modalContent = `
                        <div class="modal-booking-content">
                            <div class="modal-header">
                                <h3><i class="fas fa-calendar-check"></i> Complete Booking Details</h3>
                                <p>Booking ID: #${String(bookingData.id).padStart(4, '0')}</p>
                            </div>
                            <div class="modal-body">
                                <div class="modal-grid modal-grid-2col">
                                    <div class="modal-card modal-card-primary">
                                        <h4><i class="fas fa-camera"></i> Event Details</h4>
                                        <p><strong>Type:</strong> ${bookingData.event_type}</p>
                                        <p><strong>Product:</strong> ${bookingData.product}</p>
                                        <p><strong>Package:</strong> ${bookingData.package_type}</p>
                                        <p><strong>Duration:</strong> ${bookingData.duration}</p>
                                    </div>
                                    <div class="modal-card modal-card-secondary">
                                        <h4><i class="fas fa-calendar-alt"></i> Schedule</h4>
                                        <p><strong>Date:</strong> ${bookingData.event_date}</p>
                                        <p><strong>Time:</strong> ${bookingData.time_of_service}</p>
                                    </div>
                                    <div class="modal-card modal-card-tertiary">
                                        <h4><i class="fas fa-user-circle"></i> Contact Information</h4>
                                        <p><strong>Name:</strong> ${bookingData.name}</p>
                                        <p><strong>Email:</strong> ${bookingData.email}</p>
                                        <p><strong>Mobile:</strong> ${bookingData.mobile}</p>
                                    </div>
                                    <div class="modal-card modal-card-danger">
                                        <h4><i class="fas fa-map-marker-alt"></i> Location</h4>
                                        <p><strong>Address:</strong> ${bookingData.street_address}</p>
                                        <p><strong>City:</strong> ${bookingData.city}</p>
                                        <p><strong>Region:</strong> ${bookingData.region}</p>
                                        <p><strong>Postal:</strong> ${bookingData.postal_code}</p>
                                        <p><strong>Country:</strong> ${bookingData.country}</p>
                                    </div>
                                </div>
                                <div class="modal-price-section">
                                    <h4><i class="fas fa-tag"></i> Price & Status</h4>
                                    <div class="price-breakdown">
                                        <p><strong>Price:</strong> ${bookingData.estimated_price}</p>
                                        <p><strong>Travel Fee:</strong> ${bookingData.travel_fee || '₱0.00'}</p>
                                        <hr style="border: none; border-top: 1px solid #ddd; margin: 8px 0;">
                                        <p style="font-weight: bold; font-size: 16px;"><strong>Total:</strong> ${calculateTotal(bookingData.estimated_price, bookingData.travel_fee)}</p>
                                    </div>
                                    <div class="modal-status-container">
                                        <span class="modal-status-badge">Booking: ${bookingData.status}</span>
                                        <span class="modal-status-badge">Payment: ${bookingData.payment_status}</span>
                                    </div>
                                    <p class="modal-booked-date">Booked on: ${bookingData.booking_date}</p>
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
                        width: '700px',
                        customClass: {
                            popup: 'booking-details-modal'
                        }
                    });

                    return;
                }
                <?php endforeach; ?>

            });
            $(document).off('click', '.action-btn.edit').on('click', '.action-btn.edit', function() {
                const bookingId = $(this).data('booking-id');
                console.log('Edit booking:', bookingId);

                // Get full booking data from the PHP array
                <?php foreach ($active_bookings as $booking): ?>
                if (bookingId == '<?php echo $booking["id"]; ?>') {
                    const bookingData = {
                        id: '<?php echo $booking["id"]; ?>',
                        name: '<?php echo addslashes($booking["name"]); ?>',
                        email: '<?php echo addslashes($booking["email"]); ?>',
                        mobile: '<?php echo addslashes($booking["mobile"] ?? ""); ?>',
                        event_type: '<?php echo addslashes($booking["event_type"]); ?>',
                        product: '<?php echo addslashes($booking["product"]); ?>',
                        package_type: '<?php echo addslashes($booking["package_type"]); ?>',
                        duration: '<?php echo addslashes($booking["duration"]); ?>',
                        event_date: '<?php echo $booking["event_date"]; ?>',
                        time_of_service: '<?php echo $booking["time_of_service"]; ?>',
                        venue: '<?php echo addslashes($booking["venue"]); ?>',
                        street_address: '<?php echo addslashes($booking["street_address"] ?? ""); ?>',
                        city: '<?php echo addslashes($booking["city"] ?? ""); ?>',
                        region: '<?php echo addslashes($booking["region"] ?? ""); ?>',
                        postal_code: '<?php echo addslashes($booking["postal_code"] ?? ""); ?>',
                        country: '<?php echo addslashes($booking["country"] ?? ""); ?>'
                    };

                    // Create edit form modal
                    const editFormContent = `
                        <div class="edit-booking-form">
                            <form id="editBookingForm">
                                <input type="hidden" id="editBookingId" value="${bookingData.id}">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Full Name *</label>
                                        <input type="text" id="editName" value="${bookingData.name}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email *</label>
                                        <input type="email" id="editEmail" value="${bookingData.email}" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Mobile Number *</label>
                                        <input type="text" id="editMobile" value="${bookingData.mobile}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Event Type *</label>
                                        <input type="text" id="editEventType" value="${bookingData.event_type}" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Event Date *</label>
                                        <input type="date" id="editEventDate" value="${bookingData.event_date}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Time of Service *</label>
                                        <input type="time" id="editTimeOfService" value="${bookingData.time_of_service}" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Venue *</label>
                                        <input type="text" id="editVenue" value="${bookingData.venue}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Duration *</label>
                                        <input type="text" id="editDuration" value="${bookingData.duration}" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Street Address</label>
                                        <input type="text" id="editStreetAddress" value="${bookingData.street_address}">
                                    </div>
                                    <div class="form-group">
                                        <label>City</label>
                                        <input type="text" id="editCity" value="${bookingData.city}">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Region</label>
                                        <input type="text" id="editRegion" value="${bookingData.region}">
                                    </div>
                                    <div class="form-group">
                                        <label>Postal Code</label>
                                        <input type="text" id="editPostalCode" value="${bookingData.postal_code}">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Country</label>
                                        <input type="text" id="editCountry" value="${bookingData.country}">
                                    </div>
                                </div>

                                <style>
                                    .edit-booking-form {
                                        max-height: 500px;
                                        overflow-y: auto;
                                        padding: 20px;
                                    }
                                    .form-row {
                                        display: grid;
                                        grid-template-columns: 1fr 1fr;
                                        gap: 20px;
                                        margin-bottom: 20px;
                                    }
                                    .form-group {
                                        display: flex;
                                        flex-direction: column;
                                    }
                                    .form-group label {
                                        margin-bottom: 8px;
                                        font-weight: 600;
                                        color: #333;
                                    }
                                    .form-group input {
                                        padding: 10px;
                                        border: 1px solid #ddd;
                                        border-radius: 5px;
                                        font-size: 14px;
                                    }
                                    .form-group input:focus {
                                        outline: none;
                                        border-color: #f5276c;
                                        box-shadow: 0 0 0 3px rgba(245, 39, 108, 0.1);
                                    }
                                </style>
                            </form>
                        </div>
                    `;

                    Swal.fire({
                        html: editFormContent,
                        showConfirmButton: true,
                        confirmButtonText: 'Save Changes',
                        confirmButtonColor: '#f5276c',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        showCloseButton: true,
                        width: '700px',
                        preConfirm: () => {
                            // Validate required fields
                            const name = document.getElementById('editName').value.trim();
                            const email = document.getElementById('editEmail').value.trim();
                            const mobile = document.getElementById('editMobile').value.trim();
                            const eventType = document.getElementById('editEventType').value.trim();
                            const eventDate = document.getElementById('editEventDate').value;
                            const timeOfService = document.getElementById('editTimeOfService').value;
                            const venue = document.getElementById('editVenue').value.trim();
                            const duration = document.getElementById('editDuration').value.trim();

                            if (!name || !email || !mobile || !eventType || !eventDate || !timeOfService || !venue || !duration) {
                                Swal.showValidationMessage('Please fill in all required fields (*)');
                                return false;
                            }

                            return {
                                id: bookingData.id,
                                name: name,
                                email: email,
                                mobile: mobile,
                                event_type: eventType,
                                product: bookingData.product,
                                package_type: bookingData.package_type,
                                duration: duration,
                                event_date: eventDate,
                                time_of_service: timeOfService,
                                venue: venue,
                                street_address: document.getElementById('editStreetAddress').value.trim(),
                                city: document.getElementById('editCity').value.trim(),
                                region: document.getElementById('editRegion').value.trim(),
                                postal_code: document.getElementById('editPostalCode').value.trim(),
                                country: document.getElementById('editCountry').value.trim()
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            // AJAX call to save booking changes
                            $.ajax({
                                url: 'edit_booking.php',
                                type: 'POST',
                                data: result.value,
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        Swal.fire(
                                            'Success!',
                                            'Your booking has been updated successfully.',
                                            'success'
                                        );
                                        // Reload page to reflect changes
                                        setTimeout(() => {
                                            location.reload();
                                        }, 1500);
                                    } else {
                                        Swal.fire(
                                            'Error!',
                                            'Failed to update booking: ' + response.message,
                                            'error'
                                        );
                                    }
                                },
                                error: function(xhr) {
                                    Swal.fire(
                                        'Error!',
                                        'Failed to update booking. Please try again.',
                                        'error'
                                    );
                                }
                            });
                        }
                    });

                    return;
                }
                <?php endforeach; ?>
            });

            // Delete booking functionality
            $(document).off('click', '.action-btn.delete').on('click', '.action-btn.delete', function() {
                const bookingId = $(this).data('booking-id');
                const row = $(this).closest('tr');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this photo booking!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f5276c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete this booking!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // AJAX call to delete booking
                        $.ajax({
                            url: 'delete_booking.php',
                            type: 'POST',
                            data: {
                                booking_id: bookingId,
                                type: 'photo'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Deleted!',
                                        'Your photo booking has been deleted.',
                                        'success'
                                    );
                                    // Remove row from DataTable
                                    bookingsTable.row(row).remove().draw();
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'Failed to delete booking: ' + response.message,
                                        'error'
                                    );
                                }
                            },
                            error: function() {
                                Swal.fire(
                                    'Error!',
                                    'Failed to delete booking. Please try again.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // Pay for booking functionality
            $(document).off('click', '.action-btn.pay').on('click', '.action-btn.pay', function() {
                const bookingId = $(this).data('booking-id');

                Swal.fire({
                    title: 'Proceed to Payment',
                    text: 'You will be redirected to the payment page for this booking.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f5276c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Continue to Payment'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `payment.php?booking_id=${bookingId}`;
                    }
                });
            });

            console.log('Event handlers attached successfully');
        }

        // Cancel Booking Handler
        $(document).off('click', '.action-btn.cancel').on('click', '.action-btn.cancel', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const bookingId = $(this).data('booking-id');
            const bookingType = $(this).data('type') || 'photo_booking';
            
            Swal.fire({
                title: 'Cancel Booking?',
                text: 'Are you sure you want to cancel this booking? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f5276c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel Booking',
                cancelButtonText: 'No, Keep It'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/cancel_order.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            order_id: bookingId,
                            order_type: bookingType
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Booking Cancelled',
                                    text: response.message || 'Your booking has been cancelled successfully.',
                                    confirmButtonColor: '#f5276c'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to cancel booking', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to cancel booking. Please try again.', 'error');
                        }
                    });
                }
            });
        });

        // Archive Booking Handler
        $(document).off('click', '.action-btn.unarchive, .action-btn.archive').on('click', '.action-btn.unarchive, .action-btn.archive', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const bookingId = $(this).data('booking-id');
            const bookingType = $(this).data('type') || 'photo_booking';
            const isArchiving = $(this).hasClass('archive');
            
            const title = isArchiving ? 'Archive Booking?' : 'Restore Booking?';
            const text = isArchiving ? 
                'Are you sure you want to archive this booking? You can find it in the Archived tab.' :
                'Are you sure you want to restore this booking to active bookings?';
            
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
                            order_id: bookingId,
                            order_type: bookingType,
                            action: isArchiving ? 'archive' : 'unarchive'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: isArchiving ? 'Booking Archived' : 'Booking Restored',
                                    text: response.message || 'Operation completed successfully.',
                                    confirmButtonColor: '#f5276c'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to update booking', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to update booking. Please try again.', 'error');
                        }
                    });
                }
            });
        });
    });

    // Tab Switching Function
    function switchTab(tabName, buttonEl) {
        // Hide all tabs by removing active class and setting display to none
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
            
            // Reinitialize DataTables with proper column sizing
            setTimeout(() => {
                if (tabName === 'active-bookings' && $.fn.DataTable.isDataTable('#bookingsTable')) {
                    const table = $('#bookingsTable').DataTable();
                    table.columns.adjust().draw();
                } else if (tabName === 'archived-bookings' && $.fn.DataTable.isDataTable('#archivedTable')) {
                    const table = $('#archivedTable').DataTable();
                    // Force recalculation of column widths
                    table.columns.adjust().draw();
                }
            }, 50);
        }
    }
    </script>
</body>
</html>