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
    
    // Membership Base Collection (Minus deferred balance collections)
    $q_mem = "SELECT e.et_id, e.paid_amount, e.payment_mode FROM enrolls_to e WHERE e.paid_date = '$date'";
    $res_mem = mysqli_query($con, $q_mem);
    if($res_mem && mysqli_num_rows($res_mem) > 0){
        while($row = mysqli_fetch_assoc($res_mem)){
            $amount = intval($row['paid_amount']);
            
            // Subtract any balance collected LATER than this date for this specific enrollment
            $et_id = $row['et_id'];
            $q_def = mysqli_query($con, "SELECT SUM(amount) as def_amount FROM balance_collections WHERE et_id = '$et_id'");
            if ($q_def && mysqli_num_rows($q_def) > 0) {
                $def_row = mysqli_fetch_assoc($q_def);
                $amount -= intval($def_row['def_amount']);
            }
            
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

    // Deferred Balance Collections (Collected ON this date)
    $q_bal = "SELECT amount, payment_mode FROM balance_collections WHERE collection_date = '$date'";
    $res_bal = mysqli_query($con, $q_bal);
    if($res_bal && mysqli_num_rows($res_bal) > 0){
        while($row = mysqli_fetch_assoc($res_bal)){
            $amount = intval($row['amount']);
            $mode = strtolower(trim($row['payment_mode']));
            if(strpos($mode, 'cash') !== false) {
                $cash += $amount;
            } elseif(strpos($mode, 'upi') !== false || strpos($mode, 'online') !== false) {
                $upi += $amount;
            } else {
                $cash += $amount;
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
        .sidebar-menu { width: 250px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); padding: 20px; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; }
        
        .stat-card { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            border-radius: 24px; 
            padding: 30px 20px; 
            margin-bottom: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            position: relative; 
            overflow: hidden; 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .stat-card.tile-orange { border-bottom: 4px solid #ff6b00; box-shadow: inset 0 -15px 30px -20px rgba(255,107,0,0.5); }
        .stat-card.tile-gray { border-bottom: 4px solid #9ca3af; box-shadow: inset 0 -15px 30px -20px rgba(156,163,175,0.5); }
        .stat-card.tile-pink { border-bottom: 4px solid #ec4899; box-shadow: inset 0 -15px 30px -20px rgba(236,72,153,0.5); }
        
        .stat-card h3 { margin-top: 0; color: #9ca3af; font-size: 14px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; }
        .stat-card h2 { font-size: 42px; font-weight: 800; margin: 15px 0; color: #fff; text-shadow: 0 0 20px rgba(255,255,255,0.2); }
        
        .stat-details { display: flex; justify-content: space-between; font-size: 14px; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; }
        .stat-details span.cash { color: #10b981; font-weight: 800; }
        .stat-details span.upi { color: #3b82f6; font-weight: 800; }
        
        .sidebar-menu ul { list-style: none; padding: 0; }
        .sidebar-menu ul li { margin-bottom: 10px; }
        .sidebar-menu ul li a { color: #9ca3af; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
        .sidebar-menu ul li a:hover, .sidebar-menu ul li.active a { background: linear-gradient(135deg, #ff6b00, #e65c00); color: #fff; box-shadow: 0 4px 15px rgba(255,107,0,0.3); }
        
        .nav-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 30px; margin-top: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .btn-primary { display: inline-block; margin-top: 20px; background: linear-gradient(135deg, #ff6b00, #e65c00); color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.2s; box-shadow: 0 4px 15px rgba(255,107,0,0.3); border: none; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255,107,0,0.4); }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="sidebar-menu">
            <h2 style="color:#ff6b00; text-align:center; margin-bottom: 30px;">Titan Gym<br><small style="color:#fff;">Auditor</small></h2>
            <?php include('nav.php'); ?>
        </div>
        
        <div class="main-content">
            <h1 style="margin-bottom: 30px; font-weight: 800; font-size: 32px; display: flex; align-items: center; gap: 10px;"><i class="entypo-chart-bar" style="color: #ff6b00;"></i> Auditing Dashboard</h1>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <!-- Today's Collection -->
                <div class="stat-card tile-orange" style="flex: 1; min-width: 300px;">
                    <h3>Today's Total Collection</h3>
                    <h2>₹<?php echo number_format($today_data['total']); ?></h2>
                    <div class="stat-details">
                        <span>Cash: <span class="cash">₹<?php echo number_format($today_data['cash']); ?></span></span>
                        <span>UPI: <span class="upi">₹<?php echo number_format($today_data['upi']); ?></span></span>
                    </div>
                </div>
                
                <!-- Yesterday's Collection -->
                <div class="stat-card tile-gray" style="flex: 1; min-width: 300px;">
                    <h3>Yesterday's Total Collection</h3>
                    <h2>₹<?php echo number_format($yesterday_data['total']); ?></h2>
                    <div class="stat-details">
                        <span>Cash: <span class="cash">₹<?php echo number_format($yesterday_data['cash']); ?></span></span>
                        <span>UPI: <span class="upi">₹<?php echo number_format($yesterday_data['upi']); ?></span></span>
                    </div>
                </div>
                <!-- Specific Date Collection -->
                <div class="stat-card tile-pink" style="flex: 1; min-width: 300px;">
                    <h3>Daily Collection Report</h3>
                    <form action="invoices.php" method="GET" style="margin-top: 15px; display: flex; gap: 10px;">
                        <input type="date" name="start_date" style="flex: 1; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: white; color-scheme: dark; font-family: monospace; font-size: 14px;" required onchange="this.form.end_date.value = this.value;">
                        <input type="hidden" name="end_date" value="">
                        <button type="submit" class="btn-primary" style="margin-top: 0; padding: 10px 20px; background: linear-gradient(135deg, #ec4899, #be185d); box-shadow: 0 4px 15px rgba(236, 72, 153, 0.3);">View</button>
                    </form>
                    <p style="font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 15px;">Select a date to see all exact collections, payer details, and PT payments for that day.</p>
                </div>
            </div>
            
            <div class="nav-card">
                <h3 style="margin-top: 0; font-weight: 800; font-size: 20px;"><i class="entypo-docs"></i> Navigation</h3>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.6; font-size: 14px;">Welcome to the Auditor Dashboard. Use the sidebar to navigate to the <strong>Invoice Ledger</strong>, where you can view all individual transactions (including Memberships and Personal Training). Your role is restricted to financial auditing only.</p>
                <a href="invoices.php" class="btn-primary"><i class="entypo-book-open"></i> View Full Invoice Ledger</a>
            </div>
        </div>
    </div>
</body>
</html>
