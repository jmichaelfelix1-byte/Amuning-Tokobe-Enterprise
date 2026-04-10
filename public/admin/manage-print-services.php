<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: signin.php');
    exit();
}

$current_page = 'manage-print-services.php';
$page_title = 'Manage Print Services';
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
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>

        <main class="admin-main">
            <header class="main-header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Manage Print Services</h1>
            </header>

            <div class="main-content">
                <div class="content-wrapper">
                    <div class="recent-orders">
                        <div class="section-header">
                            <h2>Print Services</h2>
                            <button class="btn-primary" id="addServiceBtn">
                                <i class="fas fa-plus"></i> Add New Service
                            </button>
                        </div>

                        <div class="table-container">
                            <table id="printServicesTable" class="display responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>ID</th>
                                        <th>Service Name</th>
                                        <th>Description</th>
                                        <th>Base Price</th>
                                        <th>Paper Types</th>
                                        <th>Sizes</th>
                                        <th>Stock Quantity</th>
                                        <th>Available</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="11" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-right: 10px;"></i>
                                            Loading print services...
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
        // Toggle availability function
        function toggleAvailability(serviceId, currentStatus, newStatus, actionText) {
            Swal.fire({
                title: 'Toggle Availability',
                text: `Are you sure you want to ${actionText} this service?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus === '1' ? '#28a745' : '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${actionText}`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/toggle_print_service_availability.php',
                        method: 'POST',
                        data: { id: serviceId, is_available: newStatus },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: `Service ${actionText}d successfully`,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to update availability', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to update availability', 'error');
                        }
                    });
                }
            });
        }

        // Load print services data
        function loadPrintServices() {
            $.ajax({
                url: 'actions/get_print_services.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const servicesData = response.data.map(service => [
                            `<img src="../assets/${service.image_path}" alt="${service.service_name}" class="service-image">`,
                            service.id,
                            service.service_name,
                            service.description || 'No description',
                            '₱' + parseFloat(service.base_price).toFixed(2),
                            formatArray(service.paper_types),
                            formatArray(service.sizes),
                            service.stock_quantity,
                            service.is_available ? '<span class="status status-available">Available</span>' : '<span class="status status-unavailable">Unavailable</span>',
                            getActionButtons(service.id)
                        ]);

                        // Initialize DataTable
                        const servicesTable = $('#printServicesTable').DataTable({
                            data: servicesData,
                            responsive: true,
                            pageLength: 10,
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                            language: {
                                search: "Search print services:",
                                lengthMenu: "Show _MENU_ services",
                                info: "Showing _START_ to _END_ of _TOTAL_ print services",
                                infoEmpty: "No print services available",
                                infoFiltered: "(filtered from _MAX_ total print services)",
                                paginate: {
                                    first: "First",
                                    last: "Last",
                                    next: "Next",
                                    previous: "Prev"
                                },
                                zeroRecords: "No matching print services found"
                            },
                            columnDefs: [
                                {
                                    targets: 0, // Image column
                                    orderable: false,
                                    searchable: false,
                                    createdCell: function (td, cellData, rowData, row, col) {
                                        $(td).html(cellData);
                                    }
                                },
                                {
                                    targets: 8, // Availability column
                                    createdCell: function (td, cellData, rowData, row, col) {
                                        // rowData[1] is the service ID
                                        const serviceId = rowData[1];
                                        const isAvailable = rowData[8].includes('status-available');
                                        const toggleButton = `<button class="action-btn toggle-availability ${isAvailable ? 'available' : 'unavailable'}" title="${isAvailable ? 'Mark as Unavailable' : 'Mark as Available'}" data-id="${serviceId}" data-current="${isAvailable ? '1' : '0'}">
                                            <i class="fas ${isAvailable ? 'fa-toggle-on' : 'fa-toggle-off'}"></i>
                                        </button>`;
                                        $(td).html(cellData + toggleButton);
                                    },
                                    className: 'all' // Always visible in responsive mode
                                },
                                {
                                    targets: 9, // Actions column
                                    orderable: false,
                                    searchable: false,
                                    className: 'all', // Always visible in responsive mode
                                    createdCell: function (td, cellData, rowData, row, col) {
                                        $(td).html(cellData);
                                    }
                                }
                            ],
                            order: [[1, 'asc']], // Sort by ID (now at index 1)
                            drawCallback: function() {
                                // Add event listeners to action buttons after each draw
                                $('.action-btn.edit').off('click').on('click', function() {
                                    const serviceId = $(this).data('id');
                                    editService(serviceId);
                                });

                                $('.action-btn.delete').off('click').on('click', function() {
                                    const serviceId = $(this).data('id');
                                    deleteService(serviceId);
                                });

                                $('.action-btn.toggle-availability').off('click').on('click', function() {
                                    const serviceId = $(this).data('id');
                                    const currentStatus = $(this).data('current');
                                    const title = $(this).attr('title');
                                    
                                    // Extract action from title: "Mark as Unavailable" -> "mark as unavailable"
                                    const actionText = title.toLowerCase();
                                    
                                    // Determine newStatus based on title: if title contains "Unavailable", we want to make it unavailable (0)
                                    const newStatus = title.includes('Unavailable') ? '0' : '1';
                                    
                                    toggleAvailability(serviceId, currentStatus, newStatus, actionText);
                                });
                            }
                        });
                    } else {
                        console.error('Failed to load print services:', response.error);
                        $('#printServicesTable tbody').html('<tr><td colspan="10" style="text-align: center;">Failed to load data</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    $('#printServicesTable tbody').html('<tr><td colspan="10" style="text-align: center;">Failed to load data</td></tr>');
                }
            });
        }

        // Function to format arrays (paper types, sizes)
        function formatArray(jsonString) {
            try {
                const arr = JSON.parse(jsonString);
                return arr.join(', ');
            } catch (e) {
                return jsonString || 'N/A';
            }
        }

        // Function to get action buttons HTML
        function getActionButtons(serviceId) {
            return `<button class="action-btn edit" title="Edit" data-id="${serviceId}"><i class="fas fa-edit"></i></button><button class="action-btn delete" title="Delete" data-id="${serviceId}"><i class="fas fa-trash"></i></button>`;
        }

        // Modal functions
        function openModal(mode = 'add', serviceId = null) {
            $('#serviceModal').fadeIn(300);
            $('#modalTitle').text(mode === 'edit' ? 'Edit Print Service' : 'Add Print Service');
            
            if (mode === 'edit' && serviceId) {
                $('#serviceId').val(serviceId);
                loadServiceData(serviceId);
            } else {
                resetForm();
            }
        }

        function closeModal() {
            $('#serviceModal').fadeOut(300);
            resetForm();
        }

        function resetForm() {
            $('#serviceForm')[0].reset();
            $('#serviceId').val('');
            $('#modalTitle').text('Add Print Service');
        }

        function loadServiceData(serviceId) {
            $.ajax({
                url: 'actions/get_print_service.php',
                method: 'GET',
                data: { id: serviceId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const service = response.data;
                        $('#serviceId').val(service.id);
                        $('#serviceName').val(service.service_name);
                        $('#serviceDescription').val(service.description || '');
                        $('#basePrice').val(service.base_price);
                        $('#paperTypes').val(formatArrayForTextarea(service.paper_types));
                        $('#sizes').val(formatArrayForTextarea(service.sizes));
                        $('#stockQuantity').val(service.stock_quantity);
                        $('#isAvailable').val(service.is_available ? '1' : '0');
                    } else {
                        Swal.fire('Error', 'Failed to load service data', 'error');
                        closeModal();
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load service data', 'error');
                    closeModal();
                }
            });
        }

        function formatArrayForTextarea(jsonString) {
            try {
                const arr = JSON.parse(jsonString);
                return arr.join(', ');
            } catch (e) {
                return jsonString || '';
            }
        }

        // Add new service
        $('#addServiceBtn').on('click', function() {
            openModal('add');
        });

        // Edit service
        function editService(serviceId) {
            openModal('edit', serviceId);
        }

        // Form submission
        $('#serviceForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const isEdit = $('#serviceId').val() !== '';
            const url = isEdit ? 'actions/update_print_service.php' : 'actions/add_print_service.php';
            const method = 'POST';

            $.ajax({
                url: url,
                method: method,
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: isEdit ? 'Service updated successfully' : 'Service added successfully',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            closeModal();
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to save service', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    Swal.fire('Error', 'Failed to save service', 'error');
                }
            });
        });

        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if ($(e.target).is('#serviceModal')) {
                closeModal();
            }
        });

        // Close modal on escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $('#serviceModal').is(':visible')) {
                closeModal();
            }
        });

        // Close modal button click
        $('#closeModalBtn').on('click', function() {
            closeModal();
        });

        // Cancel button click
        $('#cancelBtn').on('click', function() {
            closeModal();
        });

        // Delete service
        function deleteService(serviceId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone. This will permanently delete the service.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/delete_print_service.php',
                        method: 'POST',
                        data: { id: serviceId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Service has been deleted.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message || 'Failed to delete service.',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'Failed to delete service.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        // Load data on page load
        loadPrintServices();
    });
    </script>

    <!-- Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Print Service</h3>
                <button class="close-modal" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="serviceForm" enctype="multipart/form-data">
                    <input type="hidden" id="serviceId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="serviceName">Service Name *</label>
                            <input type="text" id="serviceName" name="service_name" required>
                        </div>
                        <div class="form-group">
                            <label for="basePrice">Base Price *</label>
                            <input type="number" id="basePrice" name="base_price" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="paperTypes">Paper Types *</label>
                            <textarea id="paperTypes" name="paper_types" rows="2" placeholder="e.g., Standard, Glossy, Matte, Cardstock" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="sizes">Sizes *</label>
                            <textarea id="sizes" name="sizes" rows="2" placeholder="e.g., 3x4, 4x6, 5x7, A4, Letter" required></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="stockQuantity">Stock Quantity *</label>
                            <input type="number" id="stockQuantity" name="stock_quantity" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="isAvailable">Availability</label>
                            <select id="isAvailable" name="is_available">
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="serviceDescription">Description</label>
                        <textarea id="serviceDescription" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="serviceImage">Service Image</label>
                        <input type="file" id="serviceImage" name="image" accept="image/*">
                        <small class="form-hint">Leave empty to keep current image (for edit mode)</small>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
                        <button type="submit" class="btn-primary">Save Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


</body>
</html>
