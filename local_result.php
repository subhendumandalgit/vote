<?php
session_start();
include('db.php');

if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

$voter_id = $_SESSION['voter_id'];

if (!isset($_GET['room_id'])) {
    die("Room ID not specified.");
}

$room_id = intval($_GET['room_id']);

$room_check_query = "SELECT room_name, created_by FROM rooms WHERE room_id = $room_id";
$room_check_result = mysqli_query($election_conn, $room_check_query);
if (!$room_check_result || mysqli_num_rows($room_check_result) === 0) {
    die("Room not found.");
}

$room = mysqli_fetch_assoc($room_check_result);
if ($room['created_by'] != $voter_id) {
    die("You do not have access to view this room's results.");
}

$room_name = $room['room_name'];

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
        SELECT c.candidate_name,
               COUNT(v.vote_id) AS vote_count
        FROM candidates c
        LEFT JOIN votes v ON c.candidate_id = v.candidate_id AND v.event_id = $event_id
        WHERE c.event_id = $event_id
        GROUP BY c.candidate_id
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
    $has_votes = false;

    while ($candidate_row = mysqli_fetch_assoc($event_results)) {
        $vote_count = $candidate_row['vote_count'];
        $vote_percentage = $total_votes > 0 ? ($vote_count / $total_votes) * 100 : 0;

        if ($vote_count > 0) {
            $has_votes = true;
        }

        $candidates_data[] = [
            'candidate_name' => $candidate_row['candidate_name'],
            'vote_count' => $vote_count,
            'vote_percentage' => number_format($vote_percentage, 2)
        ];
    }

    $event_data[] = [
        'event_id' => $event_id,
        'event_name' => $event_name,
        'total_votes' => $total_votes,
        'candidates' => $has_votes ? $candidates_data : []
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Voting Results</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            background-color: cyan;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
            text-align: center;
        }

        h2 {
            border: 5px solid #8FBC8F;
            padding: 0px 20px;
            background-color: #8FBC8F;
            font-size: 2.5em;
            color: #fff;
            text-transform: uppercase;
            margin-bottom: 40px;
            font-weight: bold;
        }

        h2:hover {
            color: black;
        }

        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
        }

        .card {
            background-size: cover;
            background-position: center;
            border-radius: 12px;
            box-shadow: 4px 20px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: 400;
            color: white;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
            position: relative;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.25);
        }

        .card h3 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #fff;
            font-weight: bold;
        }

        .card table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .card th, .card td {
            padding: 12px;
            text-align: left;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: 1px solid #ddd;
        }

        .card th {
            background-color: rgba(0, 0, 0, 0.7);
            color: #f1f1f1;
        }

        .card-footer {
            margin-top: 20px;
            font-weight: bold;
            font-size: 1.2em;
        }

        .no-results {
            text-align: center;
            color: #fff;
            font-size: 1.4em;
            background-color: rgba(255, 0, 0, 0.7);
            padding: 15px;
            border-radius: 10px;
        }

        .card-voted {
            background-color: rgba(0, 128, 0, 0.7);
        }

        .card-no-votes {
            background-color: rgba(255, 0, 0, 0.7);
        }

        .back-btn {
            margin-top: 20px;
            padding: 12px 20px;
            font-size: 1.2em;
            background-color: #6495ED;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .back-btn:hover {
            background-color: #45a049;
        }

        canvas {
            margin: 20px auto;
            display: block;
            max-width: 300px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Results for Room: <?php echo htmlspecialchars($room_name); ?></h2>

        <div class="cards-container">
            <?php foreach ($event_data as $event): ?>
                <div class="card <?php echo empty($event['candidates']) ? 'card-no-votes' : 'card-voted'; ?>">
                    <h3>Event: <?php echo htmlspecialchars($event['event_name']); ?></h3>

                    <?php if (empty($event['candidates'])): ?>
                        <div class="no-results">
                            <p>Vote not done yet</p>
                        </div>
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
                                <?php
                                $maxVotes = 0;
                                $winners = [];
                                foreach ($event['candidates'] as $candidate) {
                                    if ($candidate['vote_count'] > $maxVotes) {
                                        $maxVotes = $candidate['vote_count'];
                                        $winners = [$candidate['candidate_name']];
                                    } elseif ($candidate['vote_count'] == $maxVotes && $maxVotes > 0) {
                                        $winners[] = $candidate['candidate_name'];
                                    }

                                    echo "<tr>
                                            <td>{$candidate['candidate_name']}</td>
                                            <td>{$candidate['vote_count']}</td>
                                            <td>{$candidate['vote_percentage']}%</td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="card-footer">
                            <?php
                            echo count($winners) > 1
                                ? "Draw between: " . implode(", ", $winners)
                                : "Winner: " . $winners[0];
                            ?>
                        </div>
                        <canvas id="chart_<?php echo $event['event_id']; ?>"></canvas>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <a href="create_room.php" class="back-btn">← Back to My Rooms</a>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        <?php foreach ($event_data as $event): ?>
            <?php if (!empty($event['candidates'])): ?>
                new Chart(document.getElementById("chart_<?php echo $event['event_id']; ?>"), {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_column($event['candidates'], 'candidate_name')); ?>,
                        datasets: [{
                            label: 'Votes',
                            data: <?php echo json_encode(array_column($event['candidates'], 'vote_count')); ?>,
                            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#66BB6A', '#BA68C8', '#FF7043'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' },
                            title: { display: true, text: 'Vote Distribution' }
                        }
                    }
                });
            <?php endif; ?>
        <?php endforeach; ?>
    });
    </script>
</body>
</html>
