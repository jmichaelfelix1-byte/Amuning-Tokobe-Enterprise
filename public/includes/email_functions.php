<?php
/**
 * Email Functions Index File
 *
 * This file includes all organized email modules for backward compatibility.
 * The email system has been reorganized into separate modules:
 *
 * - email_config.php     - PHPMailer setup and configuration
 * - email_payment.php    - Payment-related emails
 * - email_bookings.php   - Booking-related emails
 * - email_orders.php     - Order-related emails
 * - email_templates.php  - Shared templates and utilities
 */

// Include all email modules
require_once 'email_config.php';      // PHPMailer setup
require_once 'email_payment.php';     // Payment emails
require_once 'email_bookings.php';    // Booking emails
require_once 'email_orders.php';      // Order emails
require_once 'email_templates.php';   // Templates and utilities

/**
 * Legacy function for backward compatibility
 * @deprecated Use specific email functions instead
 */
function sendEmail($type, $email, $name, $details) {
    switch ($type) {
        case 'payment_pending':
            return sendPhotoPaymentPendingEmail($email, $name, $details);
        case 'booking_confirmation':
            return sendPhotoBookingReceivedEmail($email, $name, $details);
        case 'order_confirmation':
            return sendOrderConfirmationEmail($email, $name, $details);
        default:
            return ['success' => false, 'message' => 'Unknown email type'];
    }
}

function getPhotoPaymentConfirmationTemplate($userName, $bookingDetails) {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Confirmed</title>
        <style>
            body {
                font-family: \'Poppins\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #2d3748;
                max-width: 600px;
                margin: 0 auto;
                background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
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
            .booking-details, .payment-section {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border-radius: 12px;
                padding: 25px;
                margin: 25px 0;
                border-left: 4px solid #22c55e;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .booking-details h3, .payment-section h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #22c55e;
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
                background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
                color: #166534;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .success-message {
                background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
                border: 1px solid #86efac;
                border-radius: 12px;
                padding: 20px;
                margin: 25px 0;
                text-align: center;
            }
            .success-message h3 {
                margin: 0 0 10px;
                color: #166534;
                font-size: 20px;
            }
            .success-message p {
                margin: 0;
                color: #166534;
                font-size: 16px;
                font-weight: 500;
            }
            .email-footer {
                background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
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
                background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
                padding: 2px 6px;
                border-radius: 4px;
                margin: 0 2px;
            }
            .divider {
                height: 1px;
                background: linear-gradient(90deg, transparent 0%, #e2e8f0 20%, #e2e8f0 80%, transparent 100%);
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
                <h1>✅ Payment Confirmed!</h1>
                <p>Your photo booking is now confirmed</p>
            </div>

            <div class="email-body">
                <div class="greeting">Hi ' . htmlspecialchars($userName) . ',</div>

                <div class="success-message">
                    <h3>🎉 Payment Successfully Processed!</h3>
                    <p>Your payment has been confirmed and your photo booking is now fully secured.</p>
                </div>

                <div class="message">
                    <p>Thank you for choosing <span class="highlight">Amuning Tokobe Enterprise</span> for your photography needs! Your payment has been successfully processed and your booking is now confirmed.</p>

                    <p>Our professional photographers will arrive at your venue on time and capture your special event with the highest quality equipment and artistic expertise. We look forward to creating beautiful memories for you!</p>

                    <p>You will receive updates about your booking preparation and any additional instructions closer to your event date. If you have any questions or need to make changes to your booking, please do not hesitate to contact us.</p>
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
                    <p><strong>Status:</strong> <span class="status-badge">Confirmed & Paid</span></p>
                </div>

                <div class="payment-section">
                    <h3>💰 Payment Details</h3>
                    <p><strong>Total Amount Paid:</strong> ₱' . number_format($bookingDetails['estimated_price'], 2) . '</p>
                    <p><strong>Payment Date:</strong> ' . date('F d, Y \a\t h:i A') . '</p>
                    <p><strong>Booking Status:</strong> <span class="status-badge">Confirmed</span></p>
                </div>

                <div class="message">
                    <p><strong>What happens next?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #4a5568;">
                        <li>You will receive a confirmation call from our team within 24 hours</li>
                        <li>Our photographer will contact you 1 week before the event for final preparations</li>
                        <li>All edited photos will be delivered within 2-3 weeks after the event</li>
                        <li>You can track your booking status in your account dashboard</li>
                    </ul>
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
?>
