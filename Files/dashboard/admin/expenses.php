<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

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
    $expense_name = mysqli_real_escape_string($con, $_POST['expense_name']);
    $amount = intval($_POST['amount']);
    $category = mysqli_real_escape_string($con, $_POST['category']);
    $expense_date = mysqli_real_escape_string($con, $_POST['expense_date']);
    $remarks = mysqli_real_escape_string($con, $_POST['remarks']);

    if (empty($expense_name) || $amount <= 0 || empty($expense_date) || empty($category)) {
        echo "<script>alert('Please fill in all required fields and provide a valid amount.');</script>";
    } else {
        $insert_query = "INSERT INTO expenses (expense_name, amount, category, expense_date, remarks) 
                         VALUES ('$expense_name', $amount, '$category', '$expense_date', '$remarks')";
        if (mysqli_query($con, $insert_query)) {
            echo "<script>alert('Expense logged successfully!');</script>";
            echo "<meta http-equiv='refresh' content='0; url=expenses.php'>";
            exit();
        } else {
            echo "<script>alert('Failed to log expense: " . mysqli_error($con) . "');</script>";
        }
    }
}

// Get filter date values
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : date('Y-m');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Expenses Ledger</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#expenses_ledger > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 10px !important;
            width: 100%;
            margin-bottom: 15px;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }
        .ledger-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            align-items: start;
        }
        @media (max-width: 992px) {
            .ledger-grid {
                grid-template-columns: 1fr;
            }
        }
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            color: var(--text-main);
        }
        .expense-summary-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.03) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .expense-summary-box h3 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #ef4444;
        }
        .expense-summary-box .amount {
            font-size: 32px;
            font-weight: 700;
            color: #ef4444;
        }
        .premium-btn {
            background: linear-gradient(135deg, #ff6b00, #ea580c);
            border: none;
            color: white !important;
            padding: 12px 24px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
            transition: all 0.2s ease;
            width: 100%;
        }
        .premium-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(234, 88, 12, 0.3);
        }
        .premium-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
        }
        .premium-table th {
            background: rgba(15, 23, 42, 0.8);
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 12px 15px;
            border: none;
        }
        .premium-table th:first-child {
            border-radius: 8px 0 0 8px;
        }
        .premium-table th:last-child {
            border-radius: 0 8px 8px 0;
        }
        .premium-table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
        }
        .category-badge {
            background: rgba(255, 107, 0, 0.1);
            color: #ff6b00;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .delete-btn {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444 !important;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .delete-btn:hover {
            background: #ef4444;
            color: white !important;
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

            <h2>Expenses & Outgoings Ledger</h2>
            <hr />

            <div class="ledger-grid">
                <!-- Left: Form -->
                <div class="glass-panel">
                    <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Log New Expense</h3>
                    <form method="post" action="">
                        <label style="font-weight: 500; font-size: 12px; display: block; margin-bottom: 5px;">Expense Name *</label>
                        <input class="form-control-premium" type="text" name="expense_name" placeholder="e.g. Electricity Bill, Gym Instructor Pay" required>

                        <label style="font-weight: 500; font-size: 12px; display: block; margin-bottom: 5px;">Amount (INR) *</label>
                        <input class="form-control-premium" type="number" min="1" name="amount" placeholder="e.g. 5000" required>

                        <label style="font-weight: 500; font-size: 12px; display: block; margin-bottom: 5px;">Category *</label>
                        <select class="form-control-premium" name="category" required>
                            <option value="Maintenance">Maintenance & Repairs</option>
                            <option value="Rent">Rent & Lease</option>
                            <option value="Salaries">Staff Salaries</option>
                            <option value="Marketing">Marketing & Advertising</option>
                            <option value="Utilities">Utilities (Water, Power, Net)</option>
                            <option value="Equipment">New Gym Equipment</option>
                            <option value="Supplements">Supplements & Stock</option>
                            <option value="Other">Other Miscellaneous</option>
                        </select>

                        <label style="font-weight: 500; font-size: 12px; display: block; margin-bottom: 5px;">Expense Date *</label>
                        <input class="form-control-premium" type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>

                        <label style="font-weight: 500; font-size: 12px; display: block; margin-bottom: 5px;">Remarks / Invoice Details</label>
                        <textarea class="form-control-premium" name="remarks" rows="3" placeholder="Optional notes..."></textarea>

                        <button type="submit" name="add_expense" class="premium-btn">Log Expense</button>
                    </form>
                </div>

                <!-- Right: List and filter -->
                <div class="glass-panel">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px; gap: 15px;">
                        <h3 style="margin: 0; font-weight: 600;">Outgoing Logs</h3>
                        
                        <form method="get" action="" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                            <label style="font-size: 12px; margin: 0; white-space: nowrap;">Month:</label>
                            <input type="month" name="filter_month" class="form-control-premium" style="margin: 0; padding: 6px 10px !important; width: auto;" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="this.form.submit()">
                        </form>
                    </div>

                    <?php
                    // Fetch total expenses for the selected month
                    $start_date = $filter_month . "-01";
                    $end_date = date("Y-m-t", strtotime($start_date));

                    $total_query = "SELECT SUM(amount) AS total FROM expenses WHERE expense_date BETWEEN '$start_date' AND '$end_date'";
                    $total_res = mysqli_query($con, $total_query);
                    $total_row = mysqli_fetch_assoc($total_res);
                    $monthly_total = isset($total_row['total']) ? intval($total_row['total']) : 0;
                    ?>

                    <div class="expense-summary-box">
                        <div>
                            <h3>Total Outgoings</h3>
                            <span style="font-size: 11px; color: var(--text-muted);">For <?php echo date('F Y', strtotime($start_date)); ?></span>
                        </div>
                        <div class="amount">₹<?php echo number_format($monthly_total); ?></div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $list_query = "SELECT * FROM expenses WHERE expense_date BETWEEN '$start_date' AND '$end_date' ORDER BY expense_date DESC, id DESC";
                                $list_res = mysqli_query($con, $list_query);

                                if ($list_res && mysqli_num_rows($list_res) > 0) {
                                    while ($row = mysqli_fetch_assoc($list_res)) {
                                        echo "<tr>";
                                        echo "<td>" . date('d M Y', strtotime($row['expense_date'])) . "</td>";
                                        echo "<td><strong>" . htmlspecialchars($row['expense_name']) . "</strong></td>";
                                        echo "<td><span class='category-badge'>" . htmlspecialchars($row['category']) . "</span></td>";
                                        echo "<td style='color: #ef4444; font-weight: 600;'>₹" . number_format($row['amount']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
                                        echo "<td>
                                                <form method='post' action='' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this expense log?\");'>
                                                    <input type='hidden' name='expense_id' value='" . $row['id'] . "'>
                                                    <button type='submit' name='delete_expense' class='delete-btn'>Delete</button>
                                                </form>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align: center; padding: 30px;'>No expenses logged in this month.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
