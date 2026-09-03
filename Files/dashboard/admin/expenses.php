<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Ensure expenses table exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_name VARCHAR(255) NOT NULL,
    amount INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    expense_date DATE NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// Handle delete request
if (isset($_POST['delete_expense']) && isset($_POST['expense_id'])) {
    $expense_id = intval($_POST['expense_id']);
    $del_query = "DELETE FROM expenses WHERE id = $expense_id";
    if (mysqli_query($con, $del_query)) {
        echo "<script>alert('Expense deleted successfully!');</script>";
        echo "<meta http-equiv='refresh' content='0; url=expenses.php'>";
        exit();
    } else {
        echo "<script>alert('Failed to delete expense.');</script>";
    }
}

// Handle add request
if (isset($_POST['add_expense'])) {
    $expense_name = mysqli_real_escape_string($con, trim($_POST['expense_name']));
    $amount       = intval($_POST['amount']);
    $category     = mysqli_real_escape_string($con, $_POST['category']);
    $payment_mode = mysqli_real_escape_string($con, $_POST['payment_mode'] ?? 'Cash');
    $voucher_no   = mysqli_real_escape_string($con, trim($_POST['voucher_no'] ?? ''));
    $expense_date = mysqli_real_escape_string($con, $_POST['expense_date']);
    $remarks      = mysqli_real_escape_string($con, trim($_POST['remarks'] ?? ''));

    if (empty($expense_name) || $amount <= 0 || empty($expense_date) || empty($category)) {
        echo "<script>alert('Please fill in all required fields and provide a valid amount.');</script>";
    } else {
        $insert_query = "INSERT INTO expenses (expense_name, amount, category, payment_mode, voucher_no, expense_date, remarks) 
                         VALUES ('$expense_name', $amount, '$category', '$payment_mode', '$voucher_no', '$expense_date', '$remarks')";
        if (mysqli_query($con, $insert_query)) {
            echo "<script>alert('Expense logged successfully!');</script>";
            echo "<meta http-equiv='refresh' content='0; url=expenses.php'>";
            exit();
        } else {
            echo "<script>alert('Failed to log expense: " . mysqli_error($con) . "');</script>";
        }
    }
}

// ── Filter Parameters ────────────────────────────────────────────────────────
$view_mode    = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'month'; // 'month', 'year', 'all_time', 'custom'
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : date('Y-m');
$filter_year  = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : intval(date('Y'));
$start_date   = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date     = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$filter_mode  = isset($_GET['mode']) ? strtolower(trim($_GET['mode'])) : 'all'; // 'all', 'cash', 'upi'

