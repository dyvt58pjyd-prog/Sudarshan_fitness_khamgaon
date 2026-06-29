<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Handle Form Submission
if (isset($_POST['update_locks'])) {
    if (isset($_POST['discount_locks'])) {
        foreach ($_POST['discount_locks'] as $pid => $lock_amount) {
            $pid_esc = mysqli_real_escape_string($con, $pid);
            $lock_val = max(0, intval($lock_amount));
            mysqli_query($con, "UPDATE plan SET discount_lock = $lock_val WHERE pid = '$pid_esc'");
        }
    }
    echo "<script>alert('Discount locks saved successfully!'); window.location.href='discount_lock.php';</script>";
    exit();
}

// Fetch all active plans
$plans_res = mysqli_query($con, "SELECT * FROM plan WHERE active = 'yes' ORDER BY planName ASC");
$plans = [];
if ($plans_res) {
    while ($row = mysqli_fetch_assoc($plans_res)) {
        $plans[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Discount Lock Manager</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#discountlock > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .settings-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: var(--glass-shadow);
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 10px !important;
            width: 100%;
            margin-bottom: 0px !important;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }
        .plan-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .plan-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 60px; max-width: 180px;" />
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
                        <li>Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Discount Lock Configuration</h2>
            <hr />

            <div class="settings-card">
                <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="entypo-lock" style="color: var(--accent-primary);"></i> Set Maximum Allowed Discounts
                </h3>
                <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 25px; line-height: 1.5;">
                    Specify the maximum discount limit (in ₹) that staff/admins can apply when enrolling or renewing a member for each membership plan. Set to <strong>0</strong> to fully disable discounts for a package.
                </p>

                <form method="post" action="">
                    <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 20px; border: 1px solid var(--glass-border); margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; font-weight: 700; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 2px solid rgba(255,255,255,0.08); padding-bottom: 10px; margin-bottom: 10px;">
                            <span>Plan Details</span>
                            <span style="min-width: 200px; text-align: right; padding-right: 15px;">Max Discount Limit (₹)</span>
                        </div>
                        <?php if (count($plans) > 0): ?>
                            <?php foreach ($plans as $p): ?>
                                <div class="plan-row">
                                    <div>
                                        <strong style="color: #ffffff; font-size: 14.5px;"><?php echo htmlspecialchars($p['planName']); ?></strong>
                                        <div style="color: var(--text-muted); font-size: 12px; margin-top: 2px;">
                                            Price: ₹<?php echo number_format($p['amount']); ?> | Validity: <?php echo $p['validity']; ?> Month(s)
                                        </div>
                                    </div>
                                    <div style="width: 200px;">
                                        <input class="form-control-premium" type="number" name="discount_locks[<?php echo htmlspecialchars($p['pid']); ?>]" value="<?php echo intval($p['discount_lock']); ?>" min="0" max="<?php echo intval($p['amount']); ?>" required style="text-align: right;" />
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                                No active membership plans found. Please create plans first.
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($plans) > 0): ?>
                        <div style="text-align: right;">
                            <input class="btn btn-primary" type="submit" name="update_locks" value="Save Lock Constraints" style="width: auto !important; display: inline-block;" />
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
