<?php
session_start();
include('db.php');

// Redirect if voter is not logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: index.html");
    exit();
}

// Get and validate inputs
$voter_id = $_SESSION['voter_id'];
$event_id = $_POST['event_id'] ?? null;
$candidate_id = $_POST['candidate_id'] ?? null;
$room_id=$_SESSION['room_id'];
$_SESSION['room_id']=$room_id;
$_SESSION['vote_success'] = true;

if (!$event_id || !$candidate_id) {
    $_SESSION['error_message'] = "Invalid request. Please select both event and candidate.";
    header("Location: vote.php");
    exit();
}

// Escape inputs for security
$voter_id = mysqli_real_escape_string($election_conn, $voter_id);
$event_id = mysqli_real_escape_string($election_conn, $event_id);
$candidate_id = mysqli_real_escape_string($election_conn, $candidate_id);

// Step 1: Get room_id for the event (room_id = 0 is acceptable)
$room_query = "SELECT room_id FROM events WHERE event_id = '$event_id' LIMIT 1";
$room_result = mysqli_query($election_conn, $room_query);

if (!$room_result || mysqli_num_rows($room_result) === 0) {
    $_SESSION['error_message'] = "Event not found!";
    header("Location: vote.php");
    exit();
}

$room_data = mysqli_fetch_assoc($room_result);
$room_id = $room_data['room_id']; // Can be 0, and that's fine

// Step 2: Check if voter has already voted in this event
$check_query = "SELECT * FROM votes WHERE voter_id = '$voter_id' AND event_id = '$event_id'";
$check_result = mysqli_query($election_conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    $_SESSION['error_message'] = "You have already voted in this event!";
    header("Location: vote.php");
    exit();
}

// Step 3: Insert vote (even if room_id = 0)
$insert_query = "INSERT INTO votes (voter_id, event_id, candidate_id, room_id)
                 VALUES ('$voter_id', '$event_id', '$candidate_id', '$room_id')";

if (mysqli_query($election_conn, $insert_query)) {
    // $_SESSION['success_message'] = "Your vote has been submitted successfully!";
} else {
    $_SESSION['error_message'] = "Error submitting vote: " . mysqli_error($election_conn);
}

header("Location: enter.php"); // Redirect back to voting page
exit();
?>
