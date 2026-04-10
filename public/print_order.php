<?php
session_start();

// Suppress error output to avoid interfering with redirects
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set timezone to Manila, Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'user') {
    header('Location: print.php?message=login_required');
    exit();
}

include 'includes/config.php';
include 'includes/email_functions.php';

// Function to count pages in different file types
// Function to count pages in different file types
function countFilePages($filePath, $fileName) {
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf':
            return countPDFPages($filePath);
        case 'docx':
            return countDOCXPages($filePath);
        case 'doc':
            return countDOCPages($filePath);
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'webp':
        case 'tiff':
        case 'tif':
            return 1; // Each image counts as 1 page
        default:
            return 1; // Default to 1 page for unknown types
    }
}

// Count pages in PDF files
function countPDFPages($filePath) {
    if (!file_exists($filePath)) return 1;
    
    try {
        $content = file_get_contents($filePath);
        if ($content === false) return 1;
        
        // Normalize line breaks
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Method 1: Find the catalog object and extract /Pages reference
        // PDFs have a /Root entry pointing to catalog, which contains /Pages
        if (preg_match('/\/Root\s*(\d+)\s*0\s*R/i', $content, $rootMatches)) {
            $rootObjNum = $rootMatches[1];
            // Find the root object
            if (preg_match("/$rootObjNum\\s+0\\s+obj[\s\S]{0,2000}?\/Pages\s*(\d+)\s*0\s*R/i", $content, $pagesRefMatches)) {
                $pagesObjNum = $pagesRefMatches[1];
                // Now find the Pages object and extract /Count
                if (preg_match("/$pagesObjNum\\s+0\\s+obj[\s\S]{0,2000}?\/Count\s*(\d+)/i", $content, $countMatches)) {
                    $pageCount = (int)$countMatches[1];
                    if ($pageCount > 0) {
                        error_log("PDF page count (via catalog /Root -> /Pages /Count): $pageCount");
                        return $pageCount;
                    }
                }
            }
        }
        
        // Method 2: Direct search for /Count in /Type /Pages (broader range)
        if (preg_match('/\/Type\s*\/Pages[\s\S]{0,1000}?\/Count\s*(\d+)/i', $content, $matches)) {
            $pageCount = (int)$matches[1];
            if ($pageCount > 0) {
                error_log("PDF page count (via /Type/Pages /Count direct): $pageCount");
                return $pageCount;
            }
        }
        
        // Method 3: Search backwards - /Count before /Type /Pages
        if (preg_match('/\/Count\s*(\d+)[\s\S]{0,1000}?\/Type\s*\/Pages/i', $content, $matches)) {
            $pageCount = (int)$matches[1];
            if ($pageCount > 0) {
                error_log("PDF page count (via /Count ... /Type/Pages): $pageCount");
                return $pageCount;
            }
        }
        
        // Method 4: Count /Type/Page objects
        $pageMatches = [];
        preg_match_all('/\/Type\s*\/Page(?:[\s\/]|>>)/i', $content, $pageMatches);
        $pageCount = count($pageMatches[0]);
        
        if ($pageCount > 0) {
            error_log("PDF page count (via /Type/Page count): $pageCount");
            return $pageCount;
        }
        
        error_log("PDF page count: Unable to determine, defaulting to 1");
        return 1;
        
    } catch (Exception $e) {
        error_log("PDF page count error: " . $e->getMessage());
        return 1;
    }
}

