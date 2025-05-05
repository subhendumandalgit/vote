<?php
session_start();
require('vendor/autoload.php'); // Load mPDF
include('db.php'); // DB connection

if (!isset($_SESSION['voter_id'])) {
    die("Unauthorized access. Please log in.");
}

$current_user_id = $_SESSION['voter_id'];

// ✅ Get room ID
if (!isset($_GET['room_id'])) {
    die("Room ID not specified.");
}

$room_id = intval($_GET['room_id']);

// ✅ Verify ownership of the room
$room_check_query = "SELECT room_name, created_by FROM rooms WHERE room_id = $room_id";
$room_check_result = mysqli_query($election_conn, $room_check_query);
if (!$room_check_result || mysqli_num_rows($room_check_result) == 0) {
    die("Room not found.");
}
$room = mysqli_fetch_assoc($room_check_result);
if ($room['created_by'] != $current_user_id) {
    die("Access denied for this room.");
}

$room_name = $room['room_name'];

// ✅ Fetch events in this room
$events_query = "
    SELECT event_id, event_name
    FROM events
    WHERE room_id = $room_id
";

$events_result = mysqli_query($election_conn, $events_query);
if (!$events_result) {
    die("Error fetching events: " . mysqli_error($election_conn));
}

$event_data = [];
while ($row = mysqli_fetch_assoc($events_result)) {
    $event_id = $row['event_id'];
    $event_name = $row['event_name'];

    $event_results_query = "
        SELECT candidates.candidate_name,  
               COUNT(votes.vote_id) AS vote_count
        FROM candidates
        LEFT JOIN votes ON votes.candidate_id = candidates.candidate_id AND votes.event_id = $event_id
        WHERE candidates.event_id = $event_id  
        GROUP BY candidates.candidate_id
        ORDER BY vote_count DESC
    ";
    $event_results = mysqli_query($election_conn, $event_results_query);

    $total_votes_query = "
        SELECT COUNT(vote_id) AS total_votes 
        FROM votes 
        WHERE event_id = $event_id
    ";
    $total_votes_result = mysqli_query($election_conn, $total_votes_query);
    $total_votes_row = mysqli_fetch_assoc($total_votes_result);
    $total_votes = $total_votes_row['total_votes'];

    $candidates_data = [];
    while ($candidate_row = mysqli_fetch_assoc($event_results)) {
        $vote_percentage = $total_votes > 0 ? ($candidate_row['vote_count'] / $total_votes) * 100 : 0;

        $candidates_data[] = [
            'candidate_name' => $candidate_row['candidate_name'],
            'vote_count' => $candidate_row['vote_count'],
            'vote_percentage' => number_format($vote_percentage, 2)
        ];
    }

    $event_data[$event_name] = [
        'total_votes' => $total_votes,
        'candidates' => $candidates_data
    ];
}

// ✅ Generate PDF
$mpdf = new \Mpdf\Mpdf();

$html = '<h2 style="text-align: center; background-color:#8FBC8F; color: white; padding: 10px;">
            Voting Results: ' . htmlspecialchars($room_name) . '
        </h2>';

if (empty($event_data)) {
    $html .= '<p>No events found in this room.</p>';
} else {
    foreach ($event_data as $event_name => $event_info) {
        $html .= '<h3 style="background-color:#f0f0f0; padding:8px;">Event: ' . htmlspecialchars($event_name) . '</h3>';
        $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="margin-bottom: 20px; font-family: sans-serif;">
                    <thead style="background-color: #e0e0e0;">
                        <tr>
                            <th>Candidate Name</th>
                            <th>Vote Count</th>
                            <th>Vote Percentage</th>
                        </tr>
                    </thead>
                    <tbody>';

        if (empty($event_info['candidates'])) {
            $html .= '<tr><td colspan="3">No votes or candidates available.</td></tr>';
        } else {
            $maxVotes = 0;
            $winners = [];

            foreach ($event_info['candidates'] as $candidate) {
                if ($candidate['vote_count'] > $maxVotes) {
                    $maxVotes = $candidate['vote_count'];
                    $winners = [$candidate['candidate_name']];
                } elseif ($candidate['vote_count'] == $maxVotes) {
                    $winners[] = $candidate['candidate_name'];
                }

                $html .= '<tr>
                            <td>' . htmlspecialchars($candidate['candidate_name']) . '</td>
                            <td>' . $candidate['vote_count'] . '</td>
                            <td>' . $candidate['vote_percentage'] . '%</td>
                          </tr>';
            }

            // Winner section
            $html .= '</tbody></table>';
            // Check if there are any candidates with votes
            if ($maxVotes == 0) {
                $html .= '<p style="font-weight:bold;">No votes received.</p>';
            } else {
                $html .= '<p style="font-weight:bold;">' .
                            (count($winners) > 1 ? 'Draw between: ' . implode(", ", $winners) : 'Winner: ' . $winners[0]) .
                         '</p>';
            }
            
        }
    }
}

// ✅ Output PDF
$filename = 'Voting_Results_Room_' . $room_id . '.pdf';
$mpdf->WriteHTML($html);
$mpdf->Output($filename, 'I');
?>
