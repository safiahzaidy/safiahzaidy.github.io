<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start () ;
$conn = mysqli_connect("localhost", "root", "", "rapidprint2a7") or die(mysqli_connect_error());

if (!isset($_SESSION['STATUS']) || $_SESSION['STATUS'] !== true) {
    session_destroy();
	header("location: M1_loginForm.php");
    exit();
}

$username = $_SESSION['SESS_USER'];
$session_id = $_SESSION['SESS_ID'];
$query = "SELECT current_State FROM login 
          WHERE username = '$username' AND current_State = 'active' AND session_id = '$session_id'";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMPSA RapidPrint Student</title>
    <link rel="stylesheet" href="Module2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<script>
    // Ensure the page reloads fully when navigated to via back button
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };
</script>
    <div class="container">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="umpsaLogo.png" alt="UMPSA Logo" class="logo-img">
            </div>
            <button class="nav-button">Dashboard</button>
            <a href="RegistrationStaff.html" class="nav-button">Registration</a>
			<a href="MembershipStaff.html" class="nav-button">Membership Card</a>
            <a href="M1_logout.php" class="nav-button">Log out</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h1>WELCOME TO THE UMPSA RAPIDPRINT</h1>

            <!-- Upload Section -->
            <div class="upload-section">
                <div class="image-placeholder">Photo</div>
                <div class="image-placeholder">Photo</div>
                <div class="image-placeholder">Photo</div>
                <div class="upload-box">
                    <p>Drop your file here to print</p>
                    <div class="upload-area">Upload file here</div>
                </div>
            </div>

            <!-- Point Information -->
            <div class="point-information">
                <h2>Point Information</h2>
                <table>
                    <tr>
                        <th>Pages</th>
                        <th>Points</th>
                    </tr>
                    <tr>
                        <td>More than 10 page</td>
                        <td>10</td>
                    </tr>
                    <tr>
                        <td>More than 25 page</td>
                        <td>30</td>
                    </tr>
                </table>
                <p class="history-text">History Membership point Report</p>
            </div>

            <!-- Printing Statistics -->
            <div class="printing-stats">
                <h2>Customer Printing Statistics</h2>
                <p>Total Pages Printed: <strong>150</strong></p>
                <p>Total Print Dates: <strong>10</strong></p>
                <canvas id="printChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Chart.js Code for Printing Statistics
        const ctx = document.getElementById('printChart').getContext('2d');
        const printChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['2024-12-01', '2024-12-02', '2024-12-03', '2024-12-04', '2024-12-05'],
                datasets: [{
                    label: 'Pages Printed',
                    data: [20, 30, 25, 40, 35],
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>