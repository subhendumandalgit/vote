<?php
session_start();

// Check if the admin is logged in, otherwise redirect to login page
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.html");
    exit();
}

include('db.php'); // Include the database connection

// Initialize flags for success or error
$candidate_added = false;
$candidate_removed = false;
$voter_added = false;
$voter_removed = false;
$reset_error = '';  
$winner = ''; 

// ==================== DATABASE MANIPULATION SECTION ====================

// Add Candidate Logic
if (isset($_POST['add_candidate'])) {
    $candidate_name = $_POST['candidate_name'];
    $event_id = $_POST['event_id'];  // Get the selected event_id

    // Check if candidate name, party name, and event_id are provided
    if (!empty($candidate_name) && !empty($event_id)) {
        // First, check if the candidate already exists in the database for the same event
        $check_candidate_query = "SELECT * FROM candidates WHERE candidate_name = '$candidate_name' AND event_id = '$event_id'";
        $result = mysqli_query($election_conn, $check_candidate_query);

        // If a candidate with the same name and event_id exists
        if (mysqli_num_rows($result) > 0) {
            // Store error message in session
            $_SESSION['error_message'] = "Candidate Name Already Existed in this Event.";
        } else {
            // Insert new candidate into the database, associating with the event_id
            $add_candidate_query = "INSERT INTO candidates (candidate_name, event_id) VALUES ('$candidate_name','$event_id')";

            if (mysqli_query($election_conn, $add_candidate_query)) {
                // Store success message in session
                $_SESSION['success_message'] = "Candidate has been added successfully.";
            } else {
                // Handle SQL errors (if any)
                $_SESSION['error_message'] = "Error: " . mysqli_error($election_conn);
            }
        }
    } else {
        // Handle empty fields
        $_SESSION['error_message'] = "Please fill in all fields.";
    }

    // Redirect to prevent re-submission on page reload and show messages after reload
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// <!-- Display Success or Error Messages -->

// Check if there is a success message in the session
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert success'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']); // Clear the success message after displaying
}

// Check if there is an error message in the session
if (isset($_SESSION['error_message'])) {
    echo "<div class='alert error'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Clear the error message after displaying
}

// Add Voter Logic (with voter_no, voter_name, and voter_ph_no)
if (isset($_POST['add_voter'])) {
    $voter_no = $_POST['voter_no'];
    $voter_name = $_POST['voter_name'];
    $voter_ph_no = $_POST['voter_ph_no'];

    // Add new voter
    if (!empty($voter_no) && !empty($voter_name) && !empty($voter_ph_no)) {
        // Check if the voter already exists
        $check_voter_query = "SELECT * FROM voters WHERE voter_id = '$voter_no'";
        $check_result = mysqli_query($election_conn, $check_voter_query);

        // If the voter already exists
        if (mysqli_num_rows($check_result) > 0) {
            // Voter already exists, show an error message
            $_SESSION['error_message'] = "Voter Already Exists.";
        } else {
            // Voter does not exist, proceed with adding the new voter
            $add_voter_query = "INSERT INTO voters (voter_id, voter_name, phone_number) VALUES ('$voter_no', '$voter_name', '$voter_ph_no')";
            if (mysqli_query($election_conn, $add_voter_query)) {
                $_SESSION['success_message'] = "Voter has been added successfully."; // Store success message in session
            } else {
                $_SESSION['error_message'] = "Error: " . mysqli_error($election_conn); // Store error message in session
            }
        }
    } else {
        $_SESSION['error_message'] = "Please fill in all fields."; // Handle empty fields
    }

    // Redirect to prevent form resubmission on page reload
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
// Display Success Message
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert success'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']); // Unset the message after displaying
}

// Display Error Message
if (isset($_SESSION['error_message'])) {
    echo "<div class='alert error'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Unset the message after displaying
}


//remove voter

if (isset($_POST['remove_voter'])) {
    $voter_id = $_POST['voter_id'];

    // Ensure the voter_id is sanitized and quoted properly for SQL
    if (!empty($voter_id)) {
        // Escape the voter_id to prevent SQL injection
        $voter_id = mysqli_real_escape_string($election_conn, $voter_id);

        // Delete voter from the database
        $remove_voter_query = "DELETE FROM voters WHERE voter_id = '$voter_id'"; 

        if (mysqli_query($election_conn, $remove_voter_query)) {
            // Set success message in session
            $_SESSION['success_message'] = "Voter has been removed successfully.";
        } else {
            // If there's an error, set error message in session
            $_SESSION['error_message'] = "Error: " . mysqli_error($election_conn);
        }
    } else {
        // If no voter_id is provided, set error message in session
        $_SESSION['error_message'] = "Voter ID is missing or invalid.";
    }

    // Redirect to the same page to avoid form resubmission on reload
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// <!-- Display Success or Error Messages -->
// Check if there is a success message in the session
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert success'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']); // Clear the success message after displaying
}

