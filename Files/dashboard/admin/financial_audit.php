<?php
require '../../include/db_conn.php';
page_protect();

// Require Owner/Developer Auth
if (!isset($_SESSION['dev_owner_auth']) || $_SESSION['dev_owner_auth'] !== true) {
    $lock_title = "AUDIT HUB LOCKED";
    $lock_message = "The Financial Auditing Hub is restricted to Owners and Authorized Auditors only. Please enter your Master Authority PIN to access P&L statements, cycle calculations, and revenue leakage reports.";
    $updated_at = date('Y-m-d H:i:s');
    require '../../include/owner_lock_screen.php';
    exit;
}

$gym = get_gym_details($con);

// Financial Cycle: 7th to 6th
$today_d = (int)date('d');
$today_m = (int)date('m');
$today_y = (int)date('Y');

if (isset($_GET['m']) && isset($_GET['y'])) {
    $cycle_m_start = (int)$_GET['m'];
    $cycle_y_start = (int)$_GET['y'];
} else {
    if ($today_d >= 7) {
        $cycle_m_start = $today_m;
        $cycle_y_start = $today_y;
    } else {
        $cycle_m_start = $today_m - 1;
        $cycle_y_start = $today_y;
        if ($cycle_m_start == 0) {
            $cycle_m_start = 12;
            $cycle_y_start--;
        }
    }
}

$cycle_m_end = $cycle_m_start + 1;
$cycle_y_end = $cycle_y_start;
if ($cycle_m_end == 13) {
    $cycle_m_end = 1;
    $cycle_y_end++;
}

$cycle_start = sprintf("%04d-%02d-07 00:00:00", $cycle_y_start, $cycle_m_start);
$cycle_end   = sprintf("%04d-%02d-06 23:59:59", $cycle_y_end, $cycle_m_end);

$cycle_name = date('M j, Y', strtotime($cycle_start)) . " - " . date('M j, Y', strtotime($cycle_end));

