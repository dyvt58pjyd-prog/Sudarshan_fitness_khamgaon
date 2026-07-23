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
    $mem_amt = 0;
    $bal_amt = 0;
    $pt_amt = 0;
    $inv_amt = 0;
    
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
            
            $mem_amt += $amount;
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
            $bal_amt += $amount;
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
            $pt_amt += $amount;
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

    // Inventory Sales
    $q_inv = "SELECT total_price as amount, payment_mode FROM inventory_sales WHERE sale_date = '$date'";
    $res_inv = mysqli_query($con, $q_inv);
    if($res_inv && mysqli_num_rows($res_inv) > 0){
        while($row = mysqli_fetch_assoc($res_inv)){
            $amount = intval($row['amount']);
            $inv_amt += $amount;
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
    
    // Gym Expenses on this date
    $q_exp = "SELECT SUM(amount) as total_exp FROM expenses WHERE expense_date = '$date'";
    $res_exp = mysqli_query($con, $q_exp);
    $exp_amt = 0;
    if ($res_exp && mysqli_num_rows($res_exp) > 0) {
        $exp_row = mysqli_fetch_assoc($res_exp);
        $exp_amt = intval($exp_row['total_exp']);
    }

    $gross_total = $cash + $upi;
    $net_total = $gross_total - $exp_amt;
    
    return [
        'cash' => $cash, 
        'upi' => $upi, 
        'total' => $gross_total,
        'expenses' => $exp_amt,
        'net_total' => $net_total,
        'membership' => $mem_amt,
        'balance' => $bal_amt,
        'pt' => $pt_amt,
        'inventory' => $inv_amt
    ];
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
                <div class="stat-card tile-orange" style="flex: 1; min-width: 250px;">
                    <h3>Today's Total Collection</h3>
                    <h2>₹<?php echo number_format($today_data['total']); ?></h2>
                    <div class="stat-details">
                        <span>Cash: <span class="cash">₹<?php echo number_format($today_data['cash']); ?></span></span>
                        <span>UPI: <span class="upi">₹<?php echo number_format($today_data['upi']); ?></span></span>
                    </div>
                </div>

                <!-- Today's Gym Expenses -->
                <div class="stat-card" style="flex: 1; min-width: 250px; border-bottom: 4px solid #ef4444; box-shadow: inset 0 -15px 30px -20px rgba(239,68,68,0.5);">
                    <h3>Today's Gym Expenses</h3>
                    <h2 style="color: #ef4444;">₹<?php echo number_format($today_data['expenses']); ?></h2>
                    <div class="stat-details">
                        <span>Outgoings, Bills &amp; Restock</span>
                        <a href="expenses_audit.php" style="color: #ef4444; font-weight: bold; text-decoration: underline;">Audit Expenses</a>
                    </div>
                </div>

                <!-- Today's Net Collection / Profit -->
                <div class="stat-card" style="flex: 1; min-width: 250px; border-bottom: 4px solid #f59e0b; box-shadow: inset 0 -15px 30px -20px rgba(245,158,11,0.5);">
                    <h3>Today's Net Collection</h3>
                    <h2 style="color: #f59e0b;">₹<?php echo number_format($today_data['net_total']); ?></h2>
                    <div class="stat-details">
                        <span>Gross Income - Outgoings</span>
                        <span style="color: #cbd5e1; font-size: 11px;">Collection After Expenses</span>
                    </div>
                </div>

                <!-- Yesterday's Collection -->
                <div class="stat-card tile-gray" style="flex: 1; min-width: 250px;">
                    <h3>Yesterday's Total Collection</h3>
                    <h2>₹<?php echo number_format($yesterday_data['total']); ?></h2>
                    <div class="stat-details">
                        <span>Expenses: <span style="color: #ef4444; font-weight: 800;">₹<?php echo number_format($yesterday_data['expenses']); ?></span></span>
                        <span>Net: <span style="color: #f59e0b; font-weight: 800;">₹<?php echo number_format($yesterday_data['net_total']); ?></span></span>
                    </div>
                </div>

                <!-- Specific Date Collection -->
                <div class="stat-card tile-pink" style="flex: 1; min-width: 250px;">
                    <h3>Daily Collection Report</h3>
                    <form action="invoices.php" method="GET" style="margin-top: 15px; display: flex; gap: 10px;">
                        <input type="date" name="start_date" style="flex: 1; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: white; color-scheme: dark; font-family: monospace; font-size: 14px;" required onchange="this.form.end_date.value = this.value;">
                        <input type="hidden" name="end_date" value="">
                        <button type="submit" class="btn-primary" style="margin-top: 0; padding: 10px 20px; background: linear-gradient(135deg, #ec4899, #be185d); box-shadow: 0 4px 15px rgba(236, 72, 153, 0.3);">View</button>
                    </form>
                    <p style="font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 15px;">Select a date to see all exact collections, payer details, and PT payments for that day.</p>
                </div>
            </div>

            <!-- Today's Category Revenue Breakdown -->
            <div class="nav-card" style="margin-top: 25px;">
                <h3 style="margin-top: 0; font-weight: 800; font-size: 18px; color: #ff6b00; display: flex; align-items: center; gap: 8px;">
                    <i class="entypo-chart-pie"></i> Today's Financial Summary Breakdown
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 15px;">
                        <span style="font-size: 12px; color: #9ca3af; text-transform: uppercase; font-weight: bold;">New Memberships</span>
                        <h3 style="margin: 8px 0 0 0; color: #3b82f6; font-size: 24px; font-weight: 800;">₹<?php echo number_format($today_data['membership']); ?></h3>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 15px;">
                        <span style="font-size: 12px; color: #9ca3af; text-transform: uppercase; font-weight: bold;">Pending Dues Collected</span>
                        <h3 style="margin: 8px 0 0 0; color: #ef4444; font-size: 24px; font-weight: 800;">₹<?php echo number_format($today_data['balance']); ?></h3>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 15px;">
                        <span style="font-size: 12px; color: #9ca3af; text-transform: uppercase; font-weight: bold;">Personal Training (PT)</span>
                        <h3 style="margin: 8px 0 0 0; color: #a855f7; font-size: 24px; font-weight: 800;">₹<?php echo number_format($today_data['pt']); ?></h3>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 15px; background: rgba(16,185,129,0.05);">
                        <span style="font-size: 12px; color: #10b981; text-transform: uppercase; font-weight: bold;">🛒 Inventory Store Sales</span>
                        <h3 style="margin: 8px 0 0 0; color: #10b981; font-size: 24px; font-weight: 800;">₹<?php echo number_format($today_data['inventory']); ?></h3>
                    </div>
                    <div style="background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 15px;">
                        <span style="font-size: 12px; color: #ef4444; text-transform: uppercase; font-weight: bold;">💸 Today's Outgoings &amp; Expenses</span>
                        <h3 style="margin: 8px 0 0 0; color: #ef4444; font-size: 24px; font-weight: 800;">₹<?php echo number_format($today_data['expenses']); ?></h3>
                    </div>
                    <div style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.4); border-radius: 12px; padding: 15px;">
                        <span style="font-size: 12px; color: #f59e0b; text-transform: uppercase; font-weight: bold;">💰 Net Collection (Profit)</span>
                        <h3 style="margin: 8px 0 0 0; color: #f59e0b; font-size: 24px; font-weight: 800;">₹<?php echo number_format($today_data['net_total']); ?></h3>
                    </div>
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