// Check if there is an error message in the session
if (isset($_SESSION['error_message'])) {
    echo "<div class='alert error'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Clear the error message after displaying
}

// Add Event Logic
if (isset($_POST['add_event'])) {
    $event_name = $_POST['event_name'];

    // Check if event name is provided
    if (!empty($event_name)) {
        // Check if the event already exists
        $check_event_query = "SELECT * FROM events WHERE event_name = '$event_name' AND room_id= 0";
        $result = mysqli_query($election_conn, $check_event_query);

        // If an event with the same name exists
        if (mysqli_num_rows($result) > 0) {
            // Store error message in session
            $_SESSION['error_message'] = "Event Name Already Existed.";
        } else {
            // Insert new event into the database
            $add_event_query = "INSERT INTO events (event_name) VALUES ('$event_name')";

            if (mysqli_query($election_conn, $add_event_query)) {
                // Store success message in session
                $_SESSION['success_message'] = "Event has been added successfully.";
            } else {
                // Handle SQL errors (if any)
                $_SESSION['error_message'] = "Error: " . mysqli_error($election_conn);
            }
        }
    } else {
        // Handle empty fields
        $_SESSION['error_message'] = "Please fill in the event name.";
    }

    // Redirect to prevent re-submission on page reload and show messages after reload
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


// Remove Event Logic

if (isset($_POST['remove_event'])) {
    if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
        $event_id = $_POST['event_id'];

        // 🔍 Check the room_id of the event first
        $room_check_query = "SELECT room_id FROM events WHERE event_id = ?";
        $stmt_room_check = mysqli_prepare($election_conn, $room_check_query);
        mysqli_stmt_bind_param($stmt_room_check, "i", $event_id);
        mysqli_stmt_execute($stmt_room_check);
        mysqli_stmt_bind_result($stmt_room_check, $room_id);
        mysqli_stmt_fetch($stmt_room_check);
        mysqli_stmt_close($stmt_room_check);

        // ❌ Prevent deletion if room_id > 0
        if ($room_id > 0) {
            $_SESSION['error_message'] = "You cannot remove an event assigned to a specific room.";
        } else {
            // ✅ Start the transaction
            mysqli_begin_transaction($election_conn);

            try {
                // 1. Delete candidates
                $remove_c_query = "DELETE FROM candidates WHERE event_id = ?";
                $stmt_candidates = mysqli_prepare($election_conn, $remove_c_query);
                if ($stmt_candidates === false) {
                    throw new Exception("Error preparing statement for candidates deletion.");
                }
                mysqli_stmt_bind_param($stmt_candidates, "i", $event_id);
                $candidates_deleted = mysqli_stmt_execute($stmt_candidates);

                // 2. Delete event
                $remove_event_query = "DELETE FROM events WHERE event_id = ?";
                $stmt_event = mysqli_prepare($election_conn, $remove_event_query);
                if ($stmt_event === false) {
                    throw new Exception("Error preparing statement for event deletion.");
                }
                mysqli_stmt_bind_param($stmt_event, "i", $event_id);
                $event_deleted = mysqli_stmt_execute($stmt_event);

                if ($candidates_deleted && $event_deleted) {
                    mysqli_commit($election_conn);
                    $_SESSION['success_message'] = "Event and associated candidates have been removed successfully.";
                } else {
                    mysqli_rollback($election_conn);
                    $_SESSION['error_message'] = "Error: Could not remove event or candidates.";
                }
            } catch (Exception $e) {
                mysqli_rollback($election_conn);
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }

            // Close prepared statements
            if (isset($stmt_event)) {
                mysqli_stmt_close($stmt_event);
            }
            if (isset($stmt_candidates)) {
                mysqli_stmt_close($stmt_candidates);
            }
        }
    } else {
        $_SESSION['error_message'] = "No event selected.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


// ===================== RESET VOTES LOGIC =====================

// Query to get the total number of votes in the system
$vote_count_query = "SELECT COUNT(*) AS total_votes FROM votes WHERE room_id=0";
$vote_count_result = mysqli_query($election_conn, $vote_count_query);
$total_votes = mysqli_fetch_assoc($vote_count_result)['total_votes'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_votes'])) {
    // Reset vote count for all candidates
    $reset_votes_query = "DELETE FROM votes";
    
    // Execute both queries
    $reset_votes_result = mysqli_query($election_conn, $reset_votes_query);
    
    if ($reset_votes_result) {
        $_SESSION['success_message']="All Votes have been reset";
    } else {
        $_SESSION['error_message']="Error: ".mysqli_error($election_conn);
    }

    // Redirect to avoid resubmission on page reload
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
// Display Success Message
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert success'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']); // Unset the message after displaying
}

