<?php
session_start();
include('db.php'); // Include your database connection

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle the removal of a candidate
if (isset($_POST['remove_candidate'])) {
    // Check if candidate_id is set and valid
    if (isset($_POST['candidate_id']) && !empty($_POST['candidate_id'])) {
        $candidate_id = $_POST['candidate_id'];

        // Sanitize the candidate_id to prevent SQL injection
        $candidate_id = mysqli_real_escape_string($election_conn, $candidate_id);

        // Remove the selected candidate from the database
        $remove_candidate_query = "DELETE FROM candidates WHERE candidate_id = $candidate_id";

        // Execute the query
        if (mysqli_query($election_conn, $remove_candidate_query)) {
            // Success message
            $_SESSION['success_message'] = "Candidate has been removed successfully.";
        } else {
            // Error message
            $_SESSION['error_message'] = "Error: " . mysqli_error($election_conn);
        }
    } else {
        // Error if no candidate is selected
        $_SESSION['error_message'] = "No candidate selected.";
    }

    // Redirect to the main page after handling the request
    header("Location: admin_dashboard.php"); // Use your main page
    exit();
}

// Handle fetching candidates for the selected event via AJAX
if (isset($_POST['event_id'])) {
    // Make sure event_id is set
    if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
        $event_id = $_POST['event_id'];

        // Sanitize the event_id to prevent SQL injection
        $event_id = mysqli_real_escape_string($election_conn, $event_id);

        // Query to get candidates for the selected event
        $candidate_query = "SELECT candidate_id, candidate_name FROM candidates WHERE event_id = $event_id";
        $candidate_result = mysqli_query($election_conn, $candidate_query);

        // Prepare candidates for JSON response
        $candidates = [];
        while ($row = mysqli_fetch_assoc($candidate_result)) {
            $candidates[] = $row;
        }

        // Return candidates as a JSON response
        echo json_encode($candidates);
    } else {
        // In case event_id is not provided or is empty
        echo json_encode([]);
    }
    exit();
}
?>
