<?php
session_start();
include('db.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $voter_id = mysqli_real_escape_string($election_conn, $_POST['voter_id']);
    $password = mysqli_real_escape_string($election_conn, $_POST['password']);

    // Query to check if voter exists and the password matches
    $query = "SELECT * FROM voters WHERE voter_id = '$voter_id' AND password = '$password'";
    $result = mysqli_query($election_conn, $query);

    // Check if a match is found
    if (mysqli_num_rows($result) > 0) {
        // Fetch voter details
        $voter = mysqli_fetch_assoc($result);
        
        // Store voter ID and name in the session
        $_SESSION['voter_id'] = $voter['voter_id'];
        $_SESSION['voter_name'] = $voter['voter_name']; // Storing the voter's name in the session
        
        // Redirect to the voting page
        header("Location: vote.php");
        exit();
    } else {
        // Display an error if login fails
        $_SESSION['error_message'] = "Invalid Voter ID or Password!";
        header("Location: index.html"); // Redirect back to the login page
        exit();
    }
}
?>
