<?php
require '../../include/db_conn.php';
page_protect();

$uid = null;
if (isset($_POST['userID'])) {
    $uid = mysqli_real_escape_string($con, $_POST['userID']);
} elseif (isset($_GET['userID'])) {
    $uid = mysqli_real_escape_string($con, $_GET['userID']);
}

$name = "";
if ($uid) {
    $query1 = "select * from users WHERE userid='$uid'";
    $result1 = mysqli_query($con, $query1);
    if ($result1 && mysqli_num_rows($result1) > 0) {
        $row1 = mysqli_fetch_assoc($result1);
        $name = $row1['username'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Enroll Personal Training</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" type="text/css" rel="stylesheet">
    <style>
        .page-container .sidebar-menu #main-menu li#paymnt > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        #boxx {
            width: 250px !important;
            box-sizing: border-box !important;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
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
                        <li>Welcome <?php echo $_SESSION['full_name']; ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h3>Personal Training Enrollment</h3>
            <hr />

            <div class="a1-container a1-small a1-padding-32" style="margin-top:2px; margin-bottom:2px;">
                <div class="a1-card-8 a1-light-gray" style="width:550px; margin:0 auto; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 107, 0, 0.2); box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
                    <div class="a1-container a1-dark-gray a1-center">
                        <h6>PT ENROLLMENT FORM</h6>
                    </div>
                    <form id="form1" name="form1" method="post" class="a1-container" action="submit_pt_enroll.php">
                        <table width="100%" border="0" align="center" style="margin-top: 15px;">
                            <?php if ($uid): ?>
                                <tr>
                                    <td height="35">MEMBERSHIP ID:</td>
                                    <td height="35">
                                        <input type="text" name="m_id" id="boxx" value="<?php echo htmlspecialchars($uid); ?>" readonly />
                                    </td>
                                </tr>
                                <tr>
                                    <td height="35">MEMBER NAME:</td>
                                    <td height="35">
                                        <input type="text" name="u_name" id="boxx" value="<?php echo htmlspecialchars($name); ?>" readonly />
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td height="35">SELECT MEMBER:</td>
                                    <td height="35">
                                        <select name="m_id" id="boxx" required>
                                            <option value="">-- Select Member --</option>
                                            <?php
                                            $q_m = "SELECT userid, username FROM users ORDER BY username ASC";
                                            $r_m = mysqli_query($con, $q_m);
                                            if ($r_m && mysqli_num_rows($r_m) > 0) {
                                                while ($row_m = mysqli_fetch_assoc($r_m)) {
                                                    echo "<option value='".$row_m['userid']."'>".htmlspecialchars($row_m['username'])." (ID: ".$row_m['userid'].")</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <td height="35">ASSIGN TRAINER:</td>
                                <td height="35">
                                    <select name="trainer_id" id="boxx" required>
                                        <option value="">-- Select Trainer --</option>
                                        <?php
                                        $q_t = "SELECT username, Full_name FROM admin WHERE role='trainer' ORDER BY Full_name ASC";
                                        $r_t = mysqli_query($con, $q_t);
                                        if ($r_t && mysqli_num_rows($r_t) > 0) {
                                            while ($row_t = mysqli_fetch_assoc($r_t)) {
                                                echo "<option value='".$row_t['username']."'>".htmlspecialchars($row_t['Full_name'])."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td height="35">ENROLL DATE:</td>
                                <td height="35">
                                    <input type="date" name="enroll_date" id="enroll_date" value="<?php echo date('Y-m-d'); ?>" required onchange="calculateExpiryDate()" />
                                </td>
                            </tr>

                            <tr>
                                <td height="35">DURATION:</td>
                                <td height="35">
                                    <select name="duration" id="duration" required onchange="calculateExpiryDate()">
                                        <option value="1">1 Month</option>
                                        <option value="2">2 Months</option>
                                        <option value="3" selected>3 Months</option>
                                        <option value="6">6 Months</option>
                                        <option value="12">12 Months</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td height="35">EXPIRE DATE:</td>
                                <td height="35">
                                    <input type="date" name="expire_date" id="expire_date" value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>" required />
                                </td>
                            </tr>

                            <tr>
                                <td height="35">FEES AMOUNT (₹):</td>
                                <td height="35">
                                    <input type="number" name="amount" id="boxx" placeholder="Enter amount" min="0" required />
                                </td>
                            </tr>

                            <tr>
                                <td height="35">PAYMENT MODE:</td>
                                <td height="35">
                                    <select name="payment_mode" id="boxx" required>
                                        <option value="Cash" selected>Cash</option>
                                        <option value="Card">Card</option>
                                        <option value="UPI">UPI</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td height="35">&nbsp;</td>
                                <td height="35" style="padding-top: 15px;">
                                    <input class="a1-btn a1-blue" type="submit" name="submit" id="submit" value="ENROLL MEMBER" />
                                    <input class="a1-btn a1-blue" type="reset" name="reset" id="reset" value="Reset" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script type="text/javascript">
        function calculateExpiryDate() {
            var enrollDateVal = document.getElementById('enroll_date').value;
            var durationVal = parseInt(document.getElementById('duration').value);
            if (!enrollDateVal) return;

            var d = new Date(enrollDateVal);
            d.setMonth(d.getMonth() + durationVal);

            // Format as YYYY-MM-DD
            var year = d.getFullYear();
            var month = ('0' + (d.getMonth() + 1)).slice(-2);
            var day = ('0' + d.getDate()).slice(-2);

            document.getElementById('expire_date').value = year + '-' + month + '-' + day;
        }
    </script>
</body>
</html>
