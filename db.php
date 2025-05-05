<?php
// Election Database Connection
$election_host = "localhost";
$election_user = "root";
$election_pass = "";
$election_db = "online_vote";

// Connect to Election DB
$election_conn = mysqli_connect($election_host, $election_user, $election_pass, $election_db);
if (!$election_conn) {
    die("Election Database connection failed: " . mysqli_connect_error());
}
?>
