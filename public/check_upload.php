<?php
// Diagnostic file to test file upload configuration and permissions

// Current Date and Time (UTC)
$datetime = '2026-04-11 04:52:52';

// User's Login
$user_login = 'jmichaelfelix1-byte';

// File upload test
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['upload_file'])) {
        // Check for upload errors
        if ($_FILES['upload_file']['error'] === 0) {
            // Check file size
            if ($_FILES['upload_file']['size'] > 0) {
                // Move uploaded file to the desired directory
                $destination = 'uploads/' . basename($_FILES['upload_file']['name']);
                if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $destination)) {
                    echo "File uploaded successfully: " . htmlspecialchars($destination);
                } else {
                    echo "Error moving the uploaded file.";
                }
            } else {
                echo "Uploaded file is empty.";
            }
        } else {
            echo "Error during file upload: " . $_FILES['upload_file']['error'];
        }
    }
} else {
    // Display upload form
    echo '<form action="" method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="upload_file" required>'; 
    echo '<button type="submit">Upload</button>';
    echo '</form>';
}
?>