<?php
session_start();
include('db.php');

// Redirect if not logged in
if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

$voter_id = $_SESSION['voter_id'];

// ==========================
// HANDLE ROOM CREATION
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $room_name = mysqli_real_escape_string($election_conn, $_POST['room_name']);
    $query = "INSERT INTO rooms (room_name, created_by) VALUES (?, ?)";

    if ($stmt = mysqli_prepare($election_conn, $query)) {
        mysqli_stmt_bind_param($stmt, 'ss', $room_name, $voter_id);
        mysqli_stmt_execute($stmt);
        $_SESSION['success_message'] = "Room created successfully! Room ID: " . mysqli_insert_id($election_conn);
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_message'] = "Error creating room.";
    }

    header('Location: create_room.php');
    exit();
}

// ==========================
// HANDLE ROOM DELETION
// ==========================
if (isset($_POST['delete_room'])) {
    $room_id = (int) $_POST['room_id'];

    // Verify room belongs to the logged-in user
    $verify_query = "SELECT created_by FROM rooms WHERE room_id = ?";
    $stmt = mysqli_prepare($election_conn, $verify_query);
    mysqli_stmt_bind_param($stmt, 'i', $room_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $created_by);

    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);

        if ($created_by !== $voter_id) {
            $_SESSION['error_message'] = "Unauthorized to delete this room.";
            header('Location: create_room.php');
            exit();
        }

        // Check for any votes in events under this room
        $vote_check_query = "
            SELECT COUNT(*) 
            FROM votes v
            JOIN candidates c ON v.candidate_id = c.candidate_id
            JOIN events e ON c.event_id = e.event_id
            WHERE e.room_id = ?
        ";
        $stmt_vote_check = mysqli_prepare($election_conn, $vote_check_query);
        mysqli_stmt_bind_param($stmt_vote_check, 'i', $room_id);
        mysqli_stmt_execute($stmt_vote_check);
        mysqli_stmt_bind_result($stmt_vote_check, $vote_count);
        mysqli_stmt_fetch($stmt_vote_check);
        mysqli_stmt_close($stmt_vote_check);

        if ($vote_count > 0) {
            $_SESSION['error_message'] = "Room cannot be deleted. Voting has already started.";
            header('Location: create_room.php');
            exit();
        }

        // Delete all candidates for events in this room
        $delete_candidates_query = "
            DELETE c FROM candidates c
            JOIN events e ON c.event_id = e.event_id
            WHERE e.room_id = ?
        ";
        $stmt_cand = mysqli_prepare($election_conn, $delete_candidates_query);
        mysqli_stmt_bind_param($stmt_cand, 'i', $room_id);
        mysqli_stmt_execute($stmt_cand);
        mysqli_stmt_close($stmt_cand);

        // Delete all events in the room
        $delete_events_query = "DELETE FROM events WHERE room_id = ?";
        $stmt_events = mysqli_prepare($election_conn, $delete_events_query);
        mysqli_stmt_bind_param($stmt_events, 'i', $room_id);
        mysqli_stmt_execute($stmt_events);
        mysqli_stmt_close($stmt_events);

        // Delete the room
        $delete_room_query = "DELETE FROM rooms WHERE room_id = ?";
        $stmt_room = mysqli_prepare($election_conn, $delete_room_query);
        mysqli_stmt_bind_param($stmt_room, 'i', $room_id);
        mysqli_stmt_execute($stmt_room);
        mysqli_stmt_close($stmt_room);

        $_SESSION['success_message'] = "Room and all related data deleted successfully.";
    } else {
        mysqli_stmt_close($stmt);
        $_SESSION['error_message'] = "Room not found.";
    }

    header('Location: create_room.php');
    exit();
}

// ==========================
// HANDLE EVENT CREATION
// ==========================
if (isset($_POST['create_event'])) {
    $event_name = mysqli_real_escape_string($election_conn, $_POST['event_name']);
    $room_id = (int) $_POST['room_id'];

    $check_query = "SELECT event_id FROM events WHERE event_name = ? AND room_id = ?";
    $stmt = mysqli_prepare($election_conn, $check_query);
    mysqli_stmt_bind_param($stmt, 'si', $event_name, $room_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['error_message'] = "Event already exists in this room.";
    } else {
        $insert_query = "INSERT INTO events (event_name, room_id) VALUES (?, ?)";
        $stmt_insert = mysqli_prepare($election_conn, $insert_query);
        mysqli_stmt_bind_param($stmt_insert, 'si', $event_name, $room_id);
        mysqli_stmt_execute($stmt_insert);
        mysqli_stmt_close($stmt_insert);
        $_SESSION['success_message'] = "Event created successfully.";
    }

    mysqli_stmt_close($stmt);
    header('Location: create_room.php');
    exit();
}

