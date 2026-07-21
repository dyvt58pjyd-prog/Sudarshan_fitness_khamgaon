<?php
require '../../include/db_conn.php';
page_protect();

if (isset($_POST['add_partner'])) {
    $primary_uid = mysqli_real_escape_string($con, $_POST['primary_uid']);
    $primary_pid = mysqli_real_escape_string($con, $_POST['primary_pid']);
    $primary_expire = mysqli_real_escape_string($con, $_POST['primary_expire']);
    
    $p_name = mysqli_real_escape_string($con, $_POST['partner_name']);
    $p_gender = mysqli_real_escape_string($con, $_POST['partner_gender']);
    $p_mobile = mysqli_real_escape_string($con, $_POST['partner_mobile']);
    
    // Generate partner ID
    $res_p_max = mysqli_query($con, "SELECT MAX(CAST(userid AS UNSIGNED)) as maxid FROM users WHERE userid REGEXP '^[0-9]+$'");
    $p_max_row = mysqli_fetch_assoc($res_p_max);
    $partner_uid = ($p_max_row['maxid'] > 100) ? $p_max_row['maxid'] + 1 : 101;
    
    $jdate = date('Y-m-d');
    
    // Create partner user
    $q_partner = "INSERT INTO users (username, gender, mobile, joining_date, userid, partner_uid, biometric_id, biometric_enabled) 
                  VALUES ('$p_name', '$p_gender', '$p_mobile', '$jdate', '$partner_uid', '$primary_uid', '$partner_uid', 1)";
    
    if(mysqli_query($con, $q_partner)){
        // Enroll partner
        $q_partner_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance) 
                             VALUES ('$primary_pid', '$partner_uid', '$jdate', '$primary_expire', 'yes', 'Couple Plan Split', 'System', 0, 0, 0)";
        mysqli_query($con, $q_partner_enroll);
        
        // Create login auth
        $p_pass = '1234';
        mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$partner_uid', '$p_pass', 'member', '$p_name', 'member')");
        
        $msg = "Successfully added partner and linked them to $primary_uid!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Link Legacy Couples</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#hassubopen > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        .form-control-custom {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            width: 100%;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">    
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <img src="../../images/logo.png" alt="" width="192" height="80" />
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
                <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome <?php echo $_SESSION['full_name']; ?></li>                            
                        <li><a href="logout.php">Log Out <i class="entypo-logout right"></i></a></li>
                    </ul>
                </div>
            </div>

            <h2>Link Legacy Couple Plans</h2>
            <hr />
            
            <?php if(isset($msg)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.5); color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $msg; ?>
            </div>
            <?php endif; ?>

            <p style="color: #94a3b8; margin-bottom: 20px;">
                The system has scanned your database for members who purchased a "Couple Plan" but do not currently have a partner linked. You can quickly add their partner here to give them their own app login and QR code!
            </p>

            <table class="table table-bordered datatable" id="table-1">
                <thead>
                    <tr>
                        <th>Primary Member</th>
                        <th>Mobile</th>
                        <th>Plan Details</th>
                        <th>Add Missing Partner</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $q = "SELECT u.userid, u.username, u.mobile, p.pid, p.planName, e.expire 
                          FROM users u 
                          JOIN enrolls_to e ON u.userid = e.uid 
                          JOIN plan p ON e.pid = p.pid 
                          WHERE LOWER(p.planName) LIKE '%couple%' 
                          AND (u.partner_uid IS NULL OR u.partner_uid = '') 
                          AND u.userid NOT IN (SELECT partner_uid FROM users WHERE partner_uid IS NOT NULL AND partner_uid != '')
                          AND e.renewal = 'yes'";
                          
                    $res = mysqli_query($con, $q);
                    if ($res && mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['username']) . "</strong><br><small style='color:#888;'>" . $row['userid'] . "</small></td>";
                            echo "<td>" . $row['mobile'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['planName']) . "<br><small style='color:#ff6b00;'>Expires: " . date('d M Y', strtotime($row['expire'])) . "</small></td>";
                            echo "<td>
                                    <form method='POST' style='display:flex; gap:10px; align-items:center; flex-wrap:wrap;'>
                                        <input type='hidden' name='primary_uid' value='" . $row['userid'] . "'>
                                        <input type='hidden' name='primary_pid' value='" . $row['pid'] . "'>
                                        <input type='hidden' name='primary_expire' value='" . $row['expire'] . "'>
                                        
                                        <input type='text' name='partner_name' class='form-control-custom' placeholder='Partner Name' required style='width: 150px;'>
                                        <select name='partner_gender' class='form-control-custom' required style='width: 100px;'>
                                            <option value=''>Gender</option>
                                            <option value='Male'>Male</option>
                                            <option value='Female'>Female</option>
                                        </select>
                                        <input type='number' name='partner_mobile' class='form-control-custom' placeholder='Mobile' required style='width: 120px;'>
                                        
                                        <button type='submit' name='add_partner' class='a1-btn a1-blue' style='padding: 6px 12px; margin: 0;'>Link</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center;'>No unlinked couple plans found.</td></tr>";
                    }
                ?>
                </tbody>
            </table>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
