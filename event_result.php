<?php
session_start();
include('db.php');

// Check login
if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

// Validate event_id
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    die("Invalid event.");
}

$event_id = (int) $_GET['event_id'];

// Fetch event name
$event_query = "SELECT event_name FROM events WHERE event_id = ?";
$stmt = mysqli_prepare($election_conn, $event_query);
mysqli_stmt_bind_param($stmt, 'i', $event_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $event_name);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$event_name) {
    die("Event not found.");
}

// Fetch total votes
$total_votes_query = "SELECT COUNT(*) AS total_votes FROM votes WHERE event_id = ?";
$stmt_total = mysqli_prepare($election_conn, $total_votes_query);
mysqli_stmt_bind_param($stmt_total, 'i', $event_id);
mysqli_stmt_execute($stmt_total);
$result_total = mysqli_stmt_get_result($stmt_total);
$total_votes_row = mysqli_fetch_assoc($result_total);
$total_votes = $total_votes_row['total_votes'] ?? 0;
mysqli_stmt_close($stmt_total);

// Fetch candidates and vote counts
$candidates_query = "
    SELECT c.candidate_name, COUNT(v.vote_id) AS vote_count
    FROM candidates c
    LEFT JOIN votes v ON c.candidate_id = v.candidate_id
    WHERE c.event_id = ?
    GROUP BY c.candidate_id
    ORDER BY vote_count DESC
";
$stmt_cand = mysqli_prepare($election_conn, $candidates_query);
mysqli_stmt_bind_param($stmt_cand, 'i', $event_id);
mysqli_stmt_execute($stmt_cand);
$result_cand = mysqli_stmt_get_result($stmt_cand);

$candidates = [];
$maxVotes = 0;
$winners = [];

while ($row = mysqli_fetch_assoc($result_cand)) {
    $vote_count = $row['vote_count'];
    $percentage = $total_votes > 0 ? number_format(($vote_count / $total_votes) * 100, 2) : 0;

    $row['vote_percentage'] = $percentage;
    $candidates[] = $row;

    if ($vote_count > $maxVotes) {
        $maxVotes = $vote_count;
        $winners = [$row['candidate_name']];
    } elseif ($vote_count == $maxVotes) {
        $winners[] = $row['candidate_name'];
    }
}
mysqli_stmt_close($stmt_cand);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Result</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }
        .container {
            background-color: cyan;
            max-width: 900px;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        h2 {
            background-color: #8FBC8F;
            padding: 15px;
            color: white;
            font-size: 2.2em;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .card {
            background-color: <?= $total_votes > 0 ? 'rgba(0, 128, 0, 0.7)' : 'rgba(255, 0, 0, 0.7)' ?>;
            color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            background-color: rgba(0,0,0,0.3);
            color: white;
            border: 1px solid #ddd;
        }
        th {
            background-color: rgba(0,0,0,0.5);
        }
        .card-footer {
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
        .no-votes {
            padding: 20px;
            background-color: rgba(255, 0, 0, 0.5);
            border-radius: 8px;
            font-size: 1.2em;
        }
        .back-btn {
            margin-top: 30px;
            padding: 12px 20px;
            font-size: 1.1em;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
        }
        .back-btn:hover {
            background-color: #1abc9c;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Results for Event: <?= htmlspecialchars($event_name); ?></h2>

    <div class="card">
        <?php if (empty($candidates)): ?>
            <div class="no-votes">No candidates available for this event.</div>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Candidate Name</th>
                    <th>Vote Count</th>
                    <th>Vote Percentage</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($candidates as $cand): ?>
                    <tr>
                        <td><?= htmlspecialchars($cand['candidate_name']); ?></td>
                        <td><?= $cand['vote_count']; ?></td>
                        <td><?= $cand['vote_percentage']; ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="card-footer">
                <?php
                if ($total_votes == 0) {
                    echo "Voting has not started yet.";
                } elseif (count($winners) > 1) {
                    echo "Draw between: " . implode(", ", $winners);
                } else {
                    echo "Winner: " . $winners[0];
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
    <br>
    <a href="create_room.php" class="back-btn">← Back to Rooms</a>
</div>
</body>
</html>
