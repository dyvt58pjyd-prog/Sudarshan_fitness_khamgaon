<?php
require '../../include/db_conn.php';
page_protect();
$gym = get_gym_details($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Monthly Income & Auditing Separator</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#overviewhassubopen > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        .filter-select {
            background: #0f172a;
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            margin-right: 8px;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar(); showMember();">

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

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: #fff;">📊 Monthly Income &amp; Auditing Breakdown</h2>
                    <p style="color: #94a3b8; font-size: 13px; margin-top: 4px;">Audited monthly collection breakdown with dedicated <strong>💳 UPI Monthly</strong> and <strong>💵 Cash Monthly</strong> separators.</p>
                </div>
                <div>
                    <a href="export_payments.php" class="a1-btn a1-green" style="font-size: 12px; font-weight: bold; border-radius: 8px;">
                        <i class="entypo-download"></i> Export Audit Report
                    </a>
                </div>
            </div>

            <!-- Filter Controls Form -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 18px 20px; margin-bottom: 25px;">
                <form onsubmit="event.preventDefault(); showMember();" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <?php
                    $yearArray = range(2000, max(intval(date('Y')), isset($_SESSION['working_year']) ? $_SESSION['working_year'] : date('Y')));
                    ?>
                    <label style="color: #cbd5e1; font-size: 13px; font-weight: 700; margin: 0;">Year:</label>
                    <select name="year" id="syear" class="filter-select" onchange="showMember();">
                        <option value="0">Select Year</option>
                        <?php
                        foreach ($yearArray as $year) {
                            $selected = ($year == date('Y')) ? 'selected' : '';
                            echo '<option '.$selected.' value="'.$year.'">'.$year.'</option>';
                        }
                        ?>
                    </select>

                    <?php
                    $formattedMonthArray = array(
                        "01" => "January", "02" => "February", "03" => "March", "04" => "April",
                        "05" => "May", "06" => "June", "07" => "July", "08" => "August",
                        "09" => "September", "10" => "October", "11" => "November", "12" => "December",
                    );
                    ?>
                    <label style="color: #cbd5e1; font-size: 13px; font-weight: 700; margin: 0;">Month:</label>
                    <select name="month" id="smonth" class="filter-select" onchange="showMember();">
                        <option value="0">Select Month</option>
                        <?php
                        foreach ($formattedMonthArray as $mm => $month) {
                            $selected = ($mm == date('m')) ? 'selected' : '';
                            echo '<option '.$selected.' value="'.$mm.'">'.$month.'</option>';
                        }
                        ?>
                    </select>

                    <button type="button" class="a1-btn a1-blue" style="font-weight: bold; border-radius: 10px; padding: 8px 18px;" onclick="showMember();">
                        <i class="entypo-search"></i> Load Audit Ledger
                    </button>
                </form>
            </div>

            <!-- Dynamic Result Area -->
            <div id="memmonth" style="min-height: 200px;">
                <div style="text-align: center; color: #94a3b8; padding: 40px;">
                    <i class="entypo-arrows-ccw" style="font-size: 28px;"></i>
                    <p style="margin-top: 10px;">Loading monthly income &amp; payment separators...</p>
                </div>
            </div>

            <script>
            let currentFilterMode = 'all';

            function filterLedgerMode(mode) {
                currentFilterMode = mode;
                showMember();
            }

            function showMember() {
                var year = document.getElementById("syear");
                var month = document.getElementById("smonth");
                if (!year || !month) return;
                
                var iyear = year.selectedIndex;
                var imonth = month.selectedIndex;
                var mnumber = month.options[imonth].value;
                var ynumber = year.options[iyear].value;

                if (mnumber == "0" || ynumber == "0") {
                    document.getElementById("memmonth").innerHTML = "<div style='color:#ef4444; padding:20px; font-weight:bold;'>Please select a valid Month and Year.</div>";
                    return;
                }

                var container = document.getElementById("memmonth");
                container.style.opacity = '0.6';

                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        container.innerHTML = this.responseText;
                        container.style.opacity = '1';
                    }
                };
                xmlhttp.open("GET", "income_month.php?mm=" + encodeURIComponent(mnumber) + "&yy=" + encodeURIComponent(ynumber) + "&mode=" + encodeURIComponent(currentFilterMode), true);
                xmlhttp.send();
            }
            </script>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
