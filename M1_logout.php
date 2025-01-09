<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "rapidprint2a7") or die(mysqli_connect_error());

if (isset($_SESSION['SESS_ID']) && isset($_SESSION['SESS_USER'])) {
    $session_id = $_SESSION['SESS_ID'];
    $username = $_SESSION['SESS_USER'];

    $query = "UPDATE login SET current_State = 'inactive', logout_Timestamp = CURRENT_TIMESTAMP WHERE session_id = $session_id AND username = '$username'";
    $result = mysqli_query($conn, $query);
	$_SESSION['STATUS']=false;

    if (!$result) {
        die("Error updating session: " . mysqli_error($conn));
    }
}

$_SESSION['STATUS'] = false;
session_destroy();
?> 
<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<form action="rapidPrint.html">
    <div class="logout-page">
        <div class="logout-details">
            <h2 class="logout-title" style="padding-top:10px;">Logout Succesfully</h2> 
            <p>Your account has been logged out successfully.</p>
            <div class="buttons">
                <button type="submit" style="width: 30%;">OK</button>
            </div>
        </div>
    </div>
</form>
</body>
</html>
