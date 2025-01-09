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

// Check if the order ID and points are set
if (isset($_POST['order_id']) && isset($_POST['points_earned'])) {
    $order_id = intval($_POST['order_id']);
    $points_earned = floatval($_POST['points_earned']);

    // Insert reward into the reward table
    $stmt = $link->prepare("INSERT INTO reward (point_Earned) VALUES (?)");
    if ($stmt === false) {
        die('Prepare statement failed: ' . $link->error);
    }

    $stmt->bind_param("d", $points_earned); // 'd' means double/float
    if ($stmt->execute()) {
        echo "Reward saved successfully.";
    } else {
        echo "Error saving reward: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid data provided.";
}

// Close the database connection
mysqli_close($link);
?>
