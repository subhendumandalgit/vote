<?php
session_start();
include('db.php');

// Check if the voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: index.html"); // Redirect to login page if not logged in
    exit();
}

// Fetch voter name from the session
$voter_id = $_SESSION['voter_id']; 
$stmt = $election_conn->prepare("SELECT voter_name FROM voters WHERE voter_id = ?");
$stmt->bind_param("s", $voter_id);
$stmt->execute();
$result = $stmt->get_result();
$name = $result->fetch_assoc();

// Step 1: Fetch Room Data and Events based on room_id
if (!isset($_SESSION['room_id']) || $_SESSION['room_id'] == 0) {
    $room_id = isset($_POST['room_id']) ? $_POST['room_id'] : null;
} else {
    $room_id = $_SESSION['room_id'];
}
$_SESSION['room_id'] = $room_id;


// If room_id is provided
$error = false; // Add this at the top of PHP before HTML
if ($room_id) {
    $room_stmt = $election_conn->prepare("SELECT * FROM rooms WHERE room_id = ?");
    $room_stmt->bind_param("s", $room_id);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();

    if (mysqli_num_rows($room_result) > 0) {
        // Fetch events based on the room_id
        $events_stmt = $election_conn->prepare("SELECT event_id, event_name FROM events WHERE room_id = ?");
        $events_stmt->bind_param("s", $room_id);
        $events_stmt->execute();
        $events_result = $events_stmt->get_result();
    } else {
        $error = true; // Set flag instead of redirect
        unset($_SESSION['room_id']); // Optional: Clear invalid session
        $room_id = null; // Reset room_id to show input again
    }
}


// Fetch the events the voter has already voted for
$voted_events_stmt = $election_conn->prepare("SELECT event_id FROM votes WHERE voter_id = ?");
$voted_events_stmt->bind_param("s", $voter_id);
$voted_events_stmt->execute();
$voted_events_result = $voted_events_stmt->get_result();

