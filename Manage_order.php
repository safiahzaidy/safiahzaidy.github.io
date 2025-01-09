<?php
session_start();

// Connect to the database server
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306") or die(mysqli_connect_error());


// Initialize the cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];  // Initialize as an empty array
}

// Check if customer is logged in by verifying the cust_id session variable
if (!isset($_SESSION['cust_id'])) {
    $_SESSION['cart_message'] = "Customer ID not found. Please log in again.";
    header('Location: M1_loginForm.php');
    exit();
}

// Handle "Add to Cart" button
if (isset($_POST['add_to_cart'])) {
    if (isset($_POST['package_id']) && isset($_POST['quantity'])) {
        $package_id = $_POST['package_id'];
        $quantity = $_POST['quantity'];

        if ($quantity <= 0) {
            $_SESSION['cart_message'] = "Error: Quantity must be greater than 0!";
        } else {
            $exists = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['package_id'] == $package_id) {
                    $item['quantity'] += $quantity;
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $_SESSION['cart'][] = [
                    'package_id' => $package_id,
                    'quantity' => $quantity
                ];
            }

            $_SESSION['cart_message'] = "Item added to cart successfully!";
        }
    } else {
        $_SESSION['cart_message'] = "Error: No package or quantity selected!";
    }
}

// Handle "Update Quantity" button
if (isset($_POST['update_quantity'])) {
    if (isset($_POST['package_id']) && isset($_POST['quantity'])) {
        $package_id = $_POST['package_id'];
        $quantity = $_POST['quantity'];

        foreach ($_SESSION['cart'] as &$item) {
            if ($item['package_id'] == $package_id) {
                $item['quantity'] = $quantity;
                break;
            }
        }

        $_SESSION['cart_message'] = "Cart quantity updated successfully!";
    }
}

// Handle "Remove from Cart" button
if (isset($_POST['remove_item'])) {
    $remove_package_id = $_POST['remove_item'];

    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['package_id'] == $remove_package_id) {
            unset($_SESSION['cart'][$index]);
            break;
        }
    }

    $_SESSION['cart'] = array_values($_SESSION['cart']);
    $_SESSION['cart_message'] = "Item removed from cart successfully!";
}

// Save Cart to Database
if (isset($_POST['save_cart'])) {
    $stmt = $link->prepare("INSERT INTO order_line (package_id, quantity) VALUES (?, ?)");

    if ($stmt === false) {
        die('MySQL prepare error: ' . mysqli_error($link));
    }

    foreach ($_SESSION['cart'] as $cart_item) {
        $package_id = $cart_item['package_id'];
        $quantity = $cart_item['quantity'];

        $check_package = $link->prepare("SELECT COUNT(*) FROM printing_package WHERE package_id = ?");
        $check_package->bind_param("s", $package_id);
        $check_package->execute();
        $check_package->bind_result($count);
        $check_package->fetch();
        $check_package->close();

        if ($count > 0) {
            $stmt->bind_param("si", $package_id, $quantity);

            if ($stmt->execute()) {
                echo "Order line inserted successfully.<br>";
            } else {
                echo "Error: " . $stmt->error . "<br>";
            }
        } else {
            echo "Error: Package ID '$package_id' does not exist in the printing_package table.<br>";
        }
    }

    $stmt->close();
}

// Handle filtering packages by price
$packages = [];
if (isset($_POST['filter_price'])) {
    $min_price = isset($_POST['min_price']) ? floatval($_POST['min_price']) : 0;
    $max_price = isset($_POST['max_price']) ? floatval($_POST['max_price']) : PHP_INT_MAX;

    $query = "SELECT * FROM printing_package WHERE package_Price BETWEEN ? AND ?";
    $stmt = $link->prepare($query);

    if ($stmt === false) {
        die("SQL Error: " . mysqli_error($link));
    }

    $stmt->bind_param("dd", $min_price, $max_price);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $packages[] = $row;
    }

    $stmt->close();
} else {
    $result = mysqli_query($link, "SELECT * FROM printing_package");

    if (!$result) {
        die("SQL Error: " . mysqli_error($link));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $packages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Order</title>
    <link rel="stylesheet" href="mp_safiah.css"> <!-- Link the CSS file -->
</head>
<body>
    <!-- Menu Section -->
    <div class="menu">
        <a href="Manage_order.php">Packages</a>
        <a href="cart.php">Cart</a>
        <a href="Checkout.php">Check Out</a>
        <a href="payment.php">Order Status</a>
    </div>

    <!-- Manage Order Page -->
    <div class="manage-order">
        <img src="LogoUMP.png" alt="UMPSA Logo" class="logo">
        <h1 class="manage-order-header">Manage Order</h1>

        <!-- Back to Module2 Button -->
        <a href="Module2.php">
            <button>Back to Dashboard</button>
        </a>

        <!-- Filter Form Section -->
        <div class="filter-page">
            <h2>Filter Packages by Price</h2>
            <form action="" method="POST" class="filter-form">
                <label for="min_price">Min Price:</label>
                <input type="number" name="min_price" step="0.01" placeholder="e.g., 10.00" required>
                <label for="max_price">Max Price:</label>
                <input type="number" name="max_price" step="0.01" placeholder="e.g., 100.00" required>
                <button type="submit" name="filter_price">Filter</button>
            </form>
        </div>

        <!-- Display Success Message -->
        <?php if (isset($_SESSION['cart_message'])): ?>
            <p class="success-message"><?php echo $_SESSION['cart_message']; ?></p>
            <?php unset($_SESSION['cart_message']); // Clear message ?>
        <?php endif; ?>

        <!-- Display Packages -->
        <div class="packages">
            <?php if (!empty($packages)): ?>
                <?php foreach ($packages as $package): ?>
                    <div class="package">
                        <h3><?php echo htmlspecialchars($package['package_Name']); ?></h3>
                        <p>Price: RM<?php echo htmlspecialchars($package['package_Price']); ?></p>
                        <form action="" method="POST">
                            <input type="hidden" name="package_id" value="<?php echo htmlspecialchars($package['package_id']); ?>">
                            <label for="quantity">Quantity:</label>
                            <input type="number" name="quantity" value="1" min="1" required>
                            <button type="submit" name="add_to_cart">Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No packages found in the selected price range.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>







