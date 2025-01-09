<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306");


if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}

$order_details = null;
$payment_status = "pending";
$payment_method = isset($_GET['payment_method']) ? htmlspecialchars($_GET['payment_method']) : "cash";

// Get order ID from GET or SESSION
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_SESSION['order_id']) ? $_SESSION['order_id'] : null);

if ($order_id) {
    // Fetch order details
    $stmt = $link->prepare("SELECT order_id, total_Amount FROM `order` WHERE order_id = ?");
    if ($stmt === false) {
        die('MySQL prepare error: ' . $link->error);
    }
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_details = $result->num_rows > 0 ? $result->fetch_assoc() : null;
    $stmt->close();

    // Check if the order details exist
    if ($order_details) {
        // Proceed with payment handling if the form is submitted
        if (isset($_POST['submit_payment'])) {
            $total_amount = (float) $order_details['total_Amount'];
            $payment_id = "pay_" . substr(uniqid(mt_rand(), true), 0, 8);

            // Check if the user is logged in (session check)
            if (!isset($_SESSION['SESS_USER'])) {
                echo "You must be logged in to access this page.";
                exit();
            }

            // Debugging: Output the session variables
            $katanama = trim($_SESSION['SESS_USER']); // Use session username for card lookup

            // Fetch the card_id for the logged-in user
            $stmt = $link->prepare("SELECT card_id FROM membership_card WHERE username = ?");
            if ($stmt === false) {
                die('MySQL prepare error: ' . $link->error);
            }
            $stmt->bind_param("s", $katanama);
            $stmt->execute();
            $card_result = $stmt->get_result();

            if ($card_result->num_rows > 0) {
                $card_id = $card_result->fetch_assoc()['card_id']; // Get the card_id
            } else {
                echo "No card found for username: " . $katanama . "<br>"; // Debug output if no card found
                exit(); // Stop execution if card_id is not found
            }
            $stmt->close();

            // Fetch all staff_ids
            $staff_result = $link->query("SELECT staff_id FROM staff");
            if ($staff_result->num_rows > 0) {
                // Fetch all staff ids into an array
                $staff_ids = [];
                while ($row = $staff_result->fetch_assoc()) {
                    $staff_ids[] = $row['staff_id'];
                }

                // Select a random staff_id from the array
                $staff_id = $staff_ids[array_rand($staff_ids)];
            } else {
                echo "No staff found.";
                exit(); // Stop execution if no staff_id is found
            }

            // Ensure both card_id and staff_id are not NULL before proceeding
            if (!$card_id || !$staff_id) {
                echo "Card ID or Staff ID is missing!";
                exit(); // Stop execution if any ID is missing
            }

            // Insert data into the payment table
            $stmt = $link->prepare(
                "INSERT INTO payment (payment_id, payment_method, payment_status, card_id, payment_Amount, order_id, staff_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if ($stmt === false) {
                die('MySQL prepare error: ' . $link->error);
            }
            $stmt->bind_param(
                "ssssdis",
                $payment_id,
                $payment_method,
                $payment_status,
                $card_id,
                $total_amount,
                $order_id,
                $staff_id
            );

            if (!$stmt->execute()) {
                die('Error executing query: ' . $stmt->error); // If there's an issue with executing the query
            }
            $stmt->close();

            // Redirect after successful insert
            header("Location: payment.php");
            exit();
        }
    } else {
        echo "Order details not found.";
        exit(); // If no order details found, stop execution
    }
} else {
    echo "Order ID is missing or invalid.";
    exit(); // If order_id is not provided or invalid
}

// Close the database connection
mysqli_close($link);
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Cash</title>
    <link rel="stylesheet" href="payCash.css">
</head>
<body>
    <div class="container">
        <h1>Pay Cash</h1>

        <?php if ($order_details): ?>
            <div class="order-summary">
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_details['order_id']); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($order_details['total_Amount'], 2); ?></p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst($payment_method); ?></p>
            </div>
            <div class="message">
                <p>Please pay at your counter. Thank you!</p>
            </div>
        <?php else: ?>
            <p>Order details not found. Please check again.</p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="submit_payment" value="1">
            <div class="actions">
                <button type="submit">Go to Payment Status</button>
            </div>
        </form>
    </div>
</body>
</html>
