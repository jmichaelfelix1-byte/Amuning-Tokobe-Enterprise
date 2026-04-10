<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../signin.php');
    exit();
}

$page_title = 'Admin Dashboard | Amuning Tokobe Enterprise';

// Include config for database connection
require_once '../includes/config.php';

// Function to get dashboard statistics
function getDashboardStats($conn) {
    $stats = [];

    try {
        // Get printing orders count
        $sql = "SELECT COUNT(*) as total FROM printing_orders";
        $result = $conn->query($sql);
        $stats['printing_orders'] = $result->fetch_assoc()['total'];

        // Get photo bookings count
        $sql = "SELECT COUNT(*) as total FROM photo_bookings";
        $result = $conn->query($sql);
        $stats['photo_bookings'] = $result->fetch_assoc()['total'];

        // Get total customers (users with user_type = 'user')
        $sql = "SELECT COUNT(*) as total FROM users WHERE user_type = 'user'";
        $result = $conn->query($sql);
        $stats['total_customers'] = $result->fetch_assoc()['total'];

        

        // Placeholder percentage changes (you can implement real calculations later)
        $stats['printing_orders_change'] = '+12%';
        $stats['photo_bookings_change'] = '+8%';
        $stats['customers_change'] = '+15%';
        $stats['revenue_change'] = '+23%';

    } catch (Exception $e) {
        // Default values if query fails
        $stats = [
            'printing_orders' => 0,
            'photo_bookings' => 0,
            'total_customers' => 0,
            'total_revenue' => 0,
            'printing_orders_change' => '0%',
            'photo_bookings_change' => '0%',
            'customers_change' => '0%',
            'revenue_change' => '0%'
        ];
    }

    return $stats;
}

