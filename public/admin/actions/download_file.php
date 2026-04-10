<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File path is required']);
    exit();
}

$file_path = urldecode(trim($_GET['file']));
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

// Debug: Log the incoming file path
error_log("===== DOWNLOAD FILE DEBUG =====");
error_log("Original file_path: " . $file_path);
error_log("Order ID: " . ($order_id ?? 'not provided'));
error_log("File path length: " . strlen($file_path));
error_log("File path hex: " . bin2hex($file_path));

// Function to get order filename from database
function getOrderFilename($conn, $order_id) {
    if (!$order_id) return null;
    
    $stmt = $conn->prepare("SELECT full_name, order_date FROM printing_orders WHERE id = ?");
    if (!$stmt) {
        error_log("Database prepare error: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Order not found: $order_id");
        $stmt->close();
        return null;
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    // Format: username_YYYYMMDD (e.g., john_doe_20260325)
    $username = strtolower(str_replace([' ', '.', ',', '@'], '_', $row['full_name']));
    $date = date('Ymd', strtotime($row['order_date']));
    $filename = $username . '_' . $date;
    
    error_log("Generated filename: $filename");
    return $filename;
}

// Try to decode as JSON (multiple files)
$files_to_download = [];
$decoded = json_decode($file_path, true);

error_log("JSON decode result: " . ($decoded === null ? 'null' : (is_array($decoded) ? 'array with ' . count($decoded) . ' items' : 'not array')));
if (is_array($decoded)) {
    error_log("Decoded array contents:");
    foreach ($decoded as $idx => $f) {
        error_log("  [$idx]: " . $f);
    }
}

if (is_array($decoded) && count($decoded) > 0) {
    // It's a JSON array of multiple files
    $files_to_download = $decoded;
} else {
    // It's a single file path
    $files_to_download = [$file_path];
}

// Debug: Log decoded files
error_log("Files to download count: " . count($files_to_download));
foreach ($files_to_download as $i => $f) {
    error_log("File $i: " . $f);
}

// Sanitize all file paths to prevent directory traversal
$files_to_download = array_map(function($path) {
    $path = trim($path);
    // Remove directory traversal attempts
    $path = str_replace(['../', '..\\', '\\'], '', $path);
    return $path;
}, $files_to_download);

// Helper function to resolve file paths
function resolvePath($file_path) {
    // Normalize the path - handle both forward and backward slashes
    $file_path = str_replace('\\', '/', $file_path);
    
    // Remove any escaped slashes that might come from JSON encoding
    $file_path = str_replace('\/', '/', $file_path);
    
    error_log("Attempting to resolve: " . $file_path);
    
    // Check multiple possible locations
    $possible_paths = [
        // Path as-is from database (relative to admin/actions/)
        '../../' . $file_path,
        // Path relative to webroot (public/)
        '../' . $file_path,
        // Path as absolute
        $file_path
    ];
    
    foreach ($possible_paths as $path) {
        // Normalize path separators for the OS
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        
        $resolved = @realpath($path);
        if ($resolved && file_exists($resolved) && is_readable($resolved)) {
            error_log("Successfully resolved path: $file_path -> $resolved");
            return $resolved;
        } else {
            error_log("Failed to resolve at: $path (realpath returned: " . ($resolved ?: 'false') . ", exists: " . (file_exists($path) ? 'true' : 'false') . ")");
        }
    }
    
    error_log("Could not resolve path: $file_path");
    return null;
}

// Filter out empty paths
$files_to_download = array_filter($files_to_download);

if (empty($files_to_download)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid files found']);
    exit();
}

// If only one file, download it directly
if (count($files_to_download) === 1) {
    $file_to_check = $files_to_download[0];
    $full_path = resolvePath($file_to_check);
    
    // Check if file exists and is readable
    if (!$full_path) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit();
    }
    
    // Get file information
    $file_name = basename($full_path);
    $file_size = filesize($full_path);
    
    // Set appropriate headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output the file
    readfile($full_path);
    exit();
}

// Multiple files - create an archive (ZIP preferred, fallback to TAR.GZ)
$file_count = 0;
$missing_files = [];

// Resolve all files first
$resolved_files = [];
foreach ($files_to_download as $file_path) {
    $full_path = resolvePath($file_path);
    if ($full_path) {
        $resolved_files[] = $full_path;
        $file_count++;
    } else {
        $missing_files[] = $file_path;
    }
}

if ($file_count === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No valid files found to download']);
    exit();
}

// Try ZipArchive first
if (class_exists('ZipArchive')) {
    $zip_file = tempnam(sys_get_temp_dir(), 'order_') . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create ZIP archive']);
        exit();
    }

    foreach ($resolved_files as $full_path) {
        $file_name = basename($full_path);

        // Handle duplicate filenames inside the archive
        $zip_path = $file_name;
        $counter = 1;
        while ($zip->locateName($zip_path) !== false) {
            $pathinfo = pathinfo($file_name);
            $zip_path = $pathinfo['filename'] . '_' . $counter . (isset($pathinfo['extension']) && $pathinfo['extension'] !== '' ? '.' . $pathinfo['extension'] : '');
            $counter++;
        }

        $zip->addFile($full_path, $zip_path);
    }

    $zip->close();

    // Generate filename based on order details
    $zip_filename = 'order_files.zip';
    if ($order_id) {
        $custom_filename = getOrderFilename($conn, $order_id);
        if ($custom_filename) {
            $zip_filename = $custom_filename . '.zip';
        }
    }

    // Send the ZIP file for download
    $zip_size = filesize($zip_file);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . $zip_size);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (ob_get_level()) {
        ob_end_clean();
    }

    readfile($zip_file);
    unlink($zip_file);
    exit();

} elseif (class_exists('PharData') && ini_get('phar.readonly') != '1') {
    // Fallback: create a tar.gz using PharData
    $base = tempnam(sys_get_temp_dir(), 'order_');
    if (file_exists($base)) unlink($base);
    $tarFile = $base . '.tar';
    $gzFile = $base . '.tar.gz';

    try {
        $phar = new PharData($tarFile);
        foreach ($resolved_files as $full_path) {
            $file_name = basename($full_path);
            // Ensure unique names inside the archive
            $entryName = $file_name;
            $counter = 1;
            while ($phar->offsetExists($entryName)) {
                $pathinfo = pathinfo($file_name);
                $entryName = $pathinfo['filename'] . '_' . $counter . (isset($pathinfo['extension']) && $pathinfo['extension'] !== '' ? '.' . $pathinfo['extension'] : '');
                $counter++;
            }
            $phar->addFile($full_path, $entryName);
        }

        // Compress to gzip (creates .tar.gz)
        $phar->compress(Phar::GZ);
        // compress creates file with .gz appended; cleanup .tar
        unset($phar);
        if (file_exists($tarFile)) unlink($tarFile);

        // Send tar.gz
        $gz_size = filesize($gzFile);
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="order_files.tar.gz"');
        header('Content-Length: ' . $gz_size);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        if (ob_get_level()) {
            ob_end_clean();
        }

        readfile($gzFile);
        unlink($gzFile);
        exit();

    } catch (Exception $e) {
        if (file_exists($tarFile)) unlink($tarFile);
        if (file_exists($gzFile)) unlink($gzFile);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create archive: ' . $e->getMessage()]);
        exit();
    }

} else {
    // Neither ZipArchive nor PharData (writable) is available
    http_response_code(500);
    $msg = 'Server cannot create archives. Please enable the PHP Zip extension or allow Phar writing (phar.readonly=0).';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}
?>

