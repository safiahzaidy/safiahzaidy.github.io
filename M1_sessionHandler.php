<?php
session_start();
$errflag = false;
$errmsg_arr = array();

if ($_POST['username'] == '') {
    $errmsg_arr[] = 'Login ID missing'; 
    $errflag = true;
}

if ($_POST['password'] == '') {
    $errmsg_arr[] = 'Password missing';
    $errflag = true;
}

if ($errflag) {
    $_SESSION['ERRMSG_ARR'] = $errmsg_arr;
    session_write_close();
    header("location: M1_loginForm.php");
    exit();
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306") or die(mysqli_connect_error());



$katanama = $_POST['username'];  // Sanitize user input
$katalaluan = $_POST['password']; // Don't sanitize passwords, as they are hashed later

// Fetch hashed password from the database
$query = "SELECT password FROM profile WHERE username = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('MySQL prepare error: ' . $conn->error);
}
$stmt->bind_param("s", $katanama);
$stmt->execute();
$resultpass = $stmt->get_result();
if ($resultpass && $row = mysqli_fetch_assoc($resultpass)) {
    $hashed_password_from_db = $row['password'];
} else {
    // Handle case where the user does not exist in the database
    header("location: M1_login-failed.html");
    exit();
}

// Verify the password
if (password_verify($katalaluan, $hashed_password_from_db)) {
    // Query user details after verifying password
    $select_query = "SELECT * FROM profile WHERE username=?";
    $stmt = $conn->prepare($select_query);
    if ($stmt === false) {
        die('MySQL prepare error: ' . $conn->error);
    }
    $stmt->bind_param("s", $katanama);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        if (mysqli_num_rows($result) == 1) {
            session_regenerate_id();
            $member = mysqli_fetch_assoc($result);
            $_SESSION['SESS_USER'] = $member['username'];
            $_SESSION['SESS_NAME'] = $member['user_FullName'];
            $_SESSION['SESS_ROLE'] = $member['user_Role'];
            $_SESSION['SESS_CONTACT'] = $member['user_Contact'];
            $_SESSION['SESS_EMAIL'] = $member['user_Email'];
            $_SESSION['STATUS'] = true;

            // ** Generate and assign cust_id to the user ** 
            $cust_id = "cust_" . uniqid(); // Generate a unique customer ID

            // Insert the cust_id and username into the customer table
            $insert_customer_query = "INSERT INTO customer (cust_id, username) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_customer_query);
            if ($stmt === false) {
                die('MySQL prepare error: ' . $conn->error);
            }
            $stmt->bind_param("ss", $cust_id, $katanama);
            if (!$stmt->execute()) {
                die("Error inserting customer data: " . $stmt->error);
            }
            $_SESSION['cust_id'] = $cust_id;  // Store the generated customer ID in the session

            // Insert session info into the login table
            $session_query = "INSERT INTO login (username, login_Timestamp, current_state) 
                              VALUES (?, CURRENT_TIMESTAMP, 'active')";
            $stmt = $conn->prepare($session_query);
            if ($stmt === false) {
                die('MySQL prepare error: ' . $conn->error);
            }
            $stmt->bind_param("s", $katanama);
            if (!$stmt->execute()) {
                die("Error inserting session: " . $stmt->error);
            }

            // Fetch session ID
            $sessionid_query = "SELECT session_id FROM login WHERE username=? ORDER BY login_Timestamp DESC LIMIT 1";
            $stmt = $conn->prepare($sessionid_query);
            if ($stmt === false) {
                die('MySQL prepare error: ' . $conn->error);
            }
            $stmt->bind_param("s", $katanama);
            $stmt->execute();
            $result2 = $stmt->get_result();
            if ($result2 && mysqli_num_rows($result2) > 0) {
                $session_row = mysqli_fetch_assoc($result2);
                $_SESSION['SESS_ID'] = $session_row['session_id'];
            } else {
                die("Error fetching session ID: " . mysqli_error($conn));
            }

            session_write_close();

            // Redirect based on user role
            switch ($_SESSION['SESS_ROLE']) {
                case "Administrator":
                    header("location: M1M3_profile-admin.php");
                    break;
                case "Customer":
                    header("location: Module2.php"); // Assuming this is the correct page
                    break;
                case "Staff":
                    header("location: Module2Staff.php"); // Assuming this is the correct page
                    break;
                default:
                    // If role is unrecognized, log them out or show error
                    header("location: M1_login-failed.html");
                    exit();
            }
            exit();
        }
    }
} else {
    // Invalid password or username
    header("location: M1_login-failed.html");
    exit();
}
?>