// Get dashboard statistics
$dashboardStats = getDashboardStats($conn);
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
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Include the sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="main-header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Dashboard</h1>
            </header>

            <div class="main-content">
                <div class="content-wrapper">
                    <!-- Dashboard Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-content">
                                <h3><?php echo number_format($dashboardStats['printing_orders']); ?></h3>
                                <p>Printing Orders</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-content">
                                <h3><?php echo number_format($dashboardStats['photo_bookings']); ?></h3>
                                <p>Photo Bookings</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-content">
                                <h3><?php echo number_format($dashboardStats['total_customers']); ?></h3>
                                <p>Total Customers</p>
                            </div>
                        </div>

                       
                    </div>

                    <!-- Recent Orders Table -->
                    <div class="recent-orders">
                        <div class="section-header">
                            <h2>Recent Printing Orders</h2>
                            <a href="manage-printing-orders.php" class="view-all">View All</a>
                        </div>

                        <div class="table-container">
                            <table id="ordersTable" class="display responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Full Name</th>
                                        <th>Contact Number</th>
                                        <th>Service</th>
                                        <th>Order Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-right: 10px;"></i>
                                            Loading printing orders...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Photobooth Orders Table -->
                    <div class="recent-orders">
                        <div class="section-header">
                            <h2>Recent Photobooth Orders</h2>
                            <a href="manage-booth-orders.php" class="view-all">View All</a>
                        </div>

                        <div class="table-container">
                            <table id="photoboothOrdersTable" class="display responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Name</th>
                                        <th>Event Details</th>
                                        <th>Event Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-right: 10px;"></i>
                                            Loading photobooth orders...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Order Details Modal -->
        <div id="orderDetailsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Order Details</h2>
                    <span class="modal-close">&times;</span>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Include the admin footer -->
        <?php include '../includes/admin_footer.php'; ?>

    </div>

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/admin-sidebar.js"></script>

    <script>
    $(document).ready(function() {
        let printingOrdersData = []; // Store original printing orders data
        let photoboothOrdersData = []; // Store original photobooth orders data
        let printingTable, photoboothTable; // DataTable instances

        // Function to get status badge for PRINTING orders
        function getPrintingStatusBadge(order) {
            // Use the actual status field from the data
            const orderStatus = order.status || 'Order not yet process';

            // Map the status values to match what you see in printing orders
            let displayStatus = orderStatus;
            let cssClass = 'status-pending';
            
            if (orderStatus === 'completed') {
                displayStatus = 'ORDER COMPLETED';
                cssClass = 'status-completed';
            } else if (orderStatus === 'declined') {
                displayStatus = 'ORDER DECLINED';
                cssClass = 'status-declined';
            } else if (orderStatus === 'processing') {
                displayStatus = 'ORDER PROCESSING';
                cssClass = 'status-processing';
            } else {
                displayStatus = 'ORDER NOT YET PROCESS';
                cssClass = 'status-pending';
            }

            return `<span class="status ${cssClass}">${displayStatus}</span>`;
        }

        // Function to get status badge for PHOTOBOOTH orders
        function getPhotoboothStatusBadge(order) {
            // Use the actual status field from the data
            const bookingStatus = order.status || 'pending';

            // Map the raw status values to display names and CSS classes
            let displayStatus = 'Booking Not Yet Processed';
            let cssClass = 'pending';
            
            if (bookingStatus === 'validated') {
                displayStatus = 'Booking Validated';
                cssClass = 'validated';
            } else if (bookingStatus === 'booked') {
                displayStatus = 'Booked';
                cssClass = 'booked';
            } else if (bookingStatus === 'processing') {
                displayStatus = 'Booking Processing';
                cssClass = 'booked'; // Processing maps to 'booked' in new scheme
            } else if (bookingStatus === 'completed') {
                displayStatus = 'Booking Completed';
                cssClass = 'completed';
            } else if (bookingStatus === 'declined') {
                displayStatus = 'Booking Declined';
                cssClass = 'declined';
            } else if (bookingStatus === 'cancelled') {
                displayStatus = 'Booking Cancelled';
                cssClass = 'cancelled';
            } else {
                displayStatus = 'Booking Not Yet Processed';
                cssClass = 'pending';
            }

            return `<span class="booking ${cssClass}">${displayStatus}</span>`;
        }

        // Function to get action buttons HTML
        function getActionButtons(orderId, type) {
            return `<button class="action-btn view" title="View" data-id="${orderId}" data-type="${type}"><i class="fas fa-eye"></i></button>`;
        }

        // Load printing orders data
        function loadPrintingOrders() {
            $.ajax({
                url: 'actions/get_printing_orders.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Store original data for filtering
                        printingOrdersData = response.data;

                        // Initialize table with all data
                        initializePrintingTable(printingOrdersData);
                    } else {
                        console.error('Failed to load printing orders:', response.error);
                        $('#ordersTable tbody').html('<tr><td colspan="7" style="text-align: center;">Failed to load data</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    $('#ordersTable tbody').html('<tr><td colspan="7" style="text-align: center;">Failed to load data</td></tr>');
                }
            }); 
        }

        // Initialize printing orders table
        function initializePrintingTable(data) {
            const printingOrdersData = data.map(order => [
                '#' + order.id,
                order.full_name,
                order.contact_number,
                order.service,
                order.order_date,
                getPrintingStatusBadge(order), // CHANGED: Use printing-specific status
                getActionButtons(order.id, 'printing')
            ]);

            // Destroy existing table if it exists
            if (printingTable) {
                printingTable.destroy();
            }

            printingTable = $('#ordersTable').DataTable({
                data: printingOrdersData,
                responsive: true,
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                language: {
                    search: "Search printing orders:",
                    lengthMenu: "Show _MENU_ orders",
                    info: "Showing _START_ to _END_ of _TOTAL_ printing orders",
                    infoEmpty: "No printing orders available",
                    infoFiltered: "(filtered from _MAX_ total printing orders)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Prev"
                    },
                    zeroRecords: "No matching printing orders found"
                },
                columnDefs: [
                    {
                        targets: 5, // Status column
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 6, // Actions column
                        orderable: false,
                        searchable: false,
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    }
                ],
                order: [[4, 'desc']], // Sort by date (newest first)
                drawCallback: function() {
                    // Add event listeners to action buttons after each draw
                    $('.action-btn.view').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        const type = $(this).data('type');
                        viewOrderDetails(orderId, type);
                    });
                }
            });
        }

        // Load photobooth orders data
        function loadPhotoboothOrders() {
            $.ajax({
                url: 'actions/get_photo_bookings.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Store original data for filtering
                        photoboothOrdersData = response.data;

                        // Initialize table with all data
                        initializePhotoboothTable(photoboothOrdersData);
                    } else {
                        console.error('Failed to load photobooth orders:', response.error);
                        $('#photoboothOrdersTable tbody').html('<tr><td colspan="6" style="text-align: center;">Failed to load data</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    $('#photoboothOrdersTable tbody').html('<tr><td colspan="6" style="text-align: center;">Failed to load data</td></tr>');
                }
            });
        }

        // Initialize photobooth orders table
        function initializePhotoboothTable(data) {
            const photoboothOrdersData = data.map(booking => [
                '#' + booking.id,
                booking.name,
                booking.event_details,
                booking.event_date,
                getPhotoboothStatusBadge(booking), // CHANGED: Use photobooth-specific status
                getActionButtons(booking.id, 'photobooth')
            ]);

            // Destroy existing table if it exists
            if (photoboothTable) {
                photoboothTable.destroy();
            }

            photoboothTable = $('#photoboothOrdersTable').DataTable({
                data: photoboothOrdersData,
                responsive: true,
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                language: {
                    search: "Search photobooth orders:",
                    lengthMenu: "Show _MENU_ orders",
                    info: "Showing _START_ to _END_ of _TOTAL_ photobooth orders",
                    infoEmpty: "No photobooth orders available",
                    infoFiltered: "(filtered from _MAX_ total photobooth orders)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Prev"
                    },
                    zeroRecords: "No matching photobooth orders found"
                },
                columnDefs: [
                    {
                        targets: 4, // Status column
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 5, // Actions column
                        orderable: false,
                        searchable: false,
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    }
                ],
                order: [[3, 'desc']], // Sort by date (newest first)
                drawCallback: function() {
                    // Add event listeners to action buttons after each draw
                    $('.action-btn.view').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        const type = $(this).data('type');
                        viewOrderDetails(orderId, type);
                    });
                }
            });
        }

        // Function to view order details in modal
        function viewOrderDetails(orderId, type) {
            // Show modal with loading content first
            $('#orderDetailsContent').html(`
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 15px;"></i>
                    <p>Loading order details...</p>
                </div>
            `);
            $('#modalTitle').text(type === 'printing' ? 'Printing Order Details' : 'Photobooth Order Details');
            $('#orderDetailsModal').fadeIn(300);

            // Fetch order details
            const url = type === 'printing' ? 'actions/get_printing_order_details.php' : 'actions/get_booth_order_details.php';
            $.ajax({
                url: url,
                method: 'GET',
                data: { id: orderId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayOrderDetailsInModal(response.data, type);
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
        function displayOrderDetailsInModal(order, type) {
            let detailsHtml = '';

            if (type === 'printing') {
                detailsHtml = `
                    <div class="order-detail-row">
                        <div class="order-detail-label">Order ID:</div>
                        <div class="order-detail-value">#${order.id}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Customer Name:</div>
                        <div class="order-detail-value">${order.full_name}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Contact Number:</div>
                        <div class="order-detail-value">${order.contact_number || 'N/A'}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Service:</div>
                        <div class="order-detail-value">${order.service || 'N/A'}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Size:</div>
                        <div class="order-detail-value">${order.size || 'N/A'}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Paper Type:</div>
                        <div class="order-detail-value">${order.paper_type || 'N/A'}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Quantity:</div>
                        <div class="order-detail-value">${order.quantity || 'N/A'}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Price:</div>
                        <div class="order-detail-value">${formatPrice(order.price)}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Special Instructions:</div>
                        <div class="order-detail-value">${order.special_instruction || 'N/A'}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Order Date:</div>
                        <div class="order-detail-value">${order.order_date ? new Date(order.order_date).toLocaleDateString() : 'N/A'}</div>
                    </div>
                    <div class="order-detail-row">
                        <div class="order-detail-label">Order Status:</div>
                        <div class="order-detail-value">${getPrintingStatusBadge(order)}</div>
                    </div>
                `;
            } else {
                detailsHtml = `
                    <div class="order-detail-row">
                        <div class="order-detail-label">Booking ID:</div>
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
                        <div class="order-detail-label">Price:</div>
                        <div class="order-detail-value">${formatPrice(order.estimated_price)}</div>
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
                        <div class="order-detail-value">${getPhotoboothStatusBadge(order)}</div>
                    </div>
                `;
            }

            $('#orderDetailsContent').html(detailsHtml);
        }

        // Function to format price
        function formatPrice(price) {
            return '₱' + parseFloat(price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Load both tables
        loadPrintingOrders();
        loadPhotoboothOrders();

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