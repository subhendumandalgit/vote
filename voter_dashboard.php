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
    background-color: #e8f6f3;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    color: #2c3e50;
    padding: 20px;
    flex-direction: column;
    gap: 20px;
}

/* Container */
.container {
    background: linear-gradient(135deg, #74ebd5, #acb6e5); /* Gradient Blue-Lavender */
    width: 100%;
    max-width: 960px;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #ddd;
}

.container:hover {
    transform: scale(1.015);
    box-shadow: 0 20px 35px rgba(0, 0, 0, 0.15);
}

/* Headings */
h2 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 10px;
}

h3 {
    font-size: 1.25rem;
    color: #34495e;
    margin-top: 0px;
    margin-bottom: 10px;
}

/* Buttons */
button, .logout-btn {
    background-color: #3498db;
    color: white;
    padding: 14px 20px;
    border: none;
    border-radius: 8px;
    width: 45%;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
    margin-top: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

button:hover, .logout-btn:hover {
    background-color: #2c80b4;
    transform: translateY(-2px);
}

/* Logout Button */
.logout-btn {
    width: 100%;
    background-color: #e74c3c;
    margin-top: 40px;
}

.logout-btn:hover {
    background-color: #c0392b;
}

/* Links */
a {
    text-decoration: none;
    font-size: 1.2rem;
    color: #1e3799;
    display: inline-block;
    margin-top: 10px;
    transition: color 0.2s ease, transform 0.2s ease;
}

a:hover {
    color: #e74c3c;
    transform: scale(1.05);
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 25px;
    }

    h2 {
        font-size: 2rem;
    }

    h3 {
        font-size: 1rem;
    }

    button, .logout-btn {
        font-size: 1rem;
        padding: 12px;
    }

    a {
        font-size: 1rem;
    }
}
/* Unified Subheading Style */
.section-title {
    font-size: 1.6rem;
    color: #2c3e50;
    margin-top: 30px;
    margin-bottom: 15px;
    font-weight: 600;
}

/* Hyperlink styled as button */
a.link-button {
    display: inline-block;
    background-color: #27ae60;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    margin: 10px;
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

a.link-button:hover {
    background-color: #219150;
    transform: translateY(-2px);
    color: #fff;
}


    </style>
    <!-- Include SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($voter['voter_name']); ?></h2>
    <br>
    <h3 class="section-title">Update Your Details Here</h3>
<button id="updateDetailsBtn">Update Details</button>
<br><br><br>
<h3 class="section-title">Choose one for Voting</h3>
<a href="vote.php" class="link-button">Click Here For Voting</a>
<a href="create_event.php" class="link-button">Create Your Own Room</a>

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
