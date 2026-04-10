<?php
/**
 * Email Templates and Utility Functions
 * Contains shared template functions and utility functions for email generation
 */

require_once 'email_config.php';

/**
 * Generate detailed invoice breakdown for photo bookings
 */
function generatePhotoInvoiceBreakdown($bookingDetails) {
    // Service data structure
    $serviceData = array(
        'Birthday' => array(
            'packages' => array(
                'basic' => array('price' => 5000.00),
                'standard' => array('price' => 7500.00),
                'premium' => array('price' => 10000.00),
                'deluxe' => array('price' => 15000.00)
            )
        ),
        'Wedding' => array(
            'packages' => array(
                'basic' => array('price' => 25000.00),
                'standard' => array('price' => 35000.00),
                'premium' => array('price' => 50000.00),
                'deluxe' => array('price' => 75000.00)
            )
        ),
        'Debut' => array(
            'packages' => array(
                'basic' => array('price' => 8000.00),
                'standard' => array('price' => 12000.00),
                'premium' => array('price' => 18000.00),
                'deluxe' => array('price' => 25000.00)
            )
        ),
        'Graduation' => array(
            'packages' => array(
                'basic' => array('price' => 3000.00),
                'standard' => array('price' => 4500.00),
                'premium' => array('price' => 6500.00),
                'deluxe' => array('price' => 9000.00)
            )
        ),
        'Corporate' => array(
            'packages' => array(
                'basic' => array('price' => 10000.00),
                'standard' => array('price' => 15000.00),
                'premium' => array('price' => 22000.00),
                'deluxe' => array('price' => 35000.00)
            )
        ),
        'Family' => array(
            'packages' => array(
                'basic' => array('price' => 4000.00),
                'standard' => array('price' => 6000.00),
                'premium' => array('price' => 9000.00),
                'deluxe' => array('price' => 12000.00)
            )
        ),
        'Prenup' => array(
            'packages' => array(
                'basic' => array('price' => 15000.00),
                'standard' => array('price' => 22000.00),
                'premium' => array('price' => 32000.00),
                'deluxe' => array('price' => 45000.00)
            )
        ),
        'Christening' => array(
            'packages' => array(
                'basic' => array('price' => 4500.00),
                'standard' => array('price' => 6500.00),
                'premium' => array('price' => 9500.00),
                'deluxe' => array('price' => 13000.00)
            )
        )
    );

    // Duration multipliers
    $durationMultipliers = array(
        '2-hours' => 1.0,
        '4-hours' => 1.8,
        '6-hours' => 2.5,
        '8-hours' => 3.2,
        '12-hours' => 4.5
    );

    // Product multipliers
    $productMultipliers = array(
        'classic' => 1.0,
        '360' => 1.5,
        'mirror' => 1.3,
        'roaming' => 1.2,
        'full' => 2.0
    );

    // Extract service name from event type
    $serviceName = str_replace(' Photography', '', $bookingDetails['event_type']);
    $packageValue = $bookingDetails['package_type'];
    $durationValue = $bookingDetails['duration'];
    $productValue = $bookingDetails['product'];

    $breakdown = array();

    // Check if service exists in data
    if (isset($serviceData[$serviceName])) {
        $baseServicePrice = $serviceData[$serviceName]['packages']['basic']['price'];
        $selectedPackagePrice = $serviceData[$serviceName]['packages'][$packageValue]['price'];

        // Calculate individual component prices
        $serviceTypePrice = $baseServicePrice;
        $packageTypePrice = $selectedPackagePrice - $baseServicePrice;

        // Duration cost
        $durationMult = isset($durationMultipliers[$durationValue]) ? $durationMultipliers[$durationValue] : 1.0;
        $baseDurationCost = $selectedPackagePrice * ($durationMult - 1.0);

        // Product cost
        $productMult = isset($productMultipliers[$productValue]) ? $productMultipliers[$productValue] : 1.0;
        $productCost = ($selectedPackagePrice * $durationMult) * ($productMult - 1.0);

        // Calculate total
        $total = $selectedPackagePrice * $durationMult * $productMult;

        // Build breakdown items
        $breakdown = array(
            array(
                'description' => 'Service Type: ' . $serviceName . ' Photography',
                'amount' => $serviceTypePrice
            ),
            array(
                'description' => 'Coverage Duration: ' . $durationValue,
                'amount' => $baseDurationCost
            ),
            array(
                'description' => 'Package Type: ' . $packageValue,
                'amount' => $packageTypePrice
            ),
            array(
                'description' => 'Product: ' . $productValue,
                'amount' => $productCost
            )
        );

        // Add travel fee if present
        $travelFee = 0;
        if (isset($bookingDetails['travel_fee'])) {
            $travelFee = (float) str_replace(['₱', ',', ' '], '', $bookingDetails['travel_fee']);
            if ($travelFee > 0) {
                $breakdown[] = array(
                    'description' => 'Travel Fee',
                    'amount' => $travelFee
                );
                $total += $travelFee;
            }
        }

        return array(
            'items' => $breakdown,
            'subtotal' => $total,
            'formatted_total' => '₱' . number_format($total, 2)
        );
    }

    // Fallback if service data not available
    $price = (float) str_replace(['₱', ','], '', $bookingDetails['estimated_price']);
    $travelFee = 0;
    if (isset($bookingDetails['travel_fee'])) {
        $travelFee = (float) str_replace(['₱', ',', ' '], '', $bookingDetails['travel_fee']);
    }
    
    $items = array(
        array(
            'description' => $bookingDetails['event_type'],
            'amount' => $price
        )
    );
    
    if ($travelFee > 0) {
        $items[] = array(
            'description' => 'Travel Fee',
            'amount' => $travelFee
        );
    }
    
    $total = $price + $travelFee;
    
    return array(
        'items' => $items,
        'subtotal' => $total,
        'formatted_total' => '₱' . number_format($total, 2)
    );
}

// =============================================================================
// EMAIL TEMPLATES
// =============================================================================

/**
 * Payment pending review template
 */