// Display Error Message
if (isset($_SESSION['error_message'])) {
    echo "<div class='alert error'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Unset the message after displaying
}

// ===================== RESULT SECTION =====================

// Fetch event-specific results: event name, candidate, and their vote count
$events_query = "SELECT events.event_name, candidates.candidate_name, 
           candidates.candidate_id, COUNT(votes.vote_id) AS vote_count
    FROM events
    LEFT JOIN votes ON votes.event_id = events.event_id
    LEFT JOIN candidates ON votes.candidate_id = candidates.candidate_id
    WHERE votes.room_id = 0
    GROUP BY events.event_id, candidates.candidate_id
    ORDER BY events.event_name, vote_count DESC";


$events_result = mysqli_query($election_conn, $events_query);

$event_results = [];

while ($row = mysqli_fetch_assoc($events_result)) {
    $event_name = $row['event_name'];
    if (!isset($event_results[$event_name])) {
        $event_results[$event_name] = [];
    }
    
    $event_results[$event_name][] = [
        'candidate_name' => $row['candidate_name'],
        'vote_count' => $row['vote_count']
    ];
}
?>

<!-- // ===================== Structure===================== -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Election Results</title>
    <link rel="stylesheet" href="admin_dashboard.css">
</head>
<body>
<div class="content-wrapper">
    <div class="container">
        <h2>Admin Dashboard</h2>

       

        <!-- Display results per event -->
         <div class="container_result">
         <div class="admin-info">
            <h3>Election Results</h3>
            <p>Below is the total number of votes each candidate has received in each event:</p>
        </div>
        <?php foreach ($event_results as $event_name => $candidates): ?>
            <div class="event_style">
            <h3>Event:<?php echo $event_name; ?></h3>
        </div>
            <table>
                <thead>
                    <tr>
                        <th>Candidate Name</th>
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
                                <td>{$candidate['vote_count']}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>


            <div class="winner">
            <h4>
                <?php
                    if (count($winners) > 1) {
                        echo "Draw between: " . implode(", ", $winners);
                    } else {
                        echo "Winner: " . $winners[0];
                    }
                ?>
            </h4>
                </div>
        <?php endforeach; ?>
                </div>

        <!-- Add Event Form -->
<div class="container_event">
    <h3><center><u>Event Manipulation Section</u></center></h3>
    <h4>Add Event</h4>
    <form action="" method="POST">
        <input type="text" name="event_name" placeholder="Event Name" required>
        <button type="submit" name="add_event" class="add-btn">Add Event</button>
    </form>

<!-- Remove Event Form -->
 <br>

    <h4>Remove Event</h4>
    <form action="" method="POST">
        <select name="event_id" required <?php if ($total_votes > 0) echo 'disabled'; ?>>
            <option value="">Select Event</option>
            <?php
            // Fetch events from database for dropdown
            $event_query = "SELECT event_id, event_name FROM events WHERE room_id=0";
            $event_result = mysqli_query($election_conn, $event_query);
            while ($row = mysqli_fetch_assoc($event_result)) {
                echo "<option value='{$row['event_id']}'>{$row['event_name']}</option>";
            }
            ?>
        </select>
        <button type="submit" name="remove_event" class="remove-btn" <?php if ($total_votes > 0) echo 'disabled'; ?>>Remove Event</button>
    </form>
</div>

<!-- ADD Candidate -->
<div class="container_candidate"> 
    <h3><center><u>Candidate Manipulation Section</u></center></h3>   
    <form action="" method="POST">
        <h4>Add Candidate</h4>
        <!-- Candidate Name -->
            <input type="text" class="form-control" id="candidate_name" name="candidate_name" placeholder="Enter candidate name" required>

        <!-- Event Selection -->
        <div class="form-group">
            <select class="form-control" id="event_id" name="event_id" required>
                <option value="">-- Select Event --</option>
                <?php
                // Fetch events from the database
                $events_query = "SELECT event_id, event_name FROM events WHERE room_id=0";
                $events_result = mysqli_query($election_conn, $events_query);
                
                // Loop through events and create an option for each event
                while ($event = mysqli_fetch_assoc($events_result)) {
                    echo '<option value="' . $event['event_id'] . '">' . $event['event_name'] . '</option>';
                }
                ?>
            </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary" name="add_candidate">Add Candidate</button>
    </form>


      <!-- remove_candidate_form.php -->
        <br>
    <h4>Remove Candidate</h4>
    <form action="remove_candidate_logic.php" method="POST"  >
        <!-- Event Dropdown -->
        <select name="event_id" id="event_select" <?php if ($total_votes > 0) echo 'disabled'; ?> required>
            <option value="">Select Event</option>
            <?php
            // Fetch events from database
            $event_query = "SELECT event_id, event_name FROM events WHERE room_id=0";
            $event_result = mysqli_query($election_conn, $event_query);
            while ($row = mysqli_fetch_assoc($event_result)) {
                echo "<option value='{$row['event_id']}'>{$row['event_name']}</option>";
            }
            ?>
        </select>
    </form>

    <!-- Candidate Dropdown (Populated based on event) -->
    <form action="remove_candidate_logic.php" method="POST">
        <select name="candidate_id" id="candidate_select" required disabled>
            <option value="">Select Candidate</option>
        </select>

        <button type="submit" name="remove_candidate" class="remove-btn"  <?php if ($total_votes > 0) echo 'disabled'; ?>>Remove Candidate</button>
    </form>