// Handle Export CSV
if (isset($_GET['export_csv'])) {
    $csv_where = "1=1";
    if ($view_mode === 'month') {
        $sm = $filter_month . "-01"; $em = date("Y-m-t", strtotime($sm));
        $csv_where = "expense_date BETWEEN '$sm' AND '$em'";
    } elseif ($view_mode === 'year') {
        $csv_where = "YEAR(expense_date) = $filter_year";
    } elseif ($view_mode === 'custom') {
        $csv_where = "expense_date BETWEEN '$start_date' AND '$end_date'";
    }
    
    if ($filter_mode === 'cash') {
        $csv_where .= " AND (payment_mode LIKE '%cash%' OR payment_mode IS NULL OR payment_mode = '')";
    } elseif ($filter_mode === 'upi') {
        $csv_where .= " AND (payment_mode LIKE '%upi%' OR payment_mode LIKE '%online%' OR payment_mode LIKE '%bank%')";
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=expenses_report_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Expense Name', 'Category', 'Payment Mode', 'Voucher/Bill No', 'Amount (INR)', 'Expense Date', 'Remarks']);
    $res = mysqli_query($con, "SELECT * FROM expenses WHERE $csv_where ORDER BY expense_date DESC");
    while ($r = mysqli_fetch_assoc($res)) {
        fputcsv($out, [$r['id'], $r['expense_name'], $r['category'], $r['payment_mode'] ?? 'Cash', $r['voucher_no'] ?? '—', $r['amount'], $r['expense_date'], $r['remarks']]);
    }
    fclose($out);
    exit();
}

// ── Calculate Totals ──────────────────────────────────────────────────────────
// 1. Overall Lifetime Total
$q_overall = mysqli_query($con, "SELECT SUM(amount) AS total FROM expenses");
$r_overall = mysqli_fetch_assoc($q_overall);
$overall_total = isset($r_overall['total']) ? intval($r_overall['total']) : 0;

// 2. Current Month Breakdown (Cash vs UPI)
$q_cm_all = mysqli_query($con, "SELECT 
    SUM(amount) as total,
    SUM(IF(payment_mode LIKE '%cash%' OR payment_mode IS NULL OR payment_mode = '', amount, 0)) as cash_total,
    SUM(IF(payment_mode LIKE '%upi%' OR payment_mode LIKE '%online%' OR payment_mode LIKE '%bank%', amount, 0)) as upi_total
    FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE()) AND MONTH(expense_date) = MONTH(CURDATE())");
$r_cm_all = mysqli_fetch_assoc($q_cm_all);
$current_month_total = isset($r_cm_all['total']) ? intval($r_cm_all['total']) : 0;
$current_month_cash  = isset($r_cm_all['cash_total']) ? intval($r_cm_all['cash_total']) : 0;
$current_month_upi   = isset($r_cm_all['upi_total']) ? intval($r_cm_all['upi_total']) : 0;

// 3. Filtered Period Query & Total
$where_clause = "1=1";
$period_label = "Overall All-Time Expenses";

if ($view_mode === 'month') {
    $sm = $filter_month . "-01";
    $em = date("Y-m-t", strtotime($sm));
    $where_clause = "expense_date BETWEEN '$sm' AND '$em'";
    $period_label = "Expenses for " . date('F Y', strtotime($sm));
} elseif ($view_mode === 'year') {
    $where_clause = "YEAR(expense_date) = $filter_year";
    $period_label = "Total Expenses for Year $filter_year";
} elseif ($view_mode === 'custom') {
    $where_clause = "expense_date BETWEEN '$start_date' AND '$end_date'";
    $period_label = "Expenses from " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date));
}

if ($filter_mode === 'cash') {
    $where_clause .= " AND (payment_mode LIKE '%cash%' OR payment_mode IS NULL OR payment_mode = '')";
    $period_label .= " (Cash Only)";
} elseif ($filter_mode === 'upi') {
    $where_clause .= " AND (payment_mode LIKE '%upi%' OR payment_mode LIKE '%online%' OR payment_mode LIKE '%bank%')";
    $period_label .= " (UPI/Bank Only)";
}