// Count pages in DOCX files
function countDOCXPages($filePath) {
    if (!file_exists($filePath)) {
        error_log("DOCX file not found: $filePath");
        return 1;
    }
    
    try {
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            error_log("ZipArchive class not available for DOCX processing");
            return 1;
        }
        
        // DOCX is a ZIP archive; extract and read document.xml
        $zip = new ZipArchive();
        $openResult = $zip->open($filePath);
        
        if ($openResult !== true) {
            error_log("Failed to open DOCX file as ZIP: $filePath, Error code: $openResult");
            return 1;
        }
        
        // Get document.xml content
        $docXml = $zip->getFromName('word/document.xml');
        $zip->close();
        
        if (!$docXml) {
            error_log("Could not extract document.xml from DOCX file: $filePath");
            return 1;
        }
        
        // Count page breaks: </w:p> tags often indicate page boundaries
        // A rough estimate: count paragraph markers and divide by average paragraphs per page (~3-4)
        // Better: count explicit page breaks <w:br w:type="page"/>
        $pageBreakMatches = [];
        preg_match_all('/<w:br\s+w:type="page"/i', $docXml, $pageBreakMatches);
        $pageBreaks = count($pageBreakMatches[0]);
        
        // If no explicit page breaks, estimate based on paragraphs
        if ($pageBreaks === 0) {
            $paragraphMatches = [];
            preg_match_all('/<w:p>/i', $docXml, $paragraphMatches);
            $paragraphs = count($paragraphMatches[0]);
            
            // Rough estimate: 3-4 paragraphs per page
            $estimatedPages = max(1, (int)ceil($paragraphs / 3.5));
            error_log("DOCX page count (estimated from paragraphs): $estimatedPages");
            return $estimatedPages;
        }
        
        // Add 1 for the first page (page breaks are transitions to new pages)
        $pageCount = $pageBreaks + 1;
        error_log("DOCX page count (from explicit page breaks): $pageCount");
        return $pageCount;
        
    } catch (Exception $e) {
        error_log("DOCX page count error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
        return 1; // Fallback
    }
}

// Count pages in DOC files
function countDOCPages($filePath) {
    if (!file_exists($filePath)) return 1;
    
    try {
        // DOC format is binary and complex; read raw content and estimate
        $content = file_get_contents($filePath, false, null, 0, min(100000, filesize($filePath)));
        if ($content === false) return 1;
        
        // Look for page break indicators in the binary data
        // This is a rough heuristic; DOC format is proprietary
        $pageBreakCount = substr_count($content, "\x0c"); // Form feed character
        
        return $pageBreakCount > 0 ? $pageBreakCount + 1 : 1;
        
    } catch (Exception $e) {
        error_log("DOC page count error: " . $e->getMessage());
        return 1; // Fallback
    }
}

$service = $_POST['service'] ?? '';
$description = $_POST['description'] ?? '';
$size = $_POST['size'] ?? '';
$paperType = $_POST['paper_type'] ?? '';
$colorType = $_POST['color_type'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 0);
$basePrice = (float)($_POST['price'] ?? 0.00); // Base price from frontend
$contactNumber = $_POST['contact_number'] ?? '';
$specialInstructions = $_POST['special_instructions'] ?? '';
$paymentMethod = $_POST['payment_method'] ?? 'online';

// Get user info from session
$userId = $_SESSION['user_id'] ?? null;
$fullName = $_SESSION['full_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

// Validate required fields
if (empty($service) || empty($size) || empty($paperType) || empty($colorType) || $quantity <= 0 || empty($contactNumber) || empty($fullName)) {
    header('Location: print.php?message=validation_error');
    exit();
}

// Handle multiple file uploads and count pages
$uploadedFiles = [];
$totalPages = 0;
$filePageCounts = [];

if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    $uploadDir = "uploads/printing/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Temporary directory for page counting
    $tempDir = "uploads/temp/";
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        try {
            if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                error_log("File upload error for file $key: " . $_FILES['images']['error'][$key]);
                continue;
            }
            
            $fileName = basename($_FILES['images']['name'][$key]);
            
            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueFileName = uniqid() . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $fileName);
            $targetFile = $uploadDir . $uniqueFileName;

            // Validate file type
            $allowedTypes = ['webp', 'jpeg', 'jpg', 'png', 'tiff', 'tif', 'pdf', 'docx', 'doc'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedTypes)) {
                error_log("File type not allowed: $fileExtension");
                continue;
            }

            // Check file size
            if ($_FILES['images']['size'][$key] > 10 * 1024 * 1024) {
                error_log("File size exceeds limit: " . $_FILES['images']['size'][$key]);
                continue;
            }

            // Move uploaded file
            if (move_uploaded_file($tmp_name, $targetFile)) {
                $uploadedFiles[] = $targetFile;
                
                // Count pages for this file
                error_log("Counting pages for: $fileName ($fileExtension) at $targetFile");
                $pageCount = countFilePages($targetFile, $fileName);
                error_log("Page count result: $pageCount");
                
                $filePageCounts[] = [
                    'file' => $fileName,
                    'pages' => $pageCount
                ];
                $totalPages += $pageCount;
                
                // Debug log
                error_log("File: $fileName | Detected pages: $pageCount | Total so far: $totalPages");
            } else {
                error_log("Failed to move uploaded file to: $targetFile");
            }
        } catch (Exception $e) {
            error_log("Exception during file processing: " . $e->getMessage());
            continue;
        }
    }
}