</div>

<!-- jQuery to handle AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
    // When the event is selected
    $('#event_select').change(function() {
        var event_id = $(this).val();

        // If an event is selected, fetch candidates for that event
        if (event_id) {
            $.ajax({
                url: 'remove_candidate_logic.php', // The PHP file that handles the AJAX request
                type: 'POST',
                data: { event_id: event_id }, // Send event_id to the server
                success: function(response) {
                    try {
                        var candidates = JSON.parse(response);

                        // Clear existing options in the candidate dropdown
                        $('#candidate_select').html('<option value="">Select Candidate</option>');

                        // Populate the candidate dropdown with options based on the event
                        candidates.forEach(function(candidate) {
                            $('#candidate_select').append('<option value="' + candidate.candidate_id + '">' + candidate.candidate_name + '</option>');
                        });

                        // Enable the candidate dropdown
                        $('#candidate_select').prop('disabled', false);
                    } catch (e) {
                        console.error("Failed to parse JSON:", e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX request failed:", status, error);
                }
            });
        } else {
            // If no event is selected, disable candidate dropdown
            $('#candidate_select').prop('disabled', true);
        }
    });
});

</script>

        <!-- Add Voter Form -->
        <div class="container_voter">
            <h3><center><u>Voter Manipulation Section</u></center></h3>
            <h4>Add Voter</h4>
            <form action="" method="POST">
                <input type="text" name="voter_no" placeholder="Voter No" <?php if ($total_votes > 0) echo 'disabled'; ?> required>
                <input type="text" name="voter_name" placeholder="Voter Name" <?php if ($total_votes > 0) echo 'disabled'; ?> required>
                <input type="text" name="voter_ph_no" placeholder="Voter Phone No" <?php if ($total_votes > 0) echo 'disabled'; ?> required>
                <button type="submit" name="add_voter" class="add-btn" <?php if ($total_votes > 0) echo 'disabled'; ?>>Add Voter</button>
            </form>

        <!-- Remove Voter Form -->
         <br>

            <h4>Remove Voter</h4>
            <form action="" method="POST">
                <select name="voter_id" required <?php if ($total_votes > 0) echo 'disabled'; ?>>
                    <option value="">Select Voter</option>
                    <?php
                    // Fetch voters from database for dropdown
                    $voter_query = "SELECT voter_id, voter_name FROM voters";
                    $voter_result = mysqli_query($election_conn, $voter_query);
                    while ($row = mysqli_fetch_assoc($voter_result)) {
                        echo "<option value='{$row['voter_id']}'>{$row['voter_name']}</option>";
                    }
                    ?>
                </select>
                <button type="submit" name="remove_voter" class="remove-btn" <?php if ($total_votes > 0) echo 'disabled'; ?>>Remove Voter</button>
            </form>
        </div>


        
        <!-- Reset Votes Form -->
    <div class=container_logout>
        <div class="reset-box">
            <form action="" method="POST">
                <!-- Disable button if total_votes is 0 -->
                <button type="submit" name="reset_votes" class="reset-btn" <?php if ($total_votes == 0) echo 'disabled'; ?>>Reset All Votes</button>
            </form>
        </div>
   
        <!-- Logout Button -->
        <div class="logout-btn">
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <!-- download report section -->
     <div class='report'>
     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // On button click, trigger AJAX request
            $('#generateBtn').click(function() {
                // Make AJAX request to generate PDF
                $.ajax({
                    url: 'download_pdf.php', // The PHP script to generate PDF
                    type: 'POST',
                    success: function(response) {
                        // Open the PDF in a new tab after successful generation
                        window.open('download_pdf.php', 'blank');
                    },
                    error: function() {
                        alert('Error generating PDF');
                    }
                });
            });
        });
    </script>
    <button id="generateBtn">Generate PDF</button>
</div>

        <!-- footer -->
    <footer>
        <p>&copy; 2024 Online Voting System. All rights reserved.</p>
        <p>Developed for [Minor Project 5th sem]</p>
    </footer>

    </div>
</body>
</html>