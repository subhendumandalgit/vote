<?php
session_start();
include('db.php');

if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['room_id'])) {
    die("Room ID not set. Please enter a room first.");
}

$voter_id = $_SESSION['voter_id'];
$room_id = $_SESSION['room_id'];

// Fetch all events in this room
$events_query = "SELECT event_id, event_name FROM events WHERE room_id = '$room_id'";
$events_result = mysqli_query($election_conn, $events_query);

$event_data = [];
$all_voted = true;

while ($row = mysqli_fetch_assoc($events_result)) {
    $event_id = $row['event_id'];
    $event_name = $row['event_name'];

    // Get candidates & their votes
    $event_results_query = "
        SELECT c.candidate_name,
               COUNT(v.vote_id) AS vote_count
        FROM candidates c
        LEFT JOIN votes v ON v.candidate_id = c.candidate_id AND v.event_id = '$event_id'
        WHERE c.event_id = '$event_id'
        GROUP BY c.candidate_id
        ORDER BY vote_count DESC
    ";
    $event_results = mysqli_query($election_conn, $event_results_query);

    // Get total votes
    $total_votes_query = "SELECT COUNT(vote_id) AS total_votes FROM votes WHERE event_id = '$event_id'";
    $total_votes_result = mysqli_query($election_conn, $total_votes_query);
    $total_votes = mysqli_fetch_assoc($total_votes_result)['total_votes'];

    if ($total_votes == 0) {
        $all_voted = false;
    }

    $candidates_data = [];
    while ($candidate = mysqli_fetch_assoc($event_results)) {
        $vote_percentage = $total_votes > 0 ? ($candidate['vote_count'] / $total_votes) * 100 : 0;

        $candidates_data[] = [
            'candidate_name' => $candidate['candidate_name'],
            'vote_count' => $candidate['vote_count'],
            'vote_percentage' => number_format($vote_percentage, 2)
        ];
    }

    $event_data[] = [
        'event_id' => $event_id,
        'event_name' => $event_name,
        'total_votes' => $total_votes,
        'candidates' => $candidates_data
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Voting Results</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Global Reset and Layout */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Arial', sans-serif;
}

body {
    background-color: #e0ffff;
    margin: 0;
    padding: 0;
    font-size: 16px;
}

/* Container Styling */
.container {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
    text-align: center;
}

/* Heading */
h2 {
    font-size: 2.5em;
    background-color: #8FBC8F;
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

/* Cards Layout */
.cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    margin-top: 30px;
}

/* Event Card Base */
.card {
    background-color: rgba(0, 128, 0, 0.7);
    color: white;
    border-radius: 10px;
    padding: 20px;
    width: 450px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    margin-bottom: 30px;
}

/* Red background for cards without votes */
.card-no-votes {
    background-color: rgba(255, 0, 0, 0.7);
}

/* Vote Table */
table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    background-color: rgba(0, 0, 0, 0.4);
    border: 1px solid #ccc;
}

/* Highlight rows with no votes */
.no-votes {
    background-color: red;
    color: white;
    font-weight: bold;
}

/* Footer section on each card */
.card-footer {
    margin-top: 15px;
    font-weight: bold;
}

/* Message when no voting has started */
.no-results {
    font-weight: bold;
    font-size: 1.1em;
}

/* Chart Canvas */
canvas {
    margin: 20px auto;
    display: block;
    max-width: 300px;
}

/* Back Button */
.back-btn {
    margin-top: 35px;
    padding: 12px 25px;
    font-size: 1.1em;
    background-color: #6495ED;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.back-btn:hover {
    background-color: #45a049;
}

    </style>
</head>
<body>
<div class="container">
    <h2>Live Voting Results</h2>

    <div class="cards-container">
        <?php foreach ($event_data as $event): ?>
            <div class="card <?= $event['total_votes'] == 0 ? 'card-no-votes' : ''; ?>">
                <h3><?= htmlspecialchars($event['event_name']) ?></h3>
                <?php if ($event['total_votes'] == 0): ?>
                    <div class="no-results">Voting not start yet</div>
                <?php else: ?>
                    <table>
                        <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Votes</th>
                            <th>Percentage</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $maxVotes = 0;
                        $winners = [];
                        foreach ($event['candidates'] as $cand) {
                            $class = $cand['vote_count'] == 0 ? 'no-votes' : '';
                            echo "<tr class='$class'>
                                    <td>{$cand['candidate_name']}</td>
                                    <td>{$cand['vote_count']}</td>
                                    <td>{$cand['vote_percentage']}%</td>
                                  </tr>";
                            if ($cand['vote_count'] > $maxVotes) {
                                $maxVotes = $cand['vote_count'];
                                $winners = [$cand['candidate_name']];
                            } elseif ($cand['vote_count'] == $maxVotes) {
                                $winners[] = $cand['candidate_name'];
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                    <div class="card-footer">
                        <?= count($winners) > 1 ? "Draw between: " . implode(", ", $winners) : "Winner: " . $winners[0]; ?>
                    </div>
                    <canvas id="chart_<?= $event['event_id'] ?>" height="200"></canvas>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <a href="enter.php" class="back-btn">← Back to Home</a>
</div>

<script>
function checkVotingStatus() {
    fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newButtonArea = doc.getElementById('result-button-container');
            const currentButtonArea = document.getElementById('result-button-container');
            if (newButtonArea && currentButtonArea) {
                currentButtonArea.innerHTML = newButtonArea.innerHTML;
            }
        });
}

// Refresh every 10 seconds
setInterval(checkVotingStatus, 10000);

<?php foreach ($event_data as $event): if ($event['total_votes'] > 0): ?>
    new Chart(document.getElementById("chart_<?= $event['event_id'] ?>"), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($event['candidates'], 'candidate_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($event['candidates'], 'vote_count')) ?>,
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
<?php endif; endforeach; ?>
</script>
</body>
</html>
