<?php
require 'vendor/autoload.php';
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['SESS_USER'])) {
    header("Location: M1_loginForm.php"); // Redirect to login page if not logged in
    exit();
}

// Get username from session
$username = $_SESSION['SESS_USER'];

// Connect to the database server
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7_2", 4306) or die("Connection failed: " . mysqli_connect_error());

// Initialize $isRegistered to false (default assumption)
$isRegistered = false;
$row = null; // Initialize the $row variable to handle cases where no data is found

// Query to fetch user profile details
$profileQuery = "SELECT user_FullName, user_Contact, user_Email FROM profile WHERE username = '$username'";
$profileResult = mysqli_query($link, $profileQuery);
$profileRow = mysqli_fetch_assoc($profileResult);

// Query to fetch membership card details
$membershipQuery = "SELECT card_Number, card_Balance FROM membership_card2 WHERE username = '$username'";
$membershipResult = mysqli_query($link, $membershipQuery);

// Check if the user is already registered for a membership card
if (mysqli_num_rows($membershipResult) > 0) {
    $isRegistered = true; // User is registered
    $membershipRow = mysqli_fetch_assoc($membershipResult);
    $row = $membershipRow;
}

// Function to generate a unique card number
function generateUniqueCardNumber($link) {
    // Generate a random 10-digit card number
    $cardNumber = mt_rand(1000000000, 9999999999);

    // Check if the card number already exists in the database
    $query = "SELECT COUNT(*) FROM membership_card2 WHERE card_Number = '$cardNumber'";
    $result = mysqli_query($link, $query);
    $row = mysqli_fetch_array($result);

    // If the card number exists, generate a new one
    if ($row[0] > 0) {
        return generateUniqueCardNumber($link); // Recursively generate a new card number
    }

    return $cardNumber; // Return the unique card number
}

// Handle membership card registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['matricIDFile'])) {
    $targetDir = "membership_uploads/";
    $targetFile = $targetDir . basename($_FILES["matricIDFile"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Validate file format and size
    if ($fileType != "pdf") {
        $uploadStatus = "Only PDF files are allowed.";
        $uploadOk = 0;
    }
    if ($_FILES["matricIDFile"]["size"] > 5000000) { // 5MB limit
        $uploadStatus = "Your file is too large.";
        $uploadOk = 0;
    }

    // Process file upload if validation passed
    if ($uploadOk === 1) {
        if (move_uploaded_file($_FILES["matricIDFile"]["tmp_name"], $targetFile)) {
            // Secure inputs to prevent SQL injection
            $escapedUsername = mysqli_real_escape_string($link, $username);
            $escapedFileName = mysqli_real_escape_string($link, basename($_FILES["matricIDFile"]["name"]));

            // Generate a unique card number
            $cardNumber = generateUniqueCardNumber($link); // Ensure card number is unique
            $registrationDate = date('Y-m-d'); // Current date
            $cardStatus = 'active'; // Initial card status

            // Insert membership details into the database
            $insertMembershipQuery = "
                INSERT INTO membership_card2 (card_Number, card_Balance, username, id_matric_file, card_status, registration_date) 
                VALUES ('$cardNumber', 0, '$escapedUsername', '$escapedFileName', '$cardStatus', '$registrationDate')
            ";

            if (mysqli_query($link, $insertMembershipQuery)) {
                $uploadStatus = "Membership card registration successful.";
                $isRegistered = true; // Update the flag after successful registration
                // After successful registration, fetch the updated membership details
                $membershipQuery = "SELECT card_Number, card_Balance FROM membership_card2 WHERE username = '$username'";
                $membershipResult = mysqli_query($link, $membershipQuery);
                $row = mysqli_fetch_assoc($membershipResult); // Fetch updated data
            } else {
                $uploadStatus = "Error saving membership details to the database: " . mysqli_error($link);
            }
        } else {
            $uploadStatus = "Error uploading your file.";
        }
    }
}

// Handle discontinuing the membership
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discontinueMembership'])) {
    // Update the card_status to 'INACTIVE' and reset points to 0 in the database
    $updateQuery = "UPDATE membership_card2 SET card_status = 'INACTIVE', card_Balance = 0 WHERE username = '$username'";
    if (mysqli_query($link, $updateQuery)) {
        $uploadStatus = "Your membership has been successfully discontinued.";
        $isRegistered = false; // Membership is now inactive, so set flag to false
    } else {
        $uploadStatus = "Error updating membership status: " . mysqli_error($link);
    }
}

// Include necessary library for QR code
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Check if the user is registered for a membership card
$membershipQuery = "SELECT card_Number, card_Balance FROM membership_card2 WHERE username = '$username'";
$membershipResult = mysqli_query($link, $membershipQuery);

