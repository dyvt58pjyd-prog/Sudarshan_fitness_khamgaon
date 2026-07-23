<?php
require 'auth.php';
require '../../include/db_conn.php';

$gym = get_gym_details($con);

// Handle delete request
if (isset($_POST['delete_expense']) && isset($_POST['expense_id'])) {
    $expense_id = intval($_POST['expense_id']);
    $del_query = "DELETE FROM expenses WHERE id = $expense_id";
    if (mysqli_query($con, $del_query)) {
        echo "<script>alert('Expense log deleted successfully!');</script>";
        echo "<meta http-equiv='refresh' content='0; url=expenses_audit.php'>";
        exit();
    } else {
        echo "<script>alert('Failed to delete expense.');</script>";
    }
}

// Handle add request
if (isset($_POST['add_expense'])) {
    $expense_name = mysqli_real_escape_string($con, trim($_POST['expense_name']));
    $amount = intval($_POST['amount']);
    $category = mysqli_real_escape_string($con, $_POST['category']);
    $expense_date = mysqli_real_escape_string($con, $_POST['expense_date']);
    $remarks = mysqli_real_escape_string($con, trim($_POST['remarks']));

    if (empty($expense_name) || $amount <= 0 || empty($expense_date) || empty($category)) {
        echo "<script>alert('Please fill in all required fields and provide a valid amount.');</script>";
    } else {
        $insert_query = "INSERT INTO expenses (expense_name, amount, category, expense_date, remarks) 
                         VALUES ('$expense_name', $amount, '$category', '$expense_date', '$remarks')";
        if (mysqli_query($con, $insert_query)) {
            echo "<script>alert('Expense logged successfully!');</script>";
            echo "<meta http-equiv='refresh' content='0; url=expenses_audit.php'>";
            exit();
        } else {
            echo "<script>alert('Failed to log expense: " . mysqli_error($con) . "');</script>";
        }
    }
}

// Get filter date values
$filter_month = isset($_GET['filter_month']) ? mysqli_real_escape_string($con, $_GET['filter_month']) : date('Y-m');
$start_date = $filter_month . "-01";
$end_date = date('Y-m-t', strtotime($start_date));

// Fetch total expenses for selected month
$total_exp_q = mysqli_query($con, "SELECT SUM(amount) AS total FROM expenses WHERE expense_date BETWEEN '$start_date' AND '$end_date'");
$month_expenses = 0;
if ($total_exp_q && mysqli_num_rows($total_exp_q) > 0) {
    $r = mysqli_fetch_assoc($total_exp_q);
    $month_expenses = intval($r['total']);
}

// Fetch category breakdown for selected month
$cat_summary = [];
$cat_q = mysqli_query($con, "SELECT category, SUM(amount) as cat_total FROM expenses WHERE expense_date BETWEEN '$start_date' AND '$end_date' GROUP BY category");
if ($cat_q && mysqli_num_rows($cat_q) > 0) {
    while ($row = mysqli_fetch_assoc($cat_q)) {
        $cat_summary[$row['category']] = intval($row['cat_total']);
    }
}

