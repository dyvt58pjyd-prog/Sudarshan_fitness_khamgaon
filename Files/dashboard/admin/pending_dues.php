<?php
require '../../include/db_conn.php';
page_protect();
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <title>SUDARSHAN FITNESS | Pending Dues</title>
  
	<link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
	<link href="a1style.css" rel="stylesheet" type="text/css">
	
	<style>
 		.page-container .sidebar-menu #main-menu li#payhassubopen > a {
    	background-color: #2b303a;
    	color: #ffffff;
		}
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 400px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
	</style>

</head>
<body class="page-body  page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
		<div class="sidebar-menu">
			<header class="logo-env">
			<!-- logo -->
			<div class="logo">
				<a href="main.php">
					<?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
				</a>
			</div>
			<!-- logo collapse icon -->
			<div class="sidebar-collapse" onclick="collapseSidebar()">
				<a href="#" class="sidebar-collapse-icon with-animation"><!-- add class "with-animation" if you want sidebar to have animation during expanding/collapsing transition -->
					<i class="entypo-menu"></i>
				</a>
			</div>
			</header>
    		<?php include('nav.php'); ?>
    	</div>

    	<div class="main-content">
			<div class="row">
				<div class="col-md-6 col-sm-8 clearfix"></div>
				<div class="col-md-6 col-sm-4 clearfix hidden-xs">
					<ul class="list-inline links-list pull-right">
						<li>Welcome <?php echo $_SESSION['full_name']; ?> </li>						
						<li><a href="logout.php">Log Out <i class="entypo-logout right"></i></a></li>
					</ul>
				</div>
			</div>

            <h3>Pending Dues</h3>
            <hr />

            <div style="overflow-x:auto;">
            <table class="table table-bordered datatable" id="table-1" border=1>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Member Name</th>
                        <th>Member ID</th>
                        <th>Plan Name</th>
                        <th>Paid Date</th>
                        <th>Total Paid</th>
                        <th>Balance Due</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $query  = "SELECT u.username, e.et_id, e.uid, e.paid_date, e.paid_amount, e.balance, e.balance_due_date, p.planName
                                   FROM enrolls_to e
                                   INNER JOIN users u ON u.userid = e.uid
                                   INNER JOIN plan p ON p.pid = e.pid
                                   WHERE e.balance > 0
                                   ORDER BY e.balance_due_date ASC";
                        
                        $result = mysqli_query($con, $query);
                        $sno    = 1;

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . $sno . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['uid']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['planName']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['paid_date']) . "</td>";
                                echo "<td>₹" . htmlspecialchars($row['paid_amount']) . "</td>";
                                echo "<td style='color:red; font-weight:bold;'>₹" . htmlspecialchars($row['balance']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['balance_due_date']) . "</td>";
                                echo "<td><button class='a1-btn a1-blue' style='padding: 6px 12px;' onclick=\"openModal('".$row['et_id']."', '".$row['username']."', '".$row['balance']."')\">Collect</button></td>";
                                echo "</tr>";
                                $sno++;
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center'>No pending dues found!</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
            </div>

            <!-- Collect Payment Modal -->
            <div id="collectModal" class="modal">
              <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h4>Collect Balance Payment</h4>
                <hr>
                <form action="collect_balance.php" method="POST">
                    <input type="hidden" name="et_id" id="modal_et_id">
                    <p><strong>Member:</strong> <span id="modal_member_name"></span></p>
                    <p><strong>Current Balance:</strong> ₹<span id="modal_balance"></span></p>
                    
                    <div style="margin-top: 15px;">
                        <label>Amount to Collect (₹):</label>
                        <input type="number" name="collect_amount" id="modal_collect_amount" class="form-control" required style="margin-top:5px; margin-bottom:15px;">
                    </div>
                    
                    <div>
                        <label>Payment Mode:</label>
                        <select name="payment_mode" class="form-control" required style="margin-top:5px; margin-bottom:15px;">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>

                    <button type="submit" class="a1-btn a1-blue" style="width: 100%;">Confirm Payment</button>
                </form>
              </div>
            </div>

            <script>
                function openModal(etId, name, balance) {
                    document.getElementById('modal_et_id').value = etId;
                    document.getElementById('modal_member_name').innerText = name;
                    document.getElementById('modal_balance').innerText = balance;
                    document.getElementById('modal_collect_amount').value = balance;
                    document.getElementById('modal_collect_amount').max = balance;
                    document.getElementById('collectModal').style.display = 'block';
                }
                function closeModal() {
                    document.getElementById('collectModal').style.display = 'none';
                }
                window.onclick = function(event) {
                    if (event.target == document.getElementById('collectModal')) {
                        closeModal();
                    }
                }
            </script>

			<?php include('footer.php'); ?>
    	</div>
    </body>
</html>
