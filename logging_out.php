<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f0f0f0;
        }
        h2 {
            color: #333;
        }
        .message {
            font-size: 1.2rem;
            color: green;
            margin: 20px;
        }
        /* Add a loading spinner or any other design */
        .spinner {
            margin-top: 30px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <h2>Logging Out...</h2>

    <?php
    // Show the logout message if it's set in the session
    if (isset($_SESSION['logout_message'])) {
        echo "<div class='message'>" . $_SESSION['logout_message'] . "</div>";
        // Unset the message after displaying it
        unset($_SESSION['logout_message']);
    }
    ?>

    <div class="spinner"></div>  <!-- Optional loading spinner -->

    <p>You will be redirected to the login page shortly...</p>

    <!-- Redirect to login page after 1.5 seconds -->
    <script>
        setTimeout(function() {
            window.location.href = "index.html";
        }, 1500); // 1.5 seconds delay before redirecting
    </script>

</body>
</html>