// Fetch list of expenses
$list_query = "SELECT * FROM expenses WHERE expense_date BETWEEN '$start_date' AND '$end_date' ORDER BY expense_date DESC, id DESC";
$res_list = mysqli_query($con, $list_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Expenses Audit | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="stylesheet" href="../../css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <style>
        body { background: #0b0f19; color: #fff; font-family: 'Inter', sans-serif; }
        .page-container { display: flex; }
        .sidebar-menu { width: 250px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); padding: 20px; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; }
        
        .sidebar-menu ul { list-style: none; padding: 0; }
        .sidebar-menu ul li { margin-bottom: 10px; }
        .sidebar-menu ul li a { color: #9ca3af; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
        .sidebar-menu ul li a:hover, .sidebar-menu ul li.active a { background: linear-gradient(135deg, #ff6b00, #e65c00); color: #fff; box-shadow: 0 4px 15px rgba(255,107,0,0.3); }
        
        .nav-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        
        .form-control-custom { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 10px 14px; border-radius: 8px; font-size: 14px; width: 100%; box-sizing: border-box; }
        .form-control-custom:focus { border-color: #ff6b00; outline: none; }
        
        .btn-primary { background: linear-gradient(135deg, #ff6b00, #e65c00); color: #fff; padding: 10px 22px; border-radius: 8px; font-weight: bold; border: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 15px rgba(255,107,0,0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255,107,0,0.4); }
        
        .btn-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; }
        .btn-danger:hover { background: #ef4444; color: #fff; }

        .table-custom { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-custom th { background: rgba(255,255,255,0.05); color: #9ca3af; text-transform: uppercase; font-size: 12px; padding: 14px 18px; text-align: left; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .table-custom td { padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; }
        .table-custom tr:hover { background: rgba(255,255,255,0.02); }
        
        .cat-tag { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .cat-rent { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
        .cat-salaries { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); }
        .cat-maintenance { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
        .cat-inventory { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .cat-misc { background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid rgba(168,85,247,0.3); }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="sidebar-menu">
            <h2 style="color:#ff6b00; text-align:center; margin-bottom: 30px;">Titan Gym<br><small style="color:#fff;">Auditor</small></h2>
            <?php include('nav.php'); ?>
        </div>
        
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
                <h1 style="margin: 0; font-weight: 800; font-size: 32px; display: flex; align-items: center; gap: 10px; color: #ef4444;">
                    <i class="entypo-book-open"></i> Gym Expenses Audit &amp; Outgoings
                </h1>
                <form method="GET" action="" style="display: flex; align-items: center; gap: 10px;">
                    <label style="color: #9ca3af; font-size: 13px; font-weight: bold;">Select Month:</label>
                    <input type="month" name="filter_month" value="<?php echo htmlspecialchars($filter_month); ?>" class="form-control-custom" style="width: 170px;" onchange="this.form.submit()">
                </form>
            </div>

            <!-- Month Summary Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 20px; padding: 25px;">
                    <span style="color: #ef4444; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">TOTAL OUTGOINGS (<?php echo date('M Y', strtotime($start_date)); ?>)</span>
                    <h2 style="font-size: 36px; font-weight: 800; color: #ef4444; margin: 10px 0 0 0;">₹<?php echo number_format($month_expenses); ?></h2>
                </div>

                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 25px;">
                    <span style="color: #9ca3af; font-size: 12px; font-weight: 700; text-transform: uppercase;">Rent &amp; Utilities</span>
                    <h3 style="font-size: 26px; font-weight: 800; color: #fff; margin: 10px 0 0 0;">₹<?php echo number_format($cat_summary['Rent & Utilities'] ?? 0); ?></h3>
                </div>

                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 25px;">
                    <span style="color: #9ca3af; font-size: 12px; font-weight: 700; text-transform: uppercase;">Salaries &amp; Trainer Pay</span>
                    <h3 style="font-size: 26px; font-weight: 800; color: #fff; margin: 10px 0 0 0;">₹<?php echo number_format($cat_summary['Salaries & Wages'] ?? 0); ?></h3>
                </div>

                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 25px;">
                    <span style="color: #9ca3af; font-size: 12px; font-weight: 700; text-transform: uppercase;">Maintenance &amp; Restock</span>
                    <h3 style="font-size: 26px; font-weight: 800; color: #fff; margin: 10px 0 0 0;">₹<?php echo number_format(($cat_summary['Maintenance & Repair'] ?? 0) + ($cat_summary['Inventory & Supplies'] ?? 0)); ?></h3>
                </div>
            </div>

            <!-- Log Expense Form -->
            <div class="nav-card">
                <h3 style="margin-top: 0; color: #ff6b00; font-weight: 800; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                    <i class="entypo-plus-circled"></i> Log New Gym Expense / Outgoing
                </h3>
                <form method="POST" action="" style="margin-top: 20px;">
                    <input type="hidden" name="add_expense" value="1">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="color: #9ca3af; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Expense Name *</label>
                            <input class="form-control-custom" type="text" name="expense_name" placeholder="e.g. Electricity Bill, Gym Rent, Staff Salary" required>
                        </div>
                        <div>
                            <label style="color: #9ca3af; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Category *</label>
                            <select class="form-control-custom" name="category" required style="color-scheme: dark;">
                                <option value="Rent & Utilities">Rent &amp; Utilities (Electricity/Water)</option>
                                <option value="Salaries & Wages">Salaries &amp; Trainer Pay</option>
                                <option value="Maintenance & Repair">Maintenance &amp; Repairs</option>
                                <option value="Inventory & Supplies">Inventory &amp; Supplements Restock</option>
                                <option value="Marketing & Software">Marketing, Software &amp; Tech</option>
                                <option value="Miscellaneous">Miscellaneous / Other</option>
                            </select>
                        </div>
                        <div>
                            <label style="color: #9ca3af; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Amount (₹) *</label>
                            <input class="form-control-custom" type="number" min="1" name="amount" placeholder="e.g. 5000" required>
                        </div>
                        <div>
                            <label style="color: #9ca3af; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Expense Date *</label>
                            <input class="form-control-custom" type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required style="color-scheme: dark;">
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="color: #9ca3af; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Remarks / Notes (Optional)</label>
                        <input class="form-control-custom" type="text" name="remarks" placeholder="Add optional details e.g. Bill #1042 paid via UPI">
                    </div>
                    <button type="submit" class="btn-primary"><i class="entypo-check"></i> Log Gym Expense</button>
                </form>
            </div>

            <!-- Expenses Ledger Table -->
            <div class="nav-card">
                <h3 style="margin-top: 0; color: #fff; font-weight: 800; font-size: 18px;">
                    Detailed Outgoings Ledger for <?php echo date('F Y', strtotime($start_date)); ?>
                </h3>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Expense Title</th>
                            <th>Category</th>
                            <th>Amount (₹)</th>
                            <th>Remarks / Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($res_list && mysqli_num_rows($res_list) > 0) {
                            while ($row = mysqli_fetch_assoc($res_list)) {
                                $cat_class = 'cat-misc';
                                $c = strtolower($row['category']);
                                if (strpos($c, 'rent') !== false || strpos($c, 'utility') !== false) $cat_class = 'cat-rent';
                                elseif (strpos($c, 'salary') !== false || strpos($c, 'wage') !== false) $cat_class = 'cat-salaries';
                                elseif (strpos($c, 'maintenance') !== false || strpos($c, 'repair') !== false) $cat_class = 'cat-maintenance';
                                elseif (strpos($c, 'inventory') !== false || strpos($c, 'supply') !== false) $cat_class = 'cat-inventory';
                                
                                echo "<tr>";
                                echo "<td>" . date('d M Y', strtotime($row['expense_date'])) . "</td>";
                                echo "<td><strong style='color:#fff;'>" . htmlspecialchars($row['expense_name']) . "</strong></td>";
                                echo "<td><span class='cat-tag {$cat_class}'>" . htmlspecialchars($row['category']) . "</span></td>";
                                echo "<td><strong style='color:#ef4444;'>₹" . number_format($row['amount']) . "</strong></td>";
                                echo "<td><span style='color:#9ca3af;'>" . htmlspecialchars($row['remarks'] ?? '-') . "</span></td>";
                                echo "<td>
                                        <form method='POST' action='' style='margin:0;' onsubmit='return confirm(\"Are you sure you want to delete this expense record?\");'>
                                            <input type='hidden' name='expense_id' value='" . $row['id'] . "'>
                                            <button type='submit' name='delete_expense' class='btn-danger'>Delete</button>
                                        </form>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align: center; color: #9ca3af; padding: 30px;'>No gym expenses logged for this month.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
</html>
