<?php
session_start();
require('vendor/autoload.php'); // Ensure mPDF is loaded

// Check if the admin is logged in
include('db.php'); // Include the database connection

// Query to fetch all events
$events_query = "SELECT event_id, event_name FROM events WHERE room_id=0";
$events_result = mysqli_query($election_conn, $events_query);

// Check if the query was successful
if (!$events_result) {
    die("Error fetching events: " . mysqli_error($election_conn));
}

// Initialize the event data
$event_data = [];

// Fetch results for each event
while ($row = mysqli_fetch_assoc($events_result)) {
    $event_id = $row['event_id'];
    $event_name = $row['event_name'];

    // Query to get all candidates for this specific event
    $event_results_query = "
        SELECT candidates.candidate_name,  
               COUNT(votes.vote_id) AS vote_count
        FROM candidates
        LEFT JOIN votes ON votes.candidate_id = candidates.candidate_id AND votes.event_id = $event_id
        WHERE candidates.event_id = $event_id  
        GROUP BY candidates.candidate_id
        ORDER BY vote_count DESC";

    $event_results = mysqli_query($election_conn, $event_results_query);

    // Get the total number of votes for the event
    $total_votes_query = "
        SELECT COUNT(vote_id) AS total_votes 
        FROM votes 
        WHERE event_id = $event_id
    ";
    $total_votes_result = mysqli_query($election_conn, $total_votes_query);
    $total_votes_row = mysqli_fetch_assoc($total_votes_result);
    $total_votes = $total_votes_row['total_votes'];

    // Prepare candidates data for this event
    $candidates_data = [];
    while ($candidate_row = mysqli_fetch_assoc($event_results)) {
        $vote_percentage = $total_votes > 0 ? ($candidate_row['vote_count'] / $total_votes) * 100 : 0;

        $candidates_data[] = [
            'candidate_name' => $candidate_row['candidate_name'],
            'vote_count' => $candidate_row['vote_count'],
            'vote_percentage' => number_format($vote_percentage, 2) // Format percentage to 2 decimal places
        ];
    }

    // Store the event data in the array
    $event_data[$event_name] = [
        'total_votes' => $total_votes,
        'candidates' => $candidates_data
    ];
}

// Create mPDF instance
$mpdf = new \Mpdf\Mpdf();

// Start building the HTML content for the PDF
$html = '<h2 style="text-align: center; background-color:#8FBC8F; color: white; padding: 10px;">Voting Results</h2>';

foreach ($event_data as $event_name => $event_info) {
    // Add event name as header
    $html .= '<h3>Event: ' . $event_name . '</h3>';

        // Create a table to display the candidates and their votes for this specific event
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Candidate Name</th>
                            <th>Vote Count</th>
                            <th>Vote Percentage</th>
                        </tr>
                    </thead>
                    <tbody>';

        // Loop through each candidate and display their details for the specific event
    if (empty($event_info['candidates'])) {
        $html.= '<tr>
                    <td>No Candidates are present in this event</td>
                </tr>';
    }
    else{
        foreach ($event_info['candidates'] as $candidate) {
            $html .= '<tr>
                        <td>' . $candidate['candidate_name'] . '</td>
                        <td>' . $candidate['vote_count'] . '</td>
                        <td>' . $candidate['vote_percentage'] . '%</td>
                    </tr>';
        }
        
    }

        $html .= '</tbody></table>';

        // Determine the winner
        $maxVotes = 0;
        $winners = [];
        foreach ($event_info['candidates'] as $candidate) {
            if ($candidate['vote_count'] > $maxVotes) {
                $maxVotes = $candidate['vote_count'];
                $winners = [$candidate['candidate_name']];
            } elseif ($candidate['vote_count'] == $maxVotes) {
                $winners[] = $candidate['candidate_name'];
            }
        }
        if (empty($event_info['candidates'])) {
            
        }
        else{
        // Display winner(s)
        if (count($winners) > 1) {
            $html .= '<p><strong>Draw between: ' . implode(", ", $winners) . '</strong></p>';
        } else {
            $html .= '<p><strong>Winner: ' . $winners[0] . '</strong></p>';
        }
    }
}

// Output the PDF
$mpdf->WriteHTML($html);
$mpdf->Output('voting_results.pdf', 'i'); 
?>