// Fetch Data for Cycle
$q_inc = mysqli_query($con, "
    SELECT 
        SUM(IF(payment_mode LIKE '%upi%' OR payment_mode LIKE '%online%' OR payment_mode LIKE '%bank%', COALESCE(IF(paid_amount > 0 AND (discount_amount = 0 OR paid_amount != p.amount), paid_amount, GREATEST(0, p.amount - COALESCE(discount_amount, 0))), paid_amount, 0), 0)) as upi_inc,
        SUM(IF(payment_mode LIKE '%cash%' OR payment_mode IS NULL OR payment_mode = '', COALESCE(IF(paid_amount > 0 AND (discount_amount = 0 OR paid_amount != p.amount), paid_amount, GREATEST(0, p.amount - COALESCE(discount_amount, 0))), paid_amount, 0), 0)) as cash_inc
    FROM enrolls_to e LEFT JOIN plan p ON e.pid = p.pid 
    WHERE e.paid_date BETWEEN '$cycle_start' AND '$cycle_end'
");
$r_inc = mysqli_fetch_assoc($q_inc);
$upi_inc = intval($r_inc['upi_inc'] ?? 0);
$cash_inc = intval($r_inc['cash_inc'] ?? 0);

$q_pt = mysqli_query($con, "
    SELECT 
        SUM(IF(payment_mode LIKE '%upi%' OR payment_mode LIKE '%online%' OR payment_mode LIKE '%bank%', amount, 0)) as upi_pt,
        SUM(IF(payment_mode LIKE '%cash%' OR payment_mode IS NULL OR payment_mode = '', amount, 0)) as cash_pt
    FROM pt_enrollments 
    WHERE enroll_date BETWEEN '$cycle_start' AND '$cycle_end'
");
$r_pt = mysqli_fetch_assoc($q_pt);
$upi_inc += intval($r_pt['upi_pt'] ?? 0);
$cash_inc += intval($r_pt['cash_pt'] ?? 0);

$q_bal = mysqli_query($con, "
    SELECT 
        SUM(IF(payment_mode LIKE '%upi%' OR payment_mode LIKE '%online%' OR payment_mode LIKE '%bank%', amount, 0)) as upi_bal,
        SUM(IF(payment_mode LIKE '%cash%' OR payment_mode IS NULL OR payment_mode = '', amount, 0)) as cash_bal
    FROM balance_collections 
    WHERE collection_date BETWEEN '$cycle_start' AND '$cycle_end'
");
$r_bal = mysqli_fetch_assoc($q_bal);
$upi_inc += intval($r_bal['upi_bal'] ?? 0);
$cash_inc += intval($r_bal['cash_bal'] ?? 0);

$total_inc = $upi_inc + $cash_inc;

$q_exp = mysqli_query($con, "
    SELECT 
        SUM(IF(payment_mode LIKE '%upi%' OR payment_mode LIKE '%online%' OR payment_mode LIKE '%bank%', amount, 0)) as upi_exp,
        SUM(IF(payment_mode LIKE '%cash%' OR payment_mode IS NULL OR payment_mode = '', amount, 0)) as cash_exp
    FROM expenses 
    WHERE expense_date BETWEEN '$cycle_start' AND '$cycle_end'
");
$r_exp = mysqli_fetch_assoc($q_exp);
$upi_exp = intval($r_exp['upi_exp'] ?? 0);
$cash_exp = intval($r_exp['cash_exp'] ?? 0);
$total_exp = $upi_exp + $cash_exp;

$net_profit = $total_inc - $total_exp;

// Leakage Data (People who joined in this cycle but have pending balance)
$q_leakage = mysqli_query($con, "
    SELECT u.userid, u.username, u.mobile, b.balance 
    FROM users u 
    INNER JOIN balance b ON u.userid = b.uid 
    WHERE u.joining_date BETWEEN '$cycle_start' AND '$cycle_end' AND b.balance > 0
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Financial Audit Hub | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <script src="https://cdn.plot.ly/plotly-2.27.0.min.js" charset="utf-8"></script>
    <style>
        .audit-card {
            background: var(--glass-bg);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .val-inc { color: #10b981; }
        .val-exp { color: #ef4444; }
        .val-net { color: #3b82f6; }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-box {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            text-align: center;
        }
        .stat-box h4 {
            color: var(--text-muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .stat-box .val {
            font-size: 28px;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
        }
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }
        .nav-tabs a {
            color: var(--text-muted);
            padding: 10px 20px;
            text-decoration: none;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-tabs a.active {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        .leakage-table { width: 100%; border-collapse: collapse; }
        .leakage-table th, .leakage-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; }
    </style>
</head>
<body class="page-body" style="background: var(--bg-dark);">

<div class="page-container">
    <div style="padding: 30px; max-width: 1200px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-weight: 600; display: inline-block; margin-bottom: 10px;">← Back to Dashboard</a>
                <h2 style="color: var(--text-main); font-weight: 800; font-size: 28px; margin: 0;">Financial Audit Hub</h2>
                <div style="color: var(--text-muted); font-size: 14px; margin-top: 5px;">Locked strictly to the <strong>7th to 6th</strong> billing cycle.</div>
            </div>
            
            <form style="display: flex; gap: 10px;">
                <select name="m" style="background: var(--glass-bg); color: var(--text-main); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; padding: 10px;">
                    <?php
                    $months = ["01"=>"Jan", "02"=>"Feb", "03"=>"Mar", "04"=>"Apr", "05"=>"May", "06"=>"Jun", "07"=>"Jul", "08"=>"Aug", "09"=>"Sep", "10"=>"Oct", "11"=>"Nov", "12"=>"Dec"];
                    foreach($months as $k => $v) {
                        $sel = ($k == sprintf("%02d", $cycle_m_start)) ? 'selected' : '';
                        echo "<option value='$k' $sel>$v</option>";
                    }
                    ?>
                </select>
                <select name="y" style="background: var(--glass-bg); color: var(--text-main); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; padding: 10px;">
                    <?php
                    $start_year = 2026;
                    $end_year = (int)date('Y') + 1;
                    for ($y = $start_year; $y <= $end_year; $y++) {
                        $sel = ($y == $cycle_y_start) ? 'selected' : '';
                        echo "<option value='$y' $sel>$y</option>";
                    }
                    ?>
                </select>
                <button type="submit" style="background: #3b82f6; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Load Cycle</button>
            </form>
        </div>

        <div class="audit-card" style="border-left: 4px solid #3b82f6;">
            <h3 style="color: var(--text-main); margin: 0 0 20px 0; font-size: 18px;">
                <i class="entypo-calendar"></i> Current Audit Cycle: <span style="color: #3b82f6;"><?php echo $cycle_name; ?></span>
            </h3>
            
            <div class="stat-grid">
                <div class="stat-box" style="border-bottom: 2px solid #10b981;">
                    <h4>Total Cash & UPI In</h4>
                    <div class="val val-inc">₹<?php echo number_format($total_inc); ?></div>
                </div>
                <div class="stat-box" style="border-bottom: 2px solid #ef4444;">
                    <h4>Total Expenses Out</h4>
                    <div class="val val-exp">₹<?php echo number_format($total_exp); ?></div>
                </div>
                <div class="stat-box" style="border-bottom: 2px solid <?php echo $net_profit >= 0 ? '#3b82f6' : '#ef4444'; ?>;">
                    <h4>Net Cycle Profit</h4>
                    <div class="val val-net" style="color: <?php echo $net_profit >= 0 ? '#3b82f6' : '#ef4444'; ?>;">₹<?php echo number_format($net_profit); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-grid">
            <div class="audit-card">
                <h3 style="color: var(--text-main); margin: 0 0 20px 0; font-size: 16px;">💳 Cycle Cash Register (Reconciliation)</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">
                    <span style="color: var(--text-muted);">UPI / Online Collected</span>
                    <span style="color: var(--text-main); font-weight: 600;">+ ₹<?php echo number_format($upi_inc); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">
                    <span style="color: var(--text-muted);">Physical Cash Collected</span>
                    <span style="color: var(--text-main); font-weight: 600;">+ ₹<?php echo number_format($cash_inc); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">
                    <span style="color: var(--text-muted);">Cash Expenses Paid</span>
                    <span style="color: #ef4444; font-weight: 600;">- ₹<?php echo number_format($cash_exp); ?></span>
                </div>
                
                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 8px; padding: 15px; margin-top: 20px;">
                    <div style="font-size: 12px; color: #f59e0b; text-transform: uppercase; font-weight: 700; margin-bottom: 5px;">Expected Physical Cash in Drawer</div>
                    <div style="font-size: 24px; color: #f59e0b; font-weight: 800; font-family: 'Inter';">₹<?php echo number_format($cash_inc - $cash_exp); ?></div>
                </div>
            </div>

            <div class="audit-card">
                <h3 style="color: var(--text-main); margin: 0 0 20px 0; font-size: 16px;">⚠️ Cycle Revenue Leakage (Pending Dues)</h3>
                <?php if (mysqli_num_rows($q_leakage) > 0): ?>
                    <table class="leakage-table">
                        <tr style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">
                            <th>Member</th>
                            <th>Mobile</th>
                            <th style="text-align: right;">Pending</th>
                        </tr>
                        <?php 
                        $total_leakage = 0;
                        while($row = mysqli_fetch_assoc($q_leakage)): 
                            $total_leakage += intval($row['balance']);
                        ?>
                        <tr>
                            <td style="color: var(--text-main); font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($row['mobile']); ?></td>
                            <td style="color: #ef4444; font-weight: 700; text-align: right;">₹<?php echo number_format($row['balance']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                    <div style="text-align: right; margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05);">
                        <span style="color: var(--text-muted); font-size: 12px;">Total Cycle Deficit:</span>
                        <span style="color: #ef4444; font-size: 18px; font-weight: 800; margin-left: 10px;">₹<?php echo number_format($total_leakage); ?></span>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: #10b981; padding: 30px;">
                        <i class="entypo-check" style="font-size: 32px; margin-bottom: 10px;"></i><br>
                        Perfect! No missing payments for members who joined this cycle.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>
