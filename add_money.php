<!-- add_money.php -->
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306") or die("Connection failed: " . mysqli_connect_error());


// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount'])) {
    // Retrieve the username from the form
    $username = $_POST['username'];

    // Check if a valid amount is selected
    $amount = $_POST['amount'];
    
    // If the amount is 'custom', use the custom amount field
    if ($amount == 'custom' && isset($_POST['customAmount']) && is_numeric($_POST['customAmount']) && $_POST['customAmount'] > 0) {
        $amount = (float)$_POST['customAmount']; // Cast to float
    } else {
        $amount = (float)$amount; // Cast to float for predefined values like 5, 10, etc.
    }

    // If the amount is valid, proceed
    if ($amount > 0) {
        // Fetch the current balance from the database
        $stmt = $link->prepare("SELECT card_Balance FROM membership_card WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // Fetch the existing balance
            $stmt->bind_result($currentBalance);
            $stmt->fetch();
            $stmt->close();

            // Calculate the new balance
            $newBalance = $currentBalance + $amount;

            // Update the balance in the database for the specified username
            $updateStmt = $link->prepare("UPDATE membership_card SET card_Balance = ? WHERE username = ?");
            $updateStmt->bind_param("ds", $newBalance, $username);

            if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
                echo "<script>
                    alert('Money added successfully!');
                    window.location.href = '" . (isset($_SESSION['redirect_after_topup']) ? $_SESSION['redirect_after_topup'] : 'membership.php') . "';
                </script>";
            } else {
                echo "<script>alert('Failed to update balance. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('No membership found for this username.');</script>";
        }
    } else {
        echo "<script>alert('Please enter a valid amount!');</script>";
    }
} else {
    echo "<script>alert('Invalid request!');</script>";
}

// Close the database connection
$link->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money</title>
    <link rel="stylesheet" href="Module2.css">
</head>
<body>
    <div class="container">
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
            <button class="nav-button">Log Out</button>
        </div>
        
        <div class="main-content">
            <h1>Add Money to Membership</h1>
            <form method="POST" action="add_money.php">
                <label for="amount">Select Amount:</label>
                <select name="amount" id="amount">
                    <option value="5">RM5</option>
                    <option value="10">RM10</option>
                    <option value="20">RM20</option>
                    <option value="50">RM50</option>
                    <option value="custom">Other</option>
                </select>
                <br>

                <label for="customAmount" style="display:none;">Enter Amount:</label>
                <input type="number" id="customAmount" name="customAmount" min="1" style="display:none;">
                <br>

                <input type="hidden" name="username" value="<?php echo htmlspecialchars($_SESSION['SESS_USER']); ?>">

                <button type="submit">Add Money</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('amount').addEventListener('change', function() {
            if (this.value === 'custom') {
                document.getElementById('customAmount').style.display = 'block';
            } else {
                document.getElementById('customAmount').style.display = 'none';
            }
        });
    </script>
</body>
</html>







