<?php
// Existing PHP code to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
$conn = mysqli_connect("localhost", "root", "", "rapidprint2a7", 4306) or die(mysqli_connect_error());


// Check if user is logged in and session is valid
if (!isset($_SESSION['STATUS']) || $_SESSION['STATUS'] !== true) {
    session_destroy();
    header("location: M1_loginForm.php");
    exit();
}

$username = $_SESSION['SESS_USER'];
$session_id = $_SESSION['SESS_ID'];
$query = "SELECT current_State FROM login WHERE username = '$username' AND current_State = 'active' AND session_id = '$session_id'";
$result = mysqli_query($conn, $query);

// Fetch the customer's full name from the profile table
$nameQuery = "SELECT user_FullName FROM profile WHERE username = '$username'";
$nameResult = mysqli_query($conn, $nameQuery);
$customerName = '';

if ($nameResult && mysqli_num_rows($nameResult) > 0) {
    $row = mysqli_fetch_assoc($nameResult);
    $customerName = $row['user_FullName'];
}

// Handle "Claim Reward"
if (isset($_POST['claim_reward'])) {
    $invoice_id = $_POST['invoice_id'];

    // Add RM2 to the card_balance of the customer
    $updateBalanceQuery = "UPDATE membership_card SET card_balance = card_balance + 2 WHERE username = '$username'";
    if (mysqli_query($conn, $updateBalanceQuery)) {
        // Check if the reward has already been claimed for this invoice
        $checkRewardQuery = "SELECT * FROM reward2 WHERE invoice_id = '$invoice_id' AND username = '$username'";
        $rewardResult = mysqli_query($conn, $checkRewardQuery);
        
        // If no record found, insert a new record
        if (mysqli_num_rows($rewardResult) == 0) {
            $insertRewardQuery = "INSERT INTO reward2 (username, invoice_id, reward_Amount, claim_Status, claim_Date) 
                                  VALUES ('$username', '$invoice_id', 2, 1, NOW())";
            if (mysqli_query($conn, $insertRewardQuery)) {
                $rewardClaimMessage = "Reward claimed successfully!";
            } else {
                $rewardClaimMessage = "Error inserting reward status. Please try again.";
            }
        } else {
            // If record exists, update the claim_status
            $updateRewardQuery = "UPDATE reward2 SET claim_Status = 1, claim_Date = NOW() 
                                  WHERE invoice_id = '$invoice_id' AND username = '$username'";
            if (mysqli_query($conn, $updateRewardQuery)) {
                $rewardClaimMessage = "Reward claimed successfully!";
            } else {
                $rewardClaimMessage = "Error updating reward status. Please try again.";
            }
        }
    } else {
        $rewardClaimMessage = "Error updating card balance. Please try again.";
    }
}

// Handle File Upload
$uploadStatus = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if file is a valid format (PDF, JPG, JPEG, PNG)
    if ($fileType != "pdf" && $fileType != "jpg" && $fileType != "jpeg" && $fileType != "png") {
        $uploadStatus = "Sorry, only PDF, JPG, JPEG, PNG files are allowed.";
        $uploadOk = 0;
    }

    // Check file size (5MB max)
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        $uploadStatus = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $uploadStatus = "Sorry, your file was not uploaded.";
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
            // Save the file name in the database
            $fileName = basename($_FILES["fileToUpload"]["name"]);
            $updateFileQuery = "UPDATE profile SET file_name = '$fileName' WHERE username = '$username'";
            if (mysqli_query($conn, $updateFileQuery)) {
                $uploadStatus = "The file has been uploaded successfully.";
            } else {
                $uploadStatus = "Error saving the file to the database.";
            }
        } else {
            $uploadStatus = "Sorry, there was an error uploading your file.";
        }
    }
}

$invoiceQuery = "SELECT i.invoice_id, i.total_amount, i.invoice_date, r.reward_id, r.claim_Status, r.claim_Date
                 FROM invoice2 i 
                 LEFT JOIN reward2 r ON i.invoice_id = r.invoice_id AND r.username = '$username' 
                 WHERE i.username = '$username'";

$invoiceResult = mysqli_query($conn, $invoiceQuery);

// Check for errors in query execution
if (!$invoiceResult) {
    die('Error executing query: ' . mysqli_error($conn));
}


