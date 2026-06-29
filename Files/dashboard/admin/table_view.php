<?php
require '../../include/db_conn.php';
page_protect();

$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch all members up to the working year with trainer name joined
$query  = "SELECT u.*, t.Full_name AS trainer_name 
           FROM users u 
           LEFT JOIN admin t ON u.trainer_id = t.username
           WHERE YEAR(u.joining_date) <= " . $_SESSION['working_year'] . " 
           ORDER BY u.joining_date";
$result = mysqli_query($con, $query);

$all_members = [];
$active_members = [];
$expired_members = [];

$today = date('Y-m-d');

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $uid = $row['userid'];
        $query1  = "select * from enrolls_to WHERE uid='$uid' AND YEAR(paid_date) <= " . $_SESSION['working_year'] . " ORDER BY expire DESC LIMIT 1";
        $result1 = mysqli_query($con, $query1);
        
        $expire = "No Active Plan";
        $is_active = false;
        if ($result1 && mysqli_num_rows($result1) > 0) {
            $row1 = mysqli_fetch_array($result1, MYSQLI_ASSOC);
            $expire = $row1['expire'];
            if ($expire >= $today) {
                $is_active = true;
            }
        }
        
        $row['expire'] = $expire;
        $row['is_active'] = $is_active;
        
        $all_members[] = $row;
        if ($is_active) {
            $active_members[] = $row;
        } else {
            $expired_members[] = $row;
        }
    }
}

$total_count = count($all_members);
$active_count = count($active_members);
$expired_count = count($expired_members);