if ($membershipResult && mysqli_num_rows($membershipResult) > 0) {
    $row = mysqli_fetch_assoc($membershipResult);

    // Generate QR data with valid membership information
    $qrData = json_encode([
        "username" => $username,
        "membership_card_number" => $row['card_Number'],
        "points" => $row['card_Balance']
    ]);

    // Directory to save the QR code
    $qrDir = "membership_uploads/";
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0777, true); // Create directory if it doesn't exist
    }

    // Generate unique QR code filename (based on username)
    $qrFilename = $qrDir . "qrcode_" . $username . ".png";

    // Create the QR code
	$qrCode = QrCode::create($qrData)
		->setSize(500) // Adjust size in pixels (default is usually 200)
		->setMargin(10); // Set margin around the QR code
	$writer = new PngWriter();
	$result = $writer->write($qrCode);

	// Save the QR code image to the specified file
	$result->saveToFile($qrFilename);


    // Save the QR code image to the specified file
    $result->saveToFile($qrFilename);

    // Read the QR code image as binary data for storage
    $qrCodeBlob = file_get_contents($qrFilename);

    // Store the QR code path and blob in the database
    $updateQRQuery = "
        UPDATE membership_card2 
        SET qr_code_path = '" . mysqli_real_escape_string($link, $qrFilename) . "',
            qr_code_blob = '" . mysqli_real_escape_string($link, $qrCodeBlob) . "'
        WHERE username = '$username'
    ";

    if (mysqli_query($link, $updateQRQuery)) {
        echo "QR code data stored successfully.";
    } else {
        echo "Error storing QR code data: " . mysqli_error($link);
    }
} else {
    // Handle case where no membership exists
    echo "No membership found for this user.";
}


// Close the database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Page</title>
    <link rel="stylesheet" href="Module2.css">
	
	<script>
        // Function to confirm before discontinuing membership
        function confirmDiscontinue() {
            return confirm("Are you sure you want to discontinue the membership card?");
        }
    </script>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="umpsaLogo.png" alt="Logo" class="logo-img">
            </div>
            <div class="logo">
                <img src="images/logo2.png" alt="Logo" class="logo-img">
            </div>
            <a href="Module2.php" class="nav-button">Dashboard</a>
            <a href="CustomerProfile.php" class="nav-button">Profile</a>
            <button class="nav-button">Membership Card</button>
            <form action="M1_logout.php" method="POST">
                <button type="submit" class="nav-button">Log Out</button>
            </form>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h1>Membership Card</h1>

            <!-- Membership Card Details Box -->
            <div class="membership-box">
                <h2>Membership Card Details</h2>

                <?php if ($isRegistered) { ?>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($profileRow['user_FullName']); ?></p>
                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($profileRow['user_Contact']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profileRow['user_Email']); ?></p>
                    <p><strong>Membership Card Number:</strong> <?php echo htmlspecialchars($row['card_Number']); ?></p>
                    <p><strong>Points:</strong> RM<?php echo htmlspecialchars($row['card_Balance']); ?></p>

                    <!-- QR Code Section Box and Add Money/Redeem Buttons -->
                    <div class="qr-box">
                        <br><br>
                        <h2>SCAN THIS QR CODE</h2>
                        <div class="qr-code">
                            <!-- Display the QR code -->
                            <img src="<?php echo htmlspecialchars($qrFilename); ?>" alt="QR Code">
                        </div>
                        <p>Scan this QR code to access your membership details.</p>
                    </div>

                    <!-- Buttons for Adding Money or Redeeming Points -->
                    <div class="upload-box">
                        <a href="add_money.php?username=<?php echo urlencode($username); ?>" class="nav-button small-button">Add Money</a>
                        <a href="redeem.php?username=<?php echo urlencode($username); ?>" class="nav-button small-button">Redeem</a>
                    </div>
					
					<form method="POST" onsubmit="return confirmDiscontinue()">
						<button type="submit" name="discontinueMembership" class="nav-button small-button">Discontinue Membership</button>
					</form>
                <?php } else { ?>
                    <p>You are not registered for a membership card.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <label for="matricIDFile" class="upload-box">
                            <p>Upload your Matric ID (PDF only)</p>
                            <input type="file" name="matricIDFile" id="matricIDFile" class="upload-area">
                        </label>
                        <button type="submit" class="nav-button">Register Membership</button>
                    </form>
                <?php } ?>

                <?php if (!empty($uploadStatus)) { ?>
                    <p class="upload-status"><?php echo htmlspecialchars($uploadStatus); ?></p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>
