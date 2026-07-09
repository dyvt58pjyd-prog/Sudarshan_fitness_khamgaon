<?php
require 'auth.php';
require '../../include/db_conn.php';
$gym = get_gym_details($con);

// Calculate Today's Income (Membership + PT) grouped by Cash vs UPI
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

function get_collection($con, $date) {
    $cash = 0;
    $upi = 0;
    
    // Membership Collection
    $q_mem = "SELECT paid_amount, payment_mode FROM enrolls_to WHERE paid_date = '$date'";
    $res_mem = mysqli_query($con, $q_mem);
    if($res_mem && mysqli_num_rows($res_mem) > 0){
        while($row = mysqli_fetch_assoc($res_mem)){
            $amount = intval($row['paid_amount']);
            $mode = strtolower(trim($row['payment_mode']));
            if(strpos($mode, 'cash') !== false) {
                $cash += $amount;
            } elseif(strpos($mode, 'upi') !== false || strpos($mode, 'online') !== false) {
                $upi += $amount;
            } else {
                $cash += $amount; // Default fallback to cash
            }
        }
    }

    // PT Collection
    $q_pt = "SELECT amount, payment_mode FROM pt_enrollments WHERE enroll_date = '$date'";
    $res_pt = mysqli_query($con, $q_pt);
    if($res_pt && mysqli_num_rows($res_pt) > 0){
        while($row = mysqli_fetch_assoc($res_pt)){
            $amount = intval($row['amount']);
            $mode = strtolower(trim($row['payment_mode']));
            if(strpos($mode, 'cash') !== false) {
                $cash += $amount;
            } elseif(strpos($mode, 'upi') !== false || strpos($mode, 'online') !== false) {
                $upi += $amount;
            } else {
                $cash += $amount; // Default fallback to cash
            }
        }
    }
    
    return ['cash' => $cash, 'upi' => $upi, 'total' => $cash + $upi];
}

$today_data = get_collection($con, $today);
$yesterday_data = get_collection($con, $yesterday);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Auditor Dashboard | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="stylesheet" href="../../css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <style>
        body { background: #0b0f19; color: #fff; font-family: 'Inter', sans-serif; }
        .page-container { display: flex; }
        .sidebar-menu { width: 250px; background: #111827; padding: 20px; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px; }
        .stat-card { background: #1f2937; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border-left: 4px solid #ff6b00; }
        .stat-card h3 { margin-top: 0; color: #9ca3af; font-size: 14px; text-transform: uppercase; }
        .stat-card h2 { font-size: 28px; margin: 10px 0; color: #fff; }
        .stat-details { display: flex; justify-content: space-between; font-size: 14px; margin-top: 15px; border-top: 1px solid #374151; padding-top: 10px; }
        .stat-details span.cash { color: #10b981; font-weight: bold; }
        .stat-details span.upi { color: #3b82f6; font-weight: bold; }
        .sidebar-menu ul { list-style: none; padding: 0; }
        .sidebar-menu ul li { margin-bottom: 10px; }
        .sidebar-menu ul li a { color: #9ca3af; text-decoration: none; display: block; padding: 10px; border-radius: 6px; }
        .sidebar-menu ul li a:hover, .sidebar-menu ul li.active a { background: #ff6b00; color: #fff; }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="sidebar-menu">
            <h2 style="color:#ff6b00; text-align:center; margin-bottom: 30px;">Titan Gym<br><small style="color:#fff;">Auditor</small></h2>
            <?php include('nav.php'); ?>
        </div>
        
        <div class="main-content">
            <h1 style="margin-bottom: 30px;">Auditing Dashboard</h1>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <!-- Today's Collection -->
                <div class="stat-card" style="flex: 1; min-width: 300px;">
                    <h3>Today's Total Collection</h3>
                    <h2>₹<?php echo number_format($today_data['total']); ?></h2>
                    <div class="stat-details">
                        <span>Cash: <span class="cash">₹<?php echo number_format($today_data['cash']); ?></span></span>
                        <span>UPI: <span class="upi">₹<?php echo number_format($today_data['upi']); ?></span></span>
                    </div>
                </div>
                
                <!-- Yesterday's Collection -->
                <div class="stat-card" style="flex: 1; min-width: 300px; border-left-color: #6b7280;">
                    <h3>Yesterday's Total Collection</h3>
                    <h2>₹<?php echo number_format($yesterday_data['total']); ?></h2>
                    <div class="stat-details">
                        <span>Cash: <span class="cash">₹<?php echo number_format($yesterday_data['cash']); ?></span></span>
                        <span>UPI: <span class="upi">₹<?php echo number_format($yesterday_data['upi']); ?></span></span>
                    </div>
                </div>
                <!-- Specific Date Collection -->
                <div class="stat-card" style="flex: 1; min-width: 300px; border-left-color: #ec4899;">
                    <h3>Daily Collection Report</h3>
                    <form action="invoices.php" method="GET" style="margin-top: 15px; display: flex; gap: 10px;">
                        <input type="date" name="start_date" style="flex: 1; padding: 8px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; color-scheme: dark;" required onchange="this.form.end_date.value = this.value;">
                        <input type="hidden" name="end_date" value="">
                        <button type="submit" style="background: #ec4899; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: bold; cursor: pointer;">View</button>
                    </form>
                    <p style="font-size: 11px; color: #9ca3af; margin-top: 10px;">Select a date to see all collections, payer details, and PT payments for that day.</p>
                </div>
            </div>
            
            <div style="margin-top: 40px; background: #1f2937; padding: 20px; border-radius: 12px;">
                <h3>Navigation</h3>
                <p style="color: #9ca3af; line-height: 1.6;">Welcome to the Auditor Dashboard. Use the sidebar to navigate to the <strong>Invoice Ledger</strong>, where you can view all individual transactions (including Memberships and Personal Training). Your role is restricted to financial auditing only.</p>
                <a href="invoices.php" style="display: inline-block; margin-top: 15px; background: #ff6b00; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;">View Full Invoice Ledger</a>
            </div>
        </div>
    </div>
</body>
</html>
