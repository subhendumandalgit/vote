<?php
session_start();
session_unset();  // Unset all session variables
session_destroy();  // Destroy the session

// Set a session variable to display the logout message
$_SESSION['logout_message'] = "You have successfully logged out.";

// Redirect to a temporary logout page with the message
header("Location: logging_out.php");
exit();
?>
