<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: signin.php');
    exit();
}

$current_page = 'bookingscalendar.php';
$page_title = 'Bookings Calendar';
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

    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        .calendar-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        #calendar {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        /* FullCalendar custom styling */
        .fc {
            font-size: 14px;
        }

        .fc .fc-button-primary {
            background-color: #f5276c;
            border-color: #f5276c;
        }

        .fc .fc-button-primary:hover {
            background-color: #d91d5c;
            border-color: #d91d5c;
        }

        .fc .fc-button-primary.fc-button-active {
            background-color: #d91d5c;
            border-color: #d91d5c;
        }

        .fc .fc-col-header-cell {
            background-color: #f8f9fa;
            color: #333;
        }

        .fc .fc-daygrid-day.fc-day-today {
            background-color: #d1fae5;
        }

        .fc .fc-daygrid-day.past-date {
            background-color: #fee2e2;
            opacity: 0.7;
        }

        .fc .fc-daygrid-day.booked-date {
            background-color: #dbeafe !important;
            border: 2px solid #3b82f6 !important;
        }

        .fc .fc-daygrid-day.fully-booked-date {
            background-color: #fecaca !important;
            border: 2px solid #dc2626 !important;
        }

        .fc .fc-daygrid-day:hover {
            background-color: #f5f5f5;
        }

        .fc-event {
            border-radius: 4px;
            padding: 2px 4px;
        }

        .fc-event-available {
            background-color: #10b981;
            border-color: #059669;
        }

        .fc-event-unavailable {
            background-color: #ef4444;
            border-color: #dc2626;
        }

        .fc-event-booked {
            background-color: #3b82f6;
            border-color: #1d4ed8;
        }

        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
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
                <h1 class="page-title">Bookings Calendar</h1>
            </header>

            <div class="main-content">
                <div class="content-wrapper">
                    <!-- Legend -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #d1fae5;"></div>
                            <span>Today</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #dbeafe; border: 2px solid #3b82f6;"></div>
                            <span>Booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #fecaca; border: 2px solid #dc2626;"></div>
                            <span>Fully Booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #fee2e2;"></div>
                            <span>Unavailable</span>
                        </div>
                    </div>

                    <!-- Calendar -->
                    <div class="calendar-container">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </main>
        <?php include '../includes/admin_footer.php'; ?>
    </div>

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script src="../assets/js/admin-sidebar.js"></script>

    <script>
    // Function to get booking status badge with appropriate styling
    function getBookingStatusBadge(status) {
        // Map booking status to CSS class
        let cssClass = 'pending';
        
        if (status === 'validated') {
            cssClass = 'validated';
        } else if (status === 'booked') {
            cssClass = 'booked';
        } else if (status === 'completed') {
            cssClass = 'completed';
        } else if (status === 'declined') {
            cssClass = 'declined';
        } else if (status === 'cancelled') {
            cssClass = 'cancelled';
        } else {
            cssClass = 'pending'; // Default for 'pending' and others
        }

        return `<span class="booking ${cssClass}" style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase;">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    }

    $(document).ready(function() {
        let calendar;
        let bookedDates = [];
        let fullyBookedDates = [];

        // Initialize FullCalendar
        function initializeCalendar() {
            // Load booked dates first before initializing calendar
            loadBookedDates(function() {
                const calendarEl = document.getElementById('calendar');
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next',
                        center: 'title',
                        right: 'dayGridMonth,listMonth'
                    },
                    height: 'auto',
                    events: function(info, successCallback, failureCallback) {
                        loadCalendarEvents(info.start, info.end, successCallback, failureCallback);
                    },
                    dayCellDidMount: function(info) {
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        const cellDate = new Date(info.date);
                        cellDate.setHours(0, 0, 0, 0);
                        
                        // Format date to YYYY-MM-DD using local timezone (not UTC)
                        const year = cellDate.getFullYear();
                        const month = String(cellDate.getMonth() + 1).padStart(2, '0');
                        const day = String(cellDate.getDate()).padStart(2, '0');
                        const dateStr = `${year}-${month}-${day}`;
                        
                        // Priority: fully-booked > booked > today > past > unbooked
                        // Check if date is fully booked first (highest priority)
                        if (fullyBookedDates.includes(dateStr)) {
                            info.el.classList.add('fully-booked-date');
                        }
                        // Check if date has bookings
                        else if (bookedDates.includes(dateStr)) {
                            info.el.classList.add('booked-date');
                        }
                        // Check if date is in the past
                        else if (cellDate < today) {
                            info.el.classList.add('past-date');
                        }
                        // Check if date is today
                        else if (cellDate.getTime() === today.getTime()) {
                            info.el.classList.add('fc-day-today');
                        }
                    },
                    eventClick: function(info) {
                        const bookingId = info.event.extendedProps.bookingId;
                        if (bookingId) {
                            // Fetch detailed booking information
                            $.ajax({
                                url: 'actions/get_photobooth_bookings.php',
                                method: 'GET',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        const booking = response.data.find(b => b.id === bookingId);
                                        if (booking) {
                                            const eventDate = new Date(booking.event_date);
                                            const formattedDate = eventDate.toLocaleDateString('en-US', {
                                                weekday: 'long',
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric'
                                            });

                                            const detailsHtml = `
                                                <div style="text-align: left;">
                                                    <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">
                                                        <strong>Booking ID:</strong> #${booking.id}<br>
                                                        <strong>Customer Name:</strong> ${booking.name}<br>
                                                        <strong>Email:</strong> ${booking.email}<br>
                                                        <strong>Mobile:</strong> ${booking.mobile}
                                                    </div>
                                                    <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">
                                                        <strong>Event Date & Time:</strong> ${formattedDate} at ${booking.time_of_service}<br>
                                                        <strong>Event Type:</strong> ${booking.event_type}<br>
                                                        <strong>Product:</strong> ${booking.product}<br>
                                                        <strong>Duration:</strong> ${booking.duration}<br>
                                                        <strong>Package Type:</strong> ${booking.package_type}
                                                    </div>
                                                    <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">
                                                        <strong>Venue:</strong> ${booking.venue}<br>
                                                        <strong>Address:</strong> ${booking.street_address}<br>
                                                        ${booking.city}, ${booking.region} ${booking.postal_code}<br>
                                                        ${booking.country}
                                                    </div>
                                                    <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">
                                                        <strong>Remarks:</strong> ${booking.remarks || 'None'}<br>
                                                        <strong>Estimated Price:</strong> ${booking.estimated_price}<br>
                                                        <strong>Travel Fee:</strong> ${booking.travel_fee && booking.travel_fee > 0 ? '₱' + parseFloat(booking.travel_fee).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '₱0.00'}<br>
                                                        <strong>Total:</strong> <span style="color: #f5276c; font-weight: 600;">₱${(parseFloat(booking.estimated_price.replace(/[₱,]/g, '')) + parseFloat(booking.travel_fee || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                                    </div>
                                                    <div>
                                                        <strong>Status:</strong> ${getBookingStatusBadge(booking.status)}<br>
                                                        <strong>Booking Date:</strong> ${new Date(booking.booking_date).toLocaleString()}
                                                    </div>
                                                </div>
                                            `;

                                            Swal.fire({
                                                title: 'Booking Details',
                                                html: detailsHtml,
                                                icon: 'info',
                                                width: '600px',
                                                confirmButtonColor: '#f5276c'
                                            });
                                        }
                                    }
                                }
                            });
                        }
                    }
                });
                calendar.render();
            });
        }

        // Load calendar events
        function loadCalendarEvents(startDate, endDate, successCallback, failureCallback) {
            $.ajax({
                url: 'actions/get_photobooth_calendar_events.php',
                method: 'GET',
                data: {
                    start: startDate.toISOString().split('T')[0],
                    end: endDate.toISOString().split('T')[0]
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        successCallback(response.events);
                    } else {
                        failureCallback(new Error(response.message));
                    }
                },
                error: function() {
                    failureCallback(new Error('Failed to load calendar events'));
                }
            });
        }

        // Load booked dates and fully booked dates once on initialization
        function loadBookedDates(callback) {
            $.ajax({
                url: '../get_booked_slots.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Extract booked dates (keys from booked_slots object)
                        bookedDates = Object.keys(response.booked_slots || {});
                        // Get fully booked days array
                        fullyBookedDates = response.fully_booked_days || [];
                    }
                    // Always call callback whether successful or not
                    if (callback) callback();
                },
                error: function() {
                    // Still proceed with calendar initialization even if booking load fails
                    if (callback) callback();
                }
            });
        }

        // Initialize
        initializeCalendar();
    });
    </script>

</body>
</html>
