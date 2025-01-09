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
$payment_status = "ordered";
$payment_method = "membership card";

// Get order ID from GET or SESSION
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_SESSION['order_id']) ? $_SESSION['order_id'] : null);

if ($order_id) {
    // Fetch order details
    $stmt = $link->prepare("SELECT order_id, total_Amount FROM `order` WHERE order_id = ?");
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

            $katanama = trim($_SESSION['SESS_USER']); // Use session username for card lookup

            // Fetch the card details for the logged-in user
            $stmt = $link->prepare("SELECT card_id, card_balance FROM membership_card WHERE username = ?");
            $stmt->bind_param("s", $katanama);
            $stmt->execute();
            $card_result = $stmt->get_result();

            if ($card_result->num_rows > 0) {
                $card_details = $card_result->fetch_assoc();
                $card_id = $card_details['card_id'];
                $card_balance = (float) $card_details['card_balance'];
            } else {
                echo "No card found for username: " . htmlspecialchars($katanama) . "<br>";
                exit();
            }
            $stmt->close();

            // Check if the card balance is sufficient
            if ($card_balance >= $total_amount) {
                // Deduct the total amount from the card balance
                $new_balance = $card_balance - $total_amount;

                // Update the card balance in the database
                $stmt = $link->prepare("UPDATE membership_card SET card_balance = ? WHERE card_id = ?");
                $stmt->bind_param("ds", $new_balance, $card_id);
                if (!$stmt->execute()) {
                    die("Error updating card balance: " . $stmt->error);
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
                    exit();
                }

                // Insert data into the payment table
                $stmt = $link->prepare(
                    "INSERT INTO payment (payment_id, payment_method, payment_status, card_id, payment_Amount, order_id, staff_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
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
                    die('Error executing query: ' . $stmt->error);
                }
                $stmt->close();

                // Redirect after successful insert
                header("Location: payment_card.php");
                exit();
            } else {
                // Insufficient balance, redirect to add_money.php
                $_SESSION['redirect_after_topup'] = "CheckOut.php";
                echo "<script>
                    alert('INSUFFICIENT BALANCE! Please top up your membership card.');
                    window.location.href = 'add_money.php';
                </script>";
                exit();
            }
        }
    } else {
        echo "Order details not found.";
        exit();
    }
} else {
    echo "Order ID is missing or invalid.";
    exit();
}

// Close the database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay with Card</title>
    <link rel="stylesheet" href="payCard.css">
</head>
<body>
    <div class="container">
        <h1>Pay with Membership Card</h1>

        <?php if ($order_details): ?>
            <div class="order-summary">
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_details['order_id']); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($order_details['total_Amount'], 2); ?></p>
                <p><strong>Payment Method:</strong> Membership Card</p>
            </div>
        <?php else: ?>
            <p>Order details not found. Please check again.</p>
        <?php endif; ?>

        <?php if ($order_details): ?>
            <form method="POST">
                <input type="hidden" name="submit_payment" value="1">
                <div class="actions">
                    <button type="submit">Pay Now</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

