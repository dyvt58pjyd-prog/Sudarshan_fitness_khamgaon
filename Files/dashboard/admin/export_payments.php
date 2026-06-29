<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    die("Unauthorized access.");
}

$working_year = isset($_SESSION['working_year']) ? intval($_SESSION['working_year']) : intval(date('Y'));

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=gym_payments_' . date('Ymd_His') . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Query payments filtered by the active operating session/working year
$query = "SELECT e.et_id, e.uid, p.planName, e.paid_date, e.expire, p.amount, e.payment_mode, e.received_by 
          FROM enrolls_to e 
          INNER JOIN plan p ON e.pid = p.pid 
          WHERE YEAR(e.paid_date) = $working_year
          ORDER BY e.paid_date DESC";
$res = mysqli_query($con, $query);
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
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background-color: #ffffff;
  }
  table {
    border-collapse: collapse;
    margin-top: 10px;
  }
  th {
    background-color: #ff6b00; /* Neon Orange matching theme */
    color: #ffffff;
    font-weight: bold;
    text-align: center;
    border: 1px solid #cccccc;
    padding: 10px;
    font-size: 11pt;
  }
  td {
    border: 1px solid #dddddd;
    padding: 8px;
    font-size: 10pt;
    color: #333333;
  }
  .odd-row {
    background-color: #ffffff;
  }
  .even-row {
    background-color: #f9f9f9;
  }
  .title-header {
    font-size: 16pt;
    font-weight: bold;
    color: #ffffff;
    background-color: #0c0c0c;
    text-align: center;
    padding: 15px;
    height: 40px;
  }
  .meta-info {
    font-size: 9pt;
    color: #555555;
    background-color: #f2f2f2;
    text-align: center;
    padding: 5px;
    height: 25px;
  }
</style>
</head>
<body>
<table>
  <thead>
    <tr>
      <th colspan="8" class="title-header">SUDARSHAN FITNESS KHAMGAON - PAYMENTS LEDGER</th>
    </tr>
    <tr>
      <th colspan="8" class="meta-info">Generated: <?php echo date('Y-m-d H:i:s'); ?> | Operating Year Session: <?php echo $working_year; ?></th>
    </tr>
    <tr>
      <th style="width: 150px;">Transaction ID</th>
      <th style="width: 120px;">Membership ID</th>
      <th style="width: 180px;">Plan Subscribed</th>
      <th style="width: 100px;">Payment Date</th>
      <th style="width: 100px;">Expiry Date</th>
      <th style="width: 120px;">Amount Paid</th>
      <th style="width: 120px;">Payment Mode</th>
      <th style="width: 180px;">Processed By</th>
    </tr>
  </thead>
  <tbody>
    <?php 
    $row_count = 0;
    while ($row = mysqli_fetch_assoc($res)): 
        $row_class = ($row_count % 2 === 0) ? 'even-row' : 'odd-row';
        $row_count++;
    ?>
    <tr class="<?php echo $row_class; ?>">
      <td style="mso-number-format:'\@'; text-align: center;"><?php echo htmlspecialchars($row['et_id']); ?></td>
      <td style="mso-number-format:'\@'; text-align: center;"><?php echo htmlspecialchars($row['uid']); ?></td>
      <td><?php echo htmlspecialchars($row['planName']); ?></td>
      <td style="text-align: center;"><?php echo htmlspecialchars($row['paid_date']); ?></td>
      <td style="text-align: center;"><?php echo htmlspecialchars($row['expire']); ?></td>
      <td style="mso-number-format:'\#\,\#\#0'; text-align: right;"><?php echo htmlspecialchars($row['amount']); ?></td>
      <td style="text-align: center; text-transform: uppercase;"><?php echo htmlspecialchars($row['payment_mode']); ?></td>
      <td><?php echo htmlspecialchars($row['received_by']); ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</body>
</html>
<?php
exit();
?>
