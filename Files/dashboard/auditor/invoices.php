<?php
require '../../include/db_conn.php';
page_protect();

$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($current_role !== 'super_admin' && $current_role !== 'owner' && $current_role !== 'reception' && $current_role !== 'auditor') {
    echo "<head><script>alert('Access Denied: You do not have permissions to view invoices.');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);
$working_year = isset($_SESSION['working_year']) ? intval($_SESSION['working_year']) : intval(date('Y'));

// Search & Date Filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, trim($_GET['search'])) : '';
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($con, trim($_GET['start_date'])) : '';
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($con, trim($_GET['end_date'])) : '';

$conditions = ["YEAR(e.paid_date) = $working_year"];

if (!empty($search)) {
    $conditions[] = "(u.username LIKE '%$search%' OR e.uid LIKE '%$search%' OR p.planName LIKE '%$search%')";
}
if (!empty($start_date)) {
    $conditions[] = "e.paid_date >= '$start_date'";
}
if (!empty($end_date)) {
    $conditions[] = "e.paid_date <= '$end_date'";
}

$where_clause = "WHERE " . implode(" AND ", $conditions);

// Fetch Standard Invoices
$sql_logs = "SELECT e.et_id, e.uid, e.pid, e.paid_date, e.expire, e.payment_mode, e.received_by, 
                    e.discount_amount, e.paid_amount, p.planName, p.amount AS base_amount, u.username, u.photo 
             FROM enrolls_to e 
             INNER JOIN plan p ON e.pid = p.pid 
             INNER JOIN users u ON e.uid = u.userid
             $where_clause";

// Fetch PT Invoices
$pt_where_clause = str_replace("e.paid_date", "p.enroll_date", $where_clause);
$pt_where_clause = str_replace("e.uid", "p.uid", $pt_where_clause);
$pt_where_clause = str_replace("p.planName LIKE", "'Personal Training' LIKE", $pt_where_clause);

$sql_pt = "SELECT p.pt_id AS et_id, p.uid, 'PTPLAN' AS pid, p.enroll_date AS paid_date, p.expire_date AS expire, 
                  p.payment_mode, p.received_by, 0 AS discount_amount, p.amount AS paid_amount, 
                  CONCAT('Personal Training (', t.Full_name, ')') AS planName, p.amount AS base_amount, 
                  u.username, u.photo 
           FROM pt_enrollments p
           INNER JOIN users u ON p.uid = u.userid
           INNER JOIN admin t ON p.trainer_id = t.username
           $pt_where_clause";

$res_logs = mysqli_query($con, $sql_logs);
$res_pt = mysqli_query($con, $sql_pt);
$invoices = [];
$total_revenue = 0;

if ($res_logs) {
    while ($row = mysqli_fetch_assoc($res_logs)) {
        if ($row['paid_amount'] === null) {
            $row['paid_amount'] = intval($row['base_amount']) - intval($row['discount_amount']);
            if ($row['paid_amount'] < 0) {
                $row['paid_amount'] = 0;
            }
        }
        $row['is_pt'] = false;
        $invoices[] = $row;
        $total_revenue += intval($row['paid_amount']);
    }
}

if ($res_pt) {
    while ($row = mysqli_fetch_assoc($res_pt)) {
        $row['is_pt'] = true;
        $invoices[] = $row;
        $total_revenue += intval($row['paid_amount']);
    }
}

// Sort the combined array by paid_date DESC, then et_id DESC
usort($invoices, function($a, $b) {
    if ($a['paid_date'] === $b['paid_date']) {
        return $b['et_id'] <=> $a['et_id'];
    }
    return $b['paid_date'] <=> $a['paid_date'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Invoices Ledger</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#invoices_link > a {
            background-color: rgba(255, 107, 0, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        
        .invoices-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }

        .summary-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 107, 0, 0.08);
            border: 1px dashed rgba(255, 107, 0, 0.3);
            border-radius: 12px;
            padding: 15px 25px;
            margin-bottom: 25px;
        }

        .summary-title {
            color: var(--text-muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .summary-val {
            color: #ff6b00;
            font-size: 26px;
            font-weight: 800;
            font-family: monospace;
            margin: 0;
            text-shadow: 0 0 10px rgba(255,107,0,0.3);
        }

        .table-premium {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        .table-premium th, .table-premium td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }

        .table-premium th {
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .table-premium tr:hover td {
            background: rgba(255,255,255,0.01);
        }

        .member-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .action-btn {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none !important;
        }

        .btn-print {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .btn-print:hover {
            background: var(--success);
            color: #ffffff;
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            background: #ef4444;
            color: #ffffff;
        }

        .badge-premium {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            align-items: flex-end;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-item label {
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
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
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Invoices Ledger</h2>
            <hr />

            <div class="invoices-card">
                <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="entypo-doc-text" style="color: var(--accent-primary);"></i> Captured Payments &amp; Receipts
                </h3>
                
                <div class="summary-banner">
                    <div>
                        <h5 class="summary-title">Total Revenue in View</h5>
                        <p class="summary-val">₹<?php echo number_format($total_revenue); ?></p>
                    </div>
                    <div>
                        <a href="export_payments.php" class="btn btn-default" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="entypo-export"></i> Export to Excel
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <form method="get" action="">
                    <div class="filter-group">
                        <div class="filter-item" style="flex-grow: 2; min-width: 200px;">
                            <label>Search Query</label>
                            <input type="text" name="search" class="form-control-premium" placeholder="Search by name, member ID, or plan..." value="<?php echo htmlspecialchars($search); ?>" />
                        </div>
                        <div class="filter-item" style="flex-grow: 1; min-width: 130px;">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control-premium" value="<?php echo htmlspecialchars($start_date); ?>" />
                        </div>
                        <div class="filter-item" style="flex-grow: 1; min-width: 130px;">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control-premium" value="<?php echo htmlspecialchars($end_date); ?>" />
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="margin: 0; padding: 10px 20px;">Apply Filters</button>
                            <?php if (!empty($search) || !empty($start_date) || !empty($end_date)): ?>
                                <a href="invoices.php" class="btn btn-default" style="margin: 0; padding: 10px 15px; display: inline-flex; align-items: center;">Clear</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="table-responsive" style="border: 1px solid var(--glass-border); border-radius: 12px; background: rgba(0,0,0,0.15); overflow: hidden;">
                    <table class="table-premium">
                        <thead>
                           <tr style="background: rgba(0,0,0,0.25);">
                                <th style="width: 5%;">ID</th>
                                <th style="width: 25%;">Member</th>
                                <th style="width: 15%;">Plan Subscribed</th>
                                <th style="width: 10%;">Payment Date</th>
                                <th style="width: 10%;">Expiry Date</th>
                                <th style="width: 8%; text-align: right;">Base</th>
                                <th style="width: 8%; text-align: right;">Discount</th>
                                <th style="width: 8%; text-align: right;">Paid</th>
                                <th style="width: 5%;">Mode</th>
                                <th style="width: 10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($invoices) > 0): ?>
                                <?php foreach ($invoices as $inv): 
                                    $photo_path = !empty($inv['photo']) ? str_replace(' ', '%20', $inv['photo']) : '';
                                    $avatar = $photo_path ? htmlspecialchars($photo_path) : '../../images/logo.png';
                                    $base_amt = intval($inv['base_amount']);
                                    $disc_amt = intval($inv['discount_amount']);
                                    $paid_amt = intval($inv['paid_amount']);
                                ?>
                                    <tr>
                                        <td style="font-family: monospace; font-size: 11.5px;"><?php echo 100 + intval($inv['et_id']); ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <img src="<?php echo $avatar; ?>" class="member-avatar" alt="Avatar">
                                                <div>
                                                    <a href="read_member.php?name=<?php echo urlencode($inv['uid']); ?>" style="color: #ffffff; font-weight: bold; text-decoration: none;">
                                                        <?php echo htmlspecialchars($inv['username']); ?>
                                                    </a>
                                                    <div style="font-size: 10.5px; color: var(--text-muted); font-family: monospace; margin-top: 1px;">
                                                        ID: <?php echo htmlspecialchars($inv['uid']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="color: #ffffff;"><?php echo htmlspecialchars($inv['planName']); ?></strong>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px;"><?php echo date('d-M-Y', strtotime($inv['paid_date'])); ?></span>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px;"><?php echo date('d-M-Y', strtotime($inv['expire'])); ?></span>
                                        </td>
                                        <td style="text-align: right; font-family: monospace;">₹<?php echo number_format($base_amt); ?></td>
                                        <td style="text-align: right; font-family: monospace; color: #ef4444;">
                                            <?php echo $disc_amt > 0 ? '-₹' . number_format($disc_amt) : '₹0'; ?>
                                        </td>
                                        <td style="text-align: right; font-family: monospace; color: var(--success); font-weight: bold;">
                                            ₹<?php echo number_format($paid_amt); ?>
                                        </td>
                                        <td>
                                            <span class="badge-premium" style="background: rgba(255,255,255,0.06); color: #ffffff;">
                                                <?php echo htmlspecialchars($inv['payment_mode']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <?php if (isset($inv['is_pt']) && $inv['is_pt']): ?>
                                                    <a href="gen_pt_invoice.php?ptid=<?php echo $inv['et_id']; ?>" 
                                                       target="_blank" class="action-btn btn-print" title="Print PT Invoice">
                                                        <i class="entypo-print"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="gen_invoice.php?etid=<?php echo $inv['et_id']; ?>&pid=<?php echo urlencode($inv['pid']); ?>&id=<?php echo urlencode($inv['uid']); ?>" 
                                                       target="_blank" class="action-btn btn-print" title="Print Invoice">
                                                        <i class="entypo-print"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($current_role === 'super_admin'): ?>
                                                    <a href="del_payment.php?etid=<?php echo $inv['et_id']; ?>&uid=<?php echo urlencode($inv['uid']); ?>&redirect=invoices.php" 
                                                       class="action-btn btn-delete" title="Delete Payment"
                                                       onclick="return confirm('Are you sure you want to permanently delete this payment invoice record? This will revert the member\'s renewal status if it is the current plan.');">
                                                        <i class="entypo-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        No invoices found matching the selected filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
