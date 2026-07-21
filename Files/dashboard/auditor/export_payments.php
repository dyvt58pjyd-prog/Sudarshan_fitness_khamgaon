<?php
require '../../include/db_conn.php';
page_protect();

$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($current_role !== 'super_admin' && $current_role !== 'owner' && $current_role !== 'reception' && $current_role !== 'auditor') {
    die("Unauthorized access.");
}

$working_year = isset($_SESSION['working_year']) ? intval($_SESSION['working_year']) : intval(date('Y'));

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=auditor_payments_report_' . date('Ymd_His') . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

$invoices = [];

// 1. Membership Enrollments
$sql_mem = "SELECT e.et_id, e.uid, u.username, p.planName, e.paid_date, e.expire, p.amount AS plan_price, 
                   IFNULL(e.discount_amount, 0) AS discount_amount, e.paid_amount, e.payment_mode, e.received_by 
            FROM enrolls_to e 
            INNER JOIN plan p ON e.pid = p.pid 
            LEFT JOIN users u ON e.uid = u.userid
            WHERE YEAR(e.paid_date) = $working_year
            ORDER BY e.paid_date DESC";
$res_mem = mysqli_query($con, $sql_mem);
if ($res_mem) {
    while ($row = mysqli_fetch_assoc($res_mem)) {
        $p_price = intval($row['plan_price']);
        $p_disc = intval($row['discount_amount']);
        $max_payable = $p_price - $p_disc;
        $paid = isset($row['paid_amount']) && $row['paid_amount'] !== null ? intval($row['paid_amount']) : $max_payable;
        if ($paid > $max_payable) { $paid = $max_payable; }
        
        $et_id = $row['et_id'];
        $q_def = mysqli_query($con, "SELECT SUM(amount) as def_amount FROM balance_collections WHERE et_id = '$et_id'");
        if ($q_def && mysqli_num_rows($q_def) > 0) {
            $def_row = mysqli_fetch_assoc($q_def);
            $paid -= intval($def_row['def_amount']);
        }
        
        $row['type'] = 'Membership';
        $row['plan_price'] = $p_price;
        $row['discount_amount'] = $p_disc;
        $row['actual_paid'] = $paid;
        $invoices[] = $row;
    }
}

// 2. Deferred Balance Collections
$sql_bal = "SELECT b.id AS et_id, e.uid, u.username, CONCAT('Pending Balance (', p.planName, ')') AS planName, 
                   b.collection_date AS paid_date, e.expire, b.amount AS plan_price, 
                   0 AS discount_amount, b.amount AS paid_amount, b.payment_mode, b.received_by 
            FROM balance_collections b
            INNER JOIN enrolls_to e ON b.et_id = e.et_id
            INNER JOIN plan p ON e.pid = p.pid
            LEFT JOIN users u ON e.uid = u.userid
            WHERE YEAR(b.collection_date) = $working_year
            ORDER BY b.collection_date DESC";
$res_bal = mysqli_query($con, $sql_bal);
if ($res_bal) {
    while ($row = mysqli_fetch_assoc($res_bal)) {
        $row['type'] = 'Pending Fee Clearance';
        $row['plan_price'] = intval($row['paid_amount']);
        $row['discount_amount'] = 0;
        $row['actual_paid'] = intval($row['paid_amount']);
        $invoices[] = $row;
    }
}

// 3. PT Enrollments
$sql_pt = "SELECT p.pt_id AS et_id, p.uid, u.username, CONCAT('Personal Training (', t.Full_name, ')') AS planName, 
                  p.enroll_date AS paid_date, p.expire_date AS expire, p.amount AS plan_price, 
                  0 AS discount_amount, p.amount AS paid_amount, p.payment_mode, p.received_by 
           FROM pt_enrollments p
           LEFT JOIN users u ON p.uid = u.userid
           LEFT JOIN admin t ON p.trainer_id = t.username
           WHERE YEAR(p.enroll_date) = $working_year
           ORDER BY p.enroll_date DESC";
$res_pt = mysqli_query($con, $sql_pt);
if ($res_pt) {
    while ($row = mysqli_fetch_assoc($res_pt)) {
        $row['type'] = 'Personal Training';
        $row['plan_price'] = intval($row['paid_amount']);
        $row['discount_amount'] = 0;
        $row['actual_paid'] = intval($row['paid_amount']);
        $invoices[] = $row;
    }
}

// 4. Inventory Sales
$sql_inv = "SELECT s.id AS et_id, COALESCE(s.member_id, 'GUEST') AS uid, COALESCE(u.username, 'Guest') AS username, 
                   CONCAT('Store Purchase: ', s.quantity, 'x ', i.product_name) AS planName, 
                   s.sale_date AS paid_date, s.sale_date AS expire, s.total_price AS plan_price, 
                   0 AS discount_amount, s.total_price AS paid_amount, s.payment_mode, s.received_by 
            FROM inventory_sales s
            INNER JOIN inventory_items i ON s.product_id = i.id
            LEFT JOIN users u ON s.member_id = u.userid
            WHERE YEAR(s.sale_date) = $working_year
            ORDER BY s.sale_date DESC";
