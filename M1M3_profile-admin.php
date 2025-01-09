.search-results {
    margin-top: 20px;
    padding: 10px;
    border: 1px solid #ccc;
    background-color: #f9f9f9;
}
.search-results h4 {
    margin-top: 10px;
    color: #333;
}
.search-results table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.search-results table, .search-results th, .search-results td {
    border: 1px solid #ddd;
    padding: 8px;
}
.search-results th {
    background-color: #f2f2f2;
    text-align: left;
}
<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start () ;
$link = mysqli_connect("localhost", "root", "", "rapidprint2a7", "4306");


if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}
if (!isset($_SESSION['STATUS']) || $_SESSION['STATUS'] !== true) {
    session_destroy();
	header("location: M1_loginForm.php");
    exit();
}

$username = $_SESSION['SESS_USER'];
$session_id = $_SESSION['SESS_ID'];
$query = "SELECT current_State FROM login 
          WHERE username = '$username' AND current_State = 'active' AND session_id = '$session_id'";
$result = mysqli_query($link, $query);

// Check if any search field is filled
if (isset($_GET['searchUsername']) || isset($_GET['searchBranch']) || isset($_GET['searchPackage'])) {
    $searchUsername = mysqli_real_escape_string($link, $_GET['searchUsername']);
    $searchBranch = mysqli_real_escape_string($link, $_GET['searchBranch']);
    $searchPackage = mysqli_real_escape_string($link, $_GET['searchPackage']);

    echo "<h4>Search Results</h4>";

    // Search by username
    if (!empty($searchUsername)) {
        $userSearchQuery = "SELECT * FROM profile WHERE username LIKE '%$searchUsername%'";
        $userSearchResult = mysqli_query($link, $userSearchQuery);
        
        echo "<h5>Username Results:</h5>";
        if (mysqli_num_rows($userSearchResult) > 0) {
            echo "<table>
                    <thead>
                        <tr><th>Username</th><th>Full Name</th><th>Email</th><th>Contact</th></tr>
                    </thead>
                    <tbody>";
            while ($row = mysqli_fetch_assoc($userSearchResult)) {
                echo "<tr>
                        <td>{$row['username']}</td>
                        <td>{$row['user_FullName']}</td>
                        
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No users found.</p>";
        }
    }

    // Search by branch_id
    if (!empty($searchBranch)) {
        $branchSearchQuery = "SELECT * FROM koperasi_branch WHERE branch_id LIKE '%$searchBranch%'";
        $branchSearchResult = mysqli_query($link, $branchSearchQuery);
        
        echo "<h5>Branch ID Results:</h5>";
        if (mysqli_num_rows($branchSearchResult) > 0) {
            echo "<table>
                    <thead>
                        <tr><th>Branch ID</th><th>Branch Name</th><th>Location</th></tr>
                    </thead>
                    <tbody>";
            while ($row = mysqli_fetch_assoc($branchSearchResult)) {
                echo "<tr>
                        <td>{$row['branch_id']}</td>
                        <td>{$row['branch_Name']}</td>
                        
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No branches found.</p>";
        }
    }

    // Search by package_id
    if (!empty($searchPackage)) {
        $packageSearchQuery = "SELECT * FROM printing_package WHERE package_id LIKE '%$searchPackage%'";
        $packageSearchResult = mysqli_query($link, $packageSearchQuery);
        
        echo "<h5>Package ID Results:</h5>";
        if (mysqli_num_rows($packageSearchResult) > 0) {
            echo "<table>
                    <thead>
                        <tr><th>Package ID</th><th>Package Name</th><th>Price</th></tr>
                    </thead>
                    <tbody>";
            while ($row = mysqli_fetch_assoc($packageSearchResult)) {
                echo "<tr>
                        <td>{$row['package_id']}</td>
                        <td>{$row['package_Name']}</td>
                        <td>RM" . number_format($row['price'], 2) . "</td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No packages found.</p>";
        }
    }
}
?>

?>
<html>
<head>
    <title>Welcome</title>
    <link rel="stylesheet" href="profile.css">
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
<div class="sidebar">
    <div class="logo">
        <a href="https://www.umpsa.edu.my/en" target="_blank"><img src="images/logoUMPSA2.png" alt="LogoUMPSA" width="149.3" height="100.2"></a>
		<a href="rapidPrint.html" target="_blank"><img src="images/logo2.png" alt="LogoRapidPrint" width="164.0" height="40.6"></a>
    </div>
	<nav>
        <a href="M1M3_profile-admin.php">Dashboard</a>
        <a href="#">Profiles</a>
        <a href="M1_viewBranch.php">Koperasi Branch</a>
        <a href="M1_viewPackage.php">Printing Packages</a>
        <a href="M1_logout.php" class="logout-btn">Log Out</a>
    </nav>
</div>
<header>
	<?php echo "<h2>Welcome, " . $_SESSION['SESS_NAME'] . "</h2>"; ?>
</header>


<hr>

<main>
	<table>
	<tr><th colspan="2">Role: <?php echo $_SESSION['SESS_ROLE']; ?></td> 
	</tr>
	<tr><td>User</td>
	<td><?php echo $_SESSION['SESS_USER']; ?> ( <?php echo $_SESSION['SESS_USERID']; ?> )</td></tr>
	<tr><td>Full Name </td>
	<td><?php echo $_SESSION['SESS_NAME']; ?></td></tr>
	<tr><td>Contact </td>
	<td><?php echo $_SESSION['SESS_CONTACT']; ?></td></tr>
	<tr><td>Email </td>
	<td><?php echo $_SESSION['SESS_EMAIL']; ?></td></tr>
	</table>
	<br>
<!-- Search Section -->
    <section id="search-section">
        <h3>Search Records</h3>
        <form method="GET" action="">
            <label for="searchUsername">Username:</label>
            <input type="text" id="searchUsername" name="searchUsername" placeholder="Enter username">
            
            <label for="searchBranch">Branch ID:</label>
            <input type="text" id="searchBranch" name="searchBranch" placeholder="Enter branch ID">
            
            <label for="searchPackage">Package ID:</label>
            <input type="text" id="searchPackage" name="searchPackage" placeholder="Enter package ID">
            
            <button type="submit">Search</button>
        </form>

        <?php
        // Check if any search field is filled
        if (isset($_GET['searchUsername']) || isset($_GET['searchBranch']) || isset($_GET['searchPackage'])) {
            $searchUsername = mysqli_real_escape_string($link, $_GET['searchUsername']);
            $searchBranch = mysqli_real_escape_string($link, $_GET['searchBranch']);
            $searchPackage = mysqli_real_escape_string($link, $_GET['searchPackage']);

            echo "<div class='search-results'>";

            // Search by username
            if (!empty($searchUsername)) {
                echo "<h4>Username Results:</h4>";
                $userSearchQuery = "SELECT * FROM profile WHERE username LIKE '%$searchUsername%'";
                $userSearchResult = mysqli_query($link, $userSearchQuery);

                if (mysqli_num_rows($userSearchResult) > 0) {
                    echo "<table>
                            <thead>
                                <tr><th>Username</th><th>Full Name</th><th>Email</th><th>Contact</th></tr>
                            </thead>
                            <tbody>";
                    while ($row = mysqli_fetch_assoc($userSearchResult)) {
                        echo "<tr>
                                <td>{$row['username']}</td>
                                <td>{$row['user_FullName']}</td>
                                
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>No users found.</p>";
                }
            }

            // Search by branch_id
            if (!empty($searchBranch)) {
                echo "<h4>Branch ID Results:</h4>";
                $branchSearchQuery = "SELECT * FROM koperasi_branch WHERE branch_id LIKE '%$searchBranch%'";
                $branchSearchResult = mysqli_query($link, $branchSearchQuery);

                if (mysqli_num_rows($branchSearchResult) > 0) {
                    echo "<table>
                            <thead>
                                <tr><th>Branch ID</th><th>Branch Name</th><th>Location</th></tr>
                            </thead>
                            <tbody>";
                    while ($row = mysqli_fetch_assoc($branchSearchResult)) {
                        echo "<tr>
                                <td>{$row['branch_id']}</td>
                                <td>{$row['branch_Name']}</td>
                                
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>No branches found.</p>";
                }
            }

            // Search by package_id
            if (!empty($searchPackage)) {
                echo "<h4>Package ID Results:</h4>";
                $packageSearchQuery = "SELECT * FROM printing_package WHERE package_id LIKE '%$searchPackage%'";
                $packageSearchResult = mysqli_query($link, $packageSearchQuery);

                if (mysqli_num_rows($packageSearchResult) > 0) {
                    echo "<table>
                            <thead>
                                <tr><th>Package ID</th><th>Package Name</th><th>Price</th></tr>
                            </thead>
                            <tbody>";
                    while ($row = mysqli_fetch_assoc($packageSearchResult)) {
                        echo "<tr>
                                <td>{$row['package_id']}</td>
                                <td>{$row['package_Name']}</td>
                                <td>RM" . number_format($row['package_Price'], 2) . "</td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>No packages found.</p>";
                }
            }

            echo "</div>"; // Close search-results div
        }
        ?>
    </section>>
	<div class="dashboard">
		<!-- search -> kena ada 3 -->
        <div class="dashboard-card">
			<a href="#" style="text-decoration: none; color: inherit"> 
            <h3>Total User <br>Profiles</h3>
            <p><?php
			$user_query = "SELECT COUNT(*) AS num_of_users FROM profile;";
			$user_result = mysqli_query($link, $user_query);
			if (($user_result)) {
                $row = mysqli_fetch_assoc($user_result);
				echo $row['num_of_users'];
            } else {
                echo "0";
            }
			?></p>
			</a>
        </div>
        <div class="dashboard-card">
			<a href="M1_viewBranch.php" style="text-decoration: none; color: inherit">
            <h3>Total Koperasi Branches</h3>
            <p><?php
			$branch_query = "SELECT COUNT(*) AS num_of_branches FROM koperasi_branch;";
			$branch_result = mysqli_query($link, $branch_query);
			if (($branch_result)) {
                $row = mysqli_fetch_assoc($branch_result);
				echo $row['num_of_branches'];
            } else {
                echo "0";
            }
			?></p>
			</a>
        </div>
        <div class="dashboard-card">
			<a href="M1_viewPackage.php" style="text-decoration: none; color: inherit">
            <h3>Total Printing Packages</h3>
            <p><?php
			$package_query = "SELECT COUNT(*) AS num_of_packages FROM printing_package;";
			$package_result = mysqli_query($link, $package_query);
			if (($package_result)) {
                $row = mysqli_fetch_assoc($package_result);
				echo $row['num_of_packages'];
            } else {
                echo "0";
            }
			?></p>
			</a>
        </div>
        <div class="dashboard-card">
			<a href="#" style="text-decoration: none; color: inherit">
            <h3>Completed <br> Orders</h3>
            <p><?php
			$order_query = "SELECT COUNT(*) AS num_of_order FROM `order` WHERE `order_Status` = 'Completed';";
			$order_result = mysqli_query($link, $order_query);
			if ($order_result) {
                $row = mysqli_fetch_assoc($order_result);
				echo $row['num_of_order'];
            } else {
                echo "0";
            }
			?></p>
			</a>
        </div>
    </div>
	
	<section class="graph">
        <h3>Graphs</h3>
		<h4>Customer Type</h4>
			<?php
			$guestCustomerQuery = "SELECT 
				SUM(CASE WHEN cust_id IS NOT NULL AND guest_Name IS NULL THEN 1 ELSE 0 END) AS customer_orders, 
				SUM(CASE WHEN guest_Name IS NOT NULL AND cust_id IS NULL THEN 1 ELSE 0 END) AS guest_orders 
				FROM `order`";
			$guestCustomerResult = $link->query($guestCustomerQuery);
			$guestCustomerData = $guestCustomerResult->fetch_assoc();
			$customerOrders = $guestCustomerData['customer_orders'];
			$guestOrders = $guestCustomerData['guest_orders'];
			?>
			<canvas id="pieChart" width="900" height="400" style="margin:auto;"></canvas>
			<script>
				const pieCtx = document.getElementById('pieChart').getContext('2d');
				const pieChart = new Chart(pieCtx, {
					type: 'doughnut', 
					data: {
						labels: ['Customer Orders', 'Guest Orders'],
						datasets: [{
							data: [<?= $customerOrders; ?>, <?= $guestOrders; ?>],
							backgroundColor: [
								'rgba(255, 99, 132, 0.6)', // Lighter red
								'rgba(54, 162, 235, 0.6)'  // Lighter blue
							],
							hoverOffset: 4
						}]
					},
					options: {
						responsive: false,
						maintainAspectRatio: false,
						scales: {
							y: {
								beginAtZero: true
							}
						}
					}
				});
			</script>
		<br><br><br>
        <!-- Bar Chart -->
		<h4>Total Order per Koperasi Branch</h4>
			<?php
			$branchQuery = "SELECT branch_id, COUNT(*) AS order_count FROM `order` GROUP BY branch_id";
			$branchResult = $link->query($branchQuery);
			$branches = [];
			$orderCounts = [];
			while ($row = $branchResult->fetch_assoc()) {
				$branches[] = "KP0" . $row['branch_id'];
				$orderCounts[] = $row['order_count'];
			}
			?>
			<canvas id="barChart" width="900" height="400" style="margin:auto;"></canvas>
			<script>
				const barCtx = document.getElementById('barChart').getContext('2d');
				const barChart = new Chart(barCtx, {
					type: 'bar',
					data: {
						labels: <?= json_encode($branches); ?>,
						datasets: [{
							label: 'Orders per Branch',
							data: <?= json_encode($orderCounts); ?>,
							backgroundColor: [
								'rgba(255, 99, 132, 0.2)', // Red
								'rgba(54, 162, 235, 0.2)', // Blue
								'rgba(255, 206, 86, 0.2)', // Yellow
								'rgba(75, 192, 192, 0.2)', // Green
								'rgba(153, 102, 255, 0.2)' // Purple
							],
							borderColor: [
								'rgba(255, 99, 132, 1)',
								'rgba(54, 162, 235, 1)',
								'rgba(255, 206, 86, 1)',
								'rgba(75, 192, 192, 1)',
								'rgba(153, 102, 255, 1)'
							],
							borderWidth: 1
						}]
					},
					options: {
						responsive: false,
						maintainAspectRatio: false,
						scales: {
							y: {
								beginAtZero: true
							}
						}
					}
				});
			</script>
    </section>
	
    <section class="reports">
        <h3>Reports</h3>
        <h4>Active Order</h4>
        <!-- TABLE (order--'not completed') list order_ID, staff_ID, order_Status -->
		<table>
            <thead>
                <tr>
                    <th>ORDER ID</th>
                    <th>STAFF ID</th>
                    <th>ORDER STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $actorder_query = "SELECT * FROM `order` WHERE `order_Status` = 'Incomplete';";
                $actorder_result = mysqli_query($link, $actorder_query);

                if (mysqli_num_rows($actorder_result) > 0) {
                    while ($row = mysqli_fetch_assoc($actorder_result)) {
                        echo "<tr>";
						
                        echo "<td>" . $row['order_id'] . "</td>";
                        echo "<td>" . $row['staff_id'] . "</td>";
                        echo "<td>" . $row['order_Status'] . "</td>";
						
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No order recorded.</td></tr>";
                }
                ?>
			</tbody>
		</table>
		
		
        <h4>Pending Payment</h4>
        <!-- TABLE (order/payment--'pending') list order_ID(order), cust_ID/guest_ID(order), payment_Amount(payment), payment_Status(payment) -->
		<table>
            <thead>
                <tr>
                    <th>ORDER ID</th>
                    <th>CUSTOMER</th>
                    <th>PAYMENT AMOUNT</th>
                    <th>PAYMENT STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $payment_query = "SELECT o.order_id,o.cust_id,o.guest_Name,p.payment_Amount,p.payment_Status FROM `order` o JOIN payment p ON o.order_id = p.order_id WHERE payment_Status = 'Pending';";
                $payment_result = mysqli_query($link, $payment_query);

                if (mysqli_num_rows($payment_result) > 0) {
                    while ($row = mysqli_fetch_assoc($payment_result)) {
                        echo "<tr>";
						
                        echo "<td>" . $row['order_id'] . "</td>";
                        
						if($row['cust_id']==null){
							echo "<td>" . $row['guest_Name'] . "</td>";
						}else{
							echo "<td>" . $row['cust_id'] . "</td>";
						}
						
                        echo "<td>RM" . number_format($row['payment_Amount'], 2) . "</td>";
						echo "<td>" . $row['payment_Status'] . "</td>";
						
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No payment recorded.</td></tr>";
                }
                ?>
			</tbody>
		</table>
		
		<h4>User Log</h4>
		<!-- TABLE (profile-login) display profile(fullname) and all from login desc by session_id -->
		<table>
            <thead>
                <tr>
                    <th>SESSION ID</th>
                    <th>NAME</th>
                    <th>LOGIN</th>
                    <th>LOGOUT</th>
                    <th>CURRENT STATE</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sess_query = "SELECT l.session_id,p.user_FullName,l.login_Timestamp,l.logout_Timestamp,l.current_State FROM profile p JOIN login l ON p.username = l.username ORDER BY session_id DESC";
                $sess_result = mysqli_query($link, $sess_query);

                if (mysqli_num_rows($sess_result) > 0) {
                    while ($row = mysqli_fetch_assoc($sess_result)) {
                        echo "<tr>";
						
                        echo "<td>" . $row['session_id'] . "</td>";
                        echo "<td>" . $row['user_FullName'] . "</td>";
                        echo "<td>" . $row['login_Timestamp'] . "</td>";
						
						if($row['current_State']=="active"){
							echo "<td> </td>";
						}else{
							echo "<td>" . $row['logout_Timestamp'] . "</td>";
						}
						
                        echo "<td>" . $row['current_State'] . "</td>";
						
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No log recorded.</td></tr>";
                }
                ?>
			</tbody>
		</table>
    </section>
	
</main>

<footer>
    <p>&copy; 2024 RapidPrintUMPSA. All rights reserved.</p>
</footer>
	
</body>
</html> 