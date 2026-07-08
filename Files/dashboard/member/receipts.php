<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'member') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
    exit();
}

$gym = get_gym_details($con);
$userid = $_SESSION['user_data'];

// Query all enrollments/payments for the current user
$sql = "SELECT e.et_id, e.paid_date, e.expire, e.payment_mode, e.paid_amount, e.discount_amount, 
               p.pid, p.planName, p.amount AS plan_amount, p.description, p.validity
        FROM enrolls_to e 
        INNER JOIN plan p ON e.pid = p.pid 
        WHERE e.uid = '$userid' 
        ORDER BY e.paid_date DESC";
$result = mysqli_query($con, $sql);

// Query all PT enrollments/payments for the current user separately
$pt_sql = "SELECT p.*, t.Full_name AS trainer_name 
           FROM pt_enrollments p 
           INNER JOIN admin t ON p.trainer_id = t.username 
           WHERE p.uid = '$userid' 
           ORDER BY p.enroll_date DESC";
$pt_result = mysqli_query($con, $pt_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | My Payment Receipts</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#receipts > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .receipts-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }
        .receipts-table-container {
            overflow-x: auto;
            border-radius: 12px;
        }
        .btn-receipt-action {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            border: none;
            color: white !important;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-receipt-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.35);
        }
        .text-mono {
            font-family: monospace;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 60px; max-width: 180px;" />
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
                <div class="col-md-6 col-sm-8 clearfix"></div>
                <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?></li>
                        <li>
                            <a href="../admin/logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>My Payment Receipts</h2>
            <hr />

            <div class="receipts-card">
                <h3 style="margin-top: 0; color: var(--text-main); font-weight: 600; margin-bottom: 20px;">Billing & Subscription Invoices</h3>
                
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="receipts-table-container">
                        <table class="table table-bordered datatable" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">S.No</th>
                                    <th>Receipt ID</th>
                                    <th>Membership Plan</th>
                                    <th>Paid Date</th>
                                    <th>Expiry Date</th>
                                    <th>Amount Paid</th>
                                    <th>Method</th>
                                    <th style="text-align: center; width: 150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sno = 1;
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $et_id = $row['et_id'];
                                    $pid = $row['pid'];
                                    $paid_date = $row['paid_date'];
                                    $expire = $row['expire'];
                                    $plan_name = $row['planName'];
                                    $validity = $row['validity'];
                                    $payment_mode = $row['payment_mode'];
                                    
                                    // Parse billing details
                                    $discount = isset($row['discount_amount']) ? intval($row['discount_amount']) : 0;
                                    $paid_amount = (isset($row['paid_amount']) && $row['paid_amount'] !== null) ? intval($row['paid_amount']) : intval($row['plan_amount']);
                                    
                                    $total_paid = $paid_amount;
                                    
                                    echo "<tr>";
                                    echo "<td style='text-align: center; color: var(--text-muted);'>" . $sno . "</td>";
                                    echo "<td class='text-mono'>#" . (100 + intval($et_id)) . "</td>";
                                    echo "<td>";
                                    echo "<strong>" . htmlspecialchars($plan_name) . "</strong>";
                                    echo "<br><span style='font-size: 12px; color: var(--text-muted);'>" . htmlspecialchars($row['description']) . " (" . $validity . " Month" . ($validity > 1 ? "s" : "") . ")</span>";
                                    echo "</td>";
                                    echo "<td>" . htmlspecialchars($paid_date) . "</td>";
                                    echo "<td style='color: " . (strtotime($expire) < time() ? "var(--danger)" : "var(--success)") . ";'>" . htmlspecialchars($expire) . "</td>";
                                    echo "<td class='text-mono' style='font-weight: 600; color: var(--accent-primary);'>₹" . number_format($total_paid) . "</td>";
                                    echo "<td><span style='font-size: 11px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; text-transform: uppercase; font-weight: 600; color: var(--text-main);'>" . htmlspecialchars($payment_mode) . "</span></td>";
                                    echo "<td style='text-align: center;'>";
                                    echo "<a href='../admin/gen_invoice.php?id=" . urlencode($userid) . "&pid=" . urlencode($pid) . "&etid=" . urlencode($et_id) . "' target='_blank' class='btn-receipt-action'>";
                                    echo "<i class='entypo-print'></i> View & Print";
                                    echo "</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                    $sno++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; border: 1px dashed var(--glass-border); border-radius: 12px; background: rgba(255,255,255,0.02);">
                        <i class="entypo-doc-text" style="font-size: 48px; color: var(--text-muted); display: block; margin-bottom: 15px;"></i>
                        <h4 style="margin: 0 0 10px 0; color: var(--text-main); font-weight: 600;">No Payment Receipts Found</h4>
                        <p style="color: var(--text-muted); margin: 0; font-size: 14px;">You have no recorded membership payments or invoice history on this account.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="receipts-card" style="margin-top: 30px;">
                <h3 style="margin-top: 0; color: var(--text-main); font-weight: 600; margin-bottom: 20px;">Personal Training Receipts</h3>
                
                <?php if ($pt_result && mysqli_num_rows($pt_result) > 0): ?>
                    <div class="receipts-table-container">
                        <table class="table table-bordered datatable" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">S.No</th>
                                    <th>Receipt ID</th>
                                    <th>Assigned Trainer</th>
                                    <th>Enroll Date</th>
                                    <th>Expiry Date</th>
                                    <th>Amount Paid</th>
                                    <th>Method</th>
                                    <th style="text-align: center; width: 150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pt_sno = 1;
                                while ($pt_row = mysqli_fetch_assoc($pt_result)) {
                                    $pt_id = $pt_row['pt_id'];
                                    $trainer_name = $pt_row['trainer_name'];
                                    $enroll_date = $pt_row['enroll_date'];
                                    $expire_date = $pt_row['expire_date'];
                                    $pt_amount = intval($pt_row['amount']);
                                    $payment_mode = $pt_row['payment_mode'];
                                    
                                    echo "<tr>";
                                    echo "<td style='text-align: center; color: var(--text-muted);'>" . $pt_sno . "</td>";
                                    echo "<td class='text-mono'>#PT-" . (100 + intval($pt_id)) . "</td>";
                                    echo "<td><strong>Trainer: " . htmlspecialchars($trainer_name) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($enroll_date) . "</td>";
                                    echo "<td style='color: " . (strtotime($expire_date) < time() ? "var(--danger)" : "var(--success)") . ";'>" . htmlspecialchars($expire_date) . "</td>";
                                    echo "<td class='text-mono' style='font-weight: 600; color: var(--accent-primary);'>₹" . number_format($pt_amount) . "</td>";
                                    echo "<td><span style='font-size: 11px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; text-transform: uppercase; font-weight: 600; color: var(--text-main);'>" . htmlspecialchars($payment_mode) . "</span></td>";
                                    echo "<td style='text-align: center;'>";
                                    echo "<a href='../admin/gen_pt_invoice.php?ptid=" . urlencode($pt_id) . "' target='_blank' class='btn-receipt-action'>";
                                    echo "<i class='entypo-print'></i> View & Print";
                                    echo "</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                    $pt_sno++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; border: 1px dashed var(--glass-border); border-radius: 12px; background: rgba(255,255,255,0.02);">
                        <i class="entypo-doc-text" style="font-size: 48px; color: var(--text-muted); display: block; margin-bottom: 15px;"></i>
                        <h4 style="margin: 0 0 10px 0; color: var(--text-main); font-weight: 600;">No Personal Training Receipts Found</h4>
                        <p style="color: var(--text-muted); margin: 0; font-size: 14px;">You have no recorded personal training payments or invoice history on this account.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>
</body>
</html>
