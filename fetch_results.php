<?php
session_start();

// Check if the admin is logged in, otherwise redirect to login page


include('db.php'); // Include the database connection

// Query to fetch all events
$events_query = "SELECT event_id, event_name FROM events";
$events_result = mysqli_query($election_conn, $events_query);

// Check if the query was successful
if (!$events_result) {
    die("Error fetching events: " . mysqli_error($election_conn));
}

// Initialize the event data
$event_data = [];

// Fetch results for all events
while ($row = mysqli_fetch_assoc($events_result)) {
    $event_id = $row['event_id'];
    $event_name = $row['event_name'];

    // Query to get candidates and votes for each event
    $event_results_query = "
        SELECT candidates.candidate_name, candidates.party_name, 
               COUNT(votes.vote_id) AS vote_count
        FROM votes
        LEFT JOIN candidates ON votes.candidate_id = candidates.candidate_id
        WHERE votes.event_id = $event_id
        GROUP BY candidates.candidate_id
        ORDER BY vote_count DESC
    ";

    $event_results = mysqli_query($election_conn, $event_results_query);

    $candidates_data = [];
    while ($candidate_row = mysqli_fetch_assoc($event_results)) {
        $candidates_data[] = [
            'candidate_name' => $candidate_row['candidate_name'],
            'party_name' => $candidate_row['party_name'],
            'vote_count' => $candidate_row['vote_count']
        ];
    }

    // Store data for this event
    $event_data[$event_name] = $candidates_data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Results</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Your existing styles */
    </style>
</head>
<body>
    <div class="container">
        <h2>Voting Results</h2>

        <!-- Display Event Results in Cards -->
        <div class="cards-container">
            <?php if (!empty($event_data)): ?>
                <?php foreach ($event_data as $event_name => $candidates): ?>
                    <div class="card <?php echo empty($candidates) ? 'card-no-votes' : 'card-voted'; ?>">
                        <h3>Event: <?php echo $event_name; ?></h3>
                        <?php if (empty($candidates)): ?>
                            <div class="no-results">
                                <p>Vote not done yet</p>
                            </div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Candidate Name</th>
                                        <th>Party Name</th>
                                        <th>Vote Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $maxVotes = 0;
                                    $winners = [];
                                    foreach ($candidates as $candidate) {
                                        if ($candidate['vote_count'] > $maxVotes) {
                                            $maxVotes = $candidate['vote_count'];
                                            $winners = [$candidate['candidate_name']];
                                        } elseif ($candidate['vote_count'] == $maxVotes) {
                                            $winners[] = $candidate['candidate_name'];
                                        }

                                        echo "<tr>
                                                <td>{$candidate['candidate_name']}</td>
                                                <td>{$candidate['party_name']}</td>
                                                <td>{$candidate['vote_count']}</td>
                                              </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <div class="card-footer">
                                <?php
                                    if (count($winners) > 1) {
                                        echo "Draw between: " . implode(", ", $winners);
                                    } else {
                                        echo "Winner: " . $winners[0];
                                    }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No voting data available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
