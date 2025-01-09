<?php
session_start();
require 'vendor/autoload.php'; // Include Composer autoload file

use Endroid\QrCode\Builder\Builder;

// Error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the cart is not empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "Your cart is empty.";
    exit;
}

// Example package prices (should be fetched from a database in production)
$packagePrices = [
    'Package A' => 10,
    'Package B' => 5,
    'Package C' => 15
];

// Fetch cart items
$cartItems = $_SESSION['cart'];
$totalAmount = 0;

// Validate cart and calculate total amount
foreach ($cartItems as $item) {
    if (is_array($item) && isset($item['package_id']) && isset($item['quantity'])) {
        $package_id = $item['package_id'];
        $quantity = $item['quantity'];
        $price = isset($packagePrices[$package_id]) ? $packagePrices[$package_id] : 0;
        $totalAmount += $quantity * $price;
    }
}

// Connect to the database server
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306") or die(mysqli_connect_error());


// Fetch payment data from the database based on order_id and payment_method
$order_id = $_SESSION['order_id'] ?? uniqid('order_'); // Assuming order_id is in the session or generated
$payment_data = null;

$payment_Amount = $totalAmount;
$payment_method = 'Credit Card'; // Example; replace with actual logic
$payment_date = date('Y-m-d H:i:s'); // Current timestamp

// Prepare QR Code content
$order_summary = "Order ID: $order_id\n";
$order_summary .= "Payment Amount: $" . number_format($payment_Amount, 2) . "\n";
$order_summary .= "Payment Method: $payment_method\n";
$order_summary .= "Payment Date: $payment_date\n";

// Ensure the qrcodes directory exists
$qrCodeDir = 'qrcodes/';
if (!is_dir($qrCodeDir)) {
    mkdir($qrCodeDir, 0777, true); // Create the directory with appropriate permissions
}

// Generate QR Code
$result = Builder::create()
    ->data($order_summary) // Data to encode in QR Code
    ->size(300) // QR Code size
    ->build();

// Save the QR Code to a file
$qrCodePath = $qrCodeDir . 'order_' . $order_id . '.png';
$result->saveToFile($qrCodePath);

// Save the QR code path to the database
$qrCodeData = base64_encode($result->getString()); // Encode QR code as a string (if needed)

if ($order_id) {
    $updateQuery = "UPDATE payment SET payment_qrcode = ?, qrcode_block = ? WHERE order_id = ?";
    $stmt = $link->prepare($updateQuery);
    if ($stmt) {
        $qrCodeBlock = 1; // Assuming 1 means the QR code is active
        $stmt->bind_param("ssi", $qrCodePath, $qrCodeBlock, $order_id);
        if (!$stmt->execute()) {
            die("Error updating payment table: " . $stmt->error);
        }
        $stmt->close();
    } else {
        die("Error preparing update query: " . $link->error);
    }
}

// Clear the cart after placing the order
$_SESSION['cart'] = [];

// Calculate estimated order completion time (24 hours after payment date)
$estimated_completion_time = date('Y-m-d H:i:s', strtotime($payment_date . ' +24 hours'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Page</title>
    <link rel="stylesheet" href="paymet.css">
</head>
<body>
    <!-- Main Layout Container -->
    <div class="container">
        <!-- Sidebar Menu -->
        <div class="menu">
            <h3>Menu</h3>
            <a href="Manage_order.php">Packages</a>
            <a href="cart.php">Cart</a>
            <a href="Checkout.php">Check Out</a>
            <a href="payment.php">Order Status</a>
        </div>

        <!-- Main Content -->
        <div class="content">
            <!-- Page Header -->
            <div class="header">
                <img src="LogoUMP.png" alt="Logo" class="logo">
                <h1>Order Status</h1>
            </div>

            <!-- Order Status -->
            <div class="order-status">
                <h2>Order Status: <span class="status">ORDERED</span></h2>

                <!-- Order Details Form -->
                <div class="status-details">
                    <table>
                        <tr>
                            <td><strong>Order ID:</strong></td>
                            <td><?php echo htmlspecialchars($order_id); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Payment Amount:</strong></td>
                            <td>$<?php echo number_format($payment_Amount, 2); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Payment Method:</strong></td>
                            <td><?php echo htmlspecialchars($payment_method); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Payment Date:</strong></td>
                            <td><?php echo htmlspecialchars($payment_date); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Estimated Completion Time:</strong></td>
                            <td><?php echo htmlspecialchars($estimated_completion_time); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- QR Code Info Section -->
                <div class="box">
                    <h3>Here is your QR Code</h3>
                    <p>Please use this as proof of payment</p>
                    <img src="data:image/png;base64,<?php echo base64_encode($result->getString()); ?>" alt="QR Code">
                </div>

                <!-- WhatsApp Message -->
                <div class="whatsapp-info">
                    <p>Please WhatsApp us your printing file at <a href="https://wa.me/60147856932">+60147856932</a>. Please provide us with your Order id or just can ss and send us the Qr code receive.</p>
                </div>

                <!-- Return Button -->
                <div class="dashboard">
                    <button onclick="window.location.href='module2.php'">Return to Dashboard</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
