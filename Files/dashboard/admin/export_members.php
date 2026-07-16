<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    die("Unauthorized access.");
}

$working_year = isset($_SESSION['working_year']) ? intval($_SESSION['working_year']) : intval(date('Y'));

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=gym_members_' . date('Ymd_His') . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Query members filtered by the active operating session/working year
$query = "SELECT u.userid, u.username, u.gender, u.mobile, u.email, u.dob, u.joining_date, a.streetName, a.state, a.city, a.zipcode 
          FROM users u 
          LEFT JOIN address a ON u.userid = a.id 
          WHERE YEAR(u.joining_date) <= $working_year
          ORDER BY u.joining_date DESC";
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
    <x:Name>Gym Members</x:Name>
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
      <th colspan="11" class="title-header">SUDARSHAN FITNESS KHAMGAON - MEMBERS LIST</th>
    </tr>
    <tr>
      <th colspan="11" class="meta-info">Generated: <?php echo date('Y-m-d H:i:s'); ?> | Operating Year Session: <?php echo $working_year; ?></th>
    </tr>
    <tr>
      <th style="width: 120px;">Membership ID</th>
      <th style="width: 180px;">Full Name</th>
      <th style="width: 80px;">Gender</th>
      <th style="width: 120px;">Mobile</th>
      <th style="width: 200px;">Email</th>
      <th style="width: 100px;">Date of Birth</th>
      <th style="width: 100px;">Joining Date</th>
      <th style="width: 220px;">Street Address</th>
      <th style="width: 100px;">City</th>
      <th style="width: 100px;">State</th>
      <th style="width: 80px;">Zipcode</th>
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
      <td style="mso-number-format:'\@'; text-align: center;"><?php echo htmlspecialchars($row['userid']); ?></td>
      <td><?php echo htmlspecialchars($row['username']); ?></td>
      <td style="text-align: center;"><?php echo htmlspecialchars($row['gender']); ?></td>
      <td style="mso-number-format:'\@';"><?php echo htmlspecialchars($row['mobile']); ?></td>
      <td><?php echo htmlspecialchars($row['email']); ?></td>
      <td style="text-align: center;"><?php echo htmlspecialchars($row['dob']); ?></td>
      <td style="text-align: center;"><?php echo htmlspecialchars($row['joining_date']); ?></td>
      <td><?php echo htmlspecialchars($row['streetName']); ?></td>
      <td><?php echo htmlspecialchars($row['city']); ?></td>
      <td><?php echo htmlspecialchars($row['state']); ?></td>
      <td style="mso-number-format:'\@'; text-align: center;"><?php echo htmlspecialchars($row['zipcode']); ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</body>
</html>
<?php
exit();
?>
