<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Choose Action</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Global Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f0f8ff;  /* Light blue background */
    display: flex;
    justify-content: center;
    align-items: center; /* Center everything vertically */
    min-height: 100vh;
    padding: 20px;
    color: #333;
}

/* Main Container */
.container {
    background: linear-gradient(to right, #4facfe, #00f2fe); /* Light Blue to Aqua Gradient */
    width: 450px;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0px 15px 30px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid #eee;
}

.container h2 {
    font-size: 36px;
    color: #34495e; /* Darker color for the title */
    margin-bottom: 15px;
    font-weight: 700;
}

.container p {
    font-size: 18px;
    color: #7f8c8d;
    margin-bottom: 30px;
}

/* Button Styling */
button {
    background-color: #3498db;  /* Blue background */
    color: white;
    padding: 16px;
    border: none;
    border-radius: 5px;
    width: 100%;
    font-size: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-bottom: 20px;
}

button:hover {
    background-color: #2980b9;  /* Darker blue on hover */
}

/* Link Styling */
a {
    display: inline-block;
    margin-top: 20px;
    color: #2980b9;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    transition: color 0.3s ease;
}

a:hover {
    color: #1abc9c; /* Lighter green on hover */
    text-decoration: underline;
}

/* Responsive Design for Smaller Screens */
@media (max-width: 768px) {
    .container {
        width: 90%;
        padding: 20px;
    }

    h2 {
        font-size: 28px;
    }

    p {
        font-size: 16px;
    }

    button {
        font-size: 16px;
    }
}

    </style>
</head>
<body>

<div class="container">
    <h2>Welcome to the Election System</h2>
    <p>Choose one of the actions below:</p>

    <!-- Create Room Button -->
    <form method="GET" action="create_room.php">
        <button type="submit">Create Room</button>
    </form>

    <!-- Enter Room Button -->
    <form method="GET" action="enter.php">
        <button type="submit">Enter Room</button>
    </form>

    <a href="voter_dashboard.php">← Back</a>

</div>

</body>
</html>
