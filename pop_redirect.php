<?php
// Start the session to store and retrieve session variables
session_start();

// Set a message in session for demonstration purposes (you can set this dynamically based on your logic)
if (!isset($_SESSION['message'])) {
    $_SESSION['message'] = "Invalid Entry Try again";
}

// If you want to clear the message after showing the popup, you can unset it
if (isset($_GET['clear_message'])) {
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Popup Example with Redirect</title>
    <style>
        /* Popup styles */
        #popup-message {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border-radius: 10px;
            text-align: center;
            font-size: 16px;
        }
        /* Close button styles */
        .popup-btn {
            margin-top: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        .popup-btn:hover {
            background-color: #45a049;
        }
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

    <!-- Displaying the popup message -->
    <?php if (isset($_SESSION['message'])): ?>
        <div id="popup-message">
            <p><?php echo $_SESSION['message']; ?></p>
            <p>You will be redirected to the Home page shortly...</p>
        </div>
        <center>
        <div class="spinner"></div>  <!-- Optional loading spinner -->
        <p></p>
    </center>
    <?php endif; ?>
    

    <script>
        // Function to show the popup
        function showPopup() {
            document.getElementById('popup-message').style.display = 'block';
        }

        // Function to close the popup and trigger redirect
        function closePopup() {
            document.getElementById('popup-message').style.display = 'none';
            window.location.href = "index.html";  // Redirect to another page after closing popup
        }

        // Auto redirect after a set time (e.g., 5 seconds)
        setTimeout(function() {
            window.location.href = "index.html";  // Redirect after 5 seconds
        }, 2000);

        // Show the popup when the page loads if a message is set
        <?php if (isset($_SESSION['message'])): ?>
            showPopup();
        <?php endif; ?>
    </script>

</body>
</html>
