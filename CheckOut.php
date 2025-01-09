<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306");


if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}

// Ensure the order ID is set in the session
if (!isset($_SESSION['order_id'])) {
    $_SESSION['order_id'] = rand(1000, 9999); // Mock order ID for testing
}

$order_details = null;

// Fetch the order details
if (isset($_SESSION['order_id'])) {
    $order_id = $_SESSION['order_id'];

    $stmt = $link->prepare("SELECT order_id, total_Amount FROM `order` WHERE order_id = ?");
    if ($stmt === false) {
        die('Prepare statement failed: ' . $link->error);
    }

    $stmt->bind_param("i", $order_id); // 'i' means integer
    if (!$stmt->execute()) {
        die('Error executing query for order details: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc(); // Fetch the order details
    }
    $stmt->close();
}

// Close the database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Page</title>
    <link rel="stylesheet" href="CheckOut.css">
    <script>
        // Function to handle cancel action
        function cancelOrder(orderId) {
            if (confirm("Are you sure you want to cancel this order?")) {
                // Send a request to delete_order.php with the order ID
                fetch('delete_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'order_id=' + orderId
                })
                .then(response => response.text())
                .then(data => {
                    alert(data); // Notify user about the deletion status
                    if (data.includes("successfully")) {
                        window.location.href = 'cart.php'; // Redirect to the cart page
                    } else {
                        alert("Failed to cancel the order. Please try again.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred while canceling the order.");
                });
            }
        }

    function proceedToPayment() {
    const paymentMethod = document.querySelector('input[name="payment-method"]:checked');
    if (paymentMethod) {
        const paymentPage = paymentMethod.value === 'cash' ? 'payCash.php' : 'payCard.php';
        const orderId = <?php echo json_encode($_SESSION['order_id']); ?>;
        const totalAmount = <?php echo json_encode($order_details['total_Amount'] ?? 0); ?>;
        const pointsEarned = totalAmount * 0.1;

        if (orderId && totalAmount) {
            // Save reward points to the database
            fetch('save_reward.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `order_id=${orderId}&points_earned=${pointsEarned}`
            })
            .then(response => response.text())
            .then(data => {
                console.log(data); // Log the server response for debugging
                if (data.includes("successfully")) {
                    // Proceed to the payment page
                    window.location.href = `${paymentPage}?order_id=${orderId}&total_amount=${totalAmount}&payment_method=${paymentMethod.value}`;
                } else {
                    alert("Failed to save reward points. Please try again.");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("An error occurred while saving reward points.");
            });
        } else {
            alert("Order details are missing or invalid. Please try again.");
        }
    } else {
        alert("Please select a payment method.");
    }
}



    </script>
</head>
<body>
    <!-- Menu Section -->
    <div class="menu">
        <a href="Manage_order.php">Packages</a>
        <a href="cart.php">Cart</a>
        <a href="Checkout.php">Check Out</a>
        <a href="payment.php">Order Status</a>
    </div>

    <!-- Checkout Page -->
    <div class="checkout-page">
        <img src="LogoUMP.png" alt="Logo" class="logo">
        <h1 class="checkout-header">Check Out</h1>

        <!-- Order Details -->
        <div class="order-details">
            <?php if ($order_details): ?>
                <h2>Order Details</h2>
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_details['order_id']); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($order_details['total_Amount'], 2); ?></p>
                <p><strong>Received Points:</strong> <?php echo number_format($order_details['total_Amount'] * 0.1, 2); ?> points</p>
            <?php else: ?>
                <p>No order details available. Please check your cart.</p>
            <?php endif; ?>
        </div>

        <!-- Checkout Actions -->
        <div class="checkout-actions">
            <h3>Choose Payment Method</h3>
            <label>
                <input type="radio" name="payment-method" value="cash"> Cash
            </label>
            <label>
                <input type="radio" name="payment-method" value="membership-card"> Membership Card
            </label>
            <br><br>
            <button 
                class="cancel-btn" 
                onclick="cancelOrder(<?php echo isset($_SESSION['order_id']) ? $_SESSION['order_id'] : '0'; ?>)">
                Cancel
            </button>
            <button class="checkout-btn" onclick="proceedToPayment();">Proceed to Payment</button>
        </div>
    </div>
</body>
</html>





