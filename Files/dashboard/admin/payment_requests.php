<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    echo "<head><script>alert('Access Denied. Admins and Receptionists only.');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Fetch pending requests using LEFT JOIN because new deferred registrations won't have a user record yet
$query = "SELECT pr.*, u.username, u.mobile FROM payment_requests pr LEFT JOIN users u ON pr.uid = u.userid WHERE pr.status = 'pending' ORDER BY pr.id DESC";
$res = mysqli_query($con, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Payment Approvals</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#payment_requests > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .req-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .req-card img {
            max-height: 120px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid var(--glass-border);
        }
        .req-details {
            flex-grow: 1;
            padding: 0 20px;
        }
        .req-details h4 {
            color: var(--accent-primary);
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        .req-details p {
            color: var(--text-main);
            margin: 2px 0;
            font-size: 13px;
        }
        .req-actions {
            text-align: right;
            min-width: 150px;
        }
        .btn-approve {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
            padding: 8px 15px;
            border-radius: 8px;
            display: block;
            width: 100%;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .btn-approve:hover { background: #10b981; color: white; text-decoration: none; }
        .btn-reject {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 8px 15px;
            border-radius: 8px;
            display: block;
            width: 100%;
            font-weight: bold;
        }
        .btn-reject:hover { background: #ef4444; color: white; text-decoration: none; }
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

            <h2>Pending Payment Approvals</h2>
            <hr />

            <?php if (mysqli_num_rows($res) == 0): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 20px; border-radius: 12px; text-align: center; font-weight: bold;">
                    No pending payment requests! Everything is caught up.
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); margin-bottom: 20px;">Please verify the 12-digit UTR with your PhonePe Business / Bank app before clicking approve.</p>
                <?php while ($row = mysqli_fetch_assoc($res)): ?>
                    <div class="req-card">
                        <div>
                            <?php
                            $clean_path = ltrim($row['screenshot'], './');
                            $url_path = '../../' . $clean_path;
                            ?>
                            <a href="<?php echo htmlspecialchars($url_path); ?>" target="_blank">
                                <img src="<?php echo htmlspecialchars($url_path); ?>" alt="Payment Proof">
                            </a>
                            <div style="text-align: center; font-size: 10px; color: var(--text-muted); margin-top: 5px;">Click to Enlarge</div>
                        </div>
                        <div class="req-details">
                            <?php
                            $disp_name = "";
                            $disp_mobile = "";
                            if ($row['is_new_registration'] == 1 && !empty($row['registration_payload'])) {
                                $payload = json_decode($row['registration_payload'], true);
                                $disp_name = $payload['uname'];
                                $disp_mobile = $payload['phn'];
                            ?>
                                <h4><?php echo htmlspecialchars($disp_name); ?> <span style="color:#f59e0b; font-size: 12px;">(Pending New Reg.)</span></h4>
                            <?php } else { 
                                $disp_name = $row['username'];
                                $disp_mobile = $row['mobile'];
                            ?>
                                <h4><?php echo htmlspecialchars($disp_name); ?> (ID: <?php echo htmlspecialchars($row['uid']); ?>)</h4>
                            <?php } ?>
                            
                            <p><strong>Package ID:</strong> <?php echo htmlspecialchars($row['pid']); ?></p>
                            <p><strong>Amount:</strong> ₹<?php echo number_format($row['amount']); ?></p>
                            <p style="color: var(--accent-primary);"><strong>UTR / Ref:</strong> <?php echo htmlspecialchars($row['utr']); ?></p>
                            <p><strong>Mobile:</strong> <?php echo htmlspecialchars($disp_mobile); ?></p>
                        </div>
                        <div class="req-actions">
                            <button class="btn btn-info" style="width: 100%; margin-bottom: 10px; font-weight: bold; background: #3b82f6; border-color: #3b82f6;" onclick="verifyPayment('<?php echo htmlspecialchars($row['screenshot']); ?>')">
                                <i class="entypo-search"></i> Verify Authenticity
                            </button>
                            <a href="approve_payment.php?id=<?php echo $row['id']; ?>" class="btn-approve" onclick="return confirm('Are you sure you want to approve this payment and instantly activate their membership?');">
                                <i class="entypo-check"></i> Approve
                            </a>
                            <a href="reject_payment.php?id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Are you sure you want to REJECT this payment?');">
                                <i class="entypo-cancel"></i> Reject
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