// Check if at least one file was uploaded successfully
if (empty($uploadedFiles)) {
    header('Location: print.php?message=validation_error&error=no_valid_files');
    exit();
}

// Calculate final price based on base price, quantity, and total pages
// Price calculation: base_price * quantity * total_pages
$finalPrice = (float)($_POST['price'] ?? 0.00);

// Convert arrays to JSON for database storage
$imagePathsJson = json_encode($uploadedFiles);
$pageCountsJson = json_encode($filePageCounts);

error_log("===== FILE UPLOAD SUMMARY =====");
error_log("Total uploaded files: " . count($uploadedFiles));
error_log("uploadedFiles array: " . print_r($uploadedFiles, true));
error_log("imagePathsJson (what will be stored): " . $imagePathsJson);
error_log("imagePathsJson type: " . gettype($imagePathsJson));
error_log("imagePathsJson length: " . strlen($imagePathsJson));
error_log("Total page count: $totalPages");
error_log("filePageCounts array: " . print_r($filePageCounts, true));
error_log("pageCountsJson (what will be stored): " . $pageCountsJson);

error_log("Attempting to insert order: Service=$service, Files=" . count($uploadedFiles) . ", TotalPages=$totalPages, Price=$finalPrice");

// Save to database with page count and final price
$sql = "INSERT INTO printing_orders (user_id, full_name, contact_number, service, size, paper_type, color_type, quantity, page_count, price, image_path, special_instruction, page_counts, payment_method)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Database prepare error: " . $conn->error);
    header('Location: print.php?message=order_failed');
    exit();
}

$binding = $stmt->bind_param("issssssiddssss", $userId, $fullName, $contactNumber, $service, $size, $paperType, $colorType, $quantity, $totalPages, $finalPrice, $imagePathsJson, $specialInstructions, $pageCountsJson, $paymentMethod);
if (!$binding) {
    error_log("Database bind_param error: " . $stmt->error);
    header('Location: print.php?message=order_failed');
    exit();
}

$execution = $stmt->execute();
if (!$execution) {
    error_log("Database execute error: " . $stmt->error);
    header('Location: print.php?message=order_failed');
    exit();
}

error_log("Order inserted successfully with ID: " . $stmt->insert_id);

if ($execution) {
    $orderId = $stmt->insert_id;
    
    // Prepare order details for email
    $orderDetails = [
        'id' => $orderId,
        'service' => $service,
        'size' => $size,
        'paper_type' => $paperType,
        'color_type' => $colorType,
        'quantity' => $quantity,
        'page_count' => $totalPages,
        'base_price' => $basePrice,
        'final_price' => $finalPrice,
        'status' => 'Pending',
        'contact_number' => $contactNumber,
        'special_instructions' => $specialInstructions,
        'file_count' => count($uploadedFiles),
        'file_names' => array_map('basename', $uploadedFiles),
        'file_page_counts' => $filePageCounts
    ];

    // Send confirmation email
    $emailResult = sendOrderConfirmationEmail($userEmail, $fullName, $orderDetails);

    error_log("Email result: " . ($emailResult['success'] ? 'Success' : 'Failed - ' . $emailResult['message']));

    if ($emailResult['success']) {
        error_log("Redirecting to: print.php?message=email_sent");
        header('Location: print.php?message=email_sent');
    } else {
        error_log('Email sending failed: ' . $emailResult['message']);
        error_log("Redirecting to: print.php?message=order_success_email_failed");
        header('Location: print.php?message=order_success_email_failed');
    }
    exit();
} else {
    // Clean up uploaded files if database insert fails
    error_log("Database insert failed, cleaning up files");
    foreach ($uploadedFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
            error_log("Deleted file: $file");
        }
    }
    error_log("Redirecting to: print.php?message=order_failed");
    header('Location: print.php?message=order_failed');
    exit();
}

$stmt->close();
$conn->close();
?>