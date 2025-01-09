<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in (session check)
if (!isset($_SESSION['SESS_USER'])) {
    echo "You must be logged in to view your order history.";
    exit();
}

// Connect to the database
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306");



if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}

$katanama = trim($_SESSION['SESS_USER']); // Use session username for profile lookup

// Fetch the cust_id for the logged-in user based on the username from the customer table
$stmt = $link->prepare("SELECT cust_id FROM customer WHERE username = ?");
if (!$stmt) {
    die('Error preparing query: ' . $link->error);
}
$stmt->bind_param("s", $katanama);
$stmt->execute();
$result = $stmt->get_result();

// Check if the user exists
if ($result->num_rows > 0) {
    $user_details = $result->fetch_assoc();
    $cust_id = $user_details['cust_id'];
} else {
    echo "User not found.";
    exit();
}

$stmt->close();

// Fetch all orders associated with the logged-in cust_id from the order table
$stmt = $link->prepare("SELECT o.order_id, o.total_Amount, o.order_date, p.payment_status, p.payment_method 
                        FROM `order` o
                        LEFT JOIN payment p ON o.order_id = p.order_id
                        WHERE o.cust_id = ? ORDER BY o.order_date DESC");

if (!$stmt) {
    die('Error preparing query: ' . $link->error);
}

$stmt->bind_param("i", $cust_id); // Assuming cust_id is an integer
$stmt->execute();
$result = $stmt->get_result();

// Check if any orders exist
if ($result->num_rows > 0) {
    $orders = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $orders = [];
}

$stmt->close();

// Close the database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link rel="stylesheet" href="history.css">
</head>
<body>
    <div class="container">
        <h1>Order History</h1>

        <?php if (count($orders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Total Amount</th>
                        <th>Order Date</th>
                        <th>Payment Status</th>
                        <th>Payment Method</th>
                       
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td>$<?php echo number_format($order['total_Amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                            <td><?php echo htmlspecialchars($order['payment_status']); ?></td>
                            <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no orders yet.</p>
        <?php endif; ?>

        <div class="dashboard">
            <button onclick="window.location.href='Module2.php'">Return to Dashboard</button>
        </div>
    </div>
</body>
</html>


