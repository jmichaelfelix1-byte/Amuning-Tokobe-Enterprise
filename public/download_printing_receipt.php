<?php
include 'includes/config.php';

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    die('Order ID is required');
}

$order_id = intval($_GET['order_id']);

// Fetch printing order details
$stmt = $conn->prepare("SELECT * FROM printing_orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Order not found');
}

$order = $result->fetch_assoc();
$stmt->close();

// Set timezone for accurate local time
$originalTimezone = date_default_timezone_get();
date_default_timezone_set('Asia/Manila');
$currentTime = date('F d, Y \a\t h:i A');
date_default_timezone_set($originalTimezone);

// Calculate receipt details
$statusLabels = [
    'pending' => 'Pending',
    'validated' => 'Validated',
    'processing' => 'Processing',
    'ready_to_pickup' => 'Ready to Pick-Up',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'declined' => 'Declined'
];

$currentStatus = $statusLabels[$order['status']] ?? 'Unknown';

// HTML Receipt
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt #<?php echo htmlspecialchars($order['id']); ?></title>
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
        
        .order-details {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .order-details h3 {
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
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge.processing {
            background: #f59e0b;
        }
        
        .status-badge.completed {
            background: #22c55e;
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
            <div class="company-name">🖨️ Amuning Tokobe Enterprise</div>
            <div class="receipt-title">Professional Printing Services</div>
            <div class="receipt-id">Order Receipt #<?php echo htmlspecialchars($order['id']); ?></div>
        </div>
        
        <div class="info-section">
            <div class="info-box">
                <h3>📅 Receipt Information</h3>
                <p><strong>Receipt #:</strong> <?php echo htmlspecialchars($order['id']); ?></p>
                <p><strong>Receipt Date:</strong> <?php echo $currentTime; ?></p>
                <p><strong>Order Status:</strong> <span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo htmlspecialchars($currentStatus); ?></span></p>
            </div>
            
            <div class="info-box">
                <h3>👤 Customer Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_number'] ?? 'N/A'); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')); ?></p>
            </div>
        </div>
        
        <div class="order-details">
            <h3>📋 Printing Order Details</h3>
            <div class="details-grid">
                <div class="detail-item">
                    <strong>Service Type</strong>
                    <span><?php echo htmlspecialchars($order['service'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Size</strong>
                    <span><?php echo htmlspecialchars($order['size'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Paper Type</strong>
                    <span><?php echo htmlspecialchars($order['paper_type'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Color Type</strong>
                    <span><?php echo htmlspecialchars($order['color_type'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Quantity</strong>
                    <span><?php echo htmlspecialchars($order['quantity'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Page Count</strong>
                    <span><?php echo htmlspecialchars($order['page_count'] ?? 'N/A'); ?></span>
                </div>
                <?php if (!empty($order['special_instruction'])): ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <strong>Special Instructions</strong>
                    <span><?php echo htmlspecialchars($order['special_instruction']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="invoice-section">
            <h3>💰 Order Summary</h3>
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width: 60%;">Description</th>
                        <th style="width: 40%; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($order['service'] ?? 'Printing Service'); ?> - <?php echo htmlspecialchars($order['size'] ?? ''); ?> (<?php echo htmlspecialchars($order['color_type'] ?? ''); ?>)</td>
                        <td style="text-align: right; font-weight: 600;">₱<?php echo number_format($order['price'], 2); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td style="text-align: right; font-weight: 700;">TOTAL AMOUNT PAID:</td>
                        <td style="text-align: right; font-weight: 700;">₱<?php echo number_format($order['price'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <div style="background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; text-align: center; margin-top: 15px;">
                <p style="color: #166534; font-weight: 600; margin: 0;">✓ Order Status: <?php echo htmlspecialchars($currentStatus); ?></p>
                <p style="color: #166534; font-size: 13px; margin: 5px 0 0;">All order details have been recorded and confirmed.</p>
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-contact">
                <p><strong>📞 Contact Us:</strong> +63 912 345 6789 | 📧 info@amuning.com</p>
                <p><strong>🖨️ Amuning Tokobe Enterprise</strong></p>
                <p>Professional Printing Services</p>
            </div>
            <p style="margin-top: 20px; color: #999;">This is an official printing order receipt. Please keep this for your records.</p>
            <p style="color: #999; font-size: 12px;">Generated on <?php echo $currentTime; ?></p>
        </div>
    </div>
</body>
</html>
