<?php
session_start();
unset($_SESSION['room_id']);// Unset all session variables

// Set a session variable to display the logout message

// Redirect to a temporary logout page with the message
header("Location: enter.php");
exit();
?>
