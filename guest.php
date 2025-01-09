<?php
// Start the session
session_start();

// Connect to the database
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306");
if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize variables
$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_contact = $_POST['guest_contact'];

    // Validate phone number
    if (empty($guest_contact) || !preg_match('/^[0-9]{10,13}$/', $guest_contact)) {
        $error_message = "Please enter a valid phone number (10 to 13 digits).";
    } else {
        // Insert into database
        $query = "INSERT INTO guest (guest_contact) VALUES (?)";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, "s", $guest_contact);

        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Phone number saved successfully!";
        } else {
            $error_message = "Error saving phone number: " . mysqli_error($link);
        }

        mysqli_stmt_close($stmt);
    }
}

// Close the connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Guest Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f9;
        }

        .container {
            text-align: center;
            background: #fff;
            padding: 20px 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 20px;
            background: #5cb85c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #4cae4c;
        }

        .message {
            margin: 10px 0;
            font-size: 14px;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Enter Your Phone Number</h2>
        <form method="POST" action="guest.php">
            <input type="text" name="guest_contact" placeholder="Phone Number (10-13 digits)" required>
            <button type="submit">Submit</button>
        </form>
        <div class="message">
            <?php
            if (!empty($error_message)) {
                echo "<p class='error'>$error_message</p>";
            }

            if (!empty($success_message)) {
                echo "<p class='success'>$success_message</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>

