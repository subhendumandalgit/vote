<?php
session_start();
include('db.php');

// Check if the event_id is passed in the query string
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Fetch candidates for the given event_id
    $query = "SELECT candidate_id, candidate_name FROM candidates WHERE event_id = ?";
    if ($stmt = mysqli_prepare($election_conn, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $event_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $candidates = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $candidates[] = $row;
        }

        // Return the candidates as a JSON response
        echo json_encode($candidates);
    } else {
        echo json_encode([]); // Return an empty array if no candidates found or query failed
    }
}
?>
