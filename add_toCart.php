<?php
session_start();

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if a package is being added
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package'])) {
    $package = $_POST['package'];

    // Add the package to the cart
    if (isset($_SESSION['cart'][$package])) {
        $_SESSION['cart'][$package]++;
    } else {
        $_SESSION['cart'][$package] = 1;
    }

    echo "Package added to cart successfully!";
}
?>
