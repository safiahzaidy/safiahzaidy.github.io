<?php
// Check if the username is passed via URL, otherwise use a session or error message
if (isset($_GET['username'])) {
    $username = $_GET['username'];
} else {
    echo "Username is required!";
    exit;
}

// Connect to the database
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7_2", "4306") or die("Connection failed: " . mysqli_connect_error());

// Prepare the SQL query to fetch customer membership details by username
$sql = "SELECT m.card_Balance AS points 
        FROM membership_card2 m 
        WHERE m.username = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $username); // "s" denotes the parameter is a string

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Check if record exists
if ($result->num_rows > 0) {
    // Fetch the membership data
    $row = $result->fetch_assoc();
    $currentBalance = $row['points'];
} else {
    echo "No membership data found for username: " . htmlspecialchars($username);
    exit;
}

// Handle form submission to redeem points
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeemAmount'])) {
    $redeemAmount = $_POST['redeemAmount'];

    // If the redeem amount is valid
    if ($redeemAmount > 0 && $redeemAmount <= $currentBalance) {
        $newBalance = $currentBalance - $redeemAmount;

        // Update the balance in the database
        $updateStmt = $link->prepare("
            UPDATE membership_card2 
            SET card_Balance = ? 
            WHERE username = ?
        ");
        $updateStmt->bind_param("ds", $newBalance, $username);

        if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
            echo "<script>alert('Points redeemed successfully!'); window.location.href='membership.php?username=" . urlencode($username) . "';</script>";
        } else {
            echo "<script>alert('Failed to redeem points. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Please enter a valid redeem amount!');</script>";
    }
}

// Close the database connection
$stmt->close();
$link->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Points</title>
    <link rel="stylesheet" href="Module2.css">
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
            <a href="CustomerProfile.php?username=<?php echo urlencode($username); ?>" class="nav-button">Profile</a>
            <a href="membership.php?username=<?php echo urlencode($username); ?>" class="nav-button">Membership Card</a>
            <button class="nav-button">Log Out</button>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h1>Redeem Points</h1>
            <div class="redeem-box">
                <p><strong>Current Balance: RM<?php echo htmlspecialchars($currentBalance); ?></strong></p>
                <form method="POST" action="redeem.php?username=<?php echo urlencode($username); ?>">
                    <label for="redeemAmount">Enter Amount to Redeem:</label>
                    <input type="number" name="redeemAmount" id="redeemAmount" min="1" max="<?php echo $currentBalance; ?>" required>
                    <br><br>
                    <button type="submit">Redeem</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

