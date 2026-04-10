<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/email_payment.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: signin.php');
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments - Amuning Admin</title>
    <link rel="icon" type="image/x-icon" href="../../images/amuninglogo.ico">
    <link rel="shortcut icon" href="../../images/amuninglogo.ico" type="image/x-icon">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="modal.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        .action-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .action-btn.disabled:hover {
            opacity: 0.5;
            transform: none;
            box-shadow: none;
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
                <h1 class="page-title">Manage Payments</h1>
            </header>

            <div class="main-content">
                <div class="content-wrapper">
                    <div class="recent-orders">
                        <div class="section-header">
                            <h2>Payment Records</h2>
                        </div>

                        <!-- Filters Section -->
                        <div class="filters-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef;">
                            <div class="filter-row" style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap;">
                                <div class="filter-group" style="flex: 1; min-width: 200px;">
                                    <label for="statusFilter" style="display: block; margin-bottom: 5px; font-weight: 600; color: #495057;">Status:</label>
                                    <select id="statusFilter" class="filter-select" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending Review</option>
                                        <option value="processing">Processing</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                </div>
                                <div class="filter-group" style="flex: 1; min-width: 200px;">
                                    <label for="paymentTypeFilter" style="display: block; margin-bottom: 5px; font-weight: 600; color: #495057;">Payment Type:</label>
                                    <select id="paymentTypeFilter" class="filter-select" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
                                        <option value="">All Types</option>
                                        <option value="printing_order">Printing Order</option>
                                        <option value="photo_booking">Photo Booth</option>
                                    </select>
                                </div>
                                <div class="filter-group" style="display: flex; align-items: end;">
                                    <button id="clearFiltersBtn" class="btn-secondary" style="padding: 8px 16px; border: 1px solid #6c757d; background: #6c757d; color: white; border-radius: 4px; cursor: pointer;">Clear Filter</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table id="paymentsTable" class="display responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Payment Type</th>
                                        <th>Service Details</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Transaction #</th>
                                        <th>Proof of Payment</th>
                                        <th>Created Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="12" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-right: 10px;"></i>
                                            Loading payments...
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

    <!-- Payment Details Modal -->
    <div id="paymentDetailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Payment Details</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Payment details will be loaded here -->
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
        // Helper function to format prices
        function formatPrice(price) {
            const numericPrice = parseFloat((price || '0').toString().replace(/[₱$,\s]/g, ''));
            return isNaN(numericPrice) ? '₱0.00' : '₱' + numericPrice.toFixed(2);
        }

        // Load payments data
        function loadPayments(statusFilter = '', paymentTypeFilter = '') {
            $.ajax({
                url: 'actions/get_all_payments.php',
                method: 'GET',
                data: {
                    status: statusFilter,
                    payment_type: paymentTypeFilter
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const paymentsData = response.data.map(payment => [
                            payment.id,
                            payment.user_name || 'N/A',
                            payment.user_email,
                            formatPaymentType(payment.payment_type),
                            payment.service_details || 'N/A',
                            formatPrice(payment.amount),
                            payment.payment_method || 'N/A',
                            payment.transaction_number || 'N/A',
                            formatProofOfPayment(payment.proof_of_payment),
                            new Date(payment.created_at).toLocaleDateString(),
                            getStatusBadge(payment.status),
                            getActionButtons(payment.id, payment.status)
                        ]);

                        // Destroy existing DataTable if it exists
                        if ($.fn.DataTable.isDataTable('#paymentsTable')) {
                            $('#paymentsTable').DataTable().destroy();
                        }

                        // Clear the table content
                        $('#paymentsTable tbody').empty();

                        // Initialize DataTable
                        const paymentsTable = $('#paymentsTable').DataTable({
                            data: paymentsData,
                            responsive: true,
                            pageLength: 10,
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                            language: {
                                search: "Search payments:",
                                lengthMenu: "Show _MENU_ payments",
                                info: "Showing _START_ to _END_ of _TOTAL_ payments",
                                infoEmpty: "No payments available",
                                infoFiltered: "(filtered from _MAX_ total payments)",
                                paginate: {
                                    first: "First",
                                    last: "Last",
                                    next: "Next",
                                    previous: "Prev"
                                },
                                zeroRecords: "No matching payments found"
                            },
                            columnDefs: [
                                {
                                    targets: 8, // Proof of Payment column
                                    orderable: false,
                                    searchable: false,
                                    className: 'text-center',
                                    createdCell: function (td, cellData, rowData, row, col) {
                                        $(td).html(cellData);
                                    }
                                },
                                {
                                    targets: 10, // Status column
                                    createdCell: function (td, cellData, rowData, row, col) {
                                        $(td).html(cellData);
                                    },
                                    className: 'all' // Always visible in responsive mode
                                },
                                {
                                    targets: 11, // Actions column
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
                                    const paymentId = $(this).data('id');
                                    viewPayment(paymentId);
                                });

                                $('.action-btn.process').off('click').on('click', function() {
                                    const paymentId = $(this).data('id');
                                    processPayment(paymentId);
                                });

                                $('.action-btn.approve').off('click').on('click', function() {
                                    const paymentId = $(this).data('id');
                                    approvePayment(paymentId);
                                });

                                $('.action-btn.reject').off('click').on('click', function() {
                                    const paymentId = $(this).data('id');
                                    rejectPayment(paymentId);
                                });

                                $('.action-btn.delete').off('click').on('click', function() {
                                    const paymentId = $(this).data('id');
                                    deletePayment(paymentId);
                                });
                            }
                        });
                    } else {
                        console.error('Failed to load payments:', response.message || 'Unknown error');
                        $('#paymentsTable tbody').html('<tr><td colspan="12" style="text-align: center;">Failed to load data: ' + (response.message || 'Unknown error') + '</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    console.error('Response:', xhr.responseText);
                    $('#paymentsTable tbody').html('<tr><td colspan="12" style="text-align: center;">Failed to load data: ' + error + '</td></tr>');
                }
            });
        }

        // Function to format proof of payment display
        function formatProofOfPayment(proofPath) {
            if (proofPath && proofPath.trim() !== '') {
                const fullImagePath = '../' + proofPath;
                // Check if it's an image file
                const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
                const isImage = imageExtensions.some(ext => proofPath.toLowerCase().includes(ext));

                if (isImage) {
                    return `<div style="text-align: center;">
                        <img src="${fullImagePath}" alt="Proof of payment" style="max-width: 60px; max-height: 60px; border: 1px solid #ddd; border-radius: 4px;"><br>
                        <a href="${fullImagePath}" target="_blank" style="color: #007bff; text-decoration: none; font-size: 11px;">View</a>
                    </div>`;
                } else {
                    return `<a href="${fullImagePath}" target="_blank" style="color: #007bff; text-decoration: none;">📎 View File</a>`;
                }
            }
            return '<span style="color: #999; font-style: italic;">No proof</span>';
        }

        // Function to format payment type
        function formatPaymentType(type) {
            const typeLabels = {
                'photo_booking': 'Photo Booth',
                'printing_order': 'Printing Order'
            };
            return typeLabels[type] || type;
        }

        // Function to get status badge
        function getStatusBadge(status) {
            const statusClasses = {
                'pending': 'status-pending',
                'processing': 'status-processing',
                'paid': 'status-paid',
                'completed': 'status-completed',
                'cancelled': 'status-cancelled',
                'declined': 'status-declined'
            };

            const statusLabels = {
                'pending': 'Pending Review',
                'processing': 'Processing',
                'paid': 'Paid',
                'completed': 'Completed',
                'cancelled': 'Cancelled',
                'declined': 'Declined'
            };

            const cssClass = statusClasses[status] || 'status-pending';
            const label = statusLabels[status] || 'Unknown';

            return `<span class="status ${cssClass}">${label}</span>`;
        }

        // Function to get action buttons HTML
        function getActionButtons(paymentId, status) {
            let buttons = `<button class="action-btn view" title="View Details" data-id="${paymentId}"><i class="fas fa-eye"></i></button>`;

            if (status === 'pending') {
                buttons += `<button class="action-btn process" title="Start Processing" data-id="${paymentId}"><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn approve disabled" title="Cannot approve - pending review" data-id="${paymentId}" disabled><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn reject" title="Reject Payment" data-id="${paymentId}"><i class="fas fa-times"></i></button>`;
            } else if (status === 'processing') {
                buttons += `<button class="action-btn process disabled" title="Already processing" data-id="${paymentId}" disabled><i class="fas fa-cog"></i></button>`;
                buttons += `<button class="action-btn approve" title="Approve Payment" data-id="${paymentId}"><i class="fas fa-check"></i></button>`;
                buttons += `<button class="action-btn reject" title="Reject Payment" data-id="${paymentId}"><i class="fas fa-times"></i></button>`;
            } else if (status === 'paid') {
                buttons += `<button class="action-btn delete" title="Delete Payment" data-id="${paymentId}"><i class="fas fa-trash"></i></button>`;
            } else if (status === 'declined') {
                buttons += `<button class="action-btn delete" title="Delete Declined Payment" data-id="${paymentId}"><i class="fas fa-trash"></i></button>`;
            }

            return buttons;
        }

        // Action functions
        function viewPayment(paymentId) {
            // Show modal with loading content first
            $('#paymentDetailsContent').html(`
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 15px;"></i>
                    <p>Loading payment details...</p>
                </div>
            `);
            $('#paymentDetailsModal').fadeIn(300);

            // Fetch payment details
            $.ajax({
                url: 'actions/get_payment_details.php',
                method: 'GET',
                data: { id: paymentId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayPaymentDetails(response.data);
                    } else {
                        $('#paymentDetailsContent').html(`
                            <div style="text-align: center; padding: 40px; color: #dc3545;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 15px;"></i>
                                <p>${response.message || 'Failed to load payment details'}</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $('#paymentDetailsContent').html(`
                        <div style="text-align: center; padding: 40px; color: #dc3545;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 15px;"></i>
                            <p>Failed to load payment details</p>
                        </div>
                    `);
                }
            });
        }

        // Function to display payment details in modal
        function displayPaymentDetails(payment) {
            const detailsHtml = `
                <div class="order-detail-row">
                    <div class="order-detail-label">Payment ID:</div>
                    <div class="order-detail-value">#${payment.id}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Full Name:</div>
                    <div class="order-detail-value">${payment.user_name || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Email:</div>
                    <div class="order-detail-value">${payment.user_email}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Payment Type:</div>
                    <div class="order-detail-value">${formatPaymentType(payment.payment_type)}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Service Details:</div>
                    <div class="order-detail-value">${payment.service_details || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Amount:</div>
                    <div class="order-detail-value">${formatPrice(payment.amount)}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Payment Method:</div>
                    <div class="order-detail-value">${payment.payment_method || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Transaction Number:</div>
                    <div class="order-detail-value">${payment.transaction_number || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Proof of Payment:</div>
                    <div class="order-detail-value">${formatProofOfPayment(payment.proof_of_payment)}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Notes:</div>
                    <div class="order-detail-value">${payment.notes || 'N/A'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Payment Status:</div>
                    <div class="order-detail-value">${getStatusBadge(payment.status)}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Order Status:</div>
                    <div class="order-detail-value">${payment.order_status_display || 'Not available'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Created Date:</div>
                    <div class="order-detail-value">${new Date(payment.created_at).toLocaleString()}</div>
                </div>
            `;
            $('#paymentDetailsContent').html(detailsHtml);
        }

        function processPayment(paymentId) {
            Swal.fire({
                title: 'Process Payment',
                text: 'Are you sure you want to start processing this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Start Processing'
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

                    // First get payment details for email
                    $.ajax({
                        url: 'actions/get_payment_details.php',
                        method: 'GET',
                        data: { id: paymentId },
                        dataType: 'json',
                        success: function(paymentResponse) {
                            if (paymentResponse.success) {
                                const paymentDetails = paymentResponse.data;

                                // Prepare email data
                                const emailData = {
                                    id: paymentDetails.id,
                                    service_type: paymentDetails.payment_type === 'printing_order' ? 'Printing Order' :
                                                 paymentDetails.payment_type === 'photo_booking' ? 'Photo Booth' : 'Service',
                                    reference: paymentDetails.reference_id,
                                    amount: paymentDetails.amount,
                                    payment_method: paymentDetails.payment_method,
                                    transaction_number: paymentDetails.transaction_number
                                };

                                // First send email notification
                                $.ajax({
                                    url: 'actions/send_payment_processing_email.php',
                                    method: 'POST',
                                    data: {
                                        email: paymentDetails.user_email,
                                        name: paymentDetails.user_name,
                                        payment_details: JSON.stringify(emailData)
                                    },
                                    dataType: 'json',
                                    success: function(emailResponse) {
                                        if (emailResponse.success) {
                                            // Close loading alert and show success
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Email Sent Successfully!',
                                                text: 'Customer has been notified. Updating payment status...',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                // Email sent successfully, now update payment status
                                                updatePaymentStatus(paymentId, 'processing', 'Payment processing started successfully and customer notified via email');
                                            });
                                        } else {
                                            // Close loading and show email error
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Email Failed',
                                                text: 'Failed to send notification email to customer. Payment status not updated. Please try again or contact support.',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: function() {
                                        // Close loading and show connection error
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Connection Failed',
                                            text: 'Failed to send notification email to customer. Payment status not updated. Please check your connection and try again.',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                });
                            } else {
                                // Close loading and show error
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to load payment details. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function() {
                            // Close loading and show error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to load payment details. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        function approvePayment(paymentId) {
            Swal.fire({
                title: 'Approve Payment',
                text: 'Are you sure you want to approve this payment?',
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading alert while processing
                    Swal.fire({
                        title: 'Processing Approval',
                        text: 'Please wait while we approve the payment and notify the customer...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // First get payment details for email
                    $.ajax({
                        url: 'actions/get_payment_details.php',
                        method: 'GET',
                        data: { id: paymentId },
                        dataType: 'json',
                        success: function(paymentResponse) {
                            if (paymentResponse.success) {
                                const paymentDetails = paymentResponse.data;

                                // Prepare email data
                                const emailData = {
                                    id: paymentDetails.id,
                                    service_type: paymentDetails.payment_type === 'printing_order' ? 'Printing Order' :
                                                 paymentDetails.payment_type === 'photo_booking' ? 'Photo Booth' : 'Service',
                                    reference: paymentDetails.reference_id,
                                    amount: paymentDetails.amount,
                                    payment_method: paymentDetails.payment_method,
                                    transaction_number: paymentDetails.transaction_number
                                };

                                // First send email notification
                                $.ajax({
                                    url: 'actions/send_payment_status_email.php',
                                    method: 'POST',
                                    data: {
                                        action: 'approve',
                                        email: paymentDetails.user_email,
                                        name: paymentDetails.user_name,
                                        user_id: paymentDetails.user_id,
                                        payment_details: JSON.stringify(emailData)
                                    },
                                    dataType: 'json',
                                    success: function(emailResponse) {
                                        if (emailResponse.success) {
                                            // Close loading alert and show success
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Payment Approved!',
                                                text: 'Customer has been notified via email with receipt. Updating payment status...',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                // Email sent successfully, now update payment status
                                                updatePaymentStatus(paymentId, 'paid', 'Payment approved successfully and customer notified via email');
                                            });
                                        } else {
                                            // Close loading and show email error
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Email Failed',
                                                text: 'Failed to send approval notification to customer. Payment status not updated. Please try again or contact support.',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: function() {
                                        // Close loading and show connection error
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Connection Failed',
                                            text: 'Failed to send approval notification to customer. Payment status not updated. Please check your connection and try again.',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                });
                            } else {
                                // Close loading and show error
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to load payment details. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function() {
                            // Close loading and show error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to load payment details. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        function rejectPayment(paymentId) {
            Swal.fire({
                title: 'Reject Payment',
                text: 'Are you sure you want to reject this payment?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject',
                input: 'textarea',
                inputLabel: 'Reason for rejection (optional)',
                inputPlaceholder: 'Please provide a reason for rejecting this payment...'
            }).then((result) => {
                if (result.isConfirmed) {
                    const reason = result.value || '';

                    // Show loading alert while processing
                    Swal.fire({
                        title: 'Processing Rejection',
                        text: 'Please wait while we reject the payment and notify the customer...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // First get payment details for email
                    $.ajax({
                        url: 'actions/get_payment_details.php',
                        method: 'GET',
                        data: { id: paymentId },
                        dataType: 'json',
                        success: function(paymentResponse) {
                            if (paymentResponse.success) {
                                const paymentDetails = paymentResponse.data;

                                // Prepare email data
                                const emailData = {
                                    id: paymentDetails.id,
                                    service_type: paymentDetails.payment_type === 'printing_order' ? 'Printing Order' :
                                                 paymentDetails.payment_type === 'photo_booking' ? 'Photo Booth' : 'Service',
                                    reference: paymentDetails.reference_id,
                                    amount: paymentDetails.amount,
                                    payment_method: paymentDetails.payment_method,
                                    transaction_number: paymentDetails.transaction_number
                                };

                                // First send email notification
                                $.ajax({
                                    url: 'actions/send_payment_status_email.php',
                                    method: 'POST',
                                    data: {
                                        action: 'reject',
                                        email: paymentDetails.user_email,
                                        name: paymentDetails.user_name,
                                        payment_details: JSON.stringify(emailData),
                                        reason: reason
                                    },
                                    dataType: 'json',
                                    success: function(emailResponse) {
                                        if (emailResponse.success) {
                                            // Close loading alert and show success
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Payment Rejected',
                                                text: 'Customer has been notified via email. Updating payment status...',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                // Email sent successfully, now update payment status
                                                updatePaymentStatus(paymentId, 'declined', 'Payment rejected and customer notified via email');
                                            });
                                        } else {
                                            // Close loading and show email error
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Email Failed',
                                                text: 'Failed to send rejection notification to customer. Payment status not updated. Please try again or contact support.',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: function() {
                                        // Close loading and show connection error
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Connection Failed',
                                            text: 'Failed to send rejection notification to customer. Payment status not updated. Please check your connection and try again.',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                });
                            } else {
                                // Close loading and show error
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to load payment details. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function() {
                            // Close loading and show error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to load payment details. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        function deletePayment(paymentId) {
            Swal.fire({
                title: 'Delete Payment',
                text: 'Are you sure you want to permanently delete this payment? This action cannot be undone.',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/delete_payment.php',
                        method: 'POST',
                        data: { id: paymentId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', 'Payment has been deleted successfully.', 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to delete payment', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to delete payment', 'error');
                        }
                    });
                }
            });
        }

        function updatePaymentStatus(paymentId, status, successMessage) {
            $.ajax({
                url: 'actions/update_payment_status.php',
                method: 'POST',
                data: { id: paymentId, status: status },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', successMessage, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to update payment status', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to update payment status', 'error');
                }
            });
        }

        // Load data on page load
        loadPayments();

        // Apply filters function
        function applyFilters() {
            const statusFilter = $('#statusFilter').val();
            const paymentTypeFilter = $('#paymentTypeFilter').val();
            loadPayments(statusFilter, paymentTypeFilter);
        }

        // Clear filters function
        function clearFilters() {
            $('#statusFilter').val('');
            $('#paymentTypeFilter').val('');
            loadPayments();
        }

        // Filter event listeners
        $('#statusFilter').on('change', function() {
            applyFilters();
        });

        $('#paymentTypeFilter').on('change', function() {
            applyFilters();
        });

        $('#clearFiltersBtn').on('click', function() {
            clearFilters();
        });

        // Modal functionality
        $('.modal-close').on('click', function() {
            $('#paymentDetailsModal').fadeOut();
        });

        $(window).on('click', function(event) {
            if (event.target.id === 'paymentDetailsModal') {
                $('#paymentDetailsModal').fadeOut();
            }
        });
    });
    </script>

</body>
</html>
