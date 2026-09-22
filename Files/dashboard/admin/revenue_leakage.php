<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'auditor') {
    header("Location: index.php");
    exit();
}

$today = date('Y-m-d');

// SQL Query to find "Ghost Members"
// Logic: An active plan (expire >= today) but paid_amount is 0 or extremely low, indicating they got a 100% free override.
$query = "SELECT u.userid, u.username, u.mobile, e.paid_amount, e.balance, e.paid_date, e.expire, p.planName, p.amount as plan_price 
          FROM users u 
          INNER JOIN enrolls_to e ON u.userid = e.uid 
          INNER JOIN plan p ON e.pid = p.pid 
          WHERE e.expire >= '$today' 
          AND e.paid_amount < 100 
          AND p.amount > 500
          ORDER BY e.paid_date DESC";

$result = mysqli_query($con, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Revenue Leakage Scanner</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">    
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()">
                    <a href="#" class="sidebar-collapse-icon with-animation">
                        <i class="entypo-menu"></i>
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-12">
                    <h2><i class="entypo-search"></i> Ghost Member & Revenue Leakage Scanner</h2>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">This automated AI scanner flags suspicious accounts that have an active, ongoing gym membership but zero or almost-zero payment history. This indicates staff may have manually overridden the expiry date to give free access.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-danger" style="border-color: var(--danger);">
                        <div class="panel-heading" style="background: rgba(239, 68, 68, 0.1); border-bottom: 1px solid rgba(239, 68, 68, 0.2);">
                            <div class="panel-title" style="color: var(--danger); font-weight: bold;"><i class="entypo-alert"></i> Suspicious Active Memberships</div>
                        </div>
                        <div class="panel-body">
                            <table class="table table-bordered datatable">
                                <thead>
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Mobile</th>
                                        <th>Plan Name</th>
                                        <th>Plan Actual Price</th>
                                        <th>Amount Paid By Member</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($row['planName']); ?></td>
                                        <td>₹<?php echo number_format($row['plan_price']); ?></td>
                                        <td style="color: var(--danger); font-weight: 800; font-size: 16px;">₹<?php echo number_format($row['paid_amount']); ?></td>
                                        <td style="color: var(--success); font-weight: bold;"><?php echo htmlspecialchars($row['expire']); ?></td>
                                        <td>
                                            <span style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">HIGH RISK (FREE ACCESS)</span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if(mysqli_num_rows($result) === 0): ?>
                                    <tr><td colspan="7" style="text-align: center; color: var(--success); font-weight: bold; padding: 30px;">Great news! No ghost members or revenue leakage detected.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
