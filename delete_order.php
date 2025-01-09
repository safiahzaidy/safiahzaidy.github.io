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

// Validate if the order_id is passed via POST and session
if (isset($_POST['order_id']) && intval($_POST['order_id']) > 0) {
    $order_id = intval($_POST['order_id']);

    // Start a transaction to ensure consistency
    mysqli_begin_transaction($link);

    try {
        // Delete the related rows from the order_line table first
        $stmt = $link->prepare("DELETE FROM `order_line` WHERE order_id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare statement failed: " . $link->error);
        }

        $stmt->bind_param("i", $order_id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting from order_line: " . $stmt->error);
        }
        $stmt->close();

        // Now, delete the order from the order table
        $stmt = $link->prepare("DELETE FROM `order` WHERE order_id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare statement failed: " . $link->error);
        }

        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            echo "Order successfully canceled.";
            unset($_SESSION['order_id']); // Remove the order ID from the session
        } else {
            throw new Exception("Error canceling order: " . $stmt->error);
        }
        $stmt->close();

        // Commit the transaction
        mysqli_commit($link);
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        mysqli_roll_back($link);
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid or missing order ID.";
}

// Close the database connection
mysqli_close($link);
?>
