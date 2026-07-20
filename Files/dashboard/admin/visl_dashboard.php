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
        body {
            background: #0f172a;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            background-image: radial-gradient(circle at 100% 0%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
                              radial-gradient(circle at 0% 100%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
        }
        h2 {
            color: #fff;
            font-weight: 800;
            font-size: 28px;
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }
        p {
            color: #94a3b8;
            font-size: 15px;
        }
        hr { border-color: rgba(255,255,255,0.05); }

        .visl-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.8) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border-left: 6px solid #0ea5e9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .visl-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            border-left-color: #38bdf8;
        }
        .visl-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, transparent 100%);
            pointer-events: none;
        }
        .visl-card.called {
            border-left-color: #10b981;
            opacity: 0.6;
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.5) 100%);
        }
        .visl-card.called:hover {
            transform: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .visl-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .visl-name { 
            font-size: 20px; 
            font-weight: 700; 
            color: #fff; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .visl-meta { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        
        .visl-body {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .visl-photo {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(14, 165, 233, 0.4);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            background: rgba(0,0,0,0.2);
        }
        
        .visl-details { flex-grow: 1; font-size: 14px; color: #cbd5e1; line-height: 1.6; }
        .visl-details i { color: #0ea5e9; margin-right: 8px; width: 16px; text-align: center; }
        .visl-tag {
            display: inline-block;
            background: rgba(14, 165, 233, 0.15);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            color: #38bdf8;
            margin-top: 15px;
            border: 1px solid rgba(14, 165, 233, 0.3);
        }
        .visl-card.called .visl-tag {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }
        .btn-mark-called {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            padding: 8px 20px;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            transition: all 0.2s ease;
        }
        .btn-mark-called:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: #fff;
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
                            <div class="visl-body">
                                <?php if (!empty($row['photo_url'])) { ?>
                                    <img src="../../<?php echo htmlspecialchars($row['photo_url']); ?>" alt="Visitor Photo" class="visl-photo">
                                <?php } else { ?>
                                    <div class="visl-photo" style="display:flex; align-items:center; justify-content:center; color:#64748b;">
                                        <i class="entypo-user" style="font-size:32px;"></i>
                                    </div>
                                <?php } ?>
                                
                                <div class="visl-details">
                                    <div><i class="entypo-phone"></i> <?php echo htmlspecialchars($row['mobile']); ?></div>
                                    <div><i class="entypo-mail"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                                    <?php if (!empty($row['address'])) { ?>
                                        <div><i class="entypo-location"></i> <?php echo htmlspecialchars($row['address']); ?></div>
                                    <?php } ?>
                                    <div class="visl-tag">Target: <?php echo htmlspecialchars($row['interest_level']); ?></div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 15px; text-align: right;">
                                <?php if (!$is_called) { ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="visitor_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="mark_called" class="btn-mark-called">Mark as Called</button>
                                    </form>
                                <?php } else { ?>
                                    <span style="color: #34d399; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="entypo-check"></i> Called</span>
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
