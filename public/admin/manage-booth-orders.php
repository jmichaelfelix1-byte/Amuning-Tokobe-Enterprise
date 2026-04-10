<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: signin.php');
    exit();
}

$current_page = 'manage-booth-orders.php';
$page_title = 'Manage Booth Orders';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="../../images/amuninglogo.ico">
    <link rel="shortcut icon" href="../../images/amuninglogo.ico" type="image/x-icon">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="modal.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Simple styling for the status tabs */
        #boothOrderStatusTabs .status-tab {
            background: #f1f3f5;
            border: 1px solid #dcdcdc;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        #boothOrderStatusTabs .status-tab.active {
            background: #f5276c;
            color: #fff;
            border-color: #f5276c;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>

        <main class="admin-main">
            <header class="main-header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Manage Booth Orders</h1>
            </header>

            <div class="main-content">
                <div class="content-wrapper">
                    <div class="recent-orders">
                        <div class="section-header">
                            <h2>Booth Orders</h2>
                        </div>

                        <!-- Booking status tabs -->
                        <div id="boothOrderStatusTabs" style="margin-bottom: 12px; display:flex; gap:8px; flex-wrap:wrap;">
                            <button class="status-tab active" data-status="all">All</button>
                            <button class="status-tab" data-status="not_yet">Not Yet Processed</button>
                            <button class="status-tab" data-status="validated">Validated</button>
                            <button class="status-tab" data-status="booked">Booked</button>
                            <button class="status-tab" data-status="completed">Completed</button>
                            <button class="status-tab" data-status="declined">Declined</button>
                            <button class="status-tab" data-status="cancelled">Cancelled Orders</button>
                            <button class="status-tab" data-status="archived">Archived Orders</button>
                        </div>

                        <div class="table-container">
                            <table id="boothOrdersTable" class="display responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Event Type</th>
                                        <th>Event Date</th>
                                        <th>Time</th>
                                        <th>Address</th>
                                        <th>Price</th>
                                        <th>Order Date</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="12" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-right: 10px;"></i>
                                            Loading booth orders...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include '../includes/admin_footer.php'; ?>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Booth Order Details</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script src="../assets/js/admin-sidebar.js"></script>

    <script>
    $(document).ready(function() {
        let allOrdersData = []; // Store original data for filtering
        let ordersTable; // DataTable instance

        // Helper function to format prices
        function formatPrice(price) {
            const numericPrice = parseFloat((price || '0').toString().replace(/[₱$,\s]/g, ''));
            return isNaN(numericPrice) ? '₱0.00' : '₱' + numericPrice.toFixed(2);
        }

        function calculateTotal(priceStr, feeStr) {
            const priceNum = parseFloat((priceStr || '₱0').toString().replace(/[₱$,\s]/g, ''));
            const feeNum = parseFloat((feeStr || '₱0').toString().replace(/[₱$,\s]/g, ''));
            const total = (priceNum + feeNum).toFixed(2);
            return '₱' + parseFloat(total).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Format price details with price, travel fee, and total
        function formatPriceDetails(order) {
            const price = formatPrice(order.estimated_price);
            const travelFee = formatPrice(order.travel_fee || '0');
            const total = calculateTotal(order.estimated_price, order.travel_fee);
            
            return `<div style="font-size: 13px; line-height: 1.6;">
                        <div><strong>Price:</strong> ${price}</div>
                        <div><strong>Travel Fee:</strong> ${travelFee}</div>
                        <div style="border-top: 1px solid #ddd; padding-top: 4px; margin-top: 4px;"><strong>Total:</strong> ${total}</div>
                    </div>`;
        }

        // Load booth orders data
        function loadBoothOrders() {
            $.ajax({
                url: 'actions/get_all_photo_bookings.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Store original data for filtering
                        allOrdersData = response.data;

                        // Auto-update validated+paid orders to booked
                        autoUpdateValidatedPaidOrders(allOrdersData);

                        // Apply 'all' filter to exclude archived bookings on initial load
                        applyTabFilterBooth('all');
                    } else {
                        console.error('Failed to load booth orders:', response.error);
                        $('#boothOrdersTable tbody').html('<tr><td colspan="12" style="text-align: center;">Failed to load data</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        statusCode: xhr.status,
                        responseText: xhr.responseText,
                        readyState: xhr.readyState
                    });
                    $('#boothOrdersTable tbody').html('<tr><td colspan="12" style="text-align: center;">Failed to load data</td></tr>');
                }
            });
        }

        // Auto-update orders that are Validated and Paid to Booked
        function autoUpdateValidatedPaidOrders(orders) {
            orders.forEach(function(order) {
                const orderStatus = (order.order_status_display || order.status || '').toLowerCase();
                const paymentStatus = (order.payment_status || '').toLowerCase();

                console.log('Order #' + order.id + ': orderStatus="' + orderStatus + '", paymentStatus="' + paymentStatus + '"');

                // Check if order is validated and payment is paid
                if (orderStatus.includes('validated') && paymentStatus === 'paid') {
                    console.log('Auto-updating order #' + order.id + ' from Validated to Booked (Paid)');
                    
                    // Make AJAX call to update status
                    $.ajax({
                        url: 'actions/update_booth_order_status.php',
                        type: 'POST',
                        data: {
                            id: order.id,
                            status: 'booked'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                console.log('Order #' + order.id + ' status updated to Booked');
                            } else {
                                console.error('Failed to update order #' + order.id + ': ' + response.message);
                            }
                        },
                        error: function() {
                            console.error('Error updating order #' + order.id);
                        }
                    });
                }
            });
        }

        // Function to initialize DataTable
        function initializeTable(data) {
            const ordersData = data.map(order => [
                order.id,
                order.name,
                order.email || 'N/A',
                formatEventDetails(order),
                new Date(order.event_date).toLocaleDateString(),
                order.time_of_service || 'N/A',
                formatAddress(order),
                formatPriceDetails(order),
                new Date(order.date).toLocaleDateString(),
                getStatusBadge(order),
                formatPaymentStatus(order.payment_status_display),
                getActionButtons(order.id, order)
            ]);

            // Destroy existing table if it exists
            if (ordersTable) {
                ordersTable.destroy();
            }

            ordersTable = $('#boothOrdersTable').DataTable({
                data: ordersData,
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                language: {
                    search: "Search booth orders:",
                    lengthMenu: "Show _MENU_ orders",
                    info: "Showing _START_ to _END_ of _TOTAL_ booth orders",
                    infoEmpty: "No booth orders available",
                    infoFiltered: "(filtered from _MAX_ total booth orders)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Prev"
                    },
                    zeroRecords: "No matching booth orders found"
                },
                columnDefs: [
                    { width: "4%", targets: 0, className: 'all' },     // ID
                    { width: "10%", targets: 1, className: 'all' },    // Name
                    { width: "12%", targets: 2, className: 'all' },    // Email
                    {
                        targets: 3, // Event Details column
                        width: "12%",
                        className: 'all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    { width: "8%", targets: 4, className: 'all' },     // Event Date
                    { width: "8%", targets: 5, className: 'all' },     // Time
                    {
                        targets: 6, // Address column (adjusted index)
                        width: "10%",
                        className: 'all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 7, // Price column
                        width: "12%",
                        className: 'all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    { width: "8%", targets: 8, className: 'all' },     // Order Date
                    {
                        targets: 9, // Status column
                        width: "10%",
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        },
                        className: 'all' // Always visible in responsive mode
                    },
                    {
                        targets: 10, // Payment Status column
                        width: "10%",
                        className: 'all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 11, // Actions column
                        width: "8%",
                        orderable: false,
                        searchable: false,
                        className: 'all', // Always visible in responsive mode
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    }
                ],
                order: [[0, 'desc']], // Sort by ID descending
                drawCallback: function() {
                    // Add event listeners to action buttons after each draw
                    $('.action-btn.view').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        viewOrder(orderId);
                    });

                    $('.action-btn.download').off('click').on('click', function() {
                        const filePath = $(this).data('file');
                        downloadFile(filePath);
                    });

                    $('.action-btn.validate').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        validateOrder(orderId);
                    });

                    $('.action-btn.complete').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        markAsCompleted(orderId);
                    });

                    $('.action-btn.decline').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        declineOrder(orderId);
                    });

                    $('.action-btn.archive').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        archiveOrder(orderId);
                    });

                    $('.action-btn.unarchive').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        unarchiveOrder(orderId);
                    });

                    $('.action-btn.delete').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        deleteOrder(orderId);
                    });
                }
            });
        }

        // Function to apply filters
        function applyFilters() {
            const paymentStatus = $('#paymentStatusFilter').val();
            const eventDateFrom = $('#eventDateFrom').val();
            const eventDateTo = $('#eventDateTo').val();

            let filteredData = allOrdersData;

            // Apply payment status filter
            if (paymentStatus) {
                filteredData = filteredData.filter(order => order.payment_status === paymentStatus);
            }

            // Apply date range filter
            if (eventDateFrom || eventDateTo) {
                filteredData = filteredData.filter(order => {
                    const eventDate = new Date(order.event_date);
                    const fromDate = eventDateFrom ? new Date(eventDateFrom) : null;
                    const toDate = eventDateTo ? new Date(eventDateTo) : null;

                    // Set toDate to end of the day (23:59:59) to include the full day
                    if (toDate) {
                        toDate.setHours(23, 59, 59, 999);
                    }

                    if (fromDate && toDate) {
                        return eventDate >= fromDate && eventDate <= toDate;
                    } else if (fromDate) {
                        return eventDate >= fromDate;
                    } else if (toDate) {
                        return eventDate <= toDate;
                    }
                    return true;
                });
            }

            // Re-initialize table with filtered data
            initializeTable(filteredData);
        }

        // Function to clear filters
        function clearFilters() {
            $('#paymentStatusFilter').val('');
            $('#eventDateFrom').val('');
            $('#eventDateTo').val('');
            initializeTable(allOrdersData);
        }

        // Tab filtering by booking status
        function matchesStatusBooth(order, statusKey) {
            if (!statusKey || statusKey === 'all') return order.admin_archived !== 1;
            if (statusKey === 'archived') return order.admin_archived === 1;
            if (order.admin_archived === 1) return false; // Hide admin-archived orders in other tabs
            const s = (order.order_status_display || order.status || '').toString().toLowerCase();
            if (statusKey === 'not_yet') return s.includes('not yet') || s.includes('not') || s.includes('pending');
            if (statusKey === 'validated') return s.includes('validated');
            if (statusKey === 'booked') return s.includes('booked');
            if (statusKey === 'completed') return s.includes('completed');
            if (statusKey === 'declined') return s.includes('declined');
            if (statusKey === 'cancelled') return s.includes('cancelled');
            return true;
        }

        function applyTabFilterBooth(statusKey) {
            let filtered = allOrdersData.filter(o => matchesStatusBooth(o, statusKey));

            // Also apply any active payment/date filters
            const paymentStatus = $('#paymentStatusFilter').val();
            const eventDateFrom = $('#eventDateFrom').val();
            const eventDateTo = $('#eventDateTo').val();

            if (paymentStatus) {
                filtered = filtered.filter(order => order.payment_status === paymentStatus);
            }

            if (eventDateFrom || eventDateTo) {
                filtered = filtered.filter(order => {
                    const eventDate = new Date(order.event_date);
                    const fromDate = eventDateFrom ? new Date(eventDateFrom) : null;
                    const toDate = eventDateTo ? new Date(eventDateTo) : null;
                    if (toDate) toDate.setHours(23,59,59,999);
                    if (fromDate && toDate) return eventDate >= fromDate && eventDate <= toDate;
                    if (fromDate) return eventDate >= fromDate;
                    if (toDate) return eventDate <= toDate;
                    return true;
                });
            }

            initializeTable(filtered);
        }

        // Tab click handlers for booth orders
        $('#boothOrderStatusTabs').on('click', '.status-tab', function() {
            const status = $(this).data('status');
            // update active class
            $('#boothOrderStatusTabs .status-tab').removeClass('active');
            $(this).addClass('active');
            // Apply tab filter
            applyTabFilterBooth(status);
        });

        // Function to format event details
        function formatEventDetails(order) {
            let details = '';
            if (order.event_type) details += `<strong>Type:</strong> ${order.event_type}<br>`;
            if (order.product) details += `<strong>Product:</strong> ${order.product}<br>`;
            if (order.duration) details += `<strong>Duration:</strong> ${order.duration}<br>`;
            if (order.package_type) details += `<strong>Package:</strong> ${order.package_type}`;
            return details || 'N/A';
        }

        // Function to format address
        function formatAddress(order) {
            let address = '';
            if (order.venue) address += `<strong>Venue:</strong> ${order.venue}<br>`;
            if (order.street_address) address += `${order.street_address}<br>`;
            if (order.city) address += `${order.city}`;
            if (order.region) address += `, ${order.region}`;
            if (order.postal_code) address += ` ${order.postal_code}`;
            if (order.country) address += `<br>${order.country}`;
            return address || 'N/A';
        }

        // Function to get status badge - SHOWS ONLY BOOKING STATUS
        function getStatusBadge(order) {
            // Use only the booking status, ignore payment status
            const bookingStatus = order.order_status_display || 'Booking not yet processed';

            // Determine CSS class based on booking status only
            let cssClass = 'pending';
            
            if (bookingStatus === 'Booking Validated') {
                cssClass = 'validated';
            } else if (bookingStatus === 'Booked') {
                cssClass = 'booked';
            } else if (bookingStatus === 'Booking processing') {
                cssClass = 'booked'; // Processing maps to booked
            } else if (bookingStatus === 'Booking Completed') {
                cssClass = 'completed';
            } else if (bookingStatus === 'Booking Declined') {
                cssClass = 'declined';
            } else if (bookingStatus === 'Booking Cancelled') {
                cssClass = 'cancelled';
            } else {
                cssClass = 'pending'; // Default for 'Booking not yet processed' and others
            }

            return `<span class="booking ${cssClass}">${bookingStatus}</span>`;
        }

        // Function to format payment status
        function formatPaymentStatus(status) {
            const statusColors = {
                'Paid': '#28a745',           // Green
                'Payment Not Submitted': '#dc3545'  // Red
            };
            const color = statusColors[status] || '#6c757d';
            return `<span style="background: ${color}; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 500;">${status}</span>`;
        }

        // Function to get action buttons HTML
        function getActionButtons(orderId, order) {
            let buttons = `<button class="action-btn view" title="View Details" data-id="${orderId}"><i class="fas fa-eye"></i></button>`;

            const paymentStatus = order.payment_status;
            const orderStatus = order.order_status_display || order.status;
            const isAdminArchived = order.admin_archived === 1;

            // If archived, show buttons but disable most
            if (isAdminArchived) {
                buttons += `<button class="action-btn validate disabled" title="Archived Booking" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Archived Booking" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Archived Booking" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
                buttons += `<button class="action-btn unarchive" title="Restore Booking" data-id="${orderId}"><i class="fas fa-undo"></i></button>`;
                buttons += `<button class="action-btn delete" title="Delete Booking" data-id="${orderId}"><i class="fas fa-trash"></i></button>`;
                return buttons;
            }

            // If cancelled, show buttons but disable most
            if (orderStatus === 'cancelled' || orderStatus === 'Booking Cancelled') {
                buttons += `<button class="action-btn validate disabled" title="Booking Cancelled" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Booking Cancelled" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Booking Cancelled" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
                buttons += `<button class="action-btn delete" title="Delete Booking" data-id="${orderId}"><i class="fas fa-trash"></i></button>`;
                return buttons;
            }

            // Check order status and show appropriate buttons
            if (orderStatus === 'declined' || orderStatus === 'Booking Declined') {
                // Declined - only View button
                buttons += `<button class="action-btn validate disabled" title="Order Declined" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Order Declined" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Order Already Declined" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
            } else if (orderStatus === 'completed' || orderStatus === 'Booking Completed') {
                // Completed - only View button
                buttons += `<button class="action-btn validate disabled" title="Booking already completed" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Booking already completed" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Booking already completed" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
            } else if (orderStatus === 'booked' || orderStatus === 'Booked') {
                // Booked - only Complete button
                buttons += `<button class="action-btn validate disabled" title="Already booked" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn complete" title="Mark as Completed" data-id="${orderId}"><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Cannot decline after booking" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
            } else if (orderStatus === 'validated' || orderStatus === 'Booking Validated') {
                // Validated - waiting for payment to transition to Booked
                buttons += `<button class="action-btn validate disabled" title="Already validated" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Awaiting manual booking confirmation" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline" title="Decline Order" data-id="${orderId}"><i class="fas fa-times-circle"></i></button>`;
            } else {
                // Not Yet Processed (pending) - only Validate and Decline buttons
                buttons += `<button class="action-btn validate" title="Validate Order" data-id="${orderId}"><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Please validate first" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline" title="Decline Order" data-id="${orderId}"><i class="fas fa-times-circle"></i></button>`;
            }

            // Show Archive button only for completed, non-archived orders
            if (orderStatus === 'completed' || orderStatus === 'Booking Completed') {
                buttons += `<button class="action-btn archive" title="Archive Order" data-id="${orderId}"><i class="fas fa-archive"></i></button>`;
            }
            return buttons;
        }


        // Function to download file
        function downloadFile(filePath) {
            if (filePath && filePath.trim() !== '') {
                // Redirect to download script
                window.open('actions/download_file.php?file=' + encodeURIComponent(filePath), '_blank');
            } else {
                Swal.fire('Error', 'No file available for download', 'error');
            }
        }

        // Action functions
        function viewOrder(orderId) {
            // Show modal with loading content first
            $('#orderDetailsContent').html(`
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 15px;"></i>
                    <p>Loading order details...</p>
                </div>
            `);
            $('#orderDetailsModal').fadeIn(300);

            // Fetch order details
            $.ajax({
                url: 'actions/get_booth_order_details.php',
                method: 'GET',
                data: { id: orderId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayOrderDetails(response.data);
                    } else {
                        $('#orderDetailsContent').html(`
                            <div style="text-align: center; padding: 40px; color: #dc3545;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 15px;"></i>
                                <p>${response.message || 'Failed to load order details'}</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#orderDetailsContent').html(`
                        <div style="text-align: center; padding: 40px; color: #dc3545;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 15px;"></i>
                            <p>Failed to load order details</p>
                        </div>
                    `);
                }
            });
        }

        // Function to display order details in modal
        function displayOrderDetails(order) {
            const detailsHtml = `
                <div class="order-detail-row">
                    <div class="order-detail-label">Order ID:</div>
                    <div class="order-detail-value">#${order.id}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Customer Name:</div>
                    <div class="order-detail-value">${order.name}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Email:</div>
                    <div class="order-detail-value">${order.email || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Mobile:</div>
                    <div class="order-detail-value">${order.mobile || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Event Type:</div>
                    <div class="order-detail-value">${order.event_type || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Product:</div>
                    <div class="order-detail-value">${order.product || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Duration:</div>
                    <div class="order-detail-value">${order.duration || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Package Type:</div>
                    <div class="order-detail-value">${order.package_type || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Event Date:</div>
                    <div class="order-detail-value">${order.event_date ? new Date(order.event_date).toLocaleDateString() : 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Time of Service:</div>
                    <div class="order-detail-value">${order.time_of_service || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Venue:</div>
                    <div class="order-detail-value">${order.venue || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Address:</div>
                    <div class="order-detail-value">
                        ${order.street_address || ''}<br>
                        ${order.city || ''}, ${order.region || ''} ${order.postal_code || ''}<br>
                        ${order.country || ''}
                    </div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Price Breakdown:</div>
                    <div class="order-detail-value">
                        <p><strong>Price:</strong> ${formatPrice(order.estimated_price)}</p>
                        <p><strong>Travel Fee:</strong> ${formatPrice(order.travel_fee || '0')}</p>
                        <hr style="border: none; border-top: 1px solid #ddd; margin: 8px 0;">
                        <p style="font-weight: bold;"><strong>Total:</strong> ${calculateTotal(order.estimated_price, order.travel_fee)}</p>
                    </div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Remarks:</div>
                    <div class="order-detail-value">${order.remarks || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Book Date:</div>
                    <div class="order-detail-value">${order.booking_date ? new Date(order.booking_date).toLocaleDateString() : 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Booking Status:</div>
                    <div class="order-detail-value">${getStatusBadge(order)}</div>
                </div>
            `;
            $('#orderDetailsContent').html(detailsHtml);
        }

        function validateOrder(orderId) {
            Swal.fire({
                title: 'Validate Order',
                text: 'Are you sure you want to validate this order? The customer will be able to proceed with payment.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Validate'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateOrderStatus(orderId, 'validated', 'Order validated successfully. Customer can now proceed with payment.');
                }
            });
        }

        function declineOrder(orderId) {
            Swal.fire({
                title: 'Decline Order',
                text: 'Are you sure you want to decline this order?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Decline',
                input: 'textarea',
                inputLabel: 'Reason for declining (optional)',
                inputPlaceholder: 'Please provide a reason for declining this order...'
            }).then((result) => {
                if (result.isConfirmed) {
                    const reason = result.value || '';

                    // Show loading alert while processing
                    Swal.fire({
                        title: 'Processing Decline',
                        text: 'Please wait while we decline the order and notify the customer...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // First get order details for email
                    $.ajax({
                        url: 'actions/get_booth_order_details.php',
                        method: 'GET',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(orderResponse) {
                            if (orderResponse.success) {
                                const orderDetails = orderResponse.data;

                                // Prepare email data
                                const emailData = {
                                    id: orderDetails.id,
                                    event_type: orderDetails.event_type,
                                    product: orderDetails.product,
                                    package_type: orderDetails.package_type,
                                    duration: orderDetails.duration,
                                    event_date: orderDetails.event_date,
                                    time_of_service: orderDetails.time_of_service,
                                    venue: orderDetails.venue,
                                    estimated_price: orderDetails.estimated_price
                                };

                                // First send email notification
                                $.ajax({
                                    url: 'actions/send_payment_status_email.php',
                                    method: 'POST',
                                    data: {
                                        action: 'reject',
                                        email: orderDetails.email,
                                        name: orderDetails.name,
                                        payment_details: JSON.stringify(emailData),
                                        reason: reason
                                    },
                                    dataType: 'json',
                                    success: function(emailResponse) {
                                        if (emailResponse.success) {
                                            // Close loading alert and show success
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Order Declined',
                                                text: 'Customer has been notified via email. Updating order status...',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                // Email sent successfully, now update order status
                                                updateOrderStatus(orderId, 'declined', 'Order declined and customer notified via email', reason);
                                            });
                                        } else {
                                            // Close loading and show email error
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Email Failed',
                                                text: 'Failed to send rejection notification to customer. Order status not updated. Please try again or contact support.',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        // Close loading and show connection error
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Connection Failed',
                                            text: 'Failed to send rejection notification to customer. Order status not updated. Please check your connection and try again.',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                });
                            } else {
                                // Close loading and show error
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to load order details. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            // Close loading and show error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to load order details. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        function markAsCompleted(orderId) {
            Swal.fire({
                title: 'Mark as Completed',
                text: 'Are you sure you want to mark this booking as completed?',
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Mark as Completed'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateOrderStatus(orderId, 'completed', 'Booking marked as completed successfully');
                }
            });
        }

        function archiveOrder(orderId) {
            Swal.fire({
                title: 'Archive Order',
                text: 'Are you sure you want to archive this order? You can delete it later from the Archive tab.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Archive'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/archive_booth_order.php',
                        method: 'POST',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Archived!', 'Order has been archived successfully', 'success').then(() => {
                                    loadBoothOrders();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to archive order', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to archive order', 'error');
                        }
                    });
                }
            });
        }

        function unarchiveOrder(orderId) {
            Swal.fire({
                title: 'Restore Booking',
                text: 'Are you sure you want to restore this booking from the archive?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Restore'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/unarchive_booth_order.php',
                        method: 'POST',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Restored!', 'Booking has been restored successfully', 'success').then(() => {
                                    loadBoothOrders();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to restore booking', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to restore booking', 'error');
                        }
                    });
                }
            });
        }

        function deleteOrder(orderId) {
            Swal.fire({
                title: 'Delete Order',
                text: 'Are you sure you want to permanently delete this order? This action cannot be undone.',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/delete_booth_order.php',
                        method: 'POST',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', 'Order has been deleted successfully', 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to delete order', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to delete order', 'error');
                        }
                    });
                }
            });
        }

        function updateOrderStatus(orderId, status, successMessage, reason = '') {
            $.ajax({
                url: 'actions/update_booth_order_status.php',
                method: 'POST',
                data: { id: orderId, status: status, reason: reason },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', successMessage, 'success').then(() => {
                            loadBoothOrders();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to update order status', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to update order status', 'error');
                }
            });
        }

        // Load data on page load
        loadBoothOrders();

        // Auto-refresh every 10 seconds to check for validated+paid orders that need to be updated to booked
        setInterval(function() {
            console.log('Auto-checking for validated+paid orders that need updating...');
            loadBoothOrders();
        }, 10000); // 10 seconds

        // Real-time filter event handlers
        $('#paymentStatusFilter').on('change', function() {
            applyFilters();
        });

        // Debounced filter for date inputs to prevent excessive filtering
        let dateFilterTimeout;
        $('#eventDateFrom, #eventDateTo').on('change input', function() {
            clearTimeout(dateFilterTimeout);
            dateFilterTimeout = setTimeout(function() {
                applyFilters();
            }, 300); // 300ms delay
        });

        $('#clearFiltersBtn').on('click', function() {
            clearFilters();
        });

        // Modal functionality
        $('.modal-close').on('click', function() {
            $('#orderDetailsModal').fadeOut();
        });

        $(window).on('click', function(event) {
            if (event.target.id === 'orderDetailsModal') {
                $('#orderDetailsModal').fadeOut();
            }
        });
    });
    </script>

</body>
</html>
