<?php
// Enable exceptions for mysqli to handle errors via try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Set database connection variables
$election_host = 'localhost';  // Replace with your host
$election_user = 'root';       // Replace with your username
$election_pass = '';           // Replace with your password
$election_db   = 'online_vote'; // Replace with your database name

try {
    // Attempt to establish a database connection
    $election_conn = new mysqli($election_host, $election_user, $election_pass, $election_db);
    
    // If connection is successful, you can proceed with further operations
    // No need for additional checks as exceptions will be thrown on failure
} catch (mysqli_sql_exception $e) {
    // Catch the exception if the database connection fails
    // Log the error message to the server's error log (optional, for debugging)
    error_log("Election Database connection failed: " . $e->getMessage());
    
    // Output the HTML error page directly
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Server Failed</title>
      <style>
        * {
          box-sizing: border-box;
          margin: 0;
          padding: 0;
        }

        /* Full-page layout */
        html, body {
          height: 100%;
          font-size: 16px;
          font-family: "Arial", sans-serif;
          display: flex;
          justify-content: center;
          align-items: center;
          overflow: hidden;
          background: linear-gradient(135deg, rgba(0, 0, 0, 0.85), rgba(255, 0, 0, 0.85));
          color: white;
          text-align: center;
          position: relative;
        }

        /* Centering container */
        .container {
          position: relative;
          z-index: 1;
          padding: 50px;
          background: rgba(0, 0, 0, 0.5);
          border-radius: 20px;
          box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
          backdrop-filter: blur(10px);
          max-width: 600px;
          width: 100%;
          text-align: center;
        }

        /* Error text */
        h1 {
          font-size: 100px;
          font-weight: bold;
          font-family: "Courier New", monospace;
          color: #fff;
          letter-spacing: -5px;
          text-shadow: 0 0 20px rgba(255, 255, 255, 0.6), 0 0 30px rgba(255, 0, 0, 0.9);
          animation: fadeIn 2s ease-in-out forwards, bounce 2s ease-in-out infinite;
        }

        /* Span inside h1 for animation */
        h1 span {
          display: inline-block;
          animation: shake 1s ease-in-out infinite alternate;
        }

        h1 span[data-overlay] {
          font-size: 40px;
          animation: none;
          color: rgba(255, 255, 255, 0.7);
        }

        /* Paragraph for message */
        p {
          color: #fff;
          font-size: 1.25rem;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
          margin-top: 20px;
          animation: fadeIn 3s ease-in-out forwards;
        }

        /* Retry Button */
        .retry-btn {
          margin-top: 30px;
          padding: 10px 20px;
          font-size: 1.2rem;
          background: #ff5733;
          color: white;
          border: none;
          border-radius: 10px;
          cursor: pointer;
          transition: background 0.3s;
        }

        .retry-btn:hover {
          background: #e74c3c;
        }

        /* Keyframe animations */
        @keyframes fadeIn {
          0% {
            opacity: 0;
            transform: translateY(20px);
          }
          100% {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @keyframes bounce {
          0%, 100% {
            transform: translateY(0);
          }
          50% {
            transform: translateY(-30px);
          }
        }

        @keyframes shake {
          0% {
            transform: rotate(-5deg);
          }
          100% {
            transform: rotate(5deg);
          }
        }

        /* Titanic breaking effect */
        .titanic {
          position: absolute;
          left: 0;
          bottom: -5%;
          width: 100%;
          height: 0.25em;
          font-size: 45vmax;
          transform-origin: 50% 100%;
          transform: rotate(30deg);
          color: rgba(255, 0, 0, 0.8);
        }

        .titanic::before {
          content: "";
          position: absolute;
          left: 0;
          right: 0;
          top: -50%;
          width: 0.1em;
          height: 0.25em;
          background: #e74c3c;
          box-shadow: 0.25em 0 0 #e74c3c, 0.5em 0 0 #e74c3c;
        }

        .titanic::after {
          content: "";
          position: absolute;
          left: 0;
          right: 0;
          bottom: 0;
          width: 100%;
          height: 0.25em;
          background: linear-gradient(to bottom, #e74c3c, #ff5733);
          clip-path: polygon(0 0, 100% 0, 100% 50%, 0 100%);
        }
      </style>
    </head>
    <body>
      <!-- The container that holds the content -->
      <div class="container">
        <h1 data-txt="500" aria-label="Internal Server Error">
          !500<span data-overlay="🤦‍♀️"></span><span data-overlay="🤦‍♂️"></span>
        </h1>
        <p>Internal Server Failed</p>

        <!-- Retry Button -->
        <button class="retry-btn" onclick="window.location.reload();">Retry Connection</button>
      </div>

      <!-- Titanic breaking effect at the bottom -->
      <div class="titanic"></div>
    </body>
    </html>';
    exit;
}
?>