// ==========================
// HANDLE EVENT DELETION
// ==========================
if (isset($_POST['delete_event'])) {
    $event_id = (int) $_POST['event_id'];

    // Fetch room owner
    $fetch_query = "
        SELECT r.created_by 
        FROM events e
        JOIN rooms r ON e.room_id = r.room_id
        WHERE e.event_id = ?
    ";

    $stmt = mysqli_prepare($election_conn, $fetch_query);
    mysqli_stmt_bind_param($stmt, 'i', $event_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $created_by);

    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);

        if ($created_by === $voter_id) {
            // Check if voting has started
            $vote_check_query = "SELECT COUNT(*) FROM votes WHERE event_id = ?";
            $stmt_vote_check = mysqli_prepare($election_conn, $vote_check_query);
            mysqli_stmt_bind_param($stmt_vote_check, 'i', $event_id);
            mysqli_stmt_execute($stmt_vote_check);
            mysqli_stmt_bind_result($stmt_vote_check, $vote_count);
            mysqli_stmt_fetch($stmt_vote_check);
            mysqli_stmt_close($stmt_vote_check);

            if ($vote_count > 0) {
                $_SESSION['error_message'] = "Cannot delete event. Voting has already started.";
                header('Location: create_room.php');
                exit();
            }

            // Delete candidates for the event
            $delete_candidates_query = "DELETE FROM candidates WHERE event_id = ?";
            $stmt_del_cand = mysqli_prepare($election_conn, $delete_candidates_query);
            mysqli_stmt_bind_param($stmt_del_cand, 'i', $event_id);
            mysqli_stmt_execute($stmt_del_cand);
            mysqli_stmt_close($stmt_del_cand);

            // Delete the event
            $delete_event_query = "DELETE FROM events WHERE event_id = ?";
            $stmt_del_event = mysqli_prepare($election_conn, $delete_event_query);
            mysqli_stmt_bind_param($stmt_del_event, 'i', $event_id);
            mysqli_stmt_execute($stmt_del_event);
            mysqli_stmt_close($stmt_del_event);

            $_SESSION['success_message'] = "Event deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Unauthorized to delete this event.";
        }
    } else {
        mysqli_stmt_close($stmt);
        $_SESSION['error_message'] = "Event not found.";
    }

    header('Location: create_room.php');
    exit();
}

// ==========================
// HANDLE CANDIDATE ADDITION
// ==========================
if (isset($_POST['add_candidate'])) {
    $candidate_name = trim($_POST['candidate_name']);
    $event_id = (int) $_POST['event_id'];

    if (empty($candidate_name)) {
        $_SESSION['error_message'] = "Candidate name cannot be empty.";
        header('Location: create_room.php');
        exit();
    }

    // Check if candidate already exists in this event (case-insensitive)
    $check_query = "SELECT candidate_id FROM candidates WHERE LOWER(candidate_name) = LOWER(?) AND event_id = ?";
    $stmt_check = mysqli_prepare($election_conn, $check_query);
    mysqli_stmt_bind_param($stmt_check, 'si', $candidate_name, $event_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        $_SESSION['error_message'] = "Candidate with this name already exists in the event.";
    } else {
        mysqli_stmt_close($stmt_check);
        $insert_query = "INSERT INTO candidates (candidate_name, event_id) VALUES (?, ?)";
        $stmt_insert = mysqli_prepare($election_conn, $insert_query);
        mysqli_stmt_bind_param($stmt_insert, 'si', $candidate_name, $event_id);
        mysqli_stmt_execute($stmt_insert);
        mysqli_stmt_close($stmt_insert);
        $_SESSION['success_message'] = "Candidate added successfully.";
    }

    header('Location: create_room.php');
    exit();
}

