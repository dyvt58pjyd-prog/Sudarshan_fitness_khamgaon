<?php
require '../../include/db_conn.php';
$month = mysqli_real_escape_string($con, $_GET['mm'] ?? date('m'));
$year  = mysqli_real_escape_string($con, $_GET['yy'] ?? date('Y'));
$filter_mode = strtolower(trim($_GET['mode'] ?? 'all'));

$month_pad = str_pad(intval($month), 2, "0", STR_PAD_LEFT);
$month_prefix = $year . "-" . $month_pad;

// 1. Fetch Membership Enrollments
$q_mem = "SELECT e.et_id, u.userid, u.username, u.gender, u.mobile,
          e.paid_date, e.expire, p.planName, p.amount AS base_amount,
          e.discount_amount, e.paid_amount, e.payment_mode, p.validity, 'Membership' as service_type
          FROM users u 
          INNER JOIN enrolls_to e ON u.userid = e.uid
          INNER JOIN plan p ON p.pid = e.pid
          WHERE e.paid_date LIKE '".$month_prefix."%'
          ORDER BY e.paid_date ASC, u.userid ASC";
$res_mem = mysqli_query($con, $q_mem);

// 2. Fetch PT Enrollments
$q_pt = "SELECT p.id as et_id, u.userid, u.username, u.gender, u.mobile,
         p.enroll_date as paid_date, p.expire_date as expire, 'Personal Training (PT)' as planName,
         p.amount as base_amount, 0 as discount_amount, p.amount as paid_amount,
         p.payment_mode, 1 as validity, 'Personal Training' as service_type
         FROM pt_enrollments p
         INNER JOIN users u ON p.uid = u.userid
         WHERE p.enroll_date LIKE '".$month_prefix."%'
         ORDER BY p.enroll_date ASC";
$res_pt = mysqli_query($con, $q_pt);

// 3. Fetch Balance Collections
$q_bal = "SELECT b.id as et_id, u.userid, u.username, u.gender, u.mobile,
          b.collection_date as paid_date, '' as expire, 'Deferred Balance Settlement' as planName,
          b.amount as base_amount, 0 as discount_amount, b.amount as paid_amount,
          b.payment_mode, 0 as validity, 'Balance Settlement' as service_type
          FROM balance_collections b
          INNER JOIN users u ON b.uid = u.userid
          WHERE b.collection_date LIKE '".$month_prefix."%'
          ORDER BY b.collection_date ASC";
$res_bal = mysqli_query($con, $q_bal);

// Combine all transactions
$all_records = [];

if ($res_mem && mysqli_num_rows($res_mem) > 0) {
    while ($r = mysqli_fetch_assoc($res_mem)) {
        $base = intval($r['base_amount']);
        $disc = isset($r['discount_amount']) ? intval($r['discount_amount']) : 0;
        if (isset($r['paid_amount']) && $r['paid_amount'] !== null && intval($r['paid_amount']) > 0) {
            $paid = intval($r['paid_amount']);
            if ($disc > 0 && $paid === $base) $paid = $base - $disc;
        } else {
            $paid = $base - $disc;
        }
        if ($paid < 0) $paid = 0;
        $r['actual_paid'] = $paid;
        $all_records[] = $r;
    }
}

if ($res_pt && mysqli_num_rows($res_pt) > 0) {
    while ($r = mysqli_fetch_assoc($res_pt)) {
        $r['actual_paid'] = intval($r['paid_amount']);
        $all_records[] = $r;
    }
}

if ($res_bal && mysqli_num_rows($res_bal) > 0) {
    while ($r = mysqli_fetch_assoc($res_bal)) {
        $r['actual_paid'] = intval($r['paid_amount']);
        $all_records[] = $r;
    }
}

// Sort combined records by paid_date ASC
usort($all_records, function($a, $b) {
    return strcmp($a['paid_date'], $b['paid_date']);
});

