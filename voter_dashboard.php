<?php
session_start();
include('db.php');

// Ensure the user is logged in
if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

$voter_id = $_SESSION['voter_id'];

// Fetch voter details
$query = "SELECT voter_name, phone_number FROM voters WHERE voter_id = '$voter_id'";
$voter_result = mysqli_query($election_conn, $query);
$voter = mysqli_fetch_assoc($voter_result);

// Handle form submission to update voter details via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['voter_name']) && isset($_POST['phone_number'])) {
        $voter_name = $_POST['voter_name'];
        $phone_number = $_POST['phone_number'];

        // Update query
        $update_query = "UPDATE voters SET voter_name='$voter_name', phone_number='$phone_number' WHERE voter_id = '$voter_id'";
        
        if (mysqli_query($election_conn, $update_query)) {
            // Return success message
            echo json_encode([
                'status' => 'success',
                'message' => 'Your details were updated successfully!'
            ]);
        } else {
            // Return error message
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update details.'
            ]);
        }
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voter Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Global Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: rgb(179, 232, 215);  /* Soft background color */
            display: flex;
            justify-content: center;
            align-items: center; /* Center everything vertically */
            min-height: 100vh;
            color: #333;
            padding: 20px;
            flex-direction: column; /* Stack elements vertically */
            gap: 20px; /* Space between elements */
        }

        /* Main Container */
        .container {
            background: linear-gradient(to right, #4facfe, #00f2fe); /* Light Blue to Aqua Gradient */
            width: 550px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0px 15px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid #eee;
        }

        /* Hover effect for container */
        .container:hover {
            transform: scale(1.05); /* Slight increase on hover */
            box-shadow: 0px 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Header */
        h2 {
            font-size: 36px;
            color: #34495e; /* Darker color for the name */
            margin-bottom: 15px;
            font-weight: 700;
        }

        h3 {
            font-size: 20px;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        /* Button Styling */
        button, .logout-btn {
            background-color: #3498db;  /* Blue background */
            color: white;
            padding: 16px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 25px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        }

        button:hover, .logout-btn:hover {
            background-color: #2980b9;  /* Darker blue on hover */
        }

        /* Logout Button */
        .logout-btn {
            background-color: #e74c3c;
            margin-top: 40px; /* Add space between container and logout button */
        }

        .logout-btn:hover {
            background-color: #c0392b;  /* Darker red on hover */
        }

        /* Media Queries for Responsive Design */
        @media (max-width: 768px) {
            .container {
                width: 90%;
                padding: 20px;
            }

            h2 {
                font-size: 28px;
            }

            h3 {
                font-size: 18px;
            }

            button, .logout-btn {
                font-size: 16px;
            }

            input[type="text"] {
                font-size: 14px;
            }
        }
    </style>
    <!-- Include SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($voter['voter_name']); ?></h2>

    <!-- Button to trigger SweetAlert for updating details -->
    <h3>Update Your Details</h3>
    <button id="updateDetailsBtn">Update Details</button>

    <h3>Cast Your Vote</h3>
    <a href="vote.php">Global Vote</a><br>
    <a href="create_event.php">Local Vote</a>
</div>

<!-- Logout Button -->
<form action="logout.php" method="POST">
    <input type="submit" value="Logout" class="logout-btn">
</form>

<!-- Include SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.all.min.js"></script>

<script>
    document.getElementById('updateDetailsBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Update Your Details',
            html: ` 
                <input type="text" id="voter_name" class="swal2-input" placeholder="Enter your name" value="<?php echo $voter['voter_name']; ?>" required>
                <input type="text" id="phone_number" class="swal2-input" placeholder="Enter your phone number" value="<?php echo $voter['phone_number']; ?>" required>
            `,
            preConfirm: () => {
                const name = document.getElementById('voter_name').value;
                const phone = document.getElementById('phone_number').value;
                return { name, phone };
            },
            showCancelButton: true,
            confirmButtonText: 'Update',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                const name = result.value.name;
                const phone = result.value.phone;

                // Check if there are any changes in the data
                const isDataChanged = name !== "<?php echo $voter['voter_name']; ?>" || phone !== "<?php echo $voter['phone_number']; ?>";

                if (isDataChanged) {
                    // If data changed, send the updated data to PHP via AJAX
                    fetch('voter_dashboard.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            'voter_name': name,
                            'phone_number': phone,
                        }),
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(response => response.json())  // Expect JSON response
                    .then(responseData => {
                        if (responseData.status === 'success') {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Your details were updated successfully!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();  // Reload the page after success
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: responseData.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while updating your details.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                } else {
                    // If no changes, show SweetAlert message
                    Swal.fire({
                        title: 'No Changes',
                        text: 'You have not made any changes to your details.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });
</script>

</body>
</html>
