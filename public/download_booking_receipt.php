<?php
include 'includes/config.php';

// Check if booking ID is provided
if (!isset($_GET['booking_id'])) {
    die('Booking ID is required');
}

$booking_id = intval($_GET['booking_id']);

// Fetch booking details
$stmt = $conn->prepare("SELECT * FROM photo_bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Booking not found');
}

$booking = $result->fetch_assoc();
$stmt->close();

// Include necessary functions for invoice generation
require_once 'includes/email_templates.php';

// Generate invoice breakdown
$invoiceData = generatePhotoInvoiceBreakdown($booking);

// Set timezone for accurate local time
$originalTimezone = date_default_timezone_get();
date_default_timezone_set('Asia/Manila');
$currentTime = date('F d, Y \a\t h:i A');
date_default_timezone_set($originalTimezone);

// HTML Receipt
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt #<?php echo htmlspecialchars($booking['id']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 40px;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #22c55e;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: 700;
            color: #22c55e;
            margin-bottom: 5px;
        }
        
        .receipt-title {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .receipt-id {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-box {
            padding: 15px;
            background: #f8fafc;
            border-left: 4px solid #22c55e;
            border-radius: 4px;
        }
        
        .info-box h3 {
            font-size: 12px;
            font-weight: 700;
            color: #22c55e;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        
        .info-box p {
            font-size: 14px;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .info-box p strong {
            display: inline-block;
            width: 120px;
            font-weight: 600;
        }
        
        .booking-details {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .booking-details h3 {
            font-size: 16px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
            border-bottom: 2px solid #22c55e;
            padding-bottom: 10px;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            padding: 8px 0;
        }
        
        .detail-item strong {
            display: block;
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        
        .detail-item span {
            display: block;
            font-size: 15px;
            color: #2d3748;
            font-weight: 500;
        }
        
        .invoice-section {
            margin-bottom: 30px;
        }
        
        .invoice-section h3 {
            font-size: 16px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
            border-bottom: 2px solid #22c55e;
            padding-bottom: 10px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .invoice-table thead {
            background: #22c55e;
            color: white;
        }
        
        .invoice-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .invoice-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .invoice-table tbody tr:nth-child(even) {
            background: #fafbfc;
        }
        
        .invoice-table tbody tr:hover {
            background: #f0fdf4;
        }
        
        .total-row {
            background: #f0fdf4;
            font-weight: 700;
            border-top: 2px solid #22c55e;
        }
        
        .total-row td {
            padding: 20px 15px;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
            color: #666;
            font-size: 13px;
        }
        
        .footer p {
            margin-bottom: 5px;
        }
        
        .footer-contact {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .print-button {
            display: block;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .print-button button {
            background: #22c55e;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .print-button button:hover {
            background: #16a34a;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .info-section {
                grid-template-columns: 1fr;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .invoice-table th,
            .invoice-table td {
                padding: 8px;
                font-size: 12px;
            }
        }
        
        @media print {
            body {
                background: white;
            }
            
            .container {
                box-shadow: none;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="print-button">
            <button onclick="window.print()">🖨️ Print or Save as PDF</button>
        </div>
        
        <div class="header">
            <div class="company-name">📸 Amuning Tokobe Enterprise</div>
            <div class="receipt-title">Professional Photography Services</div>
            <div class="receipt-id">Booking Receipt #<?php echo htmlspecialchars($booking['id']); ?></div>
        </div>
        
        <div class="info-section">
            <div class="info-box">
                <h3>📅 Receipt Information</h3>
                <p><strong>Receipt #:</strong> <?php echo htmlspecialchars($booking['id']); ?></p>
                <p><strong>Receipt Date:</strong> <?php echo $currentTime; ?></p>
                <p><strong>Booking Status:</strong> <span class="status-badge">Booked</span></p>
            </div>
            
            <div class="info-box">
                <h3>👤 Customer Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['name'] ?? 'N/A'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['email'] ?? 'N/A'); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['mobile'] ?? 'N/A'); ?></p>
            </div>
        </div>
        
        <div class="booking-details">
            <h3>📸 Event & Booking Details</h3>
            <div class="details-grid">
                <div class="detail-item">
                    <strong>Event Type</strong>
                    <span><?php echo htmlspecialchars($booking['event_type'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Event Date</strong>
                    <span><?php echo isset($booking['event_date']) ? date('F d, Y', strtotime($booking['event_date'])) : 'N/A'; ?></span>
                </div>
                <div class="detail-item">
                    <strong>Time of Service</strong>
                    <span><?php echo isset($booking['time_of_service']) ? date('h:i A', strtotime($booking['time_of_service'])) : 'N/A'; ?></span>
                </div>
                <div class="detail-item">
                    <strong>Duration</strong>
                    <span><?php echo htmlspecialchars($booking['duration'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Product</strong>
                    <span><?php echo htmlspecialchars($booking['product'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Package Type</strong>
                    <span><?php echo htmlspecialchars($booking['package_type'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Venue</strong>
                    <span><?php echo htmlspecialchars($booking['venue'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Address</strong>
                    <span><?php echo htmlspecialchars(($booking['street_address'] ?? '') . ', ' . ($booking['city'] ?? '') . ', ' . ($booking['region'] ?? '')); ?></span>
                </div>
            </div>
        </div>
        
        <div class="invoice-section">
            <h3>💰 Detailed Invoice Breakdown</h3>
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width: 60%;">Service Description</th>
                        <th style="width: 20%; text-align: center;">Unit Price</th>
                        <th style="width: 20%; text-align: center;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoiceData['items'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td style="text-align: center;">₱<?php echo number_format($item['amount'], 2); ?></td>
                        <td style="text-align: center; font-weight: 600;">₱<?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right; font-weight: 700;">TOTAL AMOUNT PAID:</td>
                        <td style="text-align: center; font-weight: 700;"><?php echo $invoiceData['formatted_total']; ?></td>
                    </tr>
                </tbody>
            </table>
            
            <div style="background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; text-align: center; margin-top: 15px;">
                <p style="color: #166534; font-weight: 600; margin: 0;">✓ Payment Status: CONFIRMED</p>
                <p style="color: #166534; font-size: 13px; margin: 5px 0 0;">All payment details have been verified and confirmed.</p>
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-contact">
                <p><strong>📞 Contact Us:</strong> +63 912 345 6789 | 📧 info@amuning.com</p>
                <p><strong>🏢 Amuning Tokobe Enterprise</strong></p>
                <p>Professional Photography Services</p>
            </div>
            <p style="margin-top: 20px; color: #999;">This is an official booking receipt. Please keep this for your records.</p>
            <p style="color: #999; font-size: 12px;">Generated on <?php echo $currentTime; ?></p>
        </div>
    </div>
</body>
</html>
