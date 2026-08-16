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

$monthName = date("F", mktime(0, 0, 0, intval($month), 10));
?>

<!-- 📊 MONTHLY AUDIT PAYMENT SEPARATOR (UPI VS CASH) -->
<div style="margin-bottom: 25px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <!-- 💳 UPI Monthly Card -->
        <div style="background: linear-gradient(135deg, rgba(56, 189, 248, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%); border: 2px solid #38bdf8; border-radius: 16px; padding: 18px 20px; box-shadow: 0 8px 24px rgba(56, 189, 248, 0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #38bdf8; letter-spacing: 1px;">💳 UPI / Online Monthly</span>
                <span style="font-size: 11px; background: rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 2px 8px; border-radius: 10px; font-weight: bold;"><?php echo $upi_count; ?> Txns</span>
            </div>
            <div style="font-size: 26px; font-weight: 900; color: #38bdf8; margin-top: 6px; font-family: 'Orbitron', sans-serif;">
                ₹<?php echo number_format($upi_total); ?>
            </div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Audited UPI income for <?php echo $monthName . " " . $year; ?></div>
        </div>

        <!-- 💵 Cash Monthly Card -->
        <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%); border: 2px solid #f59e0b; border-radius: 16px; padding: 18px 20px; box-shadow: 0 8px 24px rgba(245, 158, 11, 0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #f59e0b; letter-spacing: 1px;">💵 Cash Monthly</span>
                <span style="font-size: 11px; background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 2px 8px; border-radius: 10px; font-weight: bold;"><?php echo $cash_count; ?> Txns</span>
            </div>
            <div style="font-size: 26px; font-weight: 900; color: #f59e0b; margin-top: 6px; font-family: 'Orbitron', sans-serif;">
                ₹<?php echo number_format($cash_total); ?>
            </div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Audited Cash income for <?php echo $monthName . " " . $year; ?></div>
        </div>

        <!-- 💰 Grand Total Monthly Card -->
        <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%); border: 2px solid #10b981; border-radius: 16px; padding: 18px 20px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #10b981; letter-spacing: 1px;">💰 Combined Monthly Gross</span>
                <span style="font-size: 11px; background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 2px 8px; border-radius: 10px; font-weight: bold;"><?php echo count($all_records); ?> Total</span>
            </div>
            <div style="font-size: 26px; font-weight: 900; color: #10b981; margin-top: 6px; font-family: 'Orbitron', sans-serif;">
                ₹<?php echo number_format($grand_total); ?>
            </div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Total monthly collection (UPI + Cash)</div>
        </div>
    </div>

    <!-- Quick Mode Filter Buttons -->
    <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
        <button type="button" onclick="filterLedgerMode('all')" class="a1-btn <?php echo ($filter_mode === 'all') ? 'a1-blue' : 'a1-default'; ?>" style="font-weight: bold; border-radius: 8px; padding: 6px 14px; font-size: 12px; cursor: pointer;">
            📋 Show All (₹<?php echo number_format($grand_total); ?>)
        </button>
        <button type="button" onclick="filterLedgerMode('upi')" class="a1-btn <?php echo ($filter_mode === 'upi') ? 'a1-blue' : 'a1-default'; ?>" style="font-weight: bold; border-radius: 8px; padding: 6px 14px; font-size: 12px; color: #38bdf8; border-color: #38bdf8; cursor: pointer;">
            💳 UPI Only (₹<?php echo number_format($upi_total); ?>)
        </button>
        <button type="button" onclick="filterLedgerMode('cash')" class="a1-btn <?php echo ($filter_mode === 'cash') ? 'a1-blue' : 'a1-default'; ?>" style="font-weight: bold; border-radius: 8px; padding: 6px 14px; font-size: 12px; color: #f59e0b; border-color: #f59e0b; cursor: pointer;">
            💵 Cash Only (₹<?php echo number_format($cash_total); ?>)
        </button>
    </div>
</div>

<?php if (count($all_records) > 0): ?>
<table class="table table-bordered table-striped" style="font-size:13.5px; width: 100%; border-collapse: collapse;">
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
        $is_upi = (strpos($mode_raw, 'upi') !== false || strpos($mode_raw, 'online') !== false);
        
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
            <td colspan="7" style="text-align: right; font-size: 14px; text-transform: uppercase;">
                Filtered Total Collection (<?php echo strtoupper($filter_mode); ?>):
            </td>
            <td style="text-align: right; font-size: 16px; color: #10b981;">
                ₹<?php echo number_format($displayed_total); ?>
            </td>
            <td></td>
        </tr>
    </tfoot>
</table>
<?php else: ?>
    <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.15); padding: 40px; border-radius: 16px; text-align: center; color: #94a3b8;">
        <h3>No payment transactions found on <?php echo $monthName . " " . $year; ?></h3>
        <p style="font-size: 13px;">Try selecting another month or year from the dropdown above.</p>
    </div>
<?php endif; ?>
