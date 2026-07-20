<?php
require '../../include/db_conn.php';
page_protect();

if (isset($_POST['mark_called'])) {
    $vid = mysqli_real_escape_string($con, $_POST['visitor_id']);
    mysqli_query($con, "UPDATE visitors SET status='Called' WHERE id='$vid'");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | VISL Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .visl-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #ff6b00;
        }
        .visl-card.called {
            border-left-color: #10b981;
            opacity: 0.7;
        }
        .visl-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .visl-name { font-size: 18px; font-weight: bold; color: #333; }
        .visl-meta { font-size: 13px; color: #888; }
        .visl-details { font-size: 14px; color: #555; }
        .visl-tag {
            display: inline-block;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-top: 10px;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <?php $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png"; ?>
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
            
            <h2>VISL Dashboard (Inquiries)</h2>
            <p>Real-time view of leads captured by the SalesBot.</p>
            <hr/>

            <div class="row">
                <?php
                $q = mysqli_query($con, "SELECT * FROM visitors ORDER BY id DESC");
                if (mysqli_num_rows($q) > 0) {
                    while ($row = mysqli_fetch_assoc($q)) {
                        $is_called = ($row['status'] == 'Called');
                ?>
                    <div class="col-md-6">
                        <div class="visl-card <?php echo $is_called ? 'called' : ''; ?>">
                            <div class="visl-header">
                                <div class="visl-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="visl-meta"><?php echo htmlspecialchars($row['created_at']); ?></div>
                            </div>
                            <div class="visl-details">
                                <div><i class="entypo-phone"></i> <?php echo htmlspecialchars($row['mobile']); ?></div>
                                <div><i class="entypo-mail"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                                <div class="visl-tag">Target: <?php echo htmlspecialchars($row['interest_level']); ?></div>
                            </div>
                            
                            <div style="margin-top: 15px; text-align: right;">
                                <?php if (!$is_called) { ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="visitor_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="mark_called" class="a1-btn" style="background:#10b981; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer;">Mark as Called</button>
                                    </form>
                                <?php } else { ?>
                                    <span style="color: #10b981; font-weight: bold;"><i class="entypo-check"></i> Called</span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    } 
                } else {
                    echo "<div class='col-md-12'><p>No inquiries found yet.</p></div>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
