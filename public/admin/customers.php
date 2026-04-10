<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: signin.php');
    exit();
}

$current_page = 'customers.php';
$page_title = 'Manage Customers';
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
        /* Registration method icons */
        .registration-icon {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .registration-google {
            background: #e8f4fd;
            color: #4285f4;
        }

        .registration-email {
            background: #f8f9fa;
            color: #6c757d;
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
                <h1 class="page-title">Manage Customers</h1>
            </header>

            <div class="main-content">
                <div class="content-wrapper">
                    <div class="recent-orders">
                        <div class="section-header">
                            <h2>Registered Customers</h2>
                        </div>

                        <div class="table-container">
                            <table id="customersTable" class="display responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Profile</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Address</th>
                                        <th>Registration Method</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-right: 10px;"></i>
                                            Loading customers...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Customer Details Modal -->
        <div id="customerDetailsModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Customer Details</h3>
                    <span class="modal-close">&times;</span>
                </div>
                <div class="modal-body" id="customerDetailsContent">
                    <!-- Customer details will be loaded here -->
                </div>
            </div>
        </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        let customersTable;

        // Initialize DataTable
        function initializeTable(data) {
            if (customersTable) {
                customersTable.destroy();
            }

            customersTable = $('#customersTable').DataTable({
                data: data,
                columns: [
                    { data: 'id' },
                    {
                        data: 'profile',
                        orderable: false,
                        render: function(data, type, row) {
                            if (data) {
                                // Check if it's a Google profile URL (starts with http/https)
                                if (data.startsWith('http://') || data.startsWith('https://')) {
                                    return `<img src="${data}" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">`;
                                } else {
                                    // Local uploaded file
                                    return `<img src="../uploads/profiles/${data}" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">`;
                                }
                            }
                            return `<i class="fas fa-user-circle" style="font-size: 40px; color: #6c757d;"></i>`;
                        }
                    },
                    { data: 'full_name' },
                    { data: 'email' },
                    {
                        data: 'mobile',
                        render: function(data, type, row) {
                            return data || '<span style="color: #6c757d;">Not provided</span>';
                        }
                    },
                    {
                        data: 'address',
                        render: function(data, type, row) {
                            return data || '<span style="color: #6c757d;">Not provided</span>';
                        }
                    },
                    {
                        data: 'register_as',
                        render: function(data, type, row) {
                            if (data === 'google') {
                                return '<span class="registration-icon registration-google"><i class="fab fa-google"></i> Google</span>';
                            } else {
                                return '<span class="registration-icon registration-email"><i class="fas fa-envelope"></i> Email</span>';
                            }
                        }
                    },
                    {
                        data: 'created_at',
                        render: function(data, type, row) {
                            return new Date(data).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                                <button class="action-btn view" onclick="viewCustomer(${row.id})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            `;
                        }
                    }
                ],
                responsive: true,
                pageLength: 25,
                order: [[7, 'desc']], // Sort by registration date descending
                columnDefs: [
                    {
                        targets: 8, // Actions column (0-indexed)
                        orderable: false,
                        searchable: false,
                        className: 'all' // Always visible in responsive mode
                    }
                ],
                language: {
                    emptyTable: "No customers found"
                }
            });
        }

        // Load customers data
        function loadCustomers() {
            $.ajax({
                url: 'actions/get_all_customers.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        initializeTable(response.data);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error || 'Failed to load customers'
                        });
                        $('#customersTable tbody').html(`
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #dc3545;">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-right: 10px;"></i>
                                    Failed to load customers
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Failed to connect to server'
                    });
                    $('#customersTable tbody').html(`
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #dc3545;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-right: 10px;"></i>
                                Failed to load customers
                            </td>
                        </tr>
                    `);
                }
            });
        }

        // View customer details
        window.viewCustomer = function(customerId) {
            // Show modal with loading content first
            $('#customerDetailsContent').html(`
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 15px;"></i>
                    <p>Loading customer details...</p>
                </div>
            `);
            $('#customerDetailsModal').fadeIn(300);

            // Fetch customer details
            $.ajax({
                url: 'actions/get_customer_details.php',
                method: 'GET',
                data: { id: customerId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayCustomerDetails(response.data);
                    } else {
                        $('#customerDetailsContent').html(`
                            <div style="text-align: center; padding: 40px; color: #dc3545;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 15px;"></i>
                                <p>${response.error || 'Failed to load customer details'}</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $('#customerDetailsContent').html(`
                        <div style="text-align: center; padding: 40px; color: #dc3545;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 15px;"></i>
                            <p>Failed to load customer details</p>
                        </div>
                    `);
                }
            });
        };

        // Function to display customer details in modal
        function displayCustomerDetails(customer) {
            const detailsHtml = `
                <div class="order-detail-row">
                    <div class="order-detail-label">Customer ID:</div>
                    <div class="order-detail-value">#${customer.id}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Full Name:</div>
                    <div class="order-detail-value">${customer.full_name}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Email:</div>
                    <div class="order-detail-value">${customer.email}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Mobile:</div>
                    <div class="order-detail-value">${customer.mobile || 'Not provided'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Address:</div>
                    <div class="order-detail-value">${customer.address || 'Not provided'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Registration Method:</div>
                    <div class="order-detail-value">${customer.register_as === 'google' ? 'Google Account' : 'Email Registration'}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">User Type:</div>
                    <div class="order-detail-value">${customer.user_type}</div>
                </div>
                <div class="order-detail-row">
                    <div class="order-detail-label">Registration Date:</div>
                    <div class="order-detail-value">${new Date(customer.created_at).toLocaleString()}</div>
                </div>
            `;
            $('#customerDetailsContent').html(detailsHtml);
        }

        // Toggle sidebar
        window.toggleSidebar = function() {
            document.querySelector('.admin-sidebar').classList.toggle('collapsed');
            document.querySelector('.admin-main').classList.toggle('expanded');
        };

        // Load customers on page load
        loadCustomers();

        // Modal functionality
        $('.modal-close').on('click', function() {
            $('#customerDetailsModal').fadeOut();
        });

        $(window).on('click', function(event) {
            if (event.target.id === 'customerDetailsModal') {
                $('#customerDetailsModal').fadeOut();
            }
        });
    });
    </script>
</body>
</html>