// Calculate UPI vs Cash monthly breakdown
$upi_total = 0;
$upi_count = 0;
$cash_total = 0;
$cash_count = 0;
$grand_total = 0;

foreach ($all_records as $rec) {
    $mode = strtolower(trim($rec['payment_mode'] ?? 'cash'));
    $amt  = intval($rec['actual_paid']);
    if (strpos($mode, 'upi') !== false || strpos($mode, 'online') !== false) {
        $upi_total += $amt;
        $upi_count++;
    } else {
        $cash_total += $amt;
        $cash_count++;
    }
    $grand_total += $amt;
}

// 4. Fetch Expenses for this month
$q_exp = "SELECT id as et_id, '' as userid, expense_name as username, category as gender, voucher_no as mobile,
          expense_date as paid_date, '' as expire, expense_name as planName,
          amount as base_amount, 0 as discount_amount, amount as paid_amount,
          payment_mode, 0 as validity, 'Expense' as service_type, remarks
          FROM expenses
          WHERE expense_date LIKE '".$month_prefix."%'
          ORDER BY expense_date ASC";
$res_exp = mysqli_query($con, $q_exp);

$exp_records = [];
$exp_cash_total = 0;
$exp_cash_count = 0;
$exp_upi_total  = 0;
$exp_upi_count  = 0;
$exp_grand_total = 0;

if ($res_exp && mysqli_num_rows($res_exp) > 0) {
    while ($r = mysqli_fetch_assoc($res_exp)) {
        $r['actual_paid'] = intval($r['paid_amount']);
        $exp_records[] = $r;
        $mode = strtolower(trim($r['payment_mode'] ?? 'cash'));
        $amt  = intval($r['actual_paid']);
        if (strpos($mode, 'upi') !== false || strpos($mode, 'online') !== false || strpos($mode, 'bank') !== false) {
            $exp_upi_total += $amt;
            $exp_upi_count++;
        } else {
            $exp_cash_total += $amt;
            $exp_cash_count++;
        }
        $exp_grand_total += $amt;
    }
}

// Net Cashflow & Profit Reconciliation
$net_cash_in_hand = $cash_total - $exp_cash_total;
$net_digital_surplus = $upi_total - $exp_upi_total;
$true_net_profit = $grand_total - $exp_grand_total;

$monthName = date("F", mktime(0, 0, 0, intval($month), 10));
?>

