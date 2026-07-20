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
        body {
            background: #0f172a;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            background-image: radial-gradient(circle at 50% 0%, rgba(255,107,0,0.15) 0%, transparent 70%);
        }
        .qr-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 107, 0, 0.4);
            border-radius: 28px;
            padding: 50px 40px;
            text-align: center;
            max-width: 420px;
            margin: 60px auto;
            box-shadow: 0 30px 60px rgba(0,0,0,0.6), inset 0 2px 0 rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
        }
        .qr-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(90deg, #ff6b00, #f59e0b);
        }
        .qr-title {
            color: #fff;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        .qr-subtitle {
            color: #ff6b00;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 35px;
        }
        .qr-wrapper {
            background: #ffffff;
            padding: 20px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(255, 107, 0, 0.15);
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        .qr-wrapper:hover {
            transform: scale(1.03);
        }
        #qrcode-canvas {
            display: block;
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
                    <div class="qr-subtitle">ID: <?php echo htmlspecialchars($memid); ?></div>
                    <div class="qr-wrapper">
                        <canvas id="qrcode-canvas"></canvas>
                    </div>
                    <div style="color: #94a3b8; font-size: 13px; font-weight: 500; line-height: 1.5;">
                        Present this Digital Pass at the reception scanner for contactless entry.
                    </div>
                </div>

                <script>
                    (function() {
                        var qr = new QRious({
                            element: document.getElementById('qrcode-canvas'),
                            value: '<?php echo $memid; ?>',
                            size: 260,
                            background: 'white',
                            foreground: '#0f172a',
                            padding: 25 // Critical: Adds the white quiet zone required by scanners!
                        });
                    })();
                </script>
            <?php } ?>

        </div>
    </div>
</body>
</html>
