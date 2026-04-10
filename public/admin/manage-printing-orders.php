<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: signin.php');
    exit();
}

$current_page = 'manage-printing-orders.php';
$page_title = 'Manage Printing Orders';
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
        #orderStatusTabs .status-tab {
            background: #f1f3f5;
            border: 1px solid #dcdcdc;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        #orderStatusTabs .status-tab.active {
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
                <h1 class="page-title">Manage Printing Orders</h1>
            </header>

            <div class="main-content">
                <div class="content-wrapper">
                    <div class="recent-orders">
                        <div class="section-header">
                            <h2>Printing Orders</h2>
                        </div>

                        <!-- Order status tabs -->
                        <div id="orderStatusTabs" style="margin-bottom: 12px; display:flex; gap:8px; flex-wrap:wrap;">
                            <button class="status-tab active" data-status="all">All</button>
                            <button class="status-tab" data-status="not_yet">Not Yet Processed</button>
                            <button class="status-tab" data-status="processing">Processing</button>
                            <button class="status-tab" data-status="completed">Completed</button>
                            <button class="status-tab" data-status="declined">Declined</button>
                            <button class="status-tab" data-status="cancelled">Cancelled Orders</button>
                            <button class="status-tab" data-status="archived">Archived Orders</button>
                        </div>

                        <div class="table-container">
                            <table id="printingOrdersTable" class="display responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Service Details</th>
                                        <th>Order Date</th>
                                        <th>Status</th>
                                        <th>Payment Status</th>
                                        <th>Payment Method</th>
                                        <th>Uploaded File</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="10" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-right: 10px;"></i>
                                            Loading printing orders...
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
                <h3>Printing Order Details</h3>
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

        // Load printing orders data
        function loadPrintingOrders() {
            $.ajax({
                url: 'actions/get_all_printing_orders.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Store original data for filtering
                        allOrdersData = response.data;

                        // Auto-update validated+paid orders to processing
                        autoUpdateValidatedPaidOrders(allOrdersData);

                        // Apply 'all' filter to exclude archived orders on initial load
                        applyTabFilter('all');
                    } else {
                        console.error('Failed to load printing orders:', response.error);
                        $('#printingOrdersTable tbody').html('<tr><td colspan="10" style="text-align: center;">Failed to load data</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    $('#printingOrdersTable tbody').html('<tr><td colspan="10" style="text-align: center;">Failed to load data</td></tr>');
                }
            });
        }

        // Auto-update orders that are Validated and Paid to Processing
        function autoUpdateValidatedPaidOrders(orders) {
            orders.forEach(function(order) {
                const orderStatus = (order.order_status_display || order.status || '').toLowerCase();
                const paymentStatus = (order.payment_status || '').toLowerCase();

                // Check if order is validated and payment is paid
                if (orderStatus.includes('validated') && paymentStatus === 'paid') {
                    console.log('Auto-updating order #' + order.id + ' from Validated to Processing (Paid)');
                    
                    // Make AJAX call to update status
                    $.ajax({
                        url: 'actions/update_printing_order_status.php',
                        type: 'POST',
                        data: {
                            id: order.id,
                            status: 'processing'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                console.log('Order #' + order.id + ' status updated to Processing');
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
                order.full_name,
                order.contact_number,
                formatServiceDetails(order),
                new Date(order.order_date).toLocaleDateString(),
                getStatusBadge(order),
                formatPaymentStatus(order.payment_status_display),
                formatPaymentMethod(order.payment_method),
                formatUploadedFile(order.image_path),
                getActionButtons(order.id, order.image_path, order)
            ]);

            // Destroy existing table if it exists
            if (ordersTable) {
                ordersTable.destroy();
            }

            ordersTable = $('#printingOrdersTable').DataTable({
                data: ordersData,
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
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
                        targets: 0, // ID
                        width: "5%",
                        className: 'all'
                    },
                    {
                        targets: 1, // Name
                        width: "12%",
                        className: 'all'
                    },
                    {
                        targets: 2, // Contact
                        width: "10%",
                        className: 'all'
                    },
                    {
                        targets: 3, // Service Details column
                        width: "12%",
                        className: 'all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 4, // Order Date
                        width: "10%",
                        className: 'all'
                    },
                    {
                        targets: 5, // Status column
                        width: "12%",
                        className: 'all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 6, // Payment Status column
                        width: "12%",
                        className: 'all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 7, // Payment Method column
                        width: "10%",
                        className: 'all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 8, // Uploaded File column
                        width: "8%",
                        orderable: false,
                        searchable: false,
                        className: 'all text-center',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).html(cellData);
                        }
                    },
                    {
                        targets: 9, // Actions column
                        width: "9%",
                        orderable: false,
                        searchable: false,
                        className: 'all',
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
                        const orderId = $(this).data('order-id');
                        downloadOrderFiles(orderId);
                    });

                    $('.action-btn.validate').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        validateOrder(orderId);
                    });

                    $('.action-btn.process').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        processOrder(orderId);
                    });

                    $('.action-btn.ready-pickup').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        markAsReadyForPickup(orderId);
                    });

                    $('.action-btn.complete').off('click').on('click', function() {
                        const orderId = $(this).data('id');
                        markAsComplete(orderId);
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
            const orderDateFrom = $('#orderDateFrom').val();
            const orderDateTo = $('#orderDateTo').val();

            let filteredData = allOrdersData;

            // Apply payment status filter
            if (paymentStatus) {
                filteredData = filteredData.filter(order => order.payment_status === paymentStatus);
            }

            // Apply date range filter (based on order_date)
            if (orderDateFrom || orderDateTo) {
                filteredData = filteredData.filter(order => {
                    const orderDate = new Date(order.order_date);
                    const fromDate = orderDateFrom ? new Date(orderDateFrom) : null;
                    const toDate = orderDateTo ? new Date(orderDateTo) : null;

                    // Set toDate to end of the day (23:59:59) to include the full day
                    if (toDate) {
                        toDate.setHours(23, 59, 59, 999);
                    }

                    if (fromDate && toDate) {
                        return orderDate >= fromDate && orderDate <= toDate;
                    } else if (fromDate) {
                        return orderDate >= fromDate;
                    } else if (toDate) {
                        return orderDate <= toDate;
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
            $('#orderDateFrom').val('');
            $('#orderDateTo').val('');
            initializeTable(allOrdersData);
        }

        // Tab filtering by order status
        function matchesStatus(order, statusKey) {
            if (!statusKey || statusKey === 'all') return order.admin_archived !== 1;
            if (statusKey === 'archived') return order.admin_archived === 1;
            if (order.admin_archived === 1) return false; // Hide archived orders in other tabs
            const s = (order.order_status_display || order.status || '').toString().toLowerCase();
            if (statusKey === 'not_yet') return s.includes('not yet') || s.includes('not') || s.includes('pending');
            if (statusKey === 'processing') return s.includes('processing');
            if (statusKey === 'completed') return s.includes('completed');
            if (statusKey === 'declined') return s.includes('declined');
            if (statusKey === 'cancelled') return s.includes('cancelled');
            return true;
        }

        function applyTabFilter(statusKey) {
            let filtered = allOrdersData.filter(o => matchesStatus(o, statusKey));

            // Also apply any active payment/date filters
            const paymentStatus = $('#paymentStatusFilter').val();
            const orderDateFrom = $('#orderDateFrom').val();
            const orderDateTo = $('#orderDateTo').val();

            if (paymentStatus) {
                filtered = filtered.filter(order => order.payment_status === paymentStatus);
            }

            if (orderDateFrom || orderDateTo) {
                filtered = filtered.filter(order => {
                    const orderDate = new Date(order.order_date);
                    const fromDate = orderDateFrom ? new Date(orderDateFrom) : null;
                    const toDate = orderDateTo ? new Date(orderDateTo) : null;
                    if (toDate) toDate.setHours(23,59,59,999);
                    if (fromDate && toDate) return orderDate >= fromDate && orderDate <= toDate;
                    if (fromDate) return orderDate >= fromDate;
                    if (toDate) return orderDate <= toDate;
                    return true;
                });
            }

            initializeTable(filtered);
        }

        // Tab click handlers
        $('#orderStatusTabs').on('click', '.status-tab', function() {
            const status = $(this).data('status');
            // update active class
            $('#orderStatusTabs .status-tab').removeClass('active');
            $(this).addClass('active');
            // Apply tab filter along with any other filters (date/payment)
            applyTabFilter(status);
        });

        // Function to get status badge - SHOWS ONLY ORDER STATUS
        function getStatusBadge(order) {
            // Use only the order status, ignore payment status
            const orderStatus = order.order_status_display || 'Order not yet processed';

            // Determine CSS class based on order status only
            let cssClass = 'pending';
            
            if (orderStatus === 'Order Validated') {
                cssClass = 'validated';
            } else if (orderStatus === 'Order processing') {
                cssClass = 'processing';
            } else if (orderStatus === 'Ready to Pick-Up') {
                cssClass = 'status-ready'; // Yellow-Green for ready to pickup
            } else if (orderStatus === 'Order Completed') {
                cssClass = 'completed';
            } else if (orderStatus === 'Order Declined') {
                cssClass = 'declined';
            } else if (orderStatus === 'Order Cancelled') {
                cssClass = 'cancelled';
            } else {
                cssClass = 'pending'; // Default for 'Order not yet processed' and others
            }

            return `<span class="status ${cssClass}">${orderStatus}</span>`;
        }

        // Function to format service details
        function formatServiceDetails(order) {
            let details = '';
            if (order.service) details += `<strong>Service:</strong> ${order.service}<br>`;
            if (order.size) details += `<strong>Size:</strong> ${order.size}<br>`;
            if (order.paper_type) details += `<strong>Paper Type:</strong> ${order.paper_type}<br>`;
            if (order.color_type) {
                const colorLabel = order.color_type === 'colored' ? 'Colored' : 'Black & White';
                details += `<strong>Color:</strong> ${colorLabel}<br>`;
            }
            if (order.page_count) details += `<strong>Pages:</strong> ${order.page_count}<br>`;
            if (order.file_count) details += `<strong>No. Files:</strong> ${order.file_count}<br>`;
            if (order.quantity) details += `<strong>Quantity:</strong> ${order.quantity}<br>`;
            if (order.price) details += `<strong>Price:</strong> ${formatPrice(order.price)}<br>`;
            if (order.special_instruction) details += `<strong>Instructions:</strong> ${order.special_instruction}`;
            return details || 'N/A';
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

        // Function to format payment method
        function formatPaymentMethod(method) {
            const methodLabel = method === 'in_person' ? 'In-Person' : 'Online';
            const methodIcon = method === 'in_person' ? 'fa-handshake' : 'fa-credit-card';
            const methodColor = method === 'in_person' ? '#17a2b8' : '#0066cc';
            return `<span style="background: ${methodColor}; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 500;"><i class="fas ${methodIcon}"></i> ${methodLabel}</span>`;
        }

        // Function to format uploaded file display
        function formatUploadedFile(imagePath) {
            if (imagePath && imagePath.trim() !== '') {
                // Try to decode as JSON (multiple files)
                let files = [];
                try {
                    const decoded = JSON.parse(imagePath);
                    if (Array.isArray(decoded)) {
                        files = decoded;
                    } else {
                        files = [imagePath];
                    }
                } catch (e) {
                    // Not JSON, treat as single file path
                    files = [imagePath];
                }

                if (files.length === 0) {
                    return '<span style="color: #999; font-style: italic;">No files</span>';
                }

                // Display files
                const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp', '.svg', '.tiff', '.ico'];
                let html = `<div style="text-align: center; font-size: 12px;">`;
                
                // Show file count badge
                html += `<div style="margin-bottom: 5px;"><span style="background: #f5276c; color: white; padding: 2px 8px; border-radius: 12px; font-weight: bold; font-size: 11px;">${files.length} ${files.length === 1 ? 'file' : 'files'}</span></div>`;
                
                // Show first file with thumbnail if image
                const firstFile = files[0];
                const firstFileName = firstFile.split('/').pop() || firstFile;
                const isFirstImage = imageExtensions.some(ext => firstFileName.toLowerCase().endsWith(ext));
                
                if (isFirstImage) {
                    const fullPath = '../' + firstFile;
                    html += `<img src="${fullPath}" alt="File" style="max-width: 60px; max-height: 60px; border: 1px solid #ddd; border-radius: 4px; object-fit: cover;">`;
                } else {
                    html += `<i class="fas fa-file" style="font-size: 24px; color: #999;"></i>`;
                }
                
                // Show file list tooltip on hover
                let fileListHtml = files.map(f => {
                    const name = f.split('/').pop() || f;
                    return `• ${name}`;
                }).join('<br>');
                
                html += `<div style="cursor: help; margin-top: 3px;" title="Files included:\n${files.map(f => f.split('/').pop() || f).join('\n')}">`;
                html += `<small style="color: #666;">Click download to get all</small>`;
                html += `</div></div>`;
                
                return html;
            }
            return '<span style="color: #999; font-style: italic;">No file</span>';
        }

        // Function to get action buttons HTML
        function getActionButtons(orderId, imagePath, order) {
            let buttons = `<button class="action-btn view" title="View Details" data-id="${orderId}"><i class="fas fa-eye"></i></button>`;
            
            const paymentStatus = order.payment_status;
            const orderStatus = order.order_status_display || order.status;
            const isArchived = order.admin_archived === 1;

            // If archived, show buttons but disable most
            if (isArchived) {
                if (imagePath && imagePath.trim() !== '') {
                    buttons += `<button class="action-btn download disabled" title="Archived Order" data-order-id="${orderId}" disabled><i class="fas fa-download"></i></button>`;
                }
                buttons += `<button class="action-btn validate disabled" title="Archived Order" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn process disabled" title="Archived Order" data-id="${orderId}" disabled><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Archived Order" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Archived Order" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
                buttons += `<button class="action-btn unarchive" title="Restore Order" data-id="${orderId}"><i class="fas fa-undo"></i></button>`;
                buttons += `<button class="action-btn delete" title="Delete Order" data-id="${orderId}"><i class="fas fa-trash"></i></button>`;
                return buttons;
            }

            // If cancelled, show buttons but disable most
            if (orderStatus === 'cancelled' || orderStatus === 'Order Cancelled') {
                if (imagePath && imagePath.trim() !== '') {
                    buttons += `<button class="action-btn download disabled" title="Order Cancelled" data-order-id="${orderId}" disabled><i class="fas fa-download"></i></button>`;
                }
                buttons += `<button class="action-btn validate disabled" title="Order Cancelled" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn process disabled" title="Order Cancelled" data-id="${orderId}" disabled><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Order Cancelled" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Order Cancelled" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
                buttons += `<button class="action-btn delete" title="Delete Order" data-id="${orderId}"><i class="fas fa-trash"></i></button>`;
                return buttons;
            }

            // Check order status and show appropriate buttons
            if (orderStatus === 'declined' || orderStatus === 'Order Declined') {
                // Declined - only View button
                if (imagePath && imagePath.trim() !== '') {
                    buttons += `<button class="action-btn download disabled" title="Order Declined" data-order-id="${orderId}" disabled><i class="fas fa-download"></i></button>`;
                }
                buttons += `<button class="action-btn validate disabled" title="Order Declined" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn process disabled" title="Order Declined" data-id="${orderId}" disabled><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Order Declined" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Order Already Declined" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
            } else if (orderStatus === 'completed' || orderStatus === 'Order Completed') {
                // Completed - only View button
                if (imagePath && imagePath.trim() !== '') {
                    buttons += `<button class="action-btn download" title="Download File" data-order-id="${orderId}"><i class="fas fa-download"></i></button>`;
                }
                buttons += `<button class="action-btn validate disabled" title="Order already completed" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn process disabled" title="Order already completed" data-id="${orderId}" disabled><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Order already completed" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                buttons += `<button class="action-btn decline disabled" title="Order already completed" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
            } else if (orderStatus === 'ready_to_pickup' || orderStatus === 'Ready to Pick-Up') {
                // Ready for Pick-Up - only Complete button
                if (imagePath && imagePath.trim() !== '') {
                    buttons += `<button class="action-btn download" title="Download File" data-order-id="${orderId}"><i class="fas fa-download"></i></button>`;
                }
                buttons += `<button class="action-btn validate disabled" title="Order awaiting pickup" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn process disabled" title="Order awaiting pickup" data-id="${orderId}" disabled><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn complete" title="Mark as Completed" data-id="${orderId}"><i class="fas fa-check-double"></i></button>`;
                
                // Always show Ready for Pick-Up button for In-Person orders (but disabled at this status)
                if (order.payment_method === 'in_person') {
                    buttons += `<button class="action-btn ready-pickup disabled" title="Already marked as ready for pick-up" data-id="${orderId}" style="background-color: #ccc; border-color: #ccc;" disabled><i class="fas fa-box"></i></button>`;
                } else {
                    buttons += `<button class="action-btn ready-pickup disabled" title="Not applicable for online orders" data-id="${orderId}" disabled><i class="fas fa-box"></i></button>`;
                }
                
                buttons += `<button class="action-btn decline disabled" title="Cannot decline while awaiting pickup" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
            } else if (orderStatus === 'processing' || orderStatus === 'Order processing') {
                // Processing - show Ready For Pick-Up for In-Person, Complete for Online
                if (imagePath && imagePath.trim() !== '') {
                    buttons += `<button class="action-btn download" title="Download File" data-order-id="${orderId}"><i class="fas fa-download"></i></button>`;
                }
                buttons += `<button class="action-btn validate disabled" title="Already processing" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn process disabled" title="Already processing" data-id="${orderId}" disabled><i class="fas fa-cog"></i></button>`;
                
                // Show "Ready for Pick-Up" button for In-Person orders, "Complete" for Online
                if (order.payment_method === 'in_person') {
                    buttons += `<button class="action-btn ready-pickup" title="Mark as Ready for Pick-Up" data-id="${orderId}" style="background-color: #FF6B6B; border-color: #FF6B6B;"><i class="fas fa-box"></i></button>`;
                    buttons += `<button class="action-btn complete disabled" title="Complete after pick-up payment" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                } else {
                    buttons += `<button class="action-btn ready-pickup disabled" title="Not applicable for online orders" data-id="${orderId}" disabled><i class="fas fa-box"></i></button>`;
                    buttons += `<button class="action-btn complete" title="Mark as Completed (After Payment)" data-id="${orderId}"><i class="fas fa-check-double"></i></button>`;
                }
                buttons += `<button class="action-btn decline disabled" title="Cannot decline while processing" data-id="${orderId}" disabled><i class="fas fa-times-circle"></i></button>`;
            } else if (orderStatus === 'validated' || orderStatus === 'Order Validated') {
                // Validated - only Process button
                if (imagePath && imagePath.trim() !== '') {
                    buttons += `<button class="action-btn download" title="Download File" data-order-id="${orderId}"><i class="fas fa-download"></i></button>`;
                }
                buttons += `<button class="action-btn validate disabled" title="Already validated" data-id="${orderId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn process" title="Process Order" data-id="${orderId}"><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Please process order first" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                
                // Always show Ready for Pick-Up button for In-Person orders (but disabled at this status)
                if (order.payment_method === 'in_person') {
                    buttons += `<button class="action-btn ready-pickup disabled" title="Process order first to mark ready for pick-up" data-id="${orderId}" disabled><i class="fas fa-box"></i></button>`;
                } else {
                    buttons += `<button class="action-btn ready-pickup disabled" title="Not applicable for online orders" data-id="${orderId}" disabled><i class="fas fa-box"></i></button>`;
                }
                
                buttons += `<button class="action-btn decline" title="Decline Order" data-id="${orderId}"><i class="fas fa-times-circle"></i></button>`;
            } else {
                // Not Yet Processed (pending) - only Validate and Decline buttons
                if (imagePath && imagePath.trim() !== '') {
                    buttons += `<button class="action-btn download" title="Download File" data-order-id="${orderId}"><i class="fas fa-download"></i></button>`;
                }
                buttons += `<button class="action-btn validate" title="Validate Order" data-id="${orderId}"><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn process disabled" title="Please validate first" data-id="${orderId}" disabled><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn complete disabled" title="Please validate and process first" data-id="${orderId}" disabled><i class="fas fa-check-double"></i></button>`;
                
                // Always show Ready for Pick-Up button for In-Person orders (but disabled at this status)
                if (order.payment_method === 'in_person') {
                    buttons += `<button class="action-btn ready-pickup disabled" title="Validate and process order first" data-id="${orderId}" disabled><i class="fas fa-box"></i></button>`;
                } else {
                    buttons += `<button class="action-btn ready-pickup disabled" title="Not applicable for online orders" data-id="${orderId}" disabled><i class="fas fa-box"></i></button>`;
                }
                
                buttons += `<button class="action-btn decline" title="Decline Order" data-id="${orderId}"><i class="fas fa-times-circle"></i></button>`;
            }

            // Show Archive button only for completed, non-archived orders
            if (orderStatus === 'completed' || orderStatus === 'Order Completed') {
                buttons += `<button class="action-btn archive" title="Archive Order" data-id="${orderId}"><i class="fas fa-archive"></i></button>`;
            }
            return buttons;
        }

        // Function to download file(s) - fetches image_path from server
        function downloadOrderFiles(orderId) {
            // Show loading message
            Swal.fire({
                title: 'Preparing Download',
                html: '<i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 15px;"></i><br>Fetching order files...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
            
            // Get order details to fetch the image_path
            $.ajax({
                url: 'actions/get_printing_order_details.php',
                method: 'GET',
                data: { id: orderId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.image_path) {
                        const imagePath = response.data.image_path;
                        const fileCount = response.data.file_count || 1;
                        
                        // Close the loading alert
                        Swal.close();
                        
                        // Show appropriate message
                        const message = fileCount > 1 ? 'Preparing multiple files for download as ZIP...' : 'Downloading file...';
                        Swal.fire({
                            title: 'Download in Progress',
                            html: `<i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 15px;"></i><br>${message}`,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false
                        });
                        
                        // Start the download, passing order_id for proper filename generation
                        window.open('actions/download_file.php?file=' + encodeURIComponent(imagePath) + '&order_id=' + orderId, '_blank');
                        
                        // Close the alert after a short delay
                        setTimeout(() => {
                            Swal.close();
                            if (fileCount > 1) {
                                Swal.fire('Success!', 'Your files are being downloaded as a ZIP archive', 'success');
                            }
                        }, 1500);
                    } else {
                        Swal.fire('Error', 'No files found for this order', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to fetch order details', 'error');
                }
            });
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
                url: 'actions/get_printing_order_details.php',
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
            const colorLabel = order.color_type === 'colored' ? 'Colored Print' : 
                              order.color_type === 'black_and_white' ? 'Black & White' : 'N/A';
            
            const detailsHtml = `
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
                    <div class="order-detail-label">Print Color:</div>
                    <div class="order-detail-value">${colorLabel}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Total Pages:</div>
                    <div class="order-detail-value">${order.page_count || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Number of Files:</div>
                    <div class="order-detail-value">${order.file_count || 'N/A'}</div>
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

        function processOrder(orderId) {
            Swal.fire({
                title: 'Process Order',
                text: 'Are you sure you want to start processing this order?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Process'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading alert while processing
                    Swal.fire({
                        title: 'Sending Email Notification',
                        text: 'Please wait while we send the processing notification to the customer...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // First get order details for email
                    $.ajax({
                        url: 'actions/get_printing_order_details.php',
                        method: 'GET',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(orderResponse) {
                            if (orderResponse.success) {
                                const orderDetails = orderResponse.data;

                                // Prepare email data
                                const emailData = {
                                    id: orderDetails.id,
                                    service: orderDetails.service,
                                    size: orderDetails.size,
                                    paper_type: orderDetails.paper_type,
                                    quantity: orderDetails.quantity,
                                    price: orderDetails.price,
                                    order_date: orderDetails.order_date
                                };

                                // First send email notification
                                $.ajax({
                                    url: 'actions/send_order_processing_email.php',
                                    method: 'POST',
                                    data: {
                                        email: orderDetails.user_email,
                                        name: orderDetails.full_name,
                                        order_details: JSON.stringify(emailData)
                                    },
                                    dataType: 'json',
                                    success: function(emailResponse) {
                                        if (emailResponse.success) {
                                            // Close loading alert and show success
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Email Sent Successfully!',
                                                text: 'Customer has been notified. Updating order status...',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                // Email sent successfully, now update order status
                                                updateOrderStatus(orderId, 'processing', 'Order processing started successfully and customer notified via email');
                                            });
                                        } else {
                                            // Close loading and show email error
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Email Failed',
                                                text: 'Failed to send notification email to customer. Order status not updated. Please try again or contact support.',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: function() {
                                        // Close loading and show connection error
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Connection Failed',
                                            text: 'Failed to send notification email to customer. Order status not updated. Please check your connection and try again.',
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

        function markAsComplete(orderId) {
            // Get order data to check payment method
            const order = allOrdersData.find(o => o.id == orderId);
            const paymentMethod = order ? order.payment_method : 'online';
            
            let dialogTitle = 'Mark as Complete';
            let dialogText = 'Confirm that this order has been completed?';
            let successTitle = 'Order Completed!';
            let successMessage = 'Customer has been notified via email with receipt.';
            
            if (paymentMethod === 'in_person') {
                dialogTitle = 'Complete Order';
                dialogText = 'Confirm payment received and mark this in-person order as complete. A receipt will be sent to the customer.';
                successTitle = 'Payment Received!';
                successMessage = 'Order marked as complete. Receipt sent to customer via email.';
            } else {
                dialogTitle = 'Complete Order';
                dialogText = 'Mark this order as complete after confirming customer payment and receipt. Order details will be verified as paid.';
                successTitle = 'Order Completed!';
                successMessage = 'Order marked as complete. Confirmation and receipt sent to customer.';
            }
            
            Swal.fire({
                title: dialogTitle,
                text: dialogText,
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Complete Order'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading alert while processing
                    Swal.fire({
                        title: 'Processing Completion',
                        text: 'Please wait while we mark the order as complete and notify the customer with receipt...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // First get order details for email
                    $.ajax({
                        url: 'actions/get_printing_order_details.php',
                        method: 'GET',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(orderResponse) {
                            if (orderResponse.success) {
                                const orderDetails = orderResponse.data;

                                // Prepare email data
                                const emailData = orderDetails;

                                // Send appropriate email notification
                                $.ajax({
                                    url: 'actions/send_payment_status_email.php',
                                    method: 'POST',
                                    data: {
                                        action: 'order_completed',
                                        email: orderDetails.user_email,
                                        name: orderDetails.full_name,
                                        user_id: orderDetails.user_id,
                                        payment_method: paymentMethod,
                                        payment_details: JSON.stringify(emailData)
                                    },
                                    dataType: 'json',
                                    success: function(emailResponse) {
                                        if (emailResponse.success) {
                                            // Close loading alert and show success
                                            Swal.fire({
                                                icon: 'success',
                                                title: successTitle,
                                                text: successMessage + ' Updating order status...',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                // Email sent successfully, now update order status
                                                updateOrderStatus(orderId, 'completed', 'Order marked as completed successfully and customer notified with receipt');
                                            });
                                        } else {
                                            // Close loading and show email error
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Email Failed',
                                                text: 'Failed to send completion notification to customer. Order status not updated. Please try again or contact support.',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        // Log error for debugging
                                        console.error('AJAX Error:', {status: status, error: error, response: xhr.responseText});
                                        // Close loading and show connection error
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Connection Failed',
                                            text: 'Failed to send completion notification to customer. Order status not updated. Please check your connection and try again.',
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

        // Function to mark In-Person order as Ready for Pick-Up
        function markAsReadyForPickup(orderId) {
            Swal.fire({
                title: 'Ready for Pick-Up',
                text: 'Confirm that this order is ready for customer pick-up. Customer will be notified to pick up their order.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#FF6B6B',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Mark as Ready'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading alert while processing
                    Swal.fire({
                        title: 'Notifying Customer',
                        text: 'Please wait while we notify the customer that their order is ready for pick-up...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Get order details for email
                    $.ajax({
                        url: 'actions/get_printing_order_details.php',
                        method: 'GET',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(orderResponse) {
                            if (orderResponse.success) {
                                const orderDetails = orderResponse.data;

                                // Send ready for pickup notification
                                $.ajax({
                                    url: 'actions/send_payment_status_email.php',
                                    method: 'POST',
                                    data: {
                                        action: 'ready_for_pickup',
                                        email: orderDetails.user_email,
                                        name: orderDetails.full_name,
                                        user_id: orderDetails.user_id,
                                        payment_method: 'in_person',
                                        payment_details: JSON.stringify(orderDetails)
                                    },
                                    dataType: 'json',
                                    success: function(emailResponse) {
                                        if (emailResponse.success) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Ready for Pick-Up!',
                                                text: 'Customer has been notified. Updating order status...',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                updateOrderStatus(orderId, 'ready_to_pickup', 'Order marked as ready for pick-up and customer notified');
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Notification Failed',
                                                text: 'Failed to send notification. Please try again.',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: function() {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Connection Failed',
                                            text: 'Please check your connection and try again.',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to load order details. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function() {
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
                        url: 'actions/get_printing_order_details.php',
                        method: 'GET',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(orderResponse) {
                            if (orderResponse.success) {
                                const orderDetails = orderResponse.data;

                                // Prepare email data
                                const emailData = {
                                    id: orderDetails.id,
                                    service: orderDetails.service,
                                    size: orderDetails.size,
                                    paper_type: orderDetails.paper_type,
                                    quantity: orderDetails.quantity,
                                    price: orderDetails.price,
                                    order_date: orderDetails.order_date
                                };

                                // First send email notification
                                $.ajax({
                                    url: 'actions/send_payment_status_email.php',
                                    method: 'POST',
                                    data: {
                                        action: 'reject',
                                        email: orderDetails.user_email,
                                        name: orderDetails.full_name,
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
                                    error: function() {
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
                        error: function() {
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
                        url: 'actions/archive_printing_order.php',
                        method: 'POST',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Archived!', 'Order has been archived successfully', 'success').then(() => {
                                    loadPrintingOrders();
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
                title: 'Restore Order',
                text: 'Are you sure you want to restore this order from the archive?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Restore'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/unarchive_printing_order.php',
                        method: 'POST',
                        data: { id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Restored!', 'Order has been restored successfully', 'success').then(() => {
                                    loadPrintingOrders();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to restore order', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to restore order', 'error');
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
                        url: 'actions/delete_printing_order.php',
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
                url: 'actions/update_printing_order_status.php',
                method: 'POST',
                data: { id: orderId, status: status, reason: reason },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', successMessage, 'success').then(() => {
                            loadPrintingOrders();
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
        loadPrintingOrders();

        // Auto-refresh every 10 seconds to check for validated+paid orders that need to be updated to processing
        setInterval(function() {
            console.log('Auto-checking for validated+paid printing orders that need updating...');
            loadPrintingOrders();
        }, 10000); // 10 seconds

        // Real-time filter event handlers
        $('#paymentStatusFilter').on('change', function() {
            applyFilters();
        });

        // Debounced filter for date inputs to prevent excessive filtering
        let dateFilterTimeout;
        $('#orderDateFrom, #orderDateTo').on('change input', function() {
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