<!-- 📊 DUAL CASHFLOW & ACCOUNT RECONCILIATION AUDITOR (PHYSICAL CASH VS DIGITAL UPI) -->
<div style="margin-bottom: 25px;">
    
    <!-- 3 Core Audit Statement Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 20px;">
        
        <!-- 💵 1. Physical Cash Register Reconciliation Card -->
        <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%); border: 2px solid #f59e0b; border-radius: 16px; padding: 18px 20px; box-shadow: 0 8px 24px rgba(245, 158, 11, 0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #f59e0b; letter-spacing: 1px;">💵 Physical Cash Register</span>
                <span style="font-size: 11px; background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 2px 8px; border-radius: 10px; font-weight: bold;"><?php echo $cash_count; ?> In / <?php echo $exp_cash_count; ?> Out</span>
            </div>
            <div style="font-size: 26px; font-weight: 900; color: <?php echo $net_cash_in_hand >= 0 ? '#f59e0b' : '#ef4444'; ?>; margin-top: 6px; font-family: 'Orbitron', sans-serif;">
                ₹<?php echo number_format($net_cash_in_hand); ?>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 8px; padding-top: 8px; border-top: 1px dashed rgba(255,255,255,0.12); font-size: 11px;">
                <span style="color: #10b981;">➕ Cash In: ₹<?php echo number_format($cash_total); ?></span>
                <span style="color: #ef4444;">➖ Cash Exp: ₹<?php echo number_format($exp_cash_total); ?></span>
            </div>
            <div style="font-size: 10.5px; color: #94a3b8; margin-top: 4px;">Audited physical cash drawer in hand</div>
        </div>

        <!-- 💳 2. Digital Bank Account Reconciliation Card -->
        <div style="background: linear-gradient(135deg, rgba(56, 189, 248, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%); border: 2px solid #38bdf8; border-radius: 16px; padding: 18px 20px; box-shadow: 0 8px 24px rgba(56, 189, 248, 0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #38bdf8; letter-spacing: 1px;">💳 Digital / UPI Bank Account</span>
                <span style="font-size: 11px; background: rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 2px 8px; border-radius: 10px; font-weight: bold;"><?php echo $upi_count; ?> In / <?php echo $exp_upi_count; ?> Out</span>
            </div>
            <div style="font-size: 26px; font-weight: 900; color: <?php echo $net_digital_surplus >= 0 ? '#38bdf8' : '#ef4444'; ?>; margin-top: 6px; font-family: 'Orbitron', sans-serif;">
                ₹<?php echo number_format($net_digital_surplus); ?>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 8px; padding-top: 8px; border-top: 1px dashed rgba(255,255,255,0.12); font-size: 11px;">
                <span style="color: #10b981;">➕ UPI In: ₹<?php echo number_format($upi_total); ?></span>
                <span style="color: #ef4444;">➖ UPI Exp: ₹<?php echo number_format($exp_upi_total); ?></span>
            </div>
            <div style="font-size: 10.5px; color: #94a3b8; margin-top: 4px;">Audited bank balance surplus for <?php echo $monthName . " " . $year; ?></div>
        </div>

        <!-- 💎 3. True Combined Net Profit Card -->
        <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%); border: 2px solid #10b981; border-radius: 16px; padding: 18px 20px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #10b981; letter-spacing: 1px;">💎 True Net Profit</span>
                <span style="font-size: 11px; background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 2px 8px; border-radius: 10px; font-weight: bold;">Audited Net</span>
            </div>
            <div style="font-size: 26px; font-weight: 900; color: <?php echo $true_net_profit >= 0 ? '#10b981' : '#ef4444'; ?>; margin-top: 6px; font-family: 'Orbitron', sans-serif;">
                ₹<?php echo number_format($true_net_profit); ?>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 8px; padding-top: 8px; border-top: 1px dashed rgba(255,255,255,0.12); font-size: 11px;">
                <span style="color: #10b981;">Total In: ₹<?php echo number_format($grand_total); ?></span>
                <span style="color: #ef4444;">Total Exp: ₹<?php echo number_format($exp_grand_total); ?></span>
            </div>
            <div style="font-size: 10.5px; color: #94a3b8; margin-top: 4px;">Gross income minus all physical &amp; digital expenses</div>
        </div>
    </div>

    <!-- Quick Mode Filter Buttons -->
    <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
        <button type="button" onclick="filterLedgerMode('all')" class="a1-btn <?php echo ($filter_mode === 'all') ? 'a1-blue' : 'a1-default'; ?>" style="font-weight: bold; border-radius: 8px; padding: 6px 14px; font-size: 12px; cursor: pointer;">
            📋 Show All Records (Inflows: ₹<?php echo number_format($grand_total); ?> | Outflows: ₹<?php echo number_format($exp_grand_total); ?>)
        </button>
        <button type="button" onclick="filterLedgerMode('upi')" class="a1-btn <?php echo ($filter_mode === 'upi') ? 'a1-blue' : 'a1-default'; ?>" style="font-weight: bold; border-radius: 8px; padding: 6px 14px; font-size: 12px; color: #38bdf8; border-color: #38bdf8; cursor: pointer;">
            💳 Digital / UPI Account Only (Net: ₹<?php echo number_format($net_digital_surplus); ?>)
        </button>
        <button type="button" onclick="filterLedgerMode('cash')" class="a1-btn <?php echo ($filter_mode === 'cash') ? 'a1-blue' : 'a1-default'; ?>" style="font-weight: bold; border-radius: 8px; padding: 6px 14px; font-size: 12px; color: #f59e0b; border-color: #f59e0b; cursor: pointer;">
            💵 Physical Cash Register Only (Net: ₹<?php echo number_format($net_cash_in_hand); ?>)
        </button>
        <button type="button" onclick="filterLedgerMode('expenses')" class="a1-btn <?php echo ($filter_mode === 'expenses') ? 'a1-blue' : 'a1-default'; ?>" style="font-weight: bold; border-radius: 8px; padding: 6px 14px; font-size: 12px; color: #ef4444; border-color: #ef4444; cursor: pointer;">
            🔻 Expenses Ledger Only (₹<?php echo number_format($exp_grand_total); ?>)
        </button>
    </div>
