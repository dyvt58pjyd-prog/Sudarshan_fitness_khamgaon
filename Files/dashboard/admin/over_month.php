<?php
require '../../include/db_conn.php';
$month=$_GET['mm'];
$year=$_GET['yy'];
$flag=$_GET['flag'];

$query="";

if($flag==0) {
	$m = intval($month);
	$y = intval($year);
	$start_date = sprintf("%04d-%02d-07", $y, $m);

	if ($m == 12) {
	    $next_m = 1;
	    $next_y = $y + 1;
	} else {
	    $next_m = $m + 1;
	    $next_y = $y;
	}
	$end_date = sprintf("%04d-%02d-06", $next_y, $next_m);
	$query="select * from users u INNER JOIN address a on u.userid=a.id where u.joining_date BETWEEN '".$start_date." 00:00:00' AND '".$end_date." 23:59:59'";
}
else if($flag==1) {
	$query="select * from users u INNER JOIN address a on u.userid=a.id where u.joining_date like '".$year."-%'";
}

$res=mysqli_query($con,$query);
echo "<tbody border=1>";

$sno    = 1;

if ($res && mysqli_num_rows($res) > 0) {

	echo "<thead>
				<tr>
					<th>Sl.No</th>
					<th>Member ID</th>
					<th>Name</th>
					<th>Contact</th>
					<th>Gender</th>
					<th>State</th>
					<th>City</th>
					<th>DOB</th>
					<th>Joining_Date</th>
				</tr>
	</thead>";

    while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
      

                echo "<tr><td>".$sno."</td>";
                
                echo "<td>" . $row['userid'] . "</td>";

                echo "<td width='25%'>" . $row['username'] . "</td>";

                echo "<td>" . $row['mobile'] . "</td>";


                echo "<td>" . $row['gender'] . "</td>";

                echo "<td>" . $row['state'] . "</td>";

                echo "<td>" . $row['city'] . "</td>";

                echo "<td>" . $row['dob'] . "</td>";

                echo "<td>" . $row['joining_date'] ."</td></tr>";
                
                $sno++;
            
        
    }

}
else{
	if($flag==0){
		$monthName = date("F", mktime(0, 0, 0, $month, 10));
		echo "<h2>No Data found On ".$monthName." ".$year."</h2>";
	}
	else if($flag==1)
		echo "<h2>No Data found On ".$year."</h2";
}
echo "</tbody>";


?>
