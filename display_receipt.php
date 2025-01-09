<?php
session_start();
require 'vendor/autoload.php'; // Include Composer autoload file

// Connect to the database
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306") or die(mysqli_connect_error());


// Validate and fetch order_id from the URL
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("Invalid request. Order ID is missing.");
}

$order_id = $_GET['order_id'];

// Fetch payment data from the database
$query = "SELECT * FROM payment WHERE order_id = ?";
$stmt = $link->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();

    if ($payment) {
        // Display the payment details as a receipt
        echo "<h1>Receipt</h1>";
        echo "<p><strong>Order ID:</strong> " . htmlspecialchars($payment['order_id']) . "</p>";
        echo "<p><strong>Payment Amount:</strong> $" . number_format($payment['payment_amount'], 2) . "</p>";
        echo "<p><strong>Payment Method:</strong> " . htmlspecialchars($payment['payment_method']) . "</p>";
        echo "<p><strong>Payment Date:</strong> " . htmlspecialchars($payment['payment_date']) . "</p>";
        echo "<p><strong>QR Code:</strong><br><img src='" . htmlspecialchars($payment['payment_qrcode']) . "' alt='QR Code'></p>";
    } else {
        echo "No payment details found for this order.";
    }
} else {
    die("Error preparing query: " . $link->error);
}
?>