// ==========================
// HANDLE CANDIDATE DELETION
// ==========================
if (isset($_POST['delete_candidate'])) {
    $candidate_id = (int) $_POST['candidate_id'];

    $fetch_query = "
        SELECT e.room_id, r.created_by 
        FROM candidates c
        JOIN events e ON c.event_id = e.event_id
        JOIN rooms r ON e.room_id = r.room_id
        WHERE c.candidate_id = ?
    ";

    if ($stmt = mysqli_prepare($election_conn, $fetch_query)) {
        mysqli_stmt_bind_param($stmt, 'i', $candidate_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $room_id, $created_by);

        if (mysqli_stmt_fetch($stmt)) {
            mysqli_stmt_close($stmt);

            $vote_check_query = "SELECT COUNT(*) FROM votes WHERE candidate_id = ?";
            $stmt_vote_check = mysqli_prepare($election_conn, $vote_check_query);
            mysqli_stmt_bind_param($stmt_vote_check, 'i', $candidate_id);
            mysqli_stmt_execute($stmt_vote_check);
            mysqli_stmt_bind_result($stmt_vote_check, $vote_count);
            mysqli_stmt_fetch($stmt_vote_check);
            mysqli_stmt_close($stmt_vote_check);

            if ($vote_count > 0) {
                $_SESSION['error_message'] = "Candidate cannot be deleted. Voting has already started.";
                header('Location: create_room.php');
                exit();
            }

            if ($created_by === $voter_id) {
                $stmt_votes = mysqli_prepare($election_conn, "DELETE FROM votes WHERE candidate_id = ?");
                mysqli_stmt_bind_param($stmt_votes, 'i', $candidate_id);
                mysqli_stmt_execute($stmt_votes);
                mysqli_stmt_close($stmt_votes);

                $stmt_del = mysqli_prepare($election_conn, "DELETE FROM candidates WHERE candidate_id = ?");
                mysqli_stmt_bind_param($stmt_del, 'i', $candidate_id);
                mysqli_stmt_execute($stmt_del);
                mysqli_stmt_close($stmt_del);

                $_SESSION['success_message'] = "Candidate deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Unauthorized to delete this candidate.";
            }
        } else {
            mysqli_stmt_close($stmt);
            $_SESSION['error_message'] = "Candidate not found.";
        }
    }

    header('Location: create_room.php');
    exit();
}

// ==========================
// FETCH ALL ROOMS FOR USER
// ==========================
$rooms = [];
$query = "SELECT * FROM rooms WHERE created_by = ?";
$stmt = mysqli_prepare($election_conn, $query);
mysqli_stmt_bind_param($stmt, 's', $voter_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = $row;
}

mysqli_stmt_close($stmt);

// ==========================
// HANDLE VOTE RESET BY ROOM OWNER
// ==========================
if (isset($_POST['reset_votes'])) {
    $event_id = (int) $_POST['event_id'];

    $fetch_query = "
        SELECT r.created_by 
        FROM events e
        JOIN rooms r ON e.room_id = r.room_id
        WHERE e.event_id = ?
    ";

    $stmt = mysqli_prepare($election_conn, $fetch_query);
    mysqli_stmt_bind_param($stmt, 'i', $event_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $created_by);

    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);

        if ($created_by === $voter_id) {
            // Get candidate IDs in this event
            $cand_query = "SELECT candidate_id FROM candidates WHERE event_id = ?";
            $stmt_cand = mysqli_prepare($election_conn, $cand_query);
            mysqli_stmt_bind_param($stmt_cand, 'i', $event_id);
            mysqli_stmt_execute($stmt_cand);
            $cand_result = mysqli_stmt_get_result($stmt_cand);

            while ($cand_row = mysqli_fetch_assoc($cand_result)) {
                $candidate_id = $cand_row['candidate_id'];

                // Delete votes for each candidate
                $delete_votes = mysqli_prepare($election_conn, "DELETE FROM votes WHERE candidate_id = ?");
                mysqli_stmt_bind_param($delete_votes, 'i', $candidate_id);
                mysqli_stmt_execute($delete_votes);
                mysqli_stmt_close($delete_votes);
            }
            mysqli_stmt_close($stmt_cand);

            $_SESSION['success_message'] = "Votes reset successfully for this event.";
        } else {
            $_SESSION['error_message'] = "Unauthorized to reset votes.";
        }
    } else {
        mysqli_stmt_close($stmt);
        $_SESSION['error_message'] = "Event not found.";
    }

    header('Location: create_room.php');
    exit();
}