$res_inv = mysqli_query($con, $sql_inv);
if ($res_inv) {
    while ($row = mysqli_fetch_assoc($res_inv)) {
        $row['type'] = 'Store Inventory';
        $row['plan_price'] = intval($row['paid_amount']);
        $row['discount_amount'] = 0;
        $row['actual_paid'] = intval($row['paid_amount']);
        $invoices[] = $row;
    }
}

// Sort all by paid_date DESC
usort($invoices, function($a, $b) {
    return strcmp($b['paid_date'], $a['paid_date']);
});
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Gym Payments</x:Name>
    <x:WorksheetOptions>
     <x:DisplayGridlines/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #ffffff; }
  table { border-collapse: collapse; margin-top: 10px; width: 100%; }
  th { background-color: #ff6b00; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #cccccc; padding: 10px; font-size: 11pt; }
  td { border: 1px solid #dddddd; padding: 8px; font-size: 10pt; color: #333333; }
  .even-row { background-color: #f9f9f9; }
  .title-header { font-size: 16pt; font-weight: bold; color: #ffffff; background-color: #0c0c0c; text-align: center; padding: 15px; }
  .meta-info { font-size: 9pt; color: #555555; background-color: #f2f2f2; text-align: center; padding: 5px; }
  .total-row { background-color: #e6f4ea; font-weight: bold; font-size: 11pt; border-top: 2px solid #10b981; }
</style>
</head>
<body>
<table>
  <thead>
    <tr>
      <th colspan="11" class="title-header">SUDARSHAN FITNESS KHAMGAON - AUDITOR PAYMENTS EXPORT</th>
    </tr>
    <tr>
      <th colspan="11" class="meta-info">Generated: <?php echo date('Y-m-d H:i:s'); ?> | Operating Year Session: <?php echo $working_year; ?></th>
    </tr>
    <tr>
      <th style="width: 100px;">Trans. ID</th>
      <th style="width: 120px;">Category</th>
      <th style="width: 150px;">Member Name</th>
      <th style="width: 100px;">Member ID</th>
      <th style="width: 220px;">Plan / Item Description</th>
      <th style="width: 100px;">Payment Date</th>
      <th style="width: 110px;">Plan Price (₹)</th>
      <th style="width: 110px;">Discount (₹)</th>
      <th style="width: 120px;">Actual Paid (₹)</th>
      <th style="width: 100px;">Payment Mode</th>
      <th style="width: 150px;">Received By</th>
    </tr>
  </thead>
  <tbody>
    <?php 
    $tot_price = 0;
    $tot_disc = 0;
    $tot_paid = 0;
    $row_count = 0;
    
    foreach ($invoices as $inv): 
        $row_class = ($row_count % 2 === 0) ? 'even-row' : '';
        $row_count++;
        
        $price = intval($inv['plan_price']);
        $disc = intval($inv['discount_amount']);
        $paid = intval($inv['actual_paid']);
        
        $tot_price += $price;
        $tot_disc += $disc;
        $tot_paid += $paid;
        
        $m_name = !empty($inv['username']) ? htmlspecialchars($inv['username']) : 'N/A';
    ?>
    <tr class="<?php echo $row_class; ?>">
      <td style="mso-number-format:'\@'; text-align: center;"><?php echo htmlspecialchars($inv['et_id']); ?></td>
      <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($inv['type']); ?></td>
      <td><strong><?php echo $m_name; ?></strong></td>
      <td style="mso-number-format:'\@'; text-align: center;"><?php echo htmlspecialchars($inv['uid']); ?></td>
      <td><?php echo htmlspecialchars($inv['planName']); ?></td>
      <td style="text-align: center;"><?php echo htmlspecialchars($inv['paid_date']); ?></td>
      <td style="mso-number-format:'\#\,\#\#0'; text-align: right; color: #555555;">₹<?php echo number_format($price); ?></td>
      <td style="mso-number-format:'\#\,\#\#0'; text-align: right; color: #ef4444;">₹<?php echo number_format($disc); ?></td>
      <td style="mso-number-format:'\#\,\#\#0'; text-align: right; font-weight: bold; color: #10b981;">₹<?php echo number_format($paid); ?></td>
      <td style="text-align: center; text-transform: uppercase;"><?php echo htmlspecialchars($inv['payment_mode']); ?></td>
      <td><?php echo htmlspecialchars($inv['received_by']); ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total-row">
      <td colspan="6" style="text-align: right; font-weight: bold;">GRAND TOTALS:</td>
      <td style="text-align: right; color: #555555;">₹<?php echo number_format($tot_price); ?></td>
      <td style="text-align: right; color: #ef4444;">₹<?php echo number_format($tot_disc); ?></td>
      <td style="text-align: right; color: #10b981; font-weight: bold;">₹<?php echo number_format($tot_paid); ?></td>
      <td colspan="2"></td>
    </tr>
  </tbody>
</table>
</body>
</html>
<?php
exit();
?>