// Fetch claim_Date and reward_Amount for the chart
$chartDataQuery = "SELECT claim_Date, reward_Amount FROM reward2 WHERE username = '$username'";
$chartDataResult = mysqli_query($conn, $chartDataQuery);

$chartData = [];
if ($chartDataResult) {
    while ($row = mysqli_fetch_assoc($chartDataResult)) {
        $chartData[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMPSA RapidPrint Student</title>
    <link rel="stylesheet" href="Module2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<script>
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };
</script>
<div class="container">
    <div class="sidebar">
        <div class="logo">
            <img src="umpsaLogo.png" alt="UMPSA Logo" class="logo-img">
        </div>
        
        <div class="logo">
            <img src="images/logo2.png" alt="UMPSA Logo" class="logo-img">
        </div>
        
        <button class="nav-button">Dashboard</button>
        <a href="CustomerProfile.php" class="nav-button">Profile</a>
        <a href="Membership.php" class="nav-button">Membership Card</a>
		<a href="Manage_order.php" class="nav-button">Order</a> <!-- Fixed the missing closing </a> tag -->
		<a href="order_history.php" class="nav-button">Order History</a>
		<a href="M1_logout.php" class="nav-button">Log out</a>
    </div>

    <div class="main-content">
        <h1>WELCOME TO THE UMPSA RAPIDPRINT, <?php echo htmlspecialchars($customerName); ?></h1>

        <!-- File Upload Section -->
        <div class="upload-section">
            <h2>Upload Your File</h2>
            <form method="POST" enctype="multipart/form-data">
                <label for="fileToUpload" class="upload-box">
                    <p>Drop your file here or click to upload</p>
                    <input type="file" name="fileToUpload" id="fileToUpload" class="upload-area">
                </label>
                <button type="submit" class="nav-button">Upload File</button>
            </form>
            <?php if (!empty($uploadStatus)) { ?>
                <p class="upload-status"><?= $uploadStatus ?></p>
            <?php } ?>
        </div>

        <!-- Point Information Section -->
        <div class="point-information">
            <h2>Point Information</h2>
            <p>Every customer will earn points with each successful payment. For every 1 time purchase (1 invoice), customers will earn RM2 point.</p>

            <p class="history-text">History Membership Point Report</p>
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Invoice Date</th>
                        <th>Reward Status</th>
                        <th>Claim Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = mysqli_fetch_assoc($invoiceResult)) {
                        $invoice_id = $row['invoice_id'];
                        $invoice_date = $row['invoice_date'];
                        $claim_status = $row['claim_Status'];
                        $claim_date = $row['claim_Date'];

                        echo "<tr>";
                        echo "<td>$invoice_id</td>";
                        echo "<td>$invoice_date</td>";
                        echo "<td>";

                        if ($claim_status == 0 || $claim_status == null) {
                            echo "Not Claimed";
                        } else {
                            echo "Claimed";
                        }

                        echo "</td>";
                        echo "<td>";

                        if ($claim_status != 0 && $claim_status != null) {
                            echo $claim_date;
                        }

                        echo "</td>";
                        echo "<td>";

                        // Display the "Claim Reward" button only if reward has not been claimed
                        if ($claim_status == 0 || $claim_status == null) {
                            echo " 
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='invoice_id' value='$invoice_id'>
                                    <button type='submit' name='claim_reward' class='nav-button'>Claim Reward</button>
                                </form>";
                        } else {
                            echo "- Reward Claimed";
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

            <?php if (!empty($rewardClaimMessage)) { ?>
                <p class="reward-message"><?= $rewardClaimMessage ?></p>
            <?php } ?>
        </div>

        <!-- Printing Statistics Section -->
        <div class="printing-stats">
            <h2>Customer Claim Reward Statistics</h2>
            <canvas id="printChart"></canvas>
        </div>
    </div>
</div>

<script>
    const chartData = <?php echo json_encode($chartData); ?>;
    const labels = chartData.map(item => item.claim_Date);
    const data = chartData.map(item => item.reward_Amount);

    const ctx = document.getElementById('printChart').getContext('2d');
    const printChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Reward Amount',
                data: data,
                backgroundColor: '#7e45b0',
                borderColor: '#000000',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

</body>
</html>



