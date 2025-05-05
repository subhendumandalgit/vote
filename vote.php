`<?php
session_start();
include('db.php');

// Check if the voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: index.html"); // Redirect to login page if not logged in
    exit();
}

// Fetch voter name from the session
$voter_id = $_SESSION['voter_id']; 
$voter_query="SELECT voter_name FROM voters WHERE voter_id='$voter_id' ";
$result=mysqli_query($election_conn,$voter_query);
$name=mysqli_fetch_assoc($result);

// Fetch events from the database
$events_query = "SELECT event_id, event_name FROM events where room_id=0";
$events_result = mysqli_query($election_conn, $events_query);

// Fetch the events the voter has already voted for
$voter_id = $_SESSION['voter_id'];
$voted_events_query = "SELECT event_id FROM votes WHERE voter_id = '$voter_id'";
$voted_events_result = mysqli_query($election_conn, $voted_events_query);


$voted_events = [];
while ($row = mysqli_fetch_assoc($voted_events_result)) {
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
.event-button:disabled{
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
        <!-- Voting Form --> 
    <div class="event-cards-container">
        

            <?php
                while ($event = mysqli_fetch_assoc($events_result)) {
                    // Check if the current event has already been voted on by the user
                    $disabled = in_array($event['event_id'], $voted_events) ? 'disabled' : '';
                    
                    echo "<div class='event-card' data-event-id='" . $event['event_id'] . "'>";
                    echo "<h3>" . htmlspecialchars($event['event_name']) . "</h3>";
                    if(!$disabled){
                    echo "<p>Choose this event to vote</p>";}
                    else{
                        echo "<p>This event has been voted</p>";
                    }
                    echo "<button type='button' class='event-button' $disabled>Vote</button>";
                    echo "</div>";
                }
            ?>
            <form id="votingForm" action="submit_vote.php" method="POST">
                 <!-- Store the values -->
            <input type="hidden" name="event_id" id="event_id">
            <input type="hidden" name="candidate_id" id="candidate_id">
        </form>


            <!-- SweetAlert2 JS -->
            <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.all.min.js"></script> -->
        
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function () {
    // Event card button click handler to load candidates
    const eventButtons = document.querySelectorAll('.event-button');

    eventButtons.forEach(button => {
        button.addEventListener('click', function() {
            const eventId = this.closest('.event-card').getAttribute('data-event-id');
            const eventInput = document.getElementById('event_id');

            // Set the event_id in the hidden input field
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

                                // Show candidate list in SweetAlert with a "Submit Vote" button
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
                        } catch (error) {
                            console.error('Error parsing response:', error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to load candidate data.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    } else {
                        console.error('Failed to fetch candidates. Status:', xhr.status);
                    }
                };
                xhr.send();
            }
        });
    });
});


    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.all.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Display SweetAlert for success or error message
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                title: 'Success!',
                text: '<?php echo $_SESSION['success_message']; ?>',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(function() {
                // Clear the success message after it has been shown
                <?php unset($_SESSION['success_message']); ?>
            });
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                title: 'Error!',
                text: '<?php echo $_SESSION['error_message']; ?>',
                icon: 'error',
                confirmButtonText: 'OK'
            }).then(function() {
                // Clear the error message after it has been shown
                <?php unset($_SESSION['error_message']); ?>
            });
        <?php endif; ?>
    });
</script>
</div>

<a href="voter_dashboard.php">Back</a>
   <!-- footer -->
   <footer>
        <p>&copy; 2024 Online Voting System. All rights reserved.</p>
        <p>Developed for [Minor Project 5th sem]</p>
    </footer>

</body>
</html>