$voted_events = [];
while ($row = $voted_events_result->fetch_assoc()) {
    $voted_events[] = $row['event_id'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote for Candidate</title>
    <link rel="stylesheet" href="styles.css">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Global Reset and Layout */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Arial', sans-serif;
}

body {
    background: linear-gradient(to top right, #f0f8ff, #e0ffff, #98fb98);
    color: #333;
    padding: 40px;
    font-size: 16px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* Ensures footer stays at bottom */
    align-items: center; /* Center content horizontally */
    margin: 0;
}

/* Container */
.container {
    width: 100%; /* Make sure container takes full width of its parent */
    max-width: 1200px; /* Maximum width of the container */
    margin: 0 auto; /* Center the container horizontally */
    padding: 60px; /* Increased padding for more space */
    background-color:#ADD8E6;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    border: 2px solid #2ecc71; /* Emerald green border for accent */
    flex-grow: 1; /* Allow the container to grow and take up remaining space */
}

/* Header Section */
.name {
    text-align: center;
    margin-bottom: 40px;
}

h2 {
    font-size: 2.4rem;
    font-weight: 700;
    color: #2a2a2a;
    margin-bottom: 15px;
}

h3 {
    font-size: 1.1rem;
    color: #7f7f7f;
    letter-spacing: 1px;
}

/* Voting Form Section */
.event-cards-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px; /* Space between items */
    justify-items: center;
    padding: 40px 20px;
    width: 100%;
}

/* Event Cards */
.event-card {
    background:#FAF0E6; /* Soft pink to light gray gradient */
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e0e0e0;
    overflow: hidden;
    max-width: 350px; /* Prevent the card from becoming too wide */
}

/* Hover effect for event cards */
.event-card:hover:not(:disabled) {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.event-card:hover:disabled {
    transform: translateY(0);
}

.event-card h3 {
    font-size: 1.75rem;
    font-weight: 600;
    color: #6a1b9a;
    margin-bottom: 15px;
    letter-spacing: 0.5px;
    text-transform: capitalize;
}

.event-card p {
    font-size: 1.05rem;
    color: #555555;
    margin-bottom: 20px;
    line-height: 1.6;
}

/* Event Button */
.event-button:not(:disabled) {
    background: linear-gradient(to right, #6a1b9a, #ab47bc);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 1.1rem;
    cursor: pointer;
    width: 100%;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: background 0.3s ease, transform 0.2s ease;
}

.event-button:disabled {
    background:#d3d3d3;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 1.1rem;
    cursor: pointer;
    width: 100%;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: background 0.3s ease, transform 0.2s ease;
}

/* Hover effect for event button */
.event-button:hover:not(:disabled) {
    background:#4169E1;
    transform: translateY(-2px);
}

/* Event Button when disabled */
.event-button:disabled {
    background-color: #d3d3d3; /* Change color when disabled */
    color: #888; /* Optional: Lighter text when disabled */
    cursor: not-allowed; /* Change cursor to indicate disabled state */
    transform: none; /* Ensure it doesn't get any hover transformations when disabled */
}

/* Room Entry Form */
form {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 20px;
}

input[type="text"] {
    padding: 10px;
    margin: 10px;
    border: 2px solid #2ecc71;
    border-radius: 5px;
    font-size: 1.1rem;
    width: 300px;
}

button {
    padding: 10px 20px;
    background-color: #2ecc71;
    border: none;
    border-radius: 5px;
    font-size: 1.1rem;
    color: white;
    cursor: pointer;
    text-transform: uppercase;
}

button:hover {
    background-color: #27ae60;
}

/* Logout Button */
.logout-btn {
    background-color: #27ae60;
    color: white;
    border: none;
    padding: 14px 28px;
    border-radius: 6px;
    font-size: 1.2rem;
    cursor: pointer;
    margin-top: 30px;
    width: 100%;
    text-transform: uppercase;
    font-weight: bold;
}

/* Hover effect for logout button */
.logout-btn:hover {
    background-color: #2ecc71;
    transform: scale(1.05);
}

/* Footer */
footer {
    color: black;
    padding: 2px 0;
    text-align: center;
    margin-top: 40px;
    width: 107%; /* Ensure footer spans full width */
    position: relative; /* Ensure footer is below content */
}

footer p {
    font-size: 10px;
}
</style>
</head>
<body>

    <div class="container">
        <div class="name">
            <h3><b>Welcome</b></h3>
            <h2>
                <?php
                echo($name['voter_name']);
                ?>
            </h2>
            <h3>Please cast your vote below.</h3>
        </div>

        <!-- Room Entry Form (only shown if room_id is not set) -->
        <?php if (!$room_id): ?>
            <form method="POST" action="">
                <input type="text" name="room_id" placeholder="Enter Room ID" required>
                <button type="submit">Enter Room</button>
            </form>
            <form action="create_event.php">
                <button type="submit">← Back to Main</button>
            </form>
        <?php endif; ?>

        <!-- Voting Form (if events exist) -->
        <?php if (isset($events_result) && mysqli_num_rows($events_result) > 0): ?>
            <div class="event-cards-container">
                <?php
                    while ($event = mysqli_fetch_assoc($events_result)) {
                        // Check if the current event has already been voted on by the user
                        $disabled = in_array($event['event_id'], $voted_events) ? 'disabled' : '';
                        
                        echo "<div class='event-card' data-event-id='" . $event['event_id'] . "'>";
                        echo "<h3>" . htmlspecialchars($event['event_name']) . "</h3>";
                        if(!$disabled){
                            echo "<p>Choose this event to vote</p>";
                        } else {
                            echo "<p>This event has been voted</p>";
                        }
                        echo "<button type='button' class='event-button' $disabled>Vote</button>";
                        echo "</div>";
                    }
                ?>
                <form id="votingForm" action="local_submit.php" method="POST">
                    <!-- Store the values -->
                    <input type="hidden" name="event_id" id="event_id">
                    <input type="hidden" name="candidate_id" id="candidate_id">
                </form>
            </div>
            <form action="back.php">
                <button type="submit">← Back</button>
            </form>
        <form action="local_each_result.php">
            <button type="submit">Show Result</button>
        </form>
        <?php elseif ($room_id): ?>
            <p>No events found in this room. Please try again later.</p>
            <form action="back.php">
                <button type="submit">← Back</button>
            </form>            
        <?php endif; ?>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eventButtons = document.querySelectorAll('.event-button');

            eventButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.closest('.event-card').getAttribute('data-event-id');
                    const eventInput = document.getElementById('event_id');

                    eventInput.value = eventId;

                    if (eventId) {
                        // Make an AJAX request to fetch the candidates for the selected event
                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', 'get_candidates.php?event_id=' + eventId, true);
                        xhr.onload = function() {
                            if (xhr.status == 200) {
                                try {
                                    var candidates = JSON.parse(xhr.responseText);

                                    if (candidates.length > 0) {
                                        let candidateListText = "<ul>";
                                        candidates.forEach(function(candidate) {
                                            candidateListText +=
                                                `<li>
                                                    <label>
                                                        <input type="radio" name="candidate" value="${candidate.candidate_id}" required> 
                                                        ${candidate.candidate_name}
                                                    </label>
                                                </li>`;
                                        });
                                        candidateListText += "</ul>";

                                        // Show candidate list in SweetAlert with a "Submit 0" button
                                        Swal.fire({
                                            title: 'Choose a Candidate',
                                            html: candidateListText,
                                            icon: 'info',
                                            showCancelButton: true,
                                            confirmButtonText: 'Submit Vote',
                                            preConfirm: () => {
                                                const selectedCandidate = document.querySelector('input[name="candidate"]:checked');
                                                if (!selectedCandidate) {
                                                    Swal.showValidationMessage('Please select a candidate.');
                                                } else {
                                                    document.getElementById('candidate_id').value = selectedCandidate.value;
                                                    document.getElementById('votingForm').submit();
                                                }
                                            }
                                        });
                                    } else {
                                        // No candidates available
                                        Swal.fire({
                                            title: 'No Candidates Available',
                                            text: 'There are no candidates for this event.',
                                            icon: 'warning',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'An error occurred while fetching candidates.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                                
                            }
                        };
                        xhr.send();
                    }
                });
            });
        });
        <?php if (isset($_SESSION['vote_success'])): ?>
            Swal.fire({
            title: 'Vote Submitted!',
            text: 'Your vote has been successfully recorded.',
            icon: 'success',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['vote_success']); ?>
        <?php endif; ?>

        // error message
        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Room ID',
                text: 'The Room ID you entered does not exist.',
            });
        <?php endif; ?>


    </script>
   <!-- footer -->
   <footer>
        <p>&copy; Online Voting System. All rights reserved.</p>
        <p>Developed for [Major Project 6th sem]</p>
    </footer>


    <script>
    // Push a dummy state so that browser back button triggers popstate
    if (window.history && window.history.pushState) {
        window.history.pushState('forward', null, window.location.href);
        window.onpopstate = function () {
            // Redirect when back button is pressed
            window.location.href = "back.php"; // your target page
        };
    }
</script>

</body>
</html>
