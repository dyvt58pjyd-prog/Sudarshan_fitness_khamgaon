<?php
require '../../include/db_conn.php';
page_protect();

$memid = '';
if (isset($_GET['id'])) {
    $memid = mysqli_real_escape_string($con, $_GET['id']);
}

$user_name = "Member";
if (!empty($memid)) {
    $q = mysqli_query($con, "SELECT username FROM users WHERE userid='$memid'");
    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        $user_name = $row['username'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Member QR Code</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    
    <style>
        .qr-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 107, 0, 0.3);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
            margin: 50px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .qr-title {
            color: #fff;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .qr-subtitle {
            color: #ff6b00;
            font-size: 16px;
            margin-bottom: 30px;
        }
        #qrcode-canvas {
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
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
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Contactless Entry - QR Code</h3>
                <a href="view_mem.php" class="a1-btn" style="background: rgba(255,255,255,0.08) !important; color: #fff !important; text-decoration: none;">&larr; Back</a>
            </div>
            <hr/>

            <?php if (empty($memid)) { ?>
                <div class="alert alert-danger">No Member ID provided.</div>
            <?php } else { ?>
                <div class="qr-card">
                    <div class="qr-title"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="qr-subtitle">Member ID: <?php echo htmlspecialchars($memid); ?></div>
                    <canvas id="qrcode-canvas"></canvas>
                    <div style="margin-top: 20px; color: #888; font-size: 12px;">
                        Scan this QR code at the reception for contactless entry.
                    </div>
                </div>

                <script>
                    (function() {
                        var qr = new QRious({
                            element: document.getElementById('qrcode-canvas'),
                            value: '<?php echo $memid; ?>',
                            size: 250,
                            background: 'white',
                            foreground: 'black'
                        });
                    })();
                </script>
            <?php } ?>

        </div>
    </div>
</body>
</html>
