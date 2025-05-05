<?php
session_start();
include('db.php');

// Get form data
$voter_id = $_POST['voter_id'];
$phone_number = $_POST['phone_number'];

// Check if voter exists and has not voted
$query = "SELECT * FROM voters WHERE voter_id = '$voter_id' AND phone_number = '$phone_number'";
$result = mysqli_query($election_conn, $query);
$voter = mysqli_fetch_assoc($result);

if ($voter) {
        // Store voter information in session and redirect to the voting page
        $_SESSION['voter_id'] = $voter['voter_id'];
        header('Location: voter_dashboard.php'); // Redirect to the voting page
        exit();
} else {
    // Voter not found in the database
   header('location:pop_redirect.php');
}
?>