$voter_id = $_SESSION['voter_id'];

// Fetch rooms created by this user
$rooms_query = "SELECT room_id, room_name FROM rooms WHERE created_by = '$voter_id'";
$rooms_result = mysqli_query($election_conn, $rooms_query);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f6f9;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    padding: 20px;
    color: #333;
}

/* Container */
.container {
    background: linear-gradient(to right,rgb(148, 186, 212),rgb(152, 183, 203));
    width: 1000px;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    text-align: center;
    color: white;
}

.inner_container{
    background: linear-gradient(to right,rgb(51, 180, 77),rgb(110, 175, 41));
    width: 90%;
    margin-left:50px;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    text-align: center;
    color: white;
}

/* Headings */
.container h2 {
    font-size: 36px;
    margin-bottom: 20px;
}
.container h3, .container h4 {
    font-size: 24px;
    margin-bottom: 10px;
}

/* Success and Error Messages */
.success {
    color: #2ecc71;
    font-weight: bold;
    margin-bottom: 15px;
}
.error {
    color: #e74c3c;
    font-weight: bold;
    margin-bottom: 15px;
}

/* Forms */
form {
    margin: 10px 0;
}
input[type="text"] {
    padding: 10px;
    width: 40%;
    margin: 10px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 16px;
}
input[type="text"]:focus {
    border-color: #3498db;
    outline: none;
}
button {
    background-color: #2980b9;
    color: white;
    padding: 10px 16px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    margin: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
button:hover {
    background-color:rgb(53, 118, 22);
}
.delete:hover {
    background-color:rgb(212, 20, 20);
}
.delete:disabled{
    background-color: #bdc3c7; /* Light grey */
    color: #7f8c8d;           /* Darker grey text */
    cursor: not-allowed;
    opacity: 0.8;
}

/* List Styling */
ul {
    list-style: none;
    padding-left: 0;
    margin: 10px 0;
}
li {
    background: #ecf0f1;
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 5px;
    color: #333;
    text-align: left;
}

/* Inline forms inside list */
li form {
    display: inline;
}

/* Links */
a {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    color: white;
    background-color: #34495e;
    padding: 10px 15px;
    border-radius: 5px;
    transition: background-color 0.3s;
}
a:hover {
    background-color: #1abc9c;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 20px;
    }

    input[type="text"] {
        width: 100%;
    }

    button {
        width: 100%;
        margin-top: 10px;
    }
}