</div>

<?php if ($filter_mode !== 'expenses'): ?>
    <h3 style="color:#10b981;font-size:15px;font-weight:800;text-transform:uppercase;margin:20px 0 10px 0;display:flex;align-items:center;gap:8px;">
        <span>📥 Monthly Income &amp; Inflows (Membership, PT &amp; Balances)</span>
    </h3>
    <?php if (count($all_records) > 0): ?>
    <table class="table table-bordered table-striped" style="font-size:13.5px; width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="background: rgba(255,255,255,0.06); text-transform: uppercase; font-size: 11.5px; color: #94a3b8;">
                <th>Sl.No</th>
                <th>Member ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Paid Date</th>
                <th>Service / Plan</th>
                <th style="text-align: center;">Payment Mode</th>
                <th style="text-align: right;">Amount (₹)</th>
                <th>Validity</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sno = 1;
        $displayed_total = 0;
        foreach ($all_records as $row) {
            $mode_raw = strtolower(trim($row['payment_mode'] ?? 'cash'));
            $is_upi = (strpos($mode_raw, 'upi') !== false || strpos($mode_raw, 'online') !== false || strpos($mode_raw, 'bank') !== false);
            
            // Mode filter check
            if ($filter_mode === 'upi' && !$is_upi) continue;
            if ($filter_mode === 'cash' && $is_upi) continue;

            $paid = $row['actual_paid'];
            $displayed_total += $paid;
            $disc = isset($row['discount_amount']) ? intval($row['discount_amount']) : 0;
            $base = intval($row['base_amount']);
        ?>
            <tr>
                <td><?php echo $sno; ?></td>
                <td><strong style="color:#38bdf8;"><?php echo htmlspecialchars($row['userid']); ?></strong></td>
                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                <td><?php echo date('d M Y', strtotime($row['paid_date'])); ?></td>
                <td>
                    <span><?php echo htmlspecialchars($row['planName']); ?></span>
                    <?php if ($row['service_type'] !== 'Membership'): ?>
                        <span style="font-size:10px; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; margin-left:4px;"><?php echo htmlspecialchars($row['service_type']); ?></span>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <?php if ($is_upi): ?>
                        <span style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid #38bdf8; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                            💳 UPI / Online
                        </span>
                    <?php else: ?>
                        <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid #f59e0b; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                            💵 Cash
                        </span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <strong style="color: #10b981; font-size: 14px;">₹<?php echo number_format($paid); ?></strong>
                    <?php if ($disc > 0): ?>
                        <div style="font-size: 10px; color: #ef4444;" title="Original: ₹<?php echo $base; ?> (-₹<?php echo $disc; ?> Discount)">(-₹<?php echo $disc; ?> disc)</div>
                    <?php endif; ?>
                </td>
                <td><?php echo intval($row['validity']) > 0 ? htmlspecialchars($row['validity']) . ' Month' : '-'; ?></td>
            </tr>
        <?php
            $sno++;
        }
        ?>
        </tbody>
        <tfoot>
            <tr style="background: rgba(16, 185, 129, 0.1); font-weight: bold;">
                <td colspan="7" style="text-align: right; font-size: 13px; text-transform: uppercase;">
                    Total Inflow (<?php echo strtoupper($filter_mode); ?>):
                </td>
                <td style="text-align: right; font-size: 15px; color: #10b981;">
                    ₹<?php echo number_format($displayed_total); ?>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
        <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.15); padding: 30px; border-radius: 14px; text-align: center; color: #94a3b8; margin-bottom: 25px;">
            <p style="margin:0;">No payment inflows found for this selection.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- 🔻 EXPENSES / OUTFLOWS TABLE -->
