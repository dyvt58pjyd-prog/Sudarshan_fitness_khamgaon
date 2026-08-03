<?php
require '../../include/db_conn.php';
$month = mysqli_real_escape_string($con, $_GET['mm']);
$year  = mysqli_real_escape_string($con, $_GET['yy']);

$query = "SELECT DISTINCT u.userid, u.username, u.gender, u.mobile,
          u.email, u.joining_date, a.state, a.city,
          e.paid_date, e.expire, p.planName, p.amount AS base_amount,
          e.discount_amount, e.paid_amount, p.validity 
          FROM users u 
          INNER JOIN address a ON u.userid = a.id 
          INNER JOIN enrolls_to e ON u.userid = e.uid
          INNER JOIN plan p ON p.pid = e.pid
          WHERE e.paid_date LIKE '".$year."-".$month."___'
          ORDER BY e.paid_date ASC, u.userid ASC";

$res = mysqli_query($con, $query);
echo "<tbody>";

$sno = 1;
$totalamount = 0;
if ($res && mysqli_num_rows($res) > 0) {

	echo "<thead>
				<tr>
					<th>Sl.No</th>
					<th>Member ID</th>
					<th>Name</th>
					<th>Contact</th>
					<th>Gender</th>
					<th>State</th>
					<th>Paid_Date</th>
					<th>Expire_Date</th>
					<th>Plan_Name</th>
					<th>Amount</th>
					<th>Validity</th>
				</tr>
	</thead>";

    while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
        $base = intval($row['base_amount']);
        $disc = isset($row['discount_amount']) ? intval($row['discount_amount']) : 0;
        
        // Calculate actual paid amount after discount
        if (isset($row['paid_amount']) && $row['paid_amount'] !== null && intval($row['paid_amount']) > 0) {
            $paid = intval($row['paid_amount']);
            // If paid_amount equals base plan price but discount was given, subtract discount
            if ($disc > 0 && $paid === $base) {
                $paid = $base - $disc;
            }
        } else {
            $paid = $base - $disc;
        }
        if ($paid < 0) $paid = 0;

        echo "<tr>";
        echo "<td>" . $sno . "</td>";
        echo "<td>" . htmlspecialchars($row['userid']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
        echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
        echo "<td>" . htmlspecialchars($row['state']) . "</td>";
        echo "<td>" . htmlspecialchars($row['paid_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['expire']) . "</td>";
        echo "<td>" . htmlspecialchars($row['planName']) . "</td>";
        
        // Show amount with discount indicator if applicable
        if ($disc > 0) {
            echo "<td><strong style='color:#10b981;'>" . $paid . "</strong> <span style='font-size:10px; color:#ef4444;' title='Original: ₹{$base} (-₹{$disc} Discount)'>(-₹{$disc})</span></td>";
        } else {
            echo "<td>" . $paid . "</td>";
        }

        echo "<td>" . htmlspecialchars($row['validity']) . " Month</td>";
        echo "</tr>";
        
        $totalamount += $paid;
        $sno++;
    }

 	$monthName = date("F", mktime(0, 0, 0, intval($month), 10));
    echo "<tr><td colspan='11' align='center'><h3>Total Income on " . $monthName . " is ₹" . number_format($totalamount) . "</h3></td></tr>";

} else {
    $monthName = date("F", mktime(0, 0, 0, intval($month), 10));
    echo "<h2>No Data found On " . $monthName . " " . $year . "</h2>";
}
echo "</tbody>";
?>
