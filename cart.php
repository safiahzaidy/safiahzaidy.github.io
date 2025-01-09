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

// Handle the remove item process
if (isset($_POST['remove_item']) && !empty($_POST['remove_item'])) {
    $package_id_to_remove = $_POST['remove_item'];

    // Remove the selected package from the cart
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['package_id'] === $package_id_to_remove) {
            unset($_SESSION['cart'][$index]);
            break;
        }
    }

    // Reindex the cart array to prevent gaps in the array keys
    $_SESSION['cart'] = array_values($_SESSION['cart']);

    // Redirect back to the cart page to reflect the changes
    $_SESSION['cart_message'] = "Package with ID $package_id_to_remove removed successfully.";
    header('Location: cart.php');
    exit();
}

// Check if the cart is empty
$cart_empty = empty($_SESSION['cart']);
$cart_grouped = [];
$total_amount = 0;  // Variable to store the total amount

// Fetch package prices and calculate total
if (!$cart_empty) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_grouped[$item['package_id']] = isset($cart_grouped[$item['package_id']])
            ? $cart_grouped[$item['package_id']] + $item['quantity']
            : $item['quantity'];

        $package_id = $item['package_id'];
        $quantity = $item['quantity'];

        $stmt = $link->prepare("SELECT package_Price FROM printing_package WHERE package_id = ?");
        if ($stmt === false) {
            die('Prepare statement failed: ' . $link->error);
        }

        $stmt->bind_param("s", $package_id);
        $stmt->execute();
        $stmt->bind_result($package_Price);
        $stmt->fetch();
        $stmt->close();

        if ($package_Price) {
            $total_amount += $package_Price * $quantity;
        }
    }
}

// Handle the confirm process
if (isset($_POST['confirm'])) {
    if ($cart_empty) {
        $_SESSION['cart_message'] = "Your cart is empty. Please add items to your cart first.";
        header('Location: cart.php');
        exit();
    }

    // Check if cust_id exists in session (assuming the user is logged in)
    if (isset($_SESSION['cust_id'])) {
        $cust_id = $_SESSION['cust_id'];  // Retrieve the logged-in user's customer ID
    } else {
        // If cust_id is not set, redirect to login page
        $_SESSION['cart_message'] = "You must be logged in to place an order.";
        header('Location: login.php');
        exit();
    }

    // Insert the order into the database, including the cust_id
    $stmt = $link->prepare("INSERT INTO `order` (total_Amount, cust_id) VALUES (?, ?)");
    $stmt->bind_param("ds", $total_amount, $cust_id);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $_SESSION['order_id'] = $order_id;
    $_SESSION['cart_message'] = "Order successfully placed with ID: " . $order_id;
    $stmt->close();

    // Insert items into order_line table
    foreach ($_SESSION['cart'] as $item) {
        $package_id = $item['package_id'];
        $quantity = $item['quantity'];

        $stmt = $link->prepare("INSERT INTO `order_line` (order_id, package_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $order_id, $package_id, $quantity);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle the update process
if (isset($_POST['update_order']) && isset($_SESSION['order_id'])) {
    $order_id = $_SESSION['order_id'];

    $stmt = $link->prepare("UPDATE `order` SET total_Amount = ? WHERE order_id = ?");
    $stmt->bind_param("di", $total_amount, $order_id);
    $stmt->execute();
    $stmt->close();

    $existing_packages = [];
    $stmt = $link->prepare("SELECT package_id FROM `order_line` WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $existing_packages[] = $row['package_id'];
    }
    $stmt->close();

    $current_cart_packages = array_column($_SESSION['cart'], 'package_id');
    $packages_to_delete = array_diff($existing_packages, $current_cart_packages);

    foreach ($packages_to_delete as $package_id_to_delete) {
        $stmt = $link->prepare("DELETE FROM `order_line` WHERE order_id = ? AND package_id = ?");
        $stmt->bind_param("is", $order_id, $package_id_to_delete);
        $stmt->execute();
        $stmt->close();
    }

    foreach ($_SESSION['cart'] as $item) {
        $package_id = $item['package_id'];
        $quantity = $item['quantity'];

        $stmt = $link->prepare("SELECT COUNT(*) FROM `order_line` WHERE order_id = ? AND package_id = ?");
        $stmt->bind_param("is", $order_id, $package_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $stmt = $link->prepare("UPDATE `order_line` SET quantity = ? WHERE order_id = ? AND package_id = ?");
            $stmt->bind_param("iis", $quantity, $order_id, $package_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $link->prepare("INSERT INTO `order_line` (order_id, package_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $order_id, $package_id, $quantity);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Add success message
    $_SESSION['cart_message'] = "Order updated successfully!";
    header('Location: cart.php');
    exit();
}

// Close the database connection at the end
mysqli_close($link);
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="cart.css">
</head>
<body>

<div class="menu">
    <a href="Manage_order.php">Packages</a>
    <a href="cart.php">Cart</a>
    <a href="Checkout.php">Check Out</a>
    <a href="payment.php">Order Status</a>
</div>

<div class="cart-page">
    <h1>Your Cart</h1>

    <?php if (isset($_SESSION['cart_message'])): ?>
        <p class="success-message"><?php echo $_SESSION['cart_message']; ?></p>
        <?php unset($_SESSION['cart_message']); ?>
    <?php endif; ?>

    <div class="order-list">
        <?php if ($cart_empty): ?>
            <p>Your cart is empty. Add some items to the cart first.</p>
        <?php else: ?>
            <form action="cart.php" method="POST">
                <table>
                    <tr>
                        <th>Package</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['package_id']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>
                                <button type="submit" name="remove_item" value="<?php echo $item['package_id']; ?>">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </form>
        <?php endif; ?>
    </div>

    <div class="cart-actions">
        <p>Total Amount: $<?php echo number_format($total_amount, 2); ?></p>
        <form action="cart.php" method="POST">
            <button type="submit" name="confirm">Confirm</button>
            <button type="submit" name="update_order">Update Order</button>
            <button type="button" onclick="window.location.href='Checkout.php'">Check Out</button>
        </form>
    </div>
</div>

</body>
</html>




