<?php if ($filter_mode === 'all' || $filter_mode === 'expenses' || $filter_mode === 'cash' || $filter_mode === 'upi'): ?>
    <h3 style="color:#ef4444;font-size:15px;font-weight:800;text-transform:uppercase;margin:25px 0 10px 0;display:flex;align-items:center;gap:8px;">
        <span>📤 Monthly Expenses &amp; Outflows (Operational &amp; Maintenance)</span>
    </h3>
    <?php if (count($exp_records) > 0): ?>
    <table class="table table-bordered table-striped" style="font-size:13.5px; width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: rgba(239,68,68,0.08); text-transform: uppercase; font-size: 11.5px; color: #fca5a5;">
                <th>Sl.No</th>
                <th>Expense Title</th>
                <th>Category</th>
                <th>Expense Date</th>
                <th style="text-align: center;">Payment Mode</th>
                <th>Voucher / Ref No</th>
                <th style="text-align: right;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $e_sno = 1;
        $displayed_exp_total = 0;
        foreach ($exp_records as $erow) {
            $e_mode = strtolower(trim($erow['payment_mode'] ?? 'cash'));
            $is_e_upi = (strpos($e_mode, 'upi') !== false || strpos($e_mode, 'online') !== false || strpos($e_mode, 'bank') !== false);

            if ($filter_mode === 'upi' && !$is_e_upi) continue;
            if ($filter_mode === 'cash' && $is_e_upi) continue;

            $exp_amt = $erow['actual_paid'];
            $displayed_exp_total += $exp_amt;
        ?>
            <tr>
                <td><?php echo $e_sno; ?></td>
                <td><strong style="color:#fff;"><?php echo htmlspecialchars($erow['username']); ?></strong></td>
                <td><span style="background:rgba(239,68,68,0.15);color:#fca5a5;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:bold;"><?php echo htmlspecialchars($erow['gender']); ?></span></td>
                <td><?php echo date('d M Y', strtotime($erow['paid_date'])); ?></td>
                <td style="text-align: center;">
                    <?php if ($is_e_upi): ?>
                        <span style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid #38bdf8; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                            💳 <?php echo htmlspecialchars($erow['payment_mode']); ?>
                        </span>
                    <?php else: ?>
                        <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid #f59e0b; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                            💵 <?php echo htmlspecialchars($erow['payment_mode'] ?: 'Cash'); ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td style="color:#94a3b8;font-size:12px;"><?php echo htmlspecialchars($erow['mobile'] ?: ($erow['remarks'] ?: '—')); ?></td>
                <td style="text-align: right;"><strong style="color: #ef4444; font-size: 14px;">₹<?php echo number_format($exp_amt); ?></strong></td>
            </tr>
        <?php
            $e_sno++;
        }
        ?>
        </tbody>
        <tfoot>
            <tr style="background: rgba(239, 68, 68, 0.1); font-weight: bold;">
                <td colspan="6" style="text-align: right; font-size: 13px; text-transform: uppercase;">
                    Total Outflow (<?php echo strtoupper($filter_mode); ?>):
                </td>
                <td style="text-align: right; font-size: 15px; color: #ef4444;">
                    ₹<?php echo number_format($displayed_exp_total); ?>
                </td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
        <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.15); padding: 30px; border-radius: 14px; text-align: center; color: #94a3b8;">
            <p style="margin:0;">No expenses logged for <?php echo $monthName . " " . $year; ?>.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>
