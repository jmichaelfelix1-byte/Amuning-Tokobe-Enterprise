<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to signin page with logged out message
header('Location: signin.php?message=logged_out');
exit();
?>
