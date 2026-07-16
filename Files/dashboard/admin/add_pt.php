<?php
require '../../include/db_conn.php';
page_protect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Record Personal Training</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" type="text/css" rel="stylesheet">
    <style>
        .page-container .sidebar-menu #main-menu li#pthassubopen > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        #boxx {
            width: 320px !important;
            box-sizing: border-box !important;
        }
        textarea#boxx {
            height: auto !important;
            background: rgba(15, 23, 42, 0.6) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 107, 0, 0.3) !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            transition: all 0.2s ease-in-out !important;
        }
        textarea#boxx:focus {
            border-color: #ff6b00 !important;
            outline: none !important;
            box-shadow: 0 0 8px rgba(255, 107, 0, 0.3) !important;
        }
        .a1-container table td {
            padding: 10px 0 !important;
            vertical-align: middle !important;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <!-- logo -->
                <div class="logo">
                    <a href="main.php">
                        <?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
                <!-- logo collapse icon -->
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

            <h3>Personal Training Session Record</h3>
            <hr />

            <div class="a1-container a1-small a1-padding-32" style="margin-top:2px; margin-bottom:2px;">
                <div class="a1-card-8 a1-light-gray" style="width:620px; margin:0 auto; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 107, 0, 0.2); box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
                    <div class="a1-container a1-dark-gray a1-center">
                        <h6>RECORD PT DATA</h6>
                    </div>
                    <form id="form1" name="form1" method="post" class="a1-container" action="submit_pt.php">
                        <table width="100%" border="0" align="center">
                            <tr>
                                <td height="35">SELECT MEMBER:</td>
                                <td height="35">
                                    <select name="uid" id="boxx" required>
                                        <option value="">--Select Gym Member--</option>
                                        <?php
                                        $q_mem = "SELECT userid, username FROM users ORDER BY username ASC";
                                        $res_mem = mysqli_query($con, $q_mem);
                                        if ($res_mem && mysqli_num_rows($res_mem) > 0) {
                                            while ($row = mysqli_fetch_assoc($res_mem)) {
                                                echo "<option value='".$row['userid']."'>".htmlspecialchars($row['username'])." (ID: ".$row['userid'].")</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td height="35">LOG DATE:</td>
                                <td height="35">
                                    <input type="date" name="date" id="boxx" value="<?php echo date('Y-m-d'); ?>" required />
                                </td>
                            </tr>
                            <tr>
                                <td height="35" valign="top">WORKOUT DETAILS:</td>
                                <td height="35">
                                    <textarea name="workout_details" id="boxx" rows="4" placeholder="Exercises, sets, repetitions, etc."></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td height="35" valign="top">NUTRITION NOTES:</td>
                                <td height="35">
                                    <textarea name="nutrition_notes" id="boxx" rows="4" placeholder="Diet instructions, macro splits, hydration..."></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td height="35" valign="top">TRAINER REMARKS:</td>
                                <td height="35">
                                    <textarea name="trainer_remarks" id="boxx" rows="4" placeholder="Trainer remarks, performance check, form critiques..."></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td height="35" valign="top">ACHIEVEMENTS:</td>
                                <td height="35">
                                    <textarea name="achievements" id="boxx" rows="2" placeholder="PRs hit, physical milestones..."></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td height="35">&nbsp;</td>
                                <td height="35">
                                    <input class="a1-btn a1-blue" type="submit" name="submit" id="submit" value="Log Session" />
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
</body>
</html>
