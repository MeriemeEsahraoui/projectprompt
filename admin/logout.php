<?php
// logout.php - Logout Handler

// Include database configuration (this will start the session)
require_once 'include/config.php';

// Destroy all session data
session_unset();
session_destroy();

// Start a new session for the flash message
session_start();
$_SESSION['logout_message'] = 'You have been successfully logged out.';

// Redirect to login page
header('Location: login.php');
exit;
?>