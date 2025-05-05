<?php
session_start();
include('db.php');

// Get admin login data
$admin_username = $_POST['admin_username'];
$admin_password = $_POST['admin_password'];

// Admin authentication (using hardcoded credentials for simplicity)
$query = "SELECT * FROM login_ WHERE username = '$admin_username' AND password = '$admin_password'";
$result = mysqli_query($election_conn, $query);

if (mysqli_num_rows($result) > 0) {
    $_SESSION['admin_logged_in'] = true;
    header("Location: admin_dashboard.php");
} else {
    header("Location: pop_redirect.php");
}
?>