function getPhotoPaymentPendingTemplate($userName, $bookingDetails) {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Submitted - Pending Review</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(245, 158, 11, 0.15);
                border: 1px solid rgba(245, 158, 11, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #f5276c;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #f5276c;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #fed7e2;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .booking-details, .payment-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #f5276c;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .booking-details h3, .payment-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #f5276c;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .booking-details p, .payment-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #fef3c7;
                color: #92400e;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .pending-notice {
                background: #fef3c7;
                border: 1px solid #f59e0b;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .pending-notice h3 {
                margin: 0 0 10px;
                color: #92400e;
                font-size: 20px;
            }
            .pending-notice p {
                margin: 0;
                color: #92400e;
                font-size: 16px;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #F59E0B;
                font-weight: 600;
                background: #fed7aa;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            .divider {
                height: 1px;
                background: #e2e8f0;
                margin: 30px 0;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>⏳ Payment Submitted</h1>
                <p>Pending Review - Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="pending-notice">
                    <h3>📋 Payment Under Review</h3>
                    <p>Your payment has been submitted successfully and is currently pending review. We will verify your payment within 24 hours.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span> for your photography needs! We have received your payment submission and our team is currently reviewing it.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Our finance team will verify your payment details within 24 hours</li>
                        <li>You will receive a confirmation email once payment is approved</li>
                        <li>Your booking will be fully confirmed and secured</li>
                        <li>If we need any additional information, we will contact you directly</li>
                    </ul>

                    <p>Please ensure that your payment details are accurate and the proof of payment is clear. If there are any issues with your submission, we will contact you for clarification.</p>
                </div>

                <div class="booking-details">
                    <h3>📋 Booking Details</h3>
                    <p><strong>Booking ID:</strong> #' . htmlspecialchars($bookingDetails['id']) . '</p>
                    <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type']) . '</p>
                    <p><strong>Product:</strong> ' . htmlspecialchars($bookingDetails['product']) . '</p>
                    <p><strong>Package Type:</strong> ' . htmlspecialchars($bookingDetails['package_type']) . '</p>
                    <p><strong>Duration:</strong> ' . htmlspecialchars($bookingDetails['duration']) . '</p>
                    <p><strong>Event Date:</strong> ' . date('F d, Y', strtotime($bookingDetails['event_date'])) . '</p>
                    <p><strong>Time of Service:</strong> ' . date('h:i A', strtotime($bookingDetails['time_of_service'])) . '</p>
                    <p><strong>Venue:</strong> ' . htmlspecialchars($bookingDetails['venue']) . '</p>
                    <p><strong>Total Amount:</strong> ₱' . number_format($bookingDetails['estimated_price'], 2) . '</p>
                    <p><strong>Payment Status:</strong> <span class="status-badge">Pending Review</span></p>
                </div>

                <div class="payment-section">
                    <h3>💰 Payment Information</h3>
                    <p><strong>Payment Method:</strong> ' . htmlspecialchars($bookingDetails['payment_method'] ?? 'N/A') . '</p>
                    <p><strong>Reference Number:</strong> ' . htmlspecialchars($bookingDetails['transaction_number'] ?? 'N/A') . '</p>
                    <p><strong>Submission Date:</strong> ' . date('F d, Y \a\t h:i A') . '</p>
                    <p><strong>Review Timeline:</strong> Within 24 hours</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>📸 Amuning Tokobe Enterprise - Professional Photography Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Order confirmation template
 */
function getOrderConfirmationTemplate($userName, $orderDetails) {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Print Order Confirmation</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(245, 39, 108, 0.15);
                border: 1px solid rgba(245, 39, 108, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #F5276C;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header::before {
                content: \'\';
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: url(\'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>\');
                animation: float 20s infinite linear;
            }
            @keyframes float {
                0% { transform: translateX(-50px) translateY(-50px); }
                100% { transform: translateX(50px) translateY(50px); }
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #F5276C;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #fed7e2;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .order-details {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #F5276C;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .order-details h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #F5276C;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .order-details p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #dbeafe;
                color: #1e40af;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #F5276C;
                font-weight: 600;
                background: #fed7e2;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            .divider {
                height: 1px;
                background: #e2e8f0;
                margin: 30px 0;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>🎉 Order Confirmed!</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span> for your printing needs! We have successfully received your order for printing services and our team is excited to bring your documents, photos, and designs to life.</p>

                    <p>Our team will carefully process your printing order, ensuring the highest quality output for your documents, photos, ID cards, stickers, invitations, flyers, and other printed materials. We use premium quality papers and inks to deliver exceptional results.</p>

                    <p>Please note that processing times may vary depending on the complexity of your printing job, paper type, and current workload. The printing process typically only takesa few minutes up to half an hour depending on our workload.</p>

                    <p>You will receive updates on your order status as we progress through the printing stages. If you have any questions about your printing specifications or need to make changes to your order, please do not hesitate to contact us.</p>

                    <p>Thank you for trusting us with your printing project - we look forward to delivering beautiful, professional results!</p>
                </div>

                <div class="order-details">
                    <h3>📋 Order Summary</h3>
                    <p><strong>Service:</strong> ' . htmlspecialchars($orderDetails['service']) . '</p>
                    <p><strong>Size:</strong> ' . htmlspecialchars($orderDetails['size']) . '</p>
                    <p><strong>Paper Type:</strong> ' . htmlspecialchars($orderDetails['paper_type']) . '</p>
                    <p><strong>Quantity:</strong> ' . htmlspecialchars($orderDetails['quantity']) . '</p>
                    <p><strong>Total Price:</strong> ₱' . number_format($orderDetails['price'], 2) . '</p>
                    <p><strong>Order Date:</strong> ' . date('F d, Y \a\t h:i A') . '</p>
                    <p><strong>Status:</strong> <span class="status-badge">' . htmlspecialchars($orderDetails['status']) . '</span></p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🏢 Amuning Tokobe Enterprise - Quality Printing Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Order payment pending review template for printing orders
 */
function getOrderPaymentPendingTemplate($userName, $orderDetails) {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Submitted - Printing Order Pending Review</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(245, 158, 11, 0.15);
                border: 1px solid rgba(245, 158, 11, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #f5276c;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #f5276c;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #fed7e2;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .order-details, .payment-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #f5276c;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .order-details h3, .payment-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #f5276c;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .order-details p, .payment-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #fef3c7;
                color: #92400e;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .pending-notice {
                background: #fef3c7;
                border: 1px solid #f59e0b;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .pending-notice h3 {
                margin: 0 0 10px;
                color: #92400e;
                font-size: 20px;
            }
            .pending-notice p {
                margin: 0;
                color: #92400e;
                font-size: 16px;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #F59E0B;
                font-weight: 600;
                background: #fed7aa;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            .divider {
                height: 1px;
                background: #e2e8f0;
                margin: 30px 0;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>⏳ Payment Submitted</h1>
                <p>Printing Order Pending Review - Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="pending-notice">
                    <h3>📋 Payment Under Review</h3>
                    <p>Your payment for printing order has been submitted and is currently pending review. We will verify your payment within 24 hours.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span> for your printing needs! We have received your payment submission and our team is currently reviewing it.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Our finance team will verify your payment details within 24 hours</li>
                        <li>You will receive a confirmation email once payment is approved</li>
                        <li>Your printing order will be processed and completed</li>
                        <li>If we need any additional information, we will contact you directly</li>
                    </ul>

                    <p>Please ensure that your payment details are accurate and the proof of payment is clear. If there are any issues with your submission, we will contact you for clarification.</p>
                </div>

                <div class="order-details">
                    <h3>📋 Order Details</h3>
                    <p><strong>Order ID:</strong> #' . htmlspecialchars($orderDetails['id']) . '</p>
                    <p><strong>Service:</strong> ' . htmlspecialchars($orderDetails['service']) . '</p>
                    <p><strong>Size:</strong> ' . htmlspecialchars($orderDetails['size']) . '</p>
                    <p><strong>Paper Type:</strong> ' . htmlspecialchars($orderDetails['paper_type']) . '</p>
                    <p><strong>Quantity:</strong> ' . htmlspecialchars($orderDetails['quantity']) . '</p>
                    <p><strong>Total Amount:</strong> ₱' . number_format($orderDetails['price'], 2) . '</p>
                    <p><strong>Order Date:</strong> ' . date('F d, Y', strtotime($orderDetails['order_date'])) . '</p>
                    <p><strong>Payment Status:</strong> <span class="status-badge">Pending Review</span></p>
                </div>

                <div class="payment-section">
                    <h3>💰 Payment Information</h3>
                    <p><strong>Payment Method:</strong> ' . htmlspecialchars($orderDetails['payment_method'] ?? 'N/A') . '</p>
                    <p><strong>Reference Number:</strong> ' . htmlspecialchars($orderDetails['transaction_number'] ?? 'N/A') . '</p>
                    <p><strong>Submission Date:</strong> ' . date('F d, Y \a\t h:i A') . '</p>
                    <p><strong>Review Timeline:</strong> Within 24 hours</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🖨️ Amuning Tokobe Enterprise - Quality Printing Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Photo booking received template - Initial booking submission
 */
function getPhotoBookingReceivedTemplate($userName, $bookingDetails) {
    $paymentUrl = "http://localhost/Amuning/public/payment.php?booking_id=" . $bookingDetails['booking_id'];

    // Generate detailed invoice breakdown
    $invoiceData = generatePhotoInvoiceBreakdown($bookingDetails);

    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Photo Booking Confirmation</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(245, 39, 108, 0.15);
                border: 1px solid rgba(245, 39, 108, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #F5276C;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #F5276C;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #fed7e2;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .booking-details, .invoice-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #F5276C;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .booking-details h3, .invoice-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #F5276C;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .booking-details p, .invoice-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #fef5e7;
                color: #92400e;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .pay-button {
                display: inline-block;
                background: #F5276C;
                color: white !important;
                padding: 16px 32px;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 600;
                font-size: 16px;
                margin: 25px 0;
                text-align: center;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(245, 39, 108, 0.3);
                border: 2px solid transparent;
            }
            .pay-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(245, 39, 108, 0.4);
                background: #e11d48;
                color: white !important;
            }
            .invoice-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                margin: 20px 0;
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                border: 1px solid #e2e8f0;
            }
            .invoice-table th {
                background: #F5276C;
                color: white;
                font-weight: 600;
                font-size: 14px;
                padding: 18px 20px;
                text-align: left;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .invoice-table td {
                padding: 16px 20px;
                border-bottom: 1px solid #f1f5f9;
                color: #2d3748;
            }
            .invoice-table tr:nth-child(even) {
                background: #fafbfc;
            }
            .invoice-table tr:hover {
                background: #f8fafc;
                transition: background 0.2s ease;
            }
            .total-row {
                background: #F5276C;
                color: white;
                font-weight: 700;
                border-radius: 0 0 12px 12px;
            }
            .total-row td {
                padding: 20px;
                font-size: 18px;
                border: none;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #F5276C;
                font-weight: 600;
                background: #fed7e2;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            .invoice-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 2px solid #F5276C;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .invoice-info p {
                margin: 0;
                font-size: 14px;
                color: #4a5568;
                font-weight: 500;
            }
            .payment-notice {
                background: #fef3c7;
                border: 1px solid #f59e0b;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
            }
            .payment-notice p {
                margin: 0;
                color: #92400e;
                font-weight: 600;
                font-size: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            .divider {
                height: 1px;
                background: #e2e8f0;
                margin: 30px 0;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
                .invoice-table {
                    font-size: 14px;
                }
                .invoice-table th,
                .invoice-table td {
                    padding: 12px 10px;
                }
                .invoice-header {
                    flex-direction: column;
                    gap: 15px;
                    text-align: center;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>📸 Photo Booking Received!</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span> for your photography needs! We have successfully received your photo booking and our photographers are excited to capture your special moments.</p>

                    <p>Your booking is currently <span class="status-badge">pending</span> and will be confirmed once payment is received. Please complete your payment to secure your booking date and time.</p>

                    <p>Our photographers will arrive at your venue on time and capture your event with high quality equipment and fun props to use. We specialize in social gatherings, birthdays, and special occasions.</p>

                    <p>You will receive updates on your booking status as we prepare for your event. If you have any questions about your booking or need to make changes, please do not hesitate to contact us.</p>
                </div>

                <div class="booking-details">
                    <h3>📋 Booking Details</h3>
                    <p><strong>Booking ID:</strong> #' . htmlspecialchars($bookingDetails['booking_id']) . '</p>
                    <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type']) . '</p>
                    <p><strong>Product:</strong> ' . htmlspecialchars($bookingDetails['product']) . '</p>
                    <p><strong>Package Type:</strong> ' . htmlspecialchars($bookingDetails['package_type']) . '</p>
                    <p><strong>Duration:</strong> ' . htmlspecialchars($bookingDetails['duration']) . '</p>
                    <p><strong>Event Date:</strong> ' . date('F d, Y', strtotime($bookingDetails['event_date'])) . '</p>
                    <p><strong>Time of Service:</strong> ' . date('h:i A', strtotime($bookingDetails['time_of_service'])) . '</p>
                    <p><strong>Venue:</strong> ' . htmlspecialchars($bookingDetails['venue']) . '</p>
                    <p><strong>Address:</strong> ' . htmlspecialchars($bookingDetails['street_address'] . ', ' . $bookingDetails['city'] . ', ' . $bookingDetails['region'] . ', ' . $bookingDetails['country'] . ' ' . $bookingDetails['postal_code']) . '</p>
                    <p><strong>Booking Date:</strong> ' . date('F d, Y \a\t h:i A') . '</p>
                    <p><strong>Status:</strong> <span class="status-badge">Pending Payment</span></p>
                </div>

                <div class="invoice-section">
                    <h3>💰 Detailed Invoice Breakdown</h3>

                    <div class="invoice-header">
                        <div class="invoice-info">
                            <p><strong>Invoice Date:</strong> ' . date('F d, Y \a\t h:i A') . '</p>
                            <p><strong>Payment Due:</strong> 1 day before event</p>
                        </div>
                    </div>

                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th style="width: 60%;">Description</th>
                                <th style="width: 20%; text-align: center;">Unit Price</th>
                                <th style="width: 20%; text-align: center;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>'
                            . implode('', array_map(function($item) {
                                return '
                            <tr>
                                <td>' . htmlspecialchars($item['description']) . '</td>
                                <td style="text-align: center;">₱' . number_format($item['amount'], 2) . '</td>
                                <td style="text-align: center; font-weight: 600;">₱' . number_format($item['amount'], 2) . '</td>
                            </tr>';
                            }, $invoiceData['items']))
                            . '
                            <tr class="total-row">
                                <td colspan="2" style="text-align: right; font-weight: 700; font-size: 16px;">TOTAL AMOUNT DUE</td>
                                <td style="text-align: center; font-weight: 700; font-size: 16px;">' . $invoiceData['formatted_total'] . '</td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 15px 0;">
                        <p style="margin: 0; color: #856404; font-weight: 500; font-size: 14px;">
                            ⚠️ <strong>Important:</strong> Please complete your payment within 24 hours to secure your booking date and time.
                        </p>
                    </div>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . htmlspecialchars($paymentUrl) . '" class="pay-button">
                        💳 Complete Payment Now
                    </a>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>📸 Amuning Tokobe Enterprise - Professional Photography Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Payment processing notification template
 */
function getPaymentProcessingTemplate($userName, $paymentDetails) {
    // Set Manila timezone for date display
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    $processingTime = date('F d, Y \a\t h:i A');
    date_default_timezone_set($originalTimezone); // Restore original timezone

    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Now Processing</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(34, 197, 94, 0.15);
                border: 1px solid rgba(34, 197, 94, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #22c55e;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #22c55e;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #dcfce7;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .processing-details, .payment-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #22c55e;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .processing-details h3, .payment-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #22c55e;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .processing-details p, .payment-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #dbeafe;
                color: #1e40af;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .processing-notice {
                background: #dcfce7;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .processing-notice h3 {
                margin: 0 0 10px;
                color: #166534;
                font-size: 20px;
            }
            .processing-notice p {
                margin: 0;
                color: #166534;
                font-size: 16px;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #22c55e;
                font-weight: 600;
                background: #dcfce7;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            .divider {
                height: 1px;
                background: #e2e8f0;
                margin: 30px 0;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>⚙️ Payment Now Processing</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="processing-notice">
                    <h3>🔄 Payment Processing Started</h3>
                    <p>Great news! Your payment has been approved and is now being processed.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span>! We have reviewed your payment and are excited to confirm that it has been approved. Our team has started processing your order.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Your order is now in the processing queue</li>
                        <li>Our team will begin preparing your ' . htmlspecialchars($paymentDetails['service_type']) . ' service</li>
                        <li>You will receive updates as we progress through the preparation stages</li>
                        <li>Final confirmation will be sent once your order is ready</li>
                    </ul>

                    <p>If you have any questions about your order or need to make changes, please contact us immediately. We\'re here to ensure everything goes smoothly!</p>
                </div>

                <div class="processing-details">
                    <h3>📋 Order Details</h3>
                    <p><strong>Payment ID:</strong> #' . htmlspecialchars($paymentDetails['id']) . '</p>
                    <p><strong>Service Type:</strong> ' . htmlspecialchars($paymentDetails['service_type']) . '</p>
                    <p><strong>Reference:</strong> ' . htmlspecialchars($paymentDetails['reference']) . '</p>
                    <p><strong>Amount Paid:</strong> ₱' . number_format($paymentDetails['amount'], 2) . '</p>
                    <p><strong>Payment Status:</strong> <span class="status-badge">Processing</span></p>
                </div>

                <div class="payment-section">
                    <h3>💰 Payment Information</h3>
                    <p><strong>Payment Method:</strong> ' . htmlspecialchars($paymentDetails['payment_method'] ?? 'N/A') . '</p>
                    <p><strong>Reference Number:</strong> ' . htmlspecialchars($paymentDetails['transaction_number'] ?? 'N/A') . '</p>
                    <p><strong>Processing Started:</strong> ' . $processingTime . '</p>
                    <p><strong>Estimated Timeline:</strong> 1-3 business days</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🏢 Amuning Tokobe Enterprise - Quality Service Processing</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Payment submitted notification template
 */
function getPaymentSubmittedTemplate($userName, $orderDetails) {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Submitted - Printing Order Pending Review</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(245, 39, 108, 0.15);
                border: 1px solid rgba(245, 39, 108, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #F59E0B;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #F59E0B;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #fed7aa;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .order-details, .payment-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #F59E0B;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .order-details h3, .payment-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #F59E0B;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .order-details p, .payment-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #fef3c7;
                color: #92400e;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .pending-notice {
                background: #fef3c7;
                border: 1px solid #f59e0b;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .pending-notice h3 {
                margin: 0 0 10px;
                color: #92400e;
                font-size: 20px;
            }
            .pending-notice p {
                margin: 0;
                color: #92400e;
                font-size: 16px;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #F59E0B;
                font-weight: 600;
                background: #fed7aa;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            .divider {
                height: 1px;
                background: #e2e8f0;
                margin: 30px 0;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>🖨️ Payment Submitted</h1>
                <p>Printing Order Pending Review - Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="pending-notice">
                    <h3>📋 Order Under Review</h3>
                    <p>Your payment has been submitted and is currently pending review. We will verify your payment within 24 hours.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span> for your printing needs! We have received your payment submission and our team is currently reviewing it.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Our finance team will verify your payment details within 24 hours</li>
                        <li>You will receive a confirmation email once payment is approved</li>
                        <li>Your printing order will be processed and completed</li>
                        <li>If we need any additional information, we will contact you directly</li>
                    </ul>

                    <p>Please ensure that your payment details are accurate and the proof of payment is clear. If there are any issues with your submission, we will contact you for clarification.</p>
                </div>

                <div class="order-details">
                    <h3>🖨️ Order Details</h3>
                    <p><strong>Order ID:</strong> #' . htmlspecialchars($orderDetails['id']) . '</p>
                    <p><strong>Service:</strong> ' . htmlspecialchars($orderDetails['service']) . '</p>
                    <p><strong>Size:</strong> ' . htmlspecialchars($orderDetails['size']) . '</p>
                    <p><strong>Paper Type:</strong> ' . htmlspecialchars($orderDetails['paper_type']) . '</p>
                    <p><strong>Quantity:</strong> ' . htmlspecialchars($orderDetails['quantity']) . '</p>
                    <p><strong>Total Amount:</strong> ₱' . number_format($orderDetails['price'] ?? $orderDetails['estimated_price'], 2) . '</p>
                    <p><strong>Order Date:</strong> ' . date('F d, Y', strtotime($orderDetails['order_date'] ?? $orderDetails['created_at'])) . '</p>
                    <p><strong>Order Status:</strong> <span class="status-badge">Pending Review</span></p>
                </div>

                <div class="payment-section">
                    <h3>💰 Payment Information</h3>
                    <p><strong>Payment Method:</strong> ' . htmlspecialchars($orderDetails['payment_method'] ?? 'N/A') . '</p>
                    <p><strong>Reference Number:</strong> ' . htmlspecialchars($orderDetails['transaction_number'] ?? 'N/A') . '</p>
                    <p><strong>Submission Date:</strong> ' . date('F d, Y \a\t h:i A') . '</p>
                    <p><strong>Review Timeline:</strong> Within 24 hours</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🖨️ Amuning Tokobe Enterprise - Professional Printing Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Payment approval notification template
 */
function getPaymentApprovalTemplate($userName, $paymentDetails) {
    // Set Manila timezone for date display
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    $approvalTime = date('F d, Y \a\t h:i A');
    date_default_timezone_set($originalTimezone); // Restore original timezone

    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Approved - Ready for Processing</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(34, 197, 94, 0.15);
                border: 1px solid rgba(34, 197, 94, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #22c55e;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #22c55e;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #dcfce7;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .approval-details, .payment-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #22c55e;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .approval-details h3, .payment-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #22c55e;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .approval-details p, .payment-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #dcfce7;
                color: #166534;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .approval-notice {
                background: #dcfce7;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .approval-notice h3 {
                margin: 0 0 10px;
                color: #166534;
                font-size: 20px;
            }
            .approval-notice p {
                margin: 0;
                color: #166534;
                font-size: 16px;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #22c55e;
                font-weight: 600;
                background: #dcfce7;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>✅ Payment Approved</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="approval-notice">
                    <h3>🎉 Payment Successfully Approved!</h3>
                    <p>Great news! Your payment has been reviewed and approved.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span>! We are pleased to inform you that your payment has been carefully reviewed and <strong>approved</strong>.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Your payment is now ready for processing</li>
                        <li>Our team will begin preparing your ' . htmlspecialchars($paymentDetails['service_type']) . ' service</li>
                        <li>You will receive a processing confirmation email shortly</li>
                        <li>Please expect updates on your order progress</li>
                    </ul>

                    <p>If you have any questions about your approved payment or need to make changes, please contact us immediately. We\'re here to ensure your experience is perfect!</p>
                </div>

                <div class="approval-details">
                    <h3>📋 Payment Details</h3>
                    <p><strong>Payment ID:</strong> #' . htmlspecialchars($paymentDetails['id']) . '</p>
                    <p><strong>Service Type:</strong> ' . htmlspecialchars($paymentDetails['service_type']) . '</p>
                    <p><strong>Reference:</strong> ' . htmlspecialchars($paymentDetails['reference']) . '</p>
                    <p><strong>Amount Paid:</strong> ₱' . number_format($paymentDetails['amount'], 2) . '</p>
                    <p><strong>Payment Status:</strong> <span class="status-badge">Approved</span></p>
                </div>

                <div class="payment-section">
                    <h3>💰 Payment Information</h3>
                    <p><strong>Payment Method:</strong> ' . htmlspecialchars($paymentDetails['payment_method'] ?? 'N/A') . '</p>
                    <p><strong>Reference Number:</strong> ' . htmlspecialchars($paymentDetails['transaction_number'] ?? 'N/A') . '</p>
                    <p><strong>Approval Date:</strong> ' . $approvalTime . '</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🏢 Amuning Tokobe Enterprise - Quality Service Provider</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Payment rejection notification template
 */
function getPaymentRejectionTemplate($userName, $paymentDetails, $reason = '') {
    // Set Manila timezone for date display
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    $rejectionTime = date('F d, Y \a\t h:i A');
    date_default_timezone_set($originalTimezone); // Restore original timezone

    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Review Required</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(239, 68, 68, 0.15);
                border: 1px solid rgba(239, 68, 68, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #ef4444;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #ef4444;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #fecaca;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .rejection-details, .payment-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #ef4444;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .rejection-details h3, .payment-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #ef4444;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .rejection-details p, .payment-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #fef2f2;
                color: #dc2626;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .rejection-notice {
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .rejection-notice h3 {
                margin: 0 0 10px;
                color: #dc2626;
                font-size: 20px;
            }
            .rejection-notice p {
                margin: 0;
                color: #dc2626;
                font-size: 16px;
                font-weight: 500;
            }
            .reason-box {
                background: #fff5f5;
                border: 1px solid #fed7d7;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }
            .reason-box p {
                margin: 0;
                color: #c53030;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #ef4444;
                font-weight: 600;
                background: #fef2f2;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>⚠️ Payment Review Required</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="rejection-notice">
                    <h3>📋 Payment Requires Review</h3>
                    <p>Your payment submission needs additional information or correction.</p>
                </div>

                <div class="message">
                    <p>We have reviewed your payment submission for <span class="highlight">Amuning Tokobe Enterprise</span>, but we need additional information or corrections before we can proceed.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Please check the reason below for required corrections</li>
                        <li>Make the necessary updates to your payment information</li>
                        <li>Resubmit your payment with the corrected details</li>
                        <li>Our team will review it again within 24 hours</li>
                    </ul>

                    <p>We apologize for any inconvenience this may cause. Please don\'t hesitate to contact us if you need assistance with the corrections or have questions about the requirements.</p>
                </div>' .
                ($reason ? '
                <div class="reason-box">
                    <p><strong>Reason for Review:</strong> ' . htmlspecialchars($reason) . '</p>
                </div>' : '') . '

                <div class="rejection-details">
                    <h3>📋 Payment Details</h3>
                    <p><strong>Payment ID:</strong> #' . htmlspecialchars($paymentDetails['id']) . '</p>
                    <p><strong>Service Type:</strong> ' . htmlspecialchars($paymentDetails['service_type']) . '</p>
                    <p><strong>Reference:</strong> ' . htmlspecialchars($paymentDetails['reference']) . '</p>
                    <p><strong>Amount Paid:</strong> ₱' . number_format($paymentDetails['amount'], 2) . '</p>
                    <p><strong>Payment Status:</strong> <span class="status-badge">Rejected</span></p>
                </div>

                <div class="payment-section">
                    <h3>💰 Payment Information</h3>
                    <p><strong>Payment Method:</strong> ' . htmlspecialchars($paymentDetails['payment_method'] ?? 'N/A') . '</p>
                    <p><strong>Reference Number:</strong> ' . htmlspecialchars($paymentDetails['transaction_number'] ?? 'N/A') . '</p>
                    <p><strong>Review Date:</strong> ' . $rejectionTime . '</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🏢 Amuning Tokobe Enterprise - Quality Service Provider</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Order processing notification template
 */
function getOrderProcessingTemplate($userName, $orderDetails) {
    // Set Manila timezone for accurate local time
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    
    $processingTime = date('F d, Y \a\t h:i A');
    
    // Restore original timezone
    date_default_timezone_set($originalTimezone);
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Now Processing - Amuning Tokobe Enterprise</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(59, 130, 246, 0.15);
                border: 1px solid rgba(59, 130, 246, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #3b82f6;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #3b82f6;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #dbeafe;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .processing-details, .order-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #3b82f6;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .processing-details h3, .order-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #3b82f6;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .processing-details p, .order-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #dbeafe;
                color: #1e40af;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .processing-notice {
                background: #dbeafe;
                border: 1px solid #93c5fd;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .processing-notice h3 {
                margin: 0 0 10px;
                color: #1e40af;
                font-size: 20px;
            }
            .processing-notice p {
                margin: 0;
                color: #1e40af;
                font-size: 16px;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #3b82f6;
                font-weight: 600;
                background: #dbeafe;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>⚙️ Order Now Processing</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="processing-notice">
                    <h3>🔄 Your Order is Now Being Processed!</h3>
                    <p>Great news! Your order has been approved and is now in processing.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span>! Your order has been approved and our team has started processing it.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Our skilled technicians are now working on your printing project</li>
                        <li>We will carefully prepare your documents using premium quality materials</li>
                        <li>You will receive updates as we progress through the printing stages</li>
                        <li>Final completion notification will be sent once your order is ready</li>
                    </ul>

                    <p>Please note that processing times may vary depending on the complexity of your printing job, paper type, and current workload. Standard document printing typically takes 1-2 hours, while custom photo printing and specialty items may take 2-3 hours.</p>

                    <p>If you have any questions about your order specifications or need to make changes, please contact us immediately.</p>
                </div>

                <div class="processing-details">
                    <h3>📋 Order Details</h3>
                    <p><strong>Order ID:</strong> #' . htmlspecialchars($orderDetails['id']) . '</p>
                    <p><strong>Service:</strong> ' . htmlspecialchars($orderDetails['service']) . '</p>
                    <p><strong>Size:</strong> ' . htmlspecialchars($orderDetails['size']) . '</p>
                    <p><strong>Paper Type:</strong> ' . htmlspecialchars($orderDetails['paper_type']) . '</p>
                    <p><strong>Quantity:</strong> ' . htmlspecialchars($orderDetails['quantity']) . '</p>
                    <p><strong>Total Price:</strong> ₱' . number_format($orderDetails['price'], 2) . '</p>
                    <p><strong>Order Status:</strong> <span class="status-badge">Processing</span></p>
                </div>

                <div class="order-section">
                    <h3>🖨️ Processing Information</h3>
                    <p><strong>Processing Started:</strong> ' . $processingTime . '</p>
                    <p><strong>Estimated Timeline:</strong> 1-3 hours depending on complexity</p>
                    <p><strong>Quality Assurance:</strong> Each print undergoes quality check</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🖨️ Amuning Tokobe Enterprise - Professional Printing Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Booking processing notification template
 */
function getBookingProcessingTemplate($userName, $bookingDetails) {
    // Set Manila timezone for accurate local time
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    
    $processingTime = date('F d, Y \a\t h:i A');
    
    // Restore original timezone
    date_default_timezone_set($originalTimezone);
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Now Processing - Amuning Tokobe Enterprise</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(59, 130, 246, 0.15);
                border: 1px solid rgba(59, 130, 246, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #3b82f6;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #3b82f6;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #dbeafe;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .processing-details, .booking-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #3b82f6;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .processing-details h3, .booking-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #3b82f6;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .processing-details p, .booking-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #dbeafe;
                color: #1e40af;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .processing-notice {
                background: #dbeafe;
                border: 1px solid #93c5fd;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .processing-notice h3 {
                margin: 0 0 10px;
                color: #1e40af;
                font-size: 20px;
            }
            .processing-notice p {
                margin: 0;
                color: #1e40af;
                font-size: 16px;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #3b82f6;
                font-weight: 600;
                background: #dbeafe;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>⚙️ Booking Now Processing</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="processing-notice">
                    <h3>🔄 Your Booking is Now Being Processed!</h3>
                    <p>Great news! Your booking has been approved and is now in processing.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span>! Your photography booking has been approved and our team has started processing it.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Our photography team is now preparing for your event</li>
                        <li>We will review and confirm all event details and requirements</li>
                        <li>Equipment and team assignments are being finalized</li>
                        <li>You will receive updates as we prepare for your special day</li>
                    </ul>

                    <p>Our skilled photographers will arrive at your venue on time and capture your event with the highest quality equipment and techniques. We specialize in weddings, corporate events, portraits, family gatherings, and special occasions.</p>

                    <p>If you have any questions about your booking or need to make changes, please contact us immediately.</p>
                </div>

                <div class="processing-details">
                    <h3>📋 Booking Details</h3>
                    <p><strong>Booking ID:</strong> #' . htmlspecialchars($bookingDetails['id']) . '</p>
                    <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type']) . '</p>
                    <p><strong>Product:</strong> ' . htmlspecialchars($bookingDetails['product']) . '</p>
                    <p><strong>Package Type:</strong> ' . htmlspecialchars($bookingDetails['package_type']) . '</p>
                    <p><strong>Duration:</strong> ' . htmlspecialchars($bookingDetails['duration']) . '</p>
                    <p><strong>Event Date:</strong> ' . date('F d, Y', strtotime($bookingDetails['event_date'])) . '</p>
                    <p><strong>Time of Service:</strong> ' . date('h:i A', strtotime($bookingDetails['time_of_service'])) . '</p>
                    <p><strong>Venue:</strong> ' . htmlspecialchars($bookingDetails['venue']) . '</p>
                    <p><strong>Booking Status:</strong> <span class="status-badge">Processing</span></p>
                </div>

                <div class="booking-section">
                    <h3>📸 Processing Information</h3>
                    <p><strong>Processing Started:</strong> ' . $processingTime . '</p>
                    <p><strong>Event Timeline:</strong> ' . date('F d, Y', strtotime($bookingDetails['event_date'])) . '</p>
                    <p><strong>Preparation Status:</strong> Equipment and team being assigned</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>📸 Amuning Tokobe Enterprise - Professional Photography Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Order ready for pickup template
 */
function getOrderReadyPickupTemplate($userName, $orderDetails) {
    // Set Manila timezone for accurate local time
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    
    $completionTime = date('F d, Y \a\t h:i A');
    
    // Restore original timezone
    date_default_timezone_set($originalTimezone);
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Ready for Pickup - Amuning Tokobe Enterprise</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(34, 197, 94, 0.15);
                border: 1px solid rgba(34, 197, 94, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #22c55e;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #22c55e;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #dcfce7;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .ready-notice, .pickup-details, .order-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #22c55e;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .ready-notice, .pickup-details h3, .order-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #22c55e;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .ready-notice, .pickup-details p, .order-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #dcfce7;
                color: #166534;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .success-notice {
                background: #dcfce7;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .success-notice h3 {
                margin: 0 0 10px;
                color: #166534;
                font-size: 20px;
            }
            .success-notice p {
                margin: 0;
                color: #166534;
                font-size: 16px;
                font-weight: 500;
            }
            .pickup-info {
                background: #fef3c7;
                border: 1px solid #fde68a;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
            }
            .pickup-info h4 {
                margin: 0 0 10px;
                color: #92400e;
                font-size: 18px;
            }
            .pickup-info p {
                margin: 5px 0;
                color: #92400e;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #22c55e;
                font-weight: 600;
                background: #dcfce7;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>🎉 Order Ready for Pickup!</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="success-notice">
                    <h3>✅ Your Order is Ready!</h3>
                    <p>Great news! Your printing order has been completed and is ready for pickup.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span>! Your printing order has been successfully completed and is now ready for you to pick up.</p>

                    <p><strong>What to expect:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Your order has undergone our quality assurance process</li>
                        <li>All prints have been carefully inspected for perfection</li>
                        <li>Items are packaged securely for safe transport</li>
                        <li>Ready for pickup at your earliest convenience</li>
                    </ul>

                    <p>Please bring a valid ID and this email confirmation when picking up your order. If someone else will be picking up on your behalf, please inform us in advance.</p>
                </div>

                <div class="pickup-details">
                    <h3>📍 Pickup Information</h3>
                    <div class="pickup-info">
                        <h4>📋 Required for Pickup:</h4>
                        <p>• This email confirmation (printed or digital)</p>
                        <p>• Valid government-issued ID</p>
                        <p>• Payment receipt (if applicable)</p>
                    </div>
                    <p><strong>Business Hours:</strong> Monday - Saturday, 8:00 AM - 6:00 PM</p>
                    <p><strong>Location:</strong> Amuning Tokobe Enterprise</p>
                    <p><strong>Contact:</strong> +63 912 345 6789</p>
                    <p><strong>Email:</strong> info@amuning.com</p>
                </div>

                <div class="order-section">
                    <h3>📋 Order Details</h3>
                    <p><strong>Order ID:</strong> #' . htmlspecialchars($orderDetails['id']) . '</p>
                    <p><strong>Service:</strong> ' . htmlspecialchars($orderDetails['service']) . '</p>
                    <p><strong>Size:</strong> ' . htmlspecialchars($orderDetails['size']) . '</p>
                    <p><strong>Paper Type:</strong> ' . htmlspecialchars($orderDetails['paper_type']) . '</p>
                    <p><strong>Quantity:</strong> ' . htmlspecialchars($orderDetails['quantity']) . '</p>
                    <p><strong>Total Price:</strong> ₱' . number_format($orderDetails['price'], 2) . '</p>
                    <p><strong>Order Status:</strong> <span class="status-badge">Ready for Pickup</span></p>
                    <p><strong>Completed Date:</strong> ' . $completionTime . '</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🏢 Amuning Tokobe Enterprise - Quality Printing Services</p>
                <p>Thank you for your business! We look forward to serving you again.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Booking approval template
 */
function getBookingApprovalTemplate($userName, $bookingDetails) {
    // Set Manila timezone for accurate local time
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    
    $approvalTime = date('F d, Y \a\t h:i A');
    
    // Restore original timezone
    date_default_timezone_set($originalTimezone);
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Approved - Your Event Photography is Confirmed!</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(34, 197, 94, 0.15);
                border: 1px solid rgba(34, 197, 94, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #22c55e;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #22c55e;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #dcfce7;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .approval-notice, .event-details, .photography-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #22c55e;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .approval-notice h3, .event-details h3, .photography-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #22c55e;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .approval-notice p, .event-details p, .photography-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #dcfce7;
                color: #166534;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .success-notice {
                background: #dcfce7;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .success-notice h3 {
                margin: 0 0 10px;
                color: #166534;
                font-size: 20px;
            }
            .success-notice p {
                margin: 0;
                color: #166534;
                font-size: 16px;
                font-weight: 500;
            }
            .event-info {
                background: #fef3c7;
                border: 1px solid #fde68a;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
            }
            .event-info h4 {
                margin: 0 0 10px;
                color: #92400e;
                font-size: 18px;
            }
            .event-info p {
                margin: 5px 0;
                color: #92400e;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #22c55e;
                font-weight: 600;
                background: #dcfce7;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>🎉 Booking Approved!</h1>
                <p>Your Event Photography is Confirmed</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="success-notice">
                    <h3>✅ Your Booking is Approved!</h3>
                    <p>Great news! Your photography booking has been approved and confirmed.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span> for your photography needs! Your booking has been fully approved and our photography team is excited to capture your special event.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Our photography team will review all event details</li>
                        <li>Equipment and team assignments will be finalized</li>
                        <li>You will receive preparation updates as your event approaches</li>
                        <li>Our team will arrive on time for your special day</li>
                    </ul>

                    <p>We specialize in capturing beautiful moments for gatherings, birthdays, and other special occasions. Our photographers use state-of-the-art equipment to deliver stunning results.</p>
                </div>

                <div class="event-details">
                    <h3>📅 Event Details</h3>
                    <div class="event-info">
                        <h4>📋 Confirmed Event Information:</h4>
                        <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type']) . '</p>
                        <p><strong>Product:</strong> ' . htmlspecialchars($bookingDetails['product']) . '</p>
                        <p><strong>Package:</strong> ' . htmlspecialchars($bookingDetails['package_type']) . '</p>
                        <p><strong>Duration:</strong> ' . htmlspecialchars($bookingDetails['duration']) . '</p>
                    </div>
                    <p><strong>Event Date:</strong> ' . date('F d, Y', strtotime($bookingDetails['event_date'])) . '</p>
                    <p><strong>Time of Service:</strong> ' . date('h:i A', strtotime($bookingDetails['time_of_service'])) . '</p>
                    <p><strong>Venue:</strong> ' . htmlspecialchars($bookingDetails['venue']) . '</p>
                    <p><strong>Address:</strong> ' . htmlspecialchars($bookingDetails['street_address']) . ', ' . htmlspecialchars($bookingDetails['city']) . ', ' . htmlspecialchars($bookingDetails['region']) . '</p>
                </div>

                <div class="photography-section">
                    <h3>📸 Photography Information</h3>
                    <p><strong>Booking ID:</strong> #' . htmlspecialchars($bookingDetails['id']) . '</p>
                    <p><strong>Total Amount:</strong> ₱' . number_format((float) str_replace(['₱', ','], '', $bookingDetails['estimated_price']), 2) . '</p>
                    <p><strong>Booking Status:</strong> <span class="status-badge">Approved & Confirmed</span></p>
                    <p><strong>Approved Date:</strong> ' . $approvalTime . '</p>
                    <p><strong>Photography Style:</strong> Professional event coverage with high-quality equipment</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>📸 Amuning Tokobe Enterprise - Professional Event Photography</p>
                <p>Thank you for trusting us with your special moments! We look forward to capturing your event beautifully.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Booking rejection template
 */
function getBookingRejectionTemplate($userName, $bookingDetails, $reason = '') {
    // Set Manila timezone for accurate local time
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    
    $rejectionTime = date('F d, Y at h:i A');
    
    // Restore original timezone
    date_default_timezone_set($originalTimezone);
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Review Required - Amuning Tokobe Enterprise</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(239, 68, 68, 0.15);
                border: 1px solid rgba(239, 68, 68, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #ef4444;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #ef4444;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #fecaca;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .rejection-details, .booking-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #ef4444;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .rejection-details h3, .booking-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #ef4444;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .rejection-details p, .booking-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #fef2f2;
                color: #dc2626;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .rejection-notice {
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .rejection-notice h3 {
                margin: 0 0 10px;
                color: #dc2626;
                font-size: 20px;
            }
            .rejection-notice p {
                margin: 0;
                color: #dc2626;
                font-size: 16px;
                font-weight: 500;
            }
            .reason-box {
                background: #fff5f5;
                border: 1px solid #fed7d7;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }
            .reason-box p {
                margin: 0;
                color: #c53030;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #ef4444;
                font-weight: 600;
                background: #fef2f2;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>⚠️ Booking Review Required</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="rejection-notice">
                    <h3>📋 Booking Requires Review</h3>
                    <p>Your booking submission needs additional information or correction.</p>
                </div>

                <div class="message">
                    <p>We have reviewed your booking submission for <span class="highlight">Amuning Tokobe Enterprise</span>, but we need additional information or corrections before we can proceed.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Please check the reason below for required corrections</li>
                        <li>Make the necessary updates to your booking information</li>
                        <li>Resubmit your booking with the corrected details</li>
                        <li>Our team will review it again within 24 hours</li>
                    </ul>

                    <p>We apologize for any inconvenience this may cause. Please don\'t hesitate to contact us if you need assistance with the corrections or have questions about the requirements.</p>
                </div>' .
                ($reason ? '
                <div class="reason-box">
                    <p><strong>Reason for Review:</strong> ' . htmlspecialchars($reason) . '</p>
                </div>' : '') . '

                <div class="rejection-details">
                    <h3>📋 Booking Details</h3>
                    <p><strong>Booking ID:</strong> #' . htmlspecialchars($bookingDetails['id']) . '</p>
                    <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type']) . '</p>
                    <p><strong>Product:</strong> ' . htmlspecialchars($bookingDetails['product']) . '</p>
                    <p><strong>Package:</strong> ' . htmlspecialchars($bookingDetails['package_type']) . '</p>
                    <p><strong>Event Date:</strong> ' . date('F d, Y', strtotime($bookingDetails['event_date'])) . '</p>
                    <p><strong>Total Amount:</strong> ' . htmlspecialchars($bookingDetails['estimated_price']) . '</p>
                    <p><strong>Booking Status:</strong> <span class="status-badge">Rejected</span></p>
                </div>

                <div class="booking-section">
                    <h3>📸 Booking Information</h3>
                    <p><strong>Duration:</strong> ' . htmlspecialchars($bookingDetails['duration']) . '</p>
                    <p><strong>Time of Service:</strong> ' . date('h:i A', strtotime($bookingDetails['time_of_service'])) . '</p>
                    <p><strong>Venue:</strong> ' . htmlspecialchars($bookingDetails['venue']) . '</p>
                    <p><strong>Review Date:</strong> ' . $rejectionTime . '</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>📸 Amuning Tokobe Enterprise - Professional Photography Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Order rejection template for printing orders
 */
function getOrderRejectionTemplate($userName, $orderDetails, $reason = '') {
    // Set Manila timezone for accurate local time
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    
    $rejectionTime = date('F d, Y at h:i A');
    
    // Restore original timezone
    date_default_timezone_set($originalTimezone);
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Review Required - Amuning Tokobe Enterprise</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(239, 68, 68, 0.15);
                border: 1px solid rgba(239, 68, 68, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #ef4444;
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #ef4444;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #fecaca;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .rejection-details, .order-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #ef4444;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .rejection-details h3, .order-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #ef4444;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .rejection-details p, .order-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                background: #fef2f2;
                color: #dc2626;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .rejection-notice {
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .rejection-notice h3 {
                margin: 0 0 10px;
                color: #dc2626;
                font-size: 20px;
            }
            .rejection-notice p {
                margin: 0;
                color: #dc2626;
                font-size: 16px;
                font-weight: 500;
            }
            .reason-box {
                background: #fff5f5;
                border: 1px solid #fed7d7;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }
            .reason-box p {
                margin: 0;
                color: #c53030;
                font-weight: 500;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #ef4444;
                font-weight: 600;
                background: #fef2f2;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-container {
                    border-radius: 12px;
                }
                .email-header {
                    padding: 30px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
                .email-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>⚠️ Order Review Required</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="rejection-notice">
                    <h3>📋 Order Requires Review</h3>
                    <p>Your printing order submission needs additional information or correction.</p>
                </div>

                <div class="message">
                    <p>We have reviewed your printing order submission for <span class="highlight">Amuning Tokobe Enterprise</span>, but we need additional information or corrections before we can proceed.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>Please check the reason below for required corrections</li>
                        <li>Make the necessary updates to your printing order information</li>
                        <li>Resubmit your order with the corrected details</li>
                        <li>Our team will review it again within 24 hours</li>
                    </ul>

                    <p>We apologize for any inconvenience this may cause. Please don\'t hesitate to contact us if you need assistance with the corrections or have questions about the requirements.</p>
                </div>' .
                ($reason ? '
                <div class="reason-box">
                    <p><strong>Reason for Review:</strong> ' . htmlspecialchars($reason) . '</p>
                </div>' : '') . '

                <div class="rejection-details">
                    <h3>📋 Order Details</h3>
                    <p><strong>Order ID:</strong> #' . htmlspecialchars($orderDetails['id']) . '</p>
                    <p><strong>Service:</strong> ' . htmlspecialchars($orderDetails['service']) . '</p>
                    <p><strong>Size:</strong> ' . htmlspecialchars($orderDetails['size']) . '</p>
                    <p><strong>Paper Type:</strong> ' . htmlspecialchars($orderDetails['paper_type']) . '</p>
                    <p><strong>Quantity:</strong> ' . htmlspecialchars($orderDetails['quantity']) . '</p>
                    <p><strong>Total Price:</strong> ₱' . number_format($orderDetails['price'], 2) . '</p>
                    <p><strong>Order Status:</strong> <span class="status-badge">Rejected</span></p>
                </div>

                <div class="order-section">
                    <h3>🖨️ Order Information</h3>
                    <p><strong>Order Date:</strong> ' . date('F d, Y', strtotime($orderDetails['order_date'])) . '</p>
                    <p><strong>Special Instructions:</strong> ' . htmlspecialchars($orderDetails['special_instruction'] ?? 'N/A') . '</p>
                    <p><strong>Review Date:</strong> ' . $rejectionTime . '</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>🖨️ Amuning Tokobe Enterprise - Professional Printing Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Photo Booking Validated Template - Status updated to Validated
 */
function getPhotoBookingValidatedTemplate($userName, $bookingDetails) {
    $paymentUrl = "http://localhost/Amuning/public/payment.php?booking_id=" . $bookingDetails['id'];
    
    // Generate invoice with travel fee
    $invoiceData = generatePhotoInvoiceBreakdown($bookingDetails);

    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Photo Booking Validated - Ready for Payment</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(59, 130, 246, 0.15);
                border: 1px solid rgba(59, 130, 246, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: #3b82f6;
                color: white;
                padding: 40px 30px;
                text-align: center;
            }
            .email-header h1 {
                margin: 0 0 8px;
                font-size: 32px;
                font-weight: 700;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #3b82f6;
                margin-bottom: 20px;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 20px;
                color: #4a5568;
            }
            .validated-section, .invoice-section {
                background: #dbeafe;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                border-left: 4px solid #3b82f6;
            }
            .validated-section h3, .invoice-section h3 {
                margin-top: 0;
                color: #1e40af;
                font-size: 18px;
            }
            .pay-button {
                display: inline-block;
                background: #3b82f6;
                color: white !important;
                padding: 16px 32px;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 600;
                font-size: 16px;
                margin: 20px 0;
            }
            .invoice-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            .invoice-table th {
                background: #f0f9ff;
                padding: 12px;
                text-align: left;
                border-bottom: 2px solid #3b82f6;
                font-weight: 600;
                color: #1e40af;
            }
            .invoice-table td {
                padding: 10px 12px;
                border-bottom: 1px solid #e0e7ff;
            }
            .total-row {
                background: #f0f9ff;
                font-weight: 700;
            }
            .details-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
            }
            .details-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>✅ Booking Validated!</h1>
                <p>Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="validated-section">
                    <h3>🎉 Your Booking Has Been Validated!</h3>
                    <p>Excellent news! Your photo booking #' . htmlspecialchars($bookingDetails['id']) . ' has been reviewed and validated by our team. Your event details look perfect!</p>
                </div>

                <div class="message">
                    <p>To secure your booking and confirm our photographers for your event, please proceed with payment of the total amount shown below.</p>
                    <p><strong>Payment is due before your event date to finalize all arrangements.</strong></p>
                </div>

                <div class="invoice-section">
                    <h3>💰 Payment Invoice</h3>
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th style="width: 60%;">Service Description</th>
                                <th style="width: 20%; text-align: center;">Unit Price</th>
                                <th style="width: 20%; text-align: center;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>'
                            . implode('', array_map(function($item) {
                                return '
                            <tr>
                                <td>' . htmlspecialchars($item['description']) . '</td>
                                <td style="text-align: center;">₱' . number_format($item['amount'], 2) . '</td>
                                <td style="text-align: center; font-weight: 600;">₱' . number_format($item['amount'], 2) . '</td>
                            </tr>';
                            }, $invoiceData['items']))
                            . '
                            <tr class="total-row">
                                <td colspan="2" style="text-align: right;">TOTAL AMOUNT DUE:</td>
                                <td style="text-align: center;">' . $invoiceData['formatted_total'] . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="text-align: center;">
                    <a href="' . htmlspecialchars($paymentUrl) . '" class="pay-button">💳 Proceed to Payment</a>
                </div>

                <div class="details-section">
                    <h3>📋 Booking Details</h3>
                    <p><strong>Booking ID:</strong> #' . htmlspecialchars($bookingDetails['id']) . '</p>
                    <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type'] ?? 'N/A') . '</p>
                    <p><strong>Package:</strong> ' . htmlspecialchars($bookingDetails['package_type'] ?? 'N/A') . '</p>
                    <p><strong>Event Date:</strong> ' . (isset($bookingDetails['event_date']) ? date('F d, Y', strtotime($bookingDetails['event_date'])) : 'N/A') . '</p>
                    <p><strong>Time:</strong> ' . (isset($bookingDetails['time_of_service']) ? date('h:i A', strtotime($bookingDetails['time_of_service'])) : 'N/A') . '</p>
                    <p><strong>Venue:</strong> ' . htmlspecialchars($bookingDetails['venue'] ?? 'N/A') . '</p>
                </div>

                <div class="message">
                    <p>If you have any questions or need to make changes to your booking, please contact us immediately.</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>📸 Amuning Tokobe Enterprise - Professional Photography Services</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Photo Booking Booked Template - Status updated to Booked (Payment received and confirmed)
 */
function getPhotoBookingBookedTemplate($userName, $bookingDetails) {
    // Generate invoice with travel fee for receipt
    $invoiceData = generatePhotoInvoiceBreakdown($bookingDetails);
    
    // Set Manila timezone
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    $bookedTime = date('F d, Y \a\t h:i A');
    date_default_timezone_set($originalTimezone);

    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Photo Booking Confirmed - Booking Receipt</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(34, 197, 94, 0.15);
                border: 1px solid rgba(34, 197, 94, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                color: white;
                padding: 40px 30px;
                text-align: center;
            }
            .email-header h1 {
                margin: 0 0 8px;
                font-size: 32px;
                font-weight: 700;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #22c55e;
                margin-bottom: 20px;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 20px;
                color: #4a5568;
            }
            .confirmed-section, .next-steps {
                background: #dcfce7;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                border-left: 4px solid #22c55e;
            }
            .confirmed-section h3, .next-steps h3 {
                margin-top: 0;
                color: #15803d;
                font-size: 18px;
            }
            .receipt-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                border: 2px dashed #94e6b8;
            }
            .receipt-header {
                background: #22c55e;
                color: white;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 15px;
                text-align: center;
                font-weight: 700;
                font-size: 16px;
            }
            .invoice-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            .invoice-table th {
                background: #f0fdf4;
                padding: 12px;
                text-align: left;
                border-bottom: 2px solid #22c55e;
                font-weight: 600;
                color: #15803d;
            }
            .invoice-table td {
                padding: 10px 12px;
                border-bottom: 1px solid #bbf7d0;
            }
            .total-row {
                background: #f0fdf4;
                font-weight: 700;
            }
            .details-section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
            }
            .details-section p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .next-steps ul {
                margin: 10px 0;
                padding-left: 20px;
                color: #15803d;
            }
            .next-steps li {
                margin: 8px 0;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>🎉 Booking Confirmed!</h1>
                <p>Payment Received & Your Spot is Secured</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="confirmed-section">
                    <h3>✅ Your Booking is Confirmed!</h3>
                    <p>Fantastic news! We have received your payment and your photo booking #' . htmlspecialchars($bookingDetails['id']) . ' is now officially confirmed. Your spot with Amuning Tokobe Enterprise is secured!</p>
                </div>

                <div class="receipt-section">
                    <div class="receipt-header">📄 OFFICIAL BOOKING RECEIPT</div>
                    
                    <p style="margin: 10px 0; font-size: 14px;"><strong>Receipt Date:</strong> ' . $bookedTime . '</p>
                    <p style="margin: 10px 0; font-size: 14px;"><strong>Booking ID:</strong> #' . htmlspecialchars($bookingDetails['id']) . '</p>
                    <p style="margin: 10px 0; font-size: 14px;"><strong>Customer Name:</strong> ' . htmlspecialchars($userName) . '</p>

                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th style="width: 60%;">Service Description</th>
                                <th style="width: 20%; text-align: center;">Unit Price</th>
                                <th style="width: 20%; text-align: center;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>'
                            . implode('', array_map(function($item) {
                                return '
                            <tr>
                                <td>' . htmlspecialchars($item['description']) . '</td>
                                <td style="text-align: center;">₱' . number_format($item['amount'], 2) . '</td>
                                <td style="text-align: center; font-weight: 600;">₱' . number_format($item['amount'], 2) . '</td>
                            </tr>';
                            }, $invoiceData['items']))
                            . '
                            <tr class="total-row">
                                <td colspan="2" style="text-align: right;">TOTAL PAID:</td>
                                <td style="text-align: center;">' . $invoiceData['formatted_total'] . '</td>
                            </tr>
                        </tbody>
                    </table>

                    <p style="margin: 10px 0; font-size: 14px; text-align: center;"><strong>✓ Payment Status: CONFIRMED</strong></p>
                </div>

                <div class="details-section">
                    <h3>📋 Booking Details</h3>
                    <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type'] ?? 'N/A') . '</p>
                    <p><strong>Product:</strong> ' . htmlspecialchars($bookingDetails['product'] ?? 'N/A') . '</p>
                    <p><strong>Package:</strong> ' . htmlspecialchars($bookingDetails['package_type'] ?? 'N/A') . '</p>
                    <p><strong>Duration:</strong> ' . htmlspecialchars($bookingDetails['duration'] ?? 'N/A') . '</p>
                    <p><strong>📅 Event Date:</strong> ' . (isset($bookingDetails['event_date']) ? date('F d, Y', strtotime($bookingDetails['event_date'])) : 'N/A') . '</p>
                    <p><strong>⏰ Time:</strong> ' . (isset($bookingDetails['time_of_service']) ? date('h:i A', strtotime($bookingDetails['time_of_service'])) : 'N/A') . '</p>
                    <p><strong>📍 Venue:</strong> ' . htmlspecialchars($bookingDetails['venue'] ?? 'N/A') . '</p>
                </div>

                <div class="next-steps">
                    <h3>📸 What Happens Next?</h3>
                    <p>Your professional photography team is now officially preparing for your special event. Here\'s what to expect:</p>
                    <ul>
                        <li><strong>Preparation Phase:</strong> Our team is reviewing all event details and finalizing equipment and assignments</li>
                        <li><strong>Pre-Event Confirmation:</strong> We will contact you 2-3 days before your event to confirm all final details and arrival time</li>
                        <li><strong>Equipment Setup:</strong> Our team will arrive at your venue 30 minutes early to set up all photography equipment</li>
                        <li><strong>Professional Coverage:</strong> Our skilled photographers will capture every special moment of your event</li>
                        <li><strong>Post-Event:</strong> After your event, we will proceed with post-processing and delivery of your photos according to the package terms</li>
                        <li><strong>Quality Assurance:</strong> Your photos will be carefully edited and delivered in high-resolution format</li>
                    </ul>
                </div>

                <div class="message">
                    <p><strong>Need to Make Changes?</strong> If you need to reschedule or make any changes to your booking, please contact us as soon as possible. We\'re here to help!</p>
                    <p style="margin-top: 20px;"><strong>Thank you for choosing Amuning Tokobe Enterprise!</strong> We look forward to creating beautiful memories at your event.</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>📸 Amuning Tokobe Enterprise - Professional Photography Services</p>
                <p style="margin-top: 10px; opacity: 0.8;">This is an automated confirmation receipt. Please save this email for your records.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Photo booking completed template - Event finished, service completed
 */
function getPhotoBookingCompletedTemplate($userName, $bookingDetails) {
    // Set Manila timezone for accurate local time
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');
    
    $completionTime = date('F d, Y \a\t h:i A');
    
    // Restore original timezone
    date_default_timezone_set($originalTimezone);
    
    // Generate detailed invoice breakdown
    $invoiceData = generatePhotoInvoiceBreakdown($bookingDetails);
    
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Event Completed Successfully! - Amuning Tokobe Enterprise</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: #f7fafc;
                padding: 20px;
            }
            .email-container {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(34, 197, 94, 0.15);
                border: 1px solid rgba(34, 197, 94, 0.1);
                margin: 20px 0;
            }
            .email-header {
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                color: white;
                padding: 40px 30px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .email-header h1 {
                margin: 0;
                font-size: 36px;
                font-weight: 700;
                margin-bottom: 8px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            }
            .email-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 18px;
                font-weight: 300;
                position: relative;
                z-index: 2;
            }
            .email-body {
                padding: 40px 30px;
            }
            .greeting {
                font-size: 24px;
                font-weight: 600;
                color: #22c55e;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #dcfce7;
            }
            .message {
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 30px;
                color: #4a5568;
            }
            .completion-notice {
                background: #dcfce7;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .completion-notice h3 {
                margin: 0 0 10px;
                color: #166534;
                font-size: 22px;
            }
            .completion-notice p {
                margin: 0;
                color: #166534;
                font-size: 16px;
                font-weight: 500;
            }
            .event-summary, .receipt-section, .booking-details {
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #22c55e;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .event-summary h3, .receipt-section h3, .booking-details h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #22c55e;
                font-size: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .event-summary p, .receipt-section p, .booking-details p {
                margin: 8px 0;
                font-size: 15px;
                color: #2d3748;
            }
            .receipt-header {
                background: #22c55e;
                color: white;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 15px;
                text-align: center;
                font-weight: 700;
                font-size: 16px;
            }
            .invoice-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            .invoice-table th {
                background: #f0fdf4;
                padding: 12px;
                text-align: left;
                border-bottom: 2px solid #22c55e;
                font-weight: 600;
                color: #15803d;
                font-size: 14px;
            }
            .invoice-table td {
                padding: 10px 12px;
                border-bottom: 1px solid #bbf7d0;
                font-size: 14px;
            }
            .total-row {
                background: #f0fdf4;
                font-weight: 700;
            }
            .next-steps {
                background: #fef3c7;
                border: 1px solid #fde68a;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
            }
            .next-steps h4 {
                margin: 0 0 15px;
                color: #92400e;
                font-size: 18px;
            }
            .next-steps ul {
                margin: 0;
                padding-left: 20px;
                color: #92400e;
            }
            .next-steps li {
                margin: 8px 0;
            }
            .email-footer {
                background: #2d3748;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 14px;
            }
            .email-footer p {
                margin: 5px 0;
                opacity: 0.9;
            }
            .highlight {
                color: #22c55e;
                font-weight: 600;
                background: #dcfce7;
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            @media (max-width: 600px) {
                body {
                    padding: 10px;
                }
                .email-body {
                    padding: 25px 20px;
                }
                .email-header h1 {
                    font-size: 28px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>🎉 Event Completed Successfully!</h1>
                <p>Thank You for Choosing Amuning Tokobe Enterprise</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="completion-notice">
                    <h3>✅ Your Event Photography is Complete!</h3>
                    <p>What a wonderful time we had photographing your special moment!</p>
                </div>

                <div class="message">
                    <p>Thank you so much for choosing <span class="highlight">Amuning Tokobe Enterprise</span> for your photography needs! We had an absolutely fantastic time photographing your event and capturing all those precious moments that you\'ll treasure forever.</p>

                    <p>Our team thoroughly enjoyed working with you and your guests. Your event was wonderful, and we\'re thrilled we could be part of making it even more special with professional photography.</p>

                    <p>We put our heart and soul into every shot, and we\'re confident your photos will bring back all the joy and wonderful memories from this incredible day!</p>
                </div>

                <div class="event-summary">
                    <h3>📸 Event Summary</h3>
                    <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type'] ?? 'N/A') . '</p>
                    <p><strong>Event Date:</strong> ' . (isset($bookingDetails['event_date']) ? date('F d, Y', strtotime($bookingDetails['event_date'])) : 'N/A') . '</p>
                    <p><strong>Duration of Coverage:</strong> ' . htmlspecialchars($bookingDetails['duration'] ?? 'N/A') . '</p>
                    <p><strong>Venue:</strong> ' . htmlspecialchars($bookingDetails['venue'] ?? 'N/A') . '</p>
                    <p><strong>Product Delivered:</strong> ' . htmlspecialchars($bookingDetails['product'] ?? 'N/A') . ' Package</p>
                </div>

                <div class="receipt-section">
                    <h3>📋 Final Invoice</h3>
                    <div class="receipt-header">COMPLETION INVOICE #' . htmlspecialchars($bookingDetails['id']) . '</div>
                    
                    <p style="margin: 10px 0; font-size: 14px;"><strong>Completion Date:</strong> ' . $completionTime . '</p>
                    <p style="margin: 10px 0; font-size: 14px;"><strong>Customer Name:</strong> ' . htmlspecialchars($userName) . '</p>

                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th style="width: 60%;">Service Description</th>
                                <th style="width: 20%; text-align: center;">Unit Price</th>
                                <th style="width: 20%; text-align: center;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>'
                            . implode('', array_map(function($item) {
                                return '
                            <tr>
                                <td>' . htmlspecialchars($item['description']) . '</td>
                                <td style="text-align: center;">₱' . number_format($item['amount'], 2) . '</td>
                                <td style="text-align: center; font-weight: 600;">₱' . number_format($item['amount'], 2) . '</td>
                            </tr>';
                            }, $invoiceData['items']))
                            . '
                            <tr class="total-row">
                                <td colspan="2" style="text-align: right;">TOTAL AMOUNT PAID:</td>
                                <td style="text-align: center;">' . $invoiceData['formatted_total'] . '</td>
                            </tr>
                        </tbody>
                    </table>

                    <p style="margin: 10px 0; font-size: 14px; text-align: center;"><strong>✓ Payment Status: COMPLETED</strong></p>
                </div>

                <div class="booking-details">
                    <h3>📝 Booking Details</h3>
                    <p><strong>Booking ID:</strong> #' . htmlspecialchars($bookingDetails['id']) . '</p>
                    <p><strong>Event Type:</strong> ' . htmlspecialchars($bookingDetails['event_type'] ?? 'N/A') . '</p>
                    <p><strong>Package Type:</strong> ' . htmlspecialchars($bookingDetails['package_type'] ?? 'N/A') . '</p>
                    <p><strong>Photography Service:</strong> Professional event coverage with equipment</p>
                    <p><strong>Event Status:</strong> <span class="highlight">Completed</span></p>
                    <p><strong>Service Completion:</strong> ' . $completionTime . '</p>
                </div>

                <div class="next-steps">
                    <h4>🎯 What\'s Next?</h4>
                    <ul>
                        <li><strong>Photos:</strong> Follow up with us regarding photo delivery according to your package terms</li>
                        <li><strong>Feedback:</strong> We\'d love to hear about your experience! Share your feedback with us</li>
                        <li><strong>Future Events:</strong> Remember us for your next special occasion - we\'d be honored to work with you again!</li>
                        <li><strong>Referrals:</strong> Know someone planning an event? Recommend us to friends and family!</li>
                    </ul>
                </div>

                <div class="message" style="margin-top: 30px; border-top: 2px solid #dcfce7; padding-top: 20px;">
                    <p><strong>Thank you once again for trusting Amuning Tokobe Enterprise!</strong></p>
                    <p>We can\'t wait to see you again at your next celebration. When life gives you special moments, make sure you capture them with us!</p>
                    <p style="color: #22c55e; font-weight: 600;">📸 Wishing you all the best! - The Amuning Tokobe Enterprise Team</p>
                </div>
            </div>

            <div class="email-footer">
                <p>📞 Contact us: +63 912 345 6789 | 📧 info@amuning.com</p>
                <p>📸 Amuning Tokobe Enterprise - Professional Photography Services</p>
                <p style="margin-top: 10px; opacity: 0.8;">Thank you for being part of our journey! We look forward to capturing more beautiful moments together.</p>
            </div>
        </div>
    </body>
    </html>';
}