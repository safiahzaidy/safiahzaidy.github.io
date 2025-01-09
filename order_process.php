<?php
// Connect to the database server
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306") or die(mysqli_connect_error());

// Proceed with form processing and data insertion if connected
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $user_Fullname = mysqli_real_escape_string($link, $_POST['user_Fullname']);
    $username = mysqli_real_escape_string($link, $_POST['username']);
    $password = mysqli_real_escape_string($link, $_POST['password']);
    $user_Role = mysqli_real_escape_string($link, $_POST['user_Role']);
    $user_Contact = mysqli_real_escape_string($link, $_POST['user_Contact']);
    $user_Email = mysqli_real_escape_string($link, $_POST['user_Email']);
    
    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare SQL query to insert the data into the database
    $sql = "INSERT INTO profile (username, password, user_Fullname, user_Role, user_Contact, user_Email) 
            VALUES ('$username', '$hashed_password', '$user_Fullname', '$user_Role', '$user_Contact', '$user_Email')";
    
    // Check if the query was successful
    if (mysqli_query($link, $sql)) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($link);
    }
    
    // Close the database connection
    mysqli_close($link);
}
?>