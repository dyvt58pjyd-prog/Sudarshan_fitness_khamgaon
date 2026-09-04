<?php
require '../../include/db_conn.php';
page_protect();

$report_type = isset($_GET['type']) ? $_GET['type'] : 'members';
$start_date  = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date    = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// CSV Export Handling
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Sudarshan_Fitness_' . $report_type . '_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');

    if ($report_type === 'members') {
        fputcsv($output, ['User ID', 'Full Name', 'Mobile', 'Email', 'Gender', 'Joining Date', 'Plan', 'Expiry']);
        $q = "SELECT u.userid, u.username, u.mobile, u.email, u.gender, u.joining_date, p.planName, e.expire 
              FROM users u 
              LEFT JOIN enrolls_to e ON u.userid = e.uid AND e.renewal = 'yes' 
              LEFT JOIN plan p ON e.pid = p.pid";
        $res = mysqli_query($con, $q);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($output, $row);
        }
    } elseif ($report_type === 'payments') {
        fputcsv($output, ['Enroll ID', 'User ID', 'Plan ID', 'Paid Amount', 'Discount', 'Balance', 'Paid Date', 'Expiry']);
        $q = "SELECT et_id, uid, pid, paid_amount, discount_amount, balance, paid_date, expire FROM enrolls_to WHERE paid_date BETWEEN '$start_date' AND '$end_date'";
        $res = mysqli_query($con, $q);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($output, $row);
        }
    } elseif ($report_type === 'attendance') {
        fputcsv($output, ['Log ID', 'User ID', 'Member Name', 'Date', 'Entry Time', 'Exit Time']);
        $q = "SELECT a.id, a.uid, u.username, a.date, a.entry_time, a.exit_time 
              FROM attendance a 
              JOIN users u ON a.uid = u.userid 
              WHERE a.date BETWEEN '$start_date' AND '$end_date' 
              ORDER BY a.date DESC";
        $res = mysqli_query($con, $q);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($output, $row);
        }
    } elseif ($report_type === 'expenses') {
        fputcsv($output, ['Expense ID', 'Expense Name', 'Category', 'Amount (INR)', 'Date', 'Remarks']);
        $q = "SELECT id, expense_name, category, amount, expense_date, remarks FROM expenses WHERE expense_date BETWEEN '$start_date' AND '$end_date'";
        $res = mysqli_query($con, $q);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports &amp; Export Center | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(9, 14, 28, 0.9); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .btn-action { background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #0f0a05; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; text-decoration: none; display: inline-block; }
        .btn-tab { padding: 10px 18px; border-radius: 10px; border: 1px solid var(--glass-border); background: rgba(0,240,255,0.05); color: var(--accent-primary); text-decoration: none; font-family: 'Orbitron'; font-size: 12px; font-weight: 800; }
        .btn-tab.active { background: var(--accent-primary); color: #0f0a05; }
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-custom th, .table-custom td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(0,240,255,0.15); font-size: 13px; }
        .table-custom th { color: var(--accent-primary); font-family: 'Orbitron'; text-transform: uppercase; }
        .form-control { background: rgba(3,7,18,0.8); border: 1px solid rgba(0,240,255,0.3); color: #fff; padding: 8px 12px; border-radius: 8px; font-family: 'Outfit'; color-scheme: dark; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1300px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">📊 REPORTS &amp; EXPORT CENTER</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • FINANCIAL &amp; ATTENDANCE REPORTING</div>
            </div>
            <div>
                <a href="?type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=csv" class="btn-action">📥 EXPORT CSV ➔</a>
                <button onclick="window.print()" class="btn-action" style="background: rgba(0,240,255,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); margin-left: 8px;">🖨️ PRINT REPORT</button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card" style="padding: 15px 25px;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="?type=members" class="btn-tab <?php echo $report_type === 'members' ? 'active' : ''; ?>">👥 Members Report</a>
                    <a href="?type=payments" class="btn-tab <?php echo $report_type === 'payments' ? 'active' : ''; ?>">💳 Payments Report</a>
                    <a href="?type=attendance" class="btn-tab <?php echo $report_type === 'attendance' ? 'active' : ''; ?>">📷 Attendance Report</a>
                    <a href="?type=expenses" class="btn-tab <?php echo $report_type === 'expenses' ? 'active' : ''; ?>">💸 Expenses Report</a>
                </div>

                <form method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($report_type); ?>">
                    <span style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">From:</span>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="form-control" required>
                    <span style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">To:</span>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="form-control" required>
                    <button type="submit" style="background: var(--accent-primary); color: #0f0a05; border: none; padding: 8px 14px; border-radius: 8px; font-weight: bold; font-family: 'Orbitron'; cursor: pointer;">FILTER</button>
                </form>
            </div>
        </div>

        <!-- Report Content -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0; text-transform: uppercase;">
                📋 <?php echo htmlspecialchars($report_type); ?> Report Data
            </h3>

            <?php if ($report_type === 'members'): ?>
                <?php
                $q = "SELECT u.userid, u.username, u.mobile, u.email, u.gender, u.joining_date, p.planName, e.expire 
                      FROM users u 
                      LEFT JOIN enrolls_to e ON u.userid = e.uid AND e.renewal = 'yes' 
                      LEFT JOIN plan p ON e.pid = p.pid 
                      ORDER BY u.joining_date DESC LIMIT 100";
                $res = mysqli_query($con, $q);
                ?>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Joining Date</th>
                            <th>Active Plan</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td style="font-family: 'Orbitron'; color: var(--accent-primary);"><?php echo htmlspecialchars($r['userid']); ?></td>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($r['username']); ?></td>
                                <td><?php echo htmlspecialchars($r['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($r['email']); ?></td>
                                <td><?php echo htmlspecialchars($r['joining_date']); ?></td>
                                <td><span style="background: rgba(30,144,255,0.2); color: #a78bfa; border: 1px solid #1e90ff; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-family: 'Orbitron';"><?php echo htmlspecialchars($r['planName'] ?? 'No Plan'); ?></span></td>
                                <td><strong style="color: #ffb703; font-family: 'Orbitron';"><?php echo htmlspecialchars($r['expire'] ?? 'N/A'); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'payments'): ?>
                <?php
                $q = "SELECT e.*, u.username, p.planName FROM enrolls_to e JOIN users u ON e.uid = u.userid JOIN plan p ON e.pid = p.pid WHERE e.paid_date BETWEEN '$start_date' AND '$end_date' ORDER BY e.paid_date DESC";
                $res = mysqli_query($con, $q);
                ?>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Enroll ID</th>
                            <th>Member Name</th>
                            <th>Plan</th>
                            <th>Paid Amount</th>
                            <th>Discount</th>
                            <th>Balance</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td style="font-family: 'Orbitron'; color: var(--accent-primary);"><?php echo htmlspecialchars($r['et_id']); ?></td>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($r['username']); ?></td>
                                <td><?php echo htmlspecialchars($r['planName']); ?></td>
                                <td style="color: #10b981; font-weight: bold; font-family: 'Orbitron';">₹<?php echo number_format($r['paid_amount']); ?></td>
                                <td>₹<?php echo number_format($r['discount_amount']); ?></td>
                                <td style="color: #ff0054; font-weight: bold;">₹<?php echo number_format($r['balance']); ?></td>
                                <td><?php echo htmlspecialchars($r['paid_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'attendance'): ?>
                <?php
                $q = "SELECT a.*, u.username FROM attendance a JOIN users u ON a.uid = u.userid WHERE a.date BETWEEN '$start_date' AND '$end_date' ORDER BY a.date DESC, a.entry_time DESC LIMIT 100";
                $res = mysqli_query($con, $q);
                ?>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User ID</th>
                            <th>Member Name</th>
                            <th>Check-in Time</th>
                            <th>Check-out Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($r['date']); ?></td>
                                <td style="font-family: 'Orbitron'; color: var(--accent-primary);"><?php echo htmlspecialchars($r['uid']); ?></td>
                                <td><?php echo htmlspecialchars($r['username']); ?></td>
                                <td><span style="color: #00f0ff; font-family: 'Orbitron';"><?php echo htmlspecialchars($r['entry_time'] ?? 'Recorded'); ?></span></td>
                                <td><?php echo htmlspecialchars($r['exit_time'] ?? '--'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'expenses'): ?>
                <?php
                $q = "SELECT * FROM expenses WHERE expense_date BETWEEN '$start_date' AND '$end_date' ORDER BY expense_date DESC";
                $res = mysqli_query($con, $q);
                ?>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Expense Title</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['expense_date']); ?></td>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($r['expense_name']); ?></td>
                                <td><span style="background: rgba(255,0,84,0.15); color: #ff0054; border: 1px solid #ff0054; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-family: 'Orbitron';"><?php echo htmlspecialchars($r['category']); ?></span></td>
                                <td style="color: #ff0054; font-weight: bold; font-family: 'Orbitron';">₹<?php echo number_format($r['amount']); ?></td>
                                <td><?php echo htmlspecialchars($r['remarks'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
