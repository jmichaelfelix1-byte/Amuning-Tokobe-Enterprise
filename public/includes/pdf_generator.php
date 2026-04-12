<?php
/**
 * PDF Generation Functions
 * Generates PDF receipts/invoices for orders and bookings
 */

require_once __DIR__ . '/../../send_email/vendor/autoload.php';

/**
 * Generate Order Receipt PDF
 * 
 * @param array $orderDetails Order/booking details
 * @param string $orderType 'printing_order' or 'photo_booking'
 * @return string Path to generated PDF file or false on error
 */
function generateOrderReceiptPDF($orderDetails, $orderType) {
    try {
        // Create PDF object
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Amuning Tokobe Enterprise');
        $pdf->SetAuthor('Amuning');
        $pdf->SetTitle('Order Receipt');
        $pdf->SetSubject('Order Receipt');
        
        // Set default font
        $pdf->SetFont('helvetica', '', 10);
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Ensure uploads/receipts directory exists
        $receipts_dir = __DIR__ . '/../uploads/receipts/';
        if (!file_exists($receipts_dir)) {
            mkdir($receipts_dir, 0777, true);
        }
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Add header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'AMUNING TOKOBE ENTERPRISE', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Your Photo & Printing Solution', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Add title
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 8, 'ORDER RECEIPT', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(3);
        
        // Add horizontal line
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(2);
        
        // Order Details Section
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(50, 6, 'Order Information', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        // Order ID and Date
        $pdf->Cell(50, 5, 'Order ID: ', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, $orderDetails['id'] ?? 'N/A', 0, 1);
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(50, 5, 'Date: ', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $dateCreated = isset($orderDetails['created_at']) 
            ? date('F j, Y g:i A', strtotime($orderDetails['created_at']))
            : (isset($orderDetails['order_date']) 
                ? date('F j, Y g:i A', strtotime($orderDetails['order_date']))
                : 'N/A');
        $pdf->Cell(0, 5, $dateCreated, 0, 1);
        
        $pdf->Ln(3);
        
        // Customer Details Section
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(50, 6, 'Customer Information', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(50, 5, 'Name: ', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, $orderDetails['user_name'] ?? $orderDetails['full_name'] ?? 'N/A', 0, 1);
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(50, 5, 'Email: ', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, $orderDetails['user_email'] ?? $orderDetails['email'] ?? 'N/A', 0, 1);
        
        if (!empty($orderDetails['phone'])) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(50, 5, 'Phone: ', 0, 0);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, $orderDetails['phone'] ?? '', 0, 1);
        }
        
        $pdf->Ln(3);
        
        // Service/Order Details
        if ($orderType === 'printing_order') {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(50, 6, 'Order Details', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            
            if (!empty($orderDetails['service'])) {
                $pdf->Cell(50, 5, 'Service: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 5, $orderDetails['service'], 0, 1);
            }
            
            if (!empty($orderDetails['size'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(50, 5, 'Size: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 5, $orderDetails['size'], 0, 1);
            }
            
            if (!empty($orderDetails['quantity'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(50, 5, 'Quantity: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 5, $orderDetails['quantity'], 0, 1);
            }
            
            if (!empty($orderDetails['additional_notes'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(50, 5, 'Notes: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $notes = substr($orderDetails['additional_notes'], 0, 100);
                $pdf->MultiCell(0, 5, $notes, 0, 'L');
            }
        } elseif ($orderType === 'photo_booking') {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(50, 6, 'Booking Details', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            
            if (!empty($orderDetails['event_type'])) {
                $pdf->Cell(50, 5, 'Event Type: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 5, $orderDetails['event_type'], 0, 1);
            }
            
            if (!empty($orderDetails['venue'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(50, 5, 'Venue: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 5, $orderDetails['venue'], 0, 1);
            }
            
            if (!empty($orderDetails['event_date'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(50, 5, 'Event Date: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $eventDate = date('F j, Y', strtotime($orderDetails['event_date']));
                $pdf->Cell(0, 5, $eventDate, 0, 1);
            }
            
            if (!empty($orderDetails['event_time'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(50, 5, 'Start Time: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 5, $orderDetails['event_time'], 0, 1);
            }
            
            if (!empty($orderDetails['duration_hours'])) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(50, 5, 'Duration: ', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 5, $orderDetails['duration_hours'] . ' hour(s)', 0, 1);
            }
        }
        
        $pdf->Ln(5);
        
        // Add horizontal line
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(2);
        
        // Payment Details Section
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(50, 6, 'Payment Information', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(50, 5, 'Amount: ', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $amount = $orderDetails['estimated_price'] ?? $orderDetails['price'] ?? $orderDetails['amount'] ?? 'N/A';
        $pdf->Cell(0, 5, '₱ ' . number_format($amount, 2), 0, 1);
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(50, 5, 'Payment Method: ', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, $orderDetails['payment_method'] ?? 'Pending', 0, 1);
        
        if (!empty($orderDetails['status'])) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(50, 5, 'Status: ', 0, 0);
            $pdf->SetFont('helvetica', '', 10);
            $statusText = ucfirst(str_replace('_', ' ', $orderDetails['status']));
            $pdf->Cell(0, 5, $statusText, 0, 1);
        }
        
        $pdf->Ln(5);
        
        // Add horizontal line
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(2);
        
        // Footer message
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->MultiCell(0, 5, 'Thank you for choosing Amuning Tokobe Enterprise! This is your official order receipt. Please keep this for your records.', 0, 'C');
        
        // Generate unique filename
        $filename = 'receipt_' . $orderDetails['id'] . '_' . time() . '.pdf';
        $filepath = $receipts_dir . $filename;
        
        // Output PDF to file
        $pdf->Output($filepath, 'F');
        
        if (file_exists($filepath)) {
            return $filepath;
        }
        
        return false;
    } catch (Exception $e) {
        error_log('PDF Generation Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get readable order details for PDF display
 * 
 * @param array $orderDetails Raw order details from database
 * @param string $orderType 'printing_order' or 'photo_booking'
 * @return array Formatted details
 */
function formatOrderDetailsForPDF($orderDetails, $orderType) {
    $formatted = $orderDetails;
    
    // Add human-readable labels
    if ($orderType === 'printing_order') {
        $formatted['type_label'] = 'Printing Order';
    } elseif ($orderType === 'photo_booking') {
        $formatted['type_label'] = 'Photo Booth Booking';
    }
    
    return $formatted;
}
?>