if ($status === 'active') {
    $members_to_show = $active_members;
} elseif ($status === 'expired') {
    $members_to_show = $expired_members;
} else {
    $members_to_show = $all_members;
    $status = 'all';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>

    <title>SUDARSHAN FITNESS | View Member</title>
   <link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
	<link rel="stylesheet" href="../../css/premium.css">
	<link href="a1style.css" rel="stylesheet" type="text/css">
	
	<style>
 	#button1
	{
	width:126px;
	}

	.page-container .sidebar-menu #main-menu li#hassubopen > a {
	background-color: #2b303a;
	color: #ffffff;
	}

	.member-tabs-container {
		display: flex;
		gap: 12px;
		margin-bottom: 25px;
		flex-wrap: wrap;
	}
	.tab-btn {
		background: rgba(255, 255, 255, 0.03);
		color: #a3a3a3;
		border: 1px solid rgba(255, 107, 0, 0.15);
		padding: 10px 22px;
		border-radius: 8px;
		text-decoration: none !important;
		font-weight: 600;
		font-size: 13px;
		transition: all 0.3s ease;
		display: inline-flex;
		align-items: center;
		gap: 8px;
		box-shadow: 0 4px 10px rgba(0,0,0,0.2);
	}
	.tab-btn:hover {
		background: rgba(255, 107, 0, 0.08);
		color: #ffffff;
		border-color: rgba(255, 107, 0, 0.4);
		box-shadow: 0 0 12px rgba(255, 107, 0, 0.2);
	}
	.tab-btn.active-tab {
		background: linear-gradient(135deg, rgba(255, 107, 0, 0.25), rgba(255, 107, 0, 0.08));
		color: #ffffff;
		border-color: #ff6b00;
		box-shadow: 0 0 15px rgba(255, 107, 0, 0.3);
	}
	.tab-count {
		background: rgba(255, 255, 255, 0.1);
		padding: 2px 8px;
		border-radius: 20px;
		font-size: 11px;
		font-weight: bold;
		color: #ffffff;
	}
	.tab-btn.active-tab .tab-count {
		background: #ff6b00;
	}
	.status-badge {
		padding: 2px 8px;
		border-radius: 4px;
		font-size: 10px;
		font-weight: 800;
		text-transform: uppercase;
		display: inline-block;
		margin-left: 8px;
		letter-spacing: 0.5px;
	}
	.status-active {
		background: rgba(16, 185, 129, 0.12);
		color: #10b981;
		border: 1px solid rgba(16, 185, 129, 0.3);
		text-shadow: 0 0 6px rgba(16, 185, 129, 0.4);
	}
	.status-expired {
		background: rgba(239, 68, 68, 0.12);
		color: #ef4444;
		border: 1px solid rgba(239, 68, 68, 0.3);
		text-shadow: 0 0 6px rgba(239, 68, 68, 0.4);
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
					
					<!-- Profile Info and Notifications -->
					<div class="col-md-6 col-sm-8 clearfix">	
							
					</div>
					
					
					<!-- Raw Links -->
					<div class="col-md-6 col-sm-4 clearfix hidden-xs">
						
						<ul class="list-inline links-list pull-right">

							<li>Welcome <?php echo $_SESSION['full_name']; ?> 
							</li>								
						
							<li>
								<a href="logout.php">
									Log Out <i class="entypo-logout right"></i>
								</a>
							</li>
						</ul>
						
					</div>
					
				</div>

		<h3>Member Detail</h3>

		<hr />

		<!-- Filter Tabs -->
		<div class="member-tabs-container">
			<a href="?status=all" class="tab-btn <?php echo $status === 'all' ? 'active-tab' : ''; ?>">
				All Members <span class="tab-count"><?php echo $total_count; ?></span>
			</a>
			<a href="?status=active" class="tab-btn <?php echo $status === 'active' ? 'active-tab' : ''; ?>">
				Active Plan Members <span class="tab-count"><?php echo $active_count; ?></span>
			</a>
			<a href="?status=expired" class="tab-btn <?php echo $status === 'expired' ? 'active-tab' : ''; ?>">
				Expired Plan Members <span class="tab-count"><?php echo $expired_count; ?></span>
			</a>
		</div>
		
		<table class="table table-bordered datatable" id="table-1" border=1>
			<thead>
				<tr>
					<th>Sl.No</th>
					<th>Member ID</th>
					<th>Name</th>
					<th>Contact</th>
					<th>E-Mail</th>
					<th>Gender</th>
					<th>Assigned Trainer</th>
					<th>Membership Expiry</th>
					<th>Status</th>
					<th>Joining Date</th>
					<th>Action</th>
				</tr>
			</thead>
				<tbody>

						<?php
							$sno    = 1;
							foreach ($members_to_show as $row) {
							    $uid   = $row['userid'];
							    $expire = $row['expire'];
							    $is_active = $row['is_active'];
							    
							    $badge = $is_active ? 
							        '<span class="status-badge status-active">ACTIVE</span>' : 
							        '<span class="status-badge status-expired">EXPIRED</span>';
							    
							    $trainer_disp = !empty($row['trainer_name']) ? htmlspecialchars($row['trainer_name']) : '<span style="color:var(--text-muted);">None</span>';
							    
							    echo "<tr><td>".$sno."</td>";
							    echo "<td>" . htmlspecialchars($row['userid']) . "</td>";
							    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
							    echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
							    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
							    echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
							    echo "<td>" . $trainer_disp . "</td>";
							    echo "<td>" . htmlspecialchars($expire) . "</td>";
							    echo "<td>" . $badge . "</td>";
							    echo "<td>" . htmlspecialchars($row['joining_date']) ."</td>";
							    
							    $sno++;
							   
							    echo "<td><form action='viewall_detail.php' method='post'><input type='hidden' name='name' value='" . $uid . "'/><input type='submit' class='a1-btn a1-blue' id='button1' value='View All'/></form></td></tr>";
							}
						?>									
					</tbody>
				</table>

<script>
	
	function ConfirmDelete(name){
	
    var r = confirm("Are you sure! You want to Delete this User?");
    if (r == true) {
       return true;
    } else {
        return false;
    }
}

</script>
		
			<?php include('footer.php'); ?>
    	</div>

    </body>
</html>


