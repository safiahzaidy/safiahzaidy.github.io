<?php
session_start();

// Connect to the database server
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306") or die(mysqli_connect_error());

// Initialize quantity for each package if not set
if (!isset($_SESSION['quantity_package_a'])) $_SESSION['quantity_package_a'] = 1;
if (!isset($_SESSION['quantity_package_b'])) $_SESSION['quantity_package_b'] = 1;
if (!isset($_SESSION['quantity_package_c'])) $_SESSION['quantity_package_c'] = 1;

// Initialize the cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];  // Initialize as an empty array
}

// Handle quantity increase and decrease
if (isset($_POST['increase_quantity_package_a'])) {
    $_SESSION['quantity_package_a']++;
}

if (isset($_POST['decrease_quantity_package_a']) && $_SESSION['quantity_package_a'] > 1) {
    $_SESSION['quantity_package_a']--;
}

if (isset($_POST['increase_quantity_package_b'])) {
    $_SESSION['quantity_package_b']++;
}

if (isset($_POST['decrease_quantity_package_b']) && $_SESSION['quantity_package_b'] > 1) {
    $_SESSION['quantity_package_b']--;
}

if (isset($_POST['increase_quantity_package_c'])) {
    $_SESSION['quantity_package_c']++;
}

if (isset($_POST['decrease_quantity_package_c']) && $_SESSION['quantity_package_c'] > 1) {
    $_SESSION['quantity_package_c']--;
}

// Handle "Add to Cart" button
if (isset($_POST['add_to_cart'])) {
    // Ensure package_id is set before accessing it
    if (isset($_POST['package_id'])) {
        $package_id = $_POST['package_id'];

        // Retrieve the quantity for the specific package from the session
        if ($package_id == "Package A") {
            $quantity = $_SESSION['quantity_package_a'];
        } elseif ($package_id == "Package B") {
            $quantity = $_SESSION['quantity_package_b'];
        } elseif ($package_id == "Package C") {
            $quantity = $_SESSION['quantity_package_c'];
        }

        // Add the selected package and quantity to the session cart
        $_SESSION['cart'][] = [
            'package_id' => $package_id,
            'quantity' => $quantity
        ];

        // Set success message after adding to the cart
        $_SESSION['cart_message'] = "Item added to cart successfully!";
    } else {
        // Set error message if package_id is not set
        $_SESSION['cart_message'] = "Error: No package selected!";
    }
}

// Save Cart to Database (Inserting into `order_line` table)
if (isset($_POST['save_cart'])) {
    // Prepare the SQL query with placeholders
    $stmt = $link->prepare("INSERT INTO order_line (package_id, quantity) VALUES (?, ?)");

    // Check if the statement is prepared correctly
    if ($stmt === false) {
        die('MySQL prepare error: ' . mysqli_error($link));
    }

    // Debugging: Check if cart is populated
    echo "<pre>";
    print_r($_SESSION['cart']);
    echo "</pre>";

    // Loop through cart items and insert them into the database
    foreach ($_SESSION['cart'] as $cart_item) {
        $package_id = $cart_item['package_id'];
        $quantity = $cart_item['quantity'];

        // Check if package_id exists in the printing_package table
        $check_package = $link->prepare("SELECT COUNT(*) FROM printing_package WHERE package_id = ?");
        $check_package->bind_param("s", $package_id);
        $check_package->execute();
        $check_package->bind_result($count);
        $check_package->fetch();
        $check_package->close();

        if ($count > 0) {
            // Bind the parameters (package_id as string, and quantity as integer)
            $stmt->bind_param("si", $package_id, $quantity); // 'si' means string, integer
            
            // Execute the statement and check if it was successful
            if ($stmt->execute()) {
                echo "Order line inserted successfully.<br>";
            } else {
                echo "Error: " . $stmt->error . "<br>";
            }
        } else {
            echo "Error: Package ID '$package_id' does not exist in the printing_package table.<br>";
        }
    }

    // After inserting the cart, you can clear the cart from the session if needed
    unset($_SESSION['cart']);
    
    // Set success message after saving cart to database
    $_SESSION['cart_message'] = "Cart saved to database successfully!";
}

// Display success message if set
if (isset($_SESSION['cart_message'])): 
    echo "<p class='success-message'>" . $_SESSION['cart_message'] . "</p>";
    unset($_SESSION['cart_message']); // Clear the message after displaying it 
endif;
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
    <img src="LogoUMP.png" alt="UMPSA" class="UMPSA">
    <h1 class="manage-order-header">MANAGE ORDER</h1>

    <!-- Packages Section -->
    <form action="" method="POST">
        <div class="package">
            <h3>Package A</h3>
            <div class="quantity-control">
                <button type="submit" name="decrease_quantity_package_a">-</button>
                <input type="number" name="quantity_package_a" value="<?php echo $_SESSION['quantity_package_a']; ?>" min="1" readonly>
                <button type="submit" name="increase_quantity_package_a">+</button>
            </div>
            <label for="quantity_package_a">Quantity</label>
            <input type="hidden" name="package_id" value="Package A">
            <button type="submit" name="add_to_cart">Add to Cart</button>
        </div>

        <div class="package">
            <h3>Package B</h3>
            <div class="quantity-control">
                <button type="submit" name="decrease_quantity_package_b">-</button>
                <input type="number" name="quantity_package_b" value="<?php echo $_SESSION['quantity_package_b']; ?>" min="1" readonly>
                <button type="submit" name="increase_quantity_package_b">+</button>
            </div>
            <label for="quantity_package_b">Quantity</label>
            <input type="hidden" name="package_id" value="Package B">
            <button type="submit" name="add_to_cart">Add to Cart</button>
        </div>

        <div class="package">
            <h3>Package C</h3>
            <div class="quantity-control">
                <button type="submit" name="decrease_quantity_package_c">-</button>
                <input type="number" name="quantity_package_c" value="<?php echo $_SESSION['quantity_package_c']; ?>" min="1" readonly>
                <button type="submit" name="increase_quantity_package_c">+</button>
            </div>
            <label for="quantity_package_c">Quantity</label>
            <input type="hidden" name="package_id" value="Package C">
            <button type="submit" name="add_to_cart">Add to Cart</button>
        </div>

        <!-- Save Cart Button -->
        <button type="submit" name="save_cart">Save Cart</button>
    </form>

</div>

</body>
</html>