$q_filtered = mysqli_query($con, "SELECT 
    SUM(amount) AS total, 
    SUM(IF(payment_mode LIKE '%cash%' OR payment_mode IS NULL OR payment_mode = '', amount, 0)) as cash_total,
    SUM(IF(payment_mode LIKE '%upi%' OR payment_mode LIKE '%online%' OR payment_mode LIKE '%bank%', amount, 0)) as upi_total,
    COUNT(*) as cnt 
    FROM expenses WHERE $where_clause");
$r_filtered = mysqli_fetch_assoc($q_filtered);
$filtered_total = isset($r_filtered['total']) ? intval($r_filtered['total']) : 0;
$filtered_cash  = isset($r_filtered['cash_total']) ? intval($r_filtered['cash_total']) : 0;
$filtered_upi   = isset($r_filtered['upi_total']) ? intval($r_filtered['upi_total']) : 0;
$filtered_count = isset($r_filtered['cnt']) ? intval($r_filtered['cnt']) : 0;

// ── Year-by-Year Summary (Overall Yearly Breakdown) ──────────────────────────
$q_yearly_breakdown = mysqli_query($con, "
    SELECT YEAR(expense_date) as yr, SUM(amount) as yr_total, COUNT(*) as yr_count
    FROM expenses
    GROUP BY YEAR(expense_date)
    ORDER BY yr DESC
");

// ── Month-by-Month Breakdown for Selected Year ────────────────────────────────
$q_monthly_breakdown = mysqli_query($con, "
    SELECT MONTH(expense_date) as mth, DATE_FORMAT(expense_date, '%M') as mth_name, SUM(amount) as mth_total, COUNT(*) as mth_count
    FROM expenses
    WHERE YEAR(expense_date) = $filter_year
    GROUP BY MONTH(expense_date)
    ORDER BY mth ASC
");

// ── Category Breakdown for Filtered Period ──────────────────────────────────
$q_cat_breakdown = mysqli_query($con, "
    SELECT category, SUM(amount) as cat_total, COUNT(*) as cat_count
    FROM expenses
    WHERE $where_clause
    GROUP BY category
    ORDER BY cat_total DESC
");

// ── Available Years in Database for Filter Dropdown ───────────────────────────
$q_avail_years = mysqli_query($con, "SELECT DISTINCT YEAR(expense_date) as yr FROM expenses ORDER BY yr DESC");
$avail_years = [];
while ($yr_row = mysqli_fetch_assoc($q_avail_years)) {
    if (!empty($yr_row['yr'])) $avail_years[] = intval($yr_row['yr']);
}
if (!in_array(intval(date('Y')), $avail_years)) {
    array_unshift($avail_years, intval(date('Y')));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Overall Expenses & Outgoings Ledger</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#expenses_ledger > a {
            background-color: rgba(239, 68, 68, 0.15) !important;
            color: #ef4444 !important;
            font-weight: 700 !important;
            box-shadow: inset 3px 0 0 #ef4444;
        }
        .stat-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card .val { font-size: 28px; font-weight: 900; line-height: 1.2; margin-top: 6px; }
        .stat-card .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
        
        .mode-tab {
            padding: 9px 18px;
            border-radius: 10px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none !important;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .mode-tab:hover, .mode-tab.active {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
            color: #ffffff;
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.8) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            border-radius: 10px !important;
            color: #ffffff !important;
            padding: 10px 14px !important;
            width: 100%;
            margin-bottom: 14px;
            font-size: 13px;
        }
        .form-control-premium:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25) !important;
        }
        .glass-panel {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .premium-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            color: white !important;
            padding: 12px 24px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
            transition: all 0.2s ease;
            width: 100%;
        }
        .premium-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        }
        .premium-table {
            width: 100%;
            border-collapse: collapse;
        }
        .premium-table th {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
            padding: 11px 14px;
            border-bottom: 1px solid rgba(239, 68, 68, 0.3);
            text-align: left;
        }
        .premium-table td {
            padding: 11px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
            color: #e2e8f0;
        }
        .premium-table tr:hover td { background: rgba(239, 68, 68, 0.04); }
        .category-badge {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .delete-btn {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444 !important;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .delete-btn:hover { background: #ef4444; color: white !important; }
        .cat-chip {
            display: inline-flex; align-items: center; justify-content: space-between;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px; padding: 10px 14px; margin-bottom: 8px; width: 100%;
        }
        .cat-chip .name { font-size: 13px; font-weight: 600; color: #fff; }
        .cat-chip .val { font-size: 14px; font-weight: 800; color: #ef4444; }
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
                    <li><a href="logout.php">Log Out <i class="entypo-logout right"></i></a></li>
                </ul>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;">
            <div>
                <h2>🔴 Overall Expenses &amp; Outgoings Ledger</h2>
                <p style="color:#94a3b8;margin:0 0 10px 0;font-size:13px;">Track monthly, yearly, and overall all-time gym operational expenses.</p>
            </div>
            <div>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export_csv' => '1'])); ?>" style="background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.4);color:#6ee7b7;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    📥 Export CSV Report
                </a>
            </div>
        </div>
        <hr />

        <!-- ── Top 4 Stat Overview Tiles ──────────────────────────────────── -->
        <div class="row" style="margin-bottom: 24px;">
            <div class="col-md-3 col-sm-6" style="margin-bottom: 12px;">
                <div class="stat-card" style="border-color: rgba(239, 68, 68, 0.4);">
                    <div class="lbl">Filter Selection Total</div>
                    <div class="val" style="color: #ef4444;">₹<?php echo number_format($filtered_total); ?></div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;"><?php echo $filtered_count; ?> logged records</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom: 12px;">
                <div class="stat-card" style="border-color: rgba(245, 158, 11, 0.4);">
                    <div class="lbl">💵 Physical Cash Expenses</div>
                    <div class="val" style="color: #f59e0b;">₹<?php echo number_format($filtered_cash); ?></div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Out of physical cash register</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom: 12px;">
                <div class="stat-card" style="border-color: rgba(56, 189, 248, 0.4);">
                    <div class="lbl">💳 Digital / UPI Expenses</div>
                    <div class="val" style="color: #38bdf8;">₹<?php echo number_format($filtered_upi); ?></div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Out of bank / online UPI</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom: 12px;">
                <div class="stat-card" style="border-color: rgba(168, 85, 247, 0.4);">
                    <div class="lbl">🌐 Overall All-Time Total</div>
                    <div class="val" style="color: #c084fc;">₹<?php echo number_format($overall_total); ?></div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Lifetime total expenses</div>
                </div>
            </div>
        </div>

        <!-- ── View Mode Filter Selector ─────────────────────────────────── -->
        <div class="glass-panel" style="padding:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="font-weight:700;font-size:13px;color:#fff;">View Mode:</span>
                    <a href="?view_mode=month&filter_month=<?php echo $filter_month; ?>&mode=<?php echo $filter_mode; ?>" class="mode-tab <?php echo $view_mode === 'month' ? 'active' : ''; ?>">📅 Monthly View</a>
                    <a href="?view_mode=year&filter_year=<?php echo $filter_year; ?>&mode=<?php echo $filter_mode; ?>" class="mode-tab <?php echo $view_mode === 'year' ? 'active' : ''; ?>">🗓️ Yearly View</a>
                    <a href="?view_mode=all_time&mode=<?php echo $filter_mode; ?>" class="mode-tab <?php echo $view_mode === 'all_time' ? 'active' : ''; ?>">🌐 Overall All-Time</a>
                    <a href="?view_mode=custom&mode=<?php echo $filter_mode; ?>" class="mode-tab <?php echo $view_mode === 'custom' ? 'active' : ''; ?>">🔍 Custom Dates</a>
                </div>

                <!-- Mode Specific Inputs -->
                <form method="get" action="" style="display:flex;align-items:center;gap:10px;margin:0;flex-wrap:wrap;">
                    <input type="hidden" name="view_mode" value="<?php echo htmlspecialchars($view_mode); ?>">

                    <?php if ($view_mode === 'month'): ?>
                        <label style="font-size:12px;color:#94a3b8;margin:0;">Select Month:</label>
                        <input type="month" name="filter_month" class="form-control-premium" style="margin:0;width:auto;" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="this.form.submit()">
                    <?php elseif ($view_mode === 'year'): ?>
                        <label style="font-size:12px;color:#94a3b8;margin:0;">Select Year:</label>
                        <select name="filter_year" class="form-control-premium" style="margin:0;width:auto;" onchange="this.form.submit()">
                            <?php 
                            $avail_years = range(intval(date('Y')), 2020);
                            foreach ($avail_years as $yr): ?>
                                <option value="<?php echo $yr; ?>" <?php echo $yr === $filter_year ? 'selected' : ''; ?>><?php echo $yr; ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($view_mode === 'custom'): ?>
                        <input type="date" name="start_date" class="form-control-premium" style="margin:0;width:auto;" value="<?php echo htmlspecialchars($start_date); ?>">
                        <span style="color:#94a3b8;font-size:12px;">to</span>
                        <input type="date" name="end_date" class="form-control-premium" style="margin:0;width:auto;" value="<?php echo htmlspecialchars($end_date); ?>">
                        <button type="submit" class="mode-tab active" style="padding:7px 14px;">Apply</button>
                    <?php endif; ?>

                    <select name="mode" class="form-control-premium" style="margin:0;width:auto;" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter_mode === 'all' ? 'selected' : ''; ?>>All Modes (₹<?php echo number_format($filtered_total); ?>)</option>
                        <option value="cash" <?php echo $filter_mode === 'cash' ? 'selected' : ''; ?>>💵 Cash Only (₹<?php echo number_format($filtered_cash); ?>)</option>
                        <option value="upi" <?php echo $filter_mode === 'upi' ? 'selected' : ''; ?>>💳 UPI Only (₹<?php echo number_format($filtered_upi); ?>)</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- ── Main Grid Layout: Form + Logs ─────────────────────────────── -->
        <div class="row">
            <!-- Left Column: Log New Expense & Category Breakdown -->
            <div class="col-md-4">
                <!-- Log Expense Form -->
                <div class="glass-panel">
                    <h3 style="margin-top:0;margin-bottom:18px;font-weight:800;color:#ef4444;font-size:15px;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:10px;">➕ Log New Expense</h3>
                    <form method="post" action="">
                        <label style="font-weight:600;font-size:12px;color:#e2e8f0;display:block;margin-bottom:4px;">Expense Title / Name *</label>
                        <input class="form-control-premium" type="text" name="expense_name" placeholder="e.g. Electricity Bill, Staff Salary" required>

                        <label style="font-weight:600;font-size:12px;color:#e2e8f0;display:block;margin-bottom:4px;">Amount (INR ₹) *</label>
                        <input class="form-control-premium" type="number" min="1" name="amount" placeholder="e.g. 5000" required>

                        <label style="font-weight:600;font-size:12px;color:#e2e8f0;display:block;margin-bottom:4px;">Payment Mode / Account *</label>
                        <select class="form-control-premium" name="payment_mode" required>
                            <option value="Cash">💵 Physical Cash (Cash Drawer)</option>
                            <option value="UPI">💳 UPI / QR Code (Digital Bank)</option>
                            <option value="Bank Transfer">🏦 Bank Transfer (NEFT/IMPS)</option>
                            <option value="Cheque">📑 Cheque / DD</option>
                        </select>

                        <label style="font-weight:600;font-size:12px;color:#e2e8f0;display:block;margin-bottom:4px;">Voucher / Receipt / Bill No</label>
                        <input class="form-control-premium" type="text" name="voucher_no" placeholder="e.g. Bill #482, Voucher #12">

                        <label style="font-weight:600;font-size:12px;color:#e2e8f0;display:block;margin-bottom:4px;">Category *</label>
                        <select class="form-control-premium" name="category" required>
                            <option value="Maintenance">Maintenance &amp; Repairs</option>
                            <option value="Rent">Rent &amp; Lease</option>
                            <option value="Salaries">Staff Salaries</option>
                            <option value="Utilities">Utilities (Electricity, Water, Internet)</option>
                            <option value="Equipment">New Gym Equipment</option>
                            <option value="Marketing">Marketing &amp; Promotions</option>
                            <option value="Supplements">Supplements &amp; Store Inventory</option>
                            <option value="Other">Other Miscellaneous</option>
                        </select>

                        <label style="font-weight:600;font-size:12px;color:#e2e8f0;display:block;margin-bottom:4px;">Expense Date *</label>
                        <input class="form-control-premium" type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>

                        <label style="font-weight:600;font-size:12px;color:#e2e8f0;display:block;margin-bottom:4px;">Remarks / Invoice Notes</label>
                        <textarea class="form-control-premium" name="remarks" rows="2" placeholder="Optional remarks..."></textarea>

                        <button type="submit" name="add_expense" class="premium-btn">Log Expense</button>
                    </form>
                </div>

                <!-- Category Breakdown for Selected Period -->
                <div class="glass-panel">
                    <h3 style="margin-top:0;margin-bottom:16px;font-weight:800;color:#fff;font-size:14px;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:10px;">🏷️ Category Breakdown</h3>
                    <?php
                    $q_cat_breakdown = mysqli_query($con, "
                        SELECT category, SUM(amount) as cat_total, COUNT(*) as cat_count
                        FROM expenses
                        WHERE $where_clause
                        GROUP BY category
                        ORDER BY cat_total DESC
                    ");
                    $has_cat = false;
                    if ($q_cat_breakdown && mysqli_num_rows($q_cat_breakdown) > 0) {
                        while ($cat = mysqli_fetch_assoc($q_cat_breakdown)) {
                            $has_cat = true;
                            $cat_pct = $filtered_total > 0 ? round(($cat['cat_total'] / $filtered_total) * 100, 1) : 0;
                            echo "<div class='cat-chip'>
                                <div>
                                    <div class='name'>" . htmlspecialchars($cat['category']) . "</div>
                                    <div style='font-size:11px;color:#94a3b8;'>" . $cat['cat_count'] . " log" . ($cat['cat_count'] > 1 ? 's' : '') . " ({$cat_pct}%)</div>
                                </div>
                                <div class='val'>₹" . number_format($cat['cat_total']) . "</div>
                            </div>";
                        }
                    }
                    if (!$has_cat) {
                        echo "<p style='color:#64748b;font-size:13px;text-align:center;margin:10px 0;'>No expenses recorded for this period.</p>";
                    }
                    ?>
                </div>
            </div>

            <!-- Right Column: Outgoings Logs + Yearly Summaries -->
            <div class="col-md-8">
                <!-- Filtered Expenses List Table -->
                <div class="glass-panel">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:16px;gap:10px;">
                        <h3 style="margin:0;font-weight:800;color:#fff;font-size:15px;text-transform:uppercase;letter-spacing:1px;"><?php echo $period_label; ?></h3>
                        <div style="font-size:13px;font-weight:800;">
                            <span style="color:#f59e0b;margin-right:12px;">Cash: ₹<?php echo number_format($filtered_cash); ?></span>
                            <span style="color:#38bdf8;margin-right:12px;">UPI: ₹<?php echo number_format($filtered_upi); ?></span>
                            <span style="color:#ef4444;">Total: ₹<?php echo number_format($filtered_total); ?></span>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Expense Title</th>
                                    <th>Category</th>
                                    <th>Mode</th>
                                    <th>Amount</th>
                                    <th>Voucher / Ref</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $list_query = "SELECT * FROM expenses WHERE $where_clause ORDER BY expense_date DESC, id DESC LIMIT 200";
                                $list_res = mysqli_query($con, $list_query);

                                if ($list_res && mysqli_num_rows($list_res) > 0) {
                                    while ($row = mysqli_fetch_assoc($list_res)) {
                                        $exp_mode = $row['payment_mode'] ?? 'Cash';
                                        $is_exp_upi = (stripos($exp_mode, 'upi') !== false || stripos($exp_mode, 'online') !== false || stripos($exp_mode, 'bank') !== false);
                                        $mode_badge = $is_exp_upi 
                                            ? "<span style='background:rgba(56,189,248,0.15);color:#38bdf8;border:1px solid rgba(56,189,248,0.3);padding:2px 8px;border-radius:6px;font-size:11px;font-weight:bold;'>💳 " . htmlspecialchars($exp_mode) . "</span>"
                                            : "<span style='background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);padding:2px 8px;border-radius:6px;font-size:11px;font-weight:bold;'>💵 " . htmlspecialchars($exp_mode) . "</span>";

                                        echo "<tr>";
                                        echo "<td style='white-space:nowrap;'>" . date('d M Y', strtotime($row['expense_date'])) . "</td>";
                                        echo "<td><strong style='color:#fff;'>" . htmlspecialchars($row['expense_name']) . "</strong></td>";
                                        echo "<td><span class='category-badge'>" . htmlspecialchars($row['category']) . "</span></td>";
                                        echo "<td>" . $mode_badge . "</td>";
                                        echo "<td style='color: #ef4444; font-weight: 800; white-space:nowrap;'>₹" . number_format($row['amount']) . "</td>";
                                        echo "<td style='color:#94a3b8;font-size:12px;'>" . htmlspecialchars($row['voucher_no'] ?: ($row['remarks'] ?: '—')) . "</td>";
                                        echo "<td>
                                                <form method='post' action='' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this expense log?\");'>
                                                    <input type='hidden' name='expense_id' value='" . $row['id'] . "'>
                                                    <button type='submit' name='delete_expense' class='delete-btn'>Delete</button>
                                                </form>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' style='text-align: center; padding: 40px; color:#64748b;'>No expenses found for the selected period.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php include('footer.php'); ?>
    </div>
</div>
<?php include '../../include/dev_credit.php'; ?>
</body>
</html>