button:disabled {
    background-color: #bdc3c7; /* Light grey */
    color: #7f8c8d;           /* Darker grey text */
    cursor: not-allowed;
    opacity: 0.8;
}

    </style>

    <title>Create Your Local Rooms</title>
    <!-- Include SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
    <!-- Check if there are any session messages -->
    <script type="text/javascript">
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?= $_SESSION['success_message']; ?>'
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= $_SESSION['error_message']; ?>'
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
    <h2>Create Your Local Rooms</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <p class="success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p class="error"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <!-- Create Room -->
    <h3>Create Room</h3>
    <form method="POST">
        <input type="text" name="room_name" placeholder="Enter Room Name" required>
        <button type="submit" name="create_room">Create Room</button>
    </form>
    
    <!-- Display Rooms -->
    <?php if (!empty($rooms)): ?>
        <h3>Your Created Rooms</h3>
        <ul>
        <?php foreach ($rooms as $room): ?>
            <li>
                <strong>Room ID:</strong> <?= $room['room_id']; ?> - <?= $room['room_name']; ?>
                <?php if ($room['created_by'] === $voter_id): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="room_id" value="<?= $room['room_id']; ?>">
                        <button type="submit" name="delete_room" class="delete">Delete Room</button>
                    </form>
                <?php endif; ?>
                <br>
                <!-- Create Event Button -->
                <form method="POST" style="display:inline;">
                    <button type="submit" name="show_create_event_form" value="<?= $room['room_id']; ?>">Create Event</button>
                </form>
                <!-- Create Event Form -->
                <?php if (isset($_POST['show_create_event_form']) && $_POST['show_create_event_form'] == $room['room_id']): ?>
                    <form method="POST">
                        <input type="text" name="event_name" placeholder="Enter Event Name" required>
                        <input type="hidden" name="room_id" value="<?= $room['room_id']; ?>">
                        <button type="submit" name="create_event">Submit</button>
                    </form>
                <?php endif; ?>

                <!-- Events in Room -->
                
                <?php
                $event_query = "SELECT * FROM events WHERE room_id = ?";
                $stmt_event = mysqli_prepare($election_conn, $event_query);
                mysqli_stmt_bind_param($stmt_event, 'i', $room['room_id']);
                mysqli_stmt_execute($stmt_event);
                $events_result = mysqli_stmt_get_result($stmt_event);
                ?>
                
                <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                    <?php
                    // Check if voting has started
                    $vote_check_query = "SELECT COUNT(*) FROM votes WHERE event_id = ?";
                    $stmt_vote_check = mysqli_prepare($election_conn, $vote_check_query);
                    mysqli_stmt_bind_param($stmt_vote_check, 'i', $event['event_id']);
                    mysqli_stmt_execute($stmt_vote_check);
                    mysqli_stmt_bind_result($stmt_vote_check, $vote_count);
                    mysqli_stmt_fetch($stmt_vote_check);
                    mysqli_stmt_close($stmt_vote_check);

                    $voting_started = $vote_count > 0;
                    ?>
                    <ul>
                    <div class="inner_container">
                        <li>
                            <strong>Event:</strong> <?= htmlspecialchars($event['event_name']); ?>

                            <?php if ($room['created_by'] === $voter_id): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['event_id']; ?>">
                                    <button type="submit" name="delete_event" class="delete" <?= $voting_started ? 'disabled' : '' ?>>Delete Event</button>
                                </form>
                            <?php endif; ?>

                            <br>
                            <!-- Add Candidate (disabled if voting started) -->
                            <form method="POST" style="display:inline;">
                                <input type="text" name="candidate_name" placeholder="Candidate Name" required <?= $voting_started ? 'disabled' : '' ?>>
                                <input type="hidden" name="event_id" value="<?= $event['event_id']; ?>">
                                <button type="submit" name="add_candidate" <?= $voting_started ? 'disabled' : '' ?>>Add Candidate</button>
                            </form>

                            <!-- Candidates -->
                            <ul>
                                <?php
                                $cand_query = "SELECT * FROM candidates WHERE event_id = ?";
                                $stmt_cand = mysqli_prepare($election_conn, $cand_query);
                                mysqli_stmt_bind_param($stmt_cand, 'i', $event['event_id']);
                                mysqli_stmt_execute($stmt_cand);
                                $cand_result = mysqli_stmt_get_result($stmt_cand);
                                while ($cand = mysqli_fetch_assoc($cand_result)): ?>
                                    <li>
                                        <?= htmlspecialchars($cand['candidate_name']); ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="candidate_id" value="<?= $cand['candidate_id']; ?>">
                                            <button type="submit" name="delete_candidate" class="delete" <?= $voting_started ? 'disabled' : '' ?>>
                                                <?= $voting_started ? 'Delete' : 'Delete' ?>
                                            </button>
                                        </form>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </li>
                        <?php if ($room['created_by'] === $voter_id): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="event_id" value="<?= $event['event_id']; ?>">
                            <button type="submit" name="reset_votes" <?= $vote_count == 0 ? 'disabled' : '' ?>>
                                Reset Votes
                            </button>
                        </form>

                        <form method="GET" action="event_result.php" style="display:inline;">
                            <input type="hidden" name="event_id" value="<?= $event['event_id']; ?>">
                            <button type="submit" <?= $vote_count == 0 ? 'disabled' : '' ?>>View Results</button>
                        </form>

                    <?php endif; ?>
                    </ul>
                <?php endwhile; ?>
                <?php mysqli_stmt_close($stmt_event); ?>
                <!-- Other Buttons -->
                    <!-- Show Results button -->
                <form method="get" action="local_result.php" style="display:inline;">
                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                    <button type="submit" class="btn">Show Results</button>
                </form>

                <!-- Download Full Report button (opens in new tab) -->
                <form method="get" action="local_download_pdf.php" target="_blank" style="display:inline;">
                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                    <button type="submit" class="btn">Download Full Report</button>
                </form>

    </form>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No rooms created yet.</p>
    <?php endif; ?>    
    <a href="create_event.php">← Back</a>
    </div>
    </div>
</body>
</html>
