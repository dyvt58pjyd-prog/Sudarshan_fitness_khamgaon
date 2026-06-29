<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'reception') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Handle Approve Payment Request
if (isset($_POST['approve_id'])) {
    $approve_id = intval($_POST['approve_id']);
    
    // Fetch request details
    $req_q = mysqli_query($con, "SELECT pr.*, u.username, u.email, u.gender FROM payment_requests pr INNER JOIN users u ON pr.uid = u.userid WHERE pr.id = $approve_id");
    if ($req_q && mysqli_num_rows($req_q) > 0) {
        $req = mysqli_fetch_assoc($req_q);
        $uid = $req['uid'];
        $pid = $req['pid'];
        $amount = $req['amount'];
        $email = $req['email'];
        $username = $req['username'];
        $gender = $req['gender'];
        
        // Fetch plan details
        $plan_q = mysqli_query($con, "SELECT planName, validity FROM plan WHERE pid = '$pid'");
        if ($plan_q && mysqli_num_rows($plan_q) > 0) {
            $plan_data = mysqli_fetch_assoc($plan_q);
            $plan_name = $plan_data['planName'];
            $validity = intval($plan_data['validity']);
            
            // Set other plans for this user to renewal = 'no'
            mysqli_query($con, "UPDATE enrolls_to SET renewal = 'no' WHERE uid = '$uid'");
            
            // Calculate active subscription dates
            date_default_timezone_set("Asia/Calcutta");
            $today = date('Y-m-d');
            $cdate = ($today < '2026-07-08') ? '2026-07-08' : $today;
            $d = strtotime("+" . $validity . " Months", strtotime($cdate));
            $expiredate = date("Y-m-d", $d);
            
            $payment_mode = 'UPI';
            $received_by = 'Approved by ' . (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin');
            
            // Insert active subscription into enrolls_to
            $ins_q = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount) 
                      VALUES ('$pid', '$uid', '$cdate', '$expiredate', 'yes', '$payment_mode', '$received_by', 0, $amount)";
            
            if (mysqli_query($con, $ins_q)) {
                // Update payment request status to approved
                mysqli_query($con, "UPDATE payment_requests SET status = 'approved' WHERE id = $approve_id");
                
                // Rotate/generate gate entry code
                $new_entry_code = strval(rand(100000, 999999));
                mysqli_query($con, "UPDATE users SET entry_code = '$new_entry_code' WHERE userid = '$uid'");
                
                // Check if this is their first subscription or a renewal
                $check_q = mysqli_query($con, "SELECT COUNT(*) as cnt FROM enrolls_to WHERE uid = '$uid'");
                $row_check = mysqli_fetch_assoc($check_q);
                $is_new = ($row_check['cnt'] <= 1);
                
                if ($is_new) {
                    // Fetch user chosen password
                    $pass_q = mysqli_query($con, "SELECT pass_key FROM admin WHERE username = '$uid'");
                    $pass_row = mysqli_fetch_assoc($pass_q);
                    $password = $pass_row['pass_key'];
                    
                    // Send welcome confirmation email (which auto-sends welcome WhatsApp message with PDF receipt)
                    send_member_email($con, $email, $username, $uid, $password, $plan_name, $amount, $expiredate, $new_entry_code, 0, $amount, $gender);
                } else {
                    // Send payment confirmation email (which auto-sends payment WhatsApp message with PDF receipt)
                    send_payment_email($con, $email, $username, $uid, $plan_name, $amount, $expiredate, $payment_mode, $received_by, $new_entry_code, 0, $amount);
                }
                
                echo "<script>alert('Payment request approved and membership plan activated!'); window.location.href='online_payments_records.php';</script>";
                exit();
            }
        }
    }
}

// Handle Reject Payment Request
if (isset($_POST['reject_id'])) {
    $reject_id = intval($_POST['reject_id']);
    
    // Optional: delete screenshot file to save space
    $file_q = mysqli_query($con, "SELECT screenshot FROM payment_requests WHERE id = $reject_id");
    if ($file_q && $file_row = mysqli_fetch_assoc($file_q)) {
        $screenshot_path = $file_row['screenshot'];
        // resolve physical path
        $physical_path = __DIR__ . '/../../' . str_replace('../../', '', $screenshot_path);
        if (file_exists($physical_path)) {
            @unlink($physical_path);
        }
    }
    
    // Update status to rejected
    mysqli_query($con, "UPDATE payment_requests SET status = 'rejected' WHERE id = $reject_id");
    echo "<script>alert('Payment request rejected.'); window.location.href='online_payments_records.php';</script>";
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

// Fetch pending online payments
$q_pending = "SELECT pr.*, u.username, u.email, u.mobile, p.planName 
              FROM payment_requests pr
              INNER JOIN users u ON pr.uid = u.userid
              INNER JOIN plan p ON pr.pid = p.pid
              WHERE pr.status = 'pending'
              ORDER BY pr.submitted_at ASC";
$res_pending = mysqli_query($con, $q_pending);

// Fetch approved online payments with search filtering
$q_list = "SELECT pr.*, u.username, u.email, u.mobile, p.planName 
           FROM payment_requests pr
           INNER JOIN users u ON pr.uid = u.userid
           INNER JOIN plan p ON pr.pid = p.pid
           WHERE pr.status = 'approved'";

if (!empty($search)) {
    $q_list .= " AND (pr.uid LIKE '%$search%' 
                     OR u.username LIKE '%$search%' 
                     OR p.planName LIKE '%$search%' 
                     OR DATE_FORMAT(pr.submitted_at, '%Y-%m-%d') LIKE '%$search%')";
}

$q_list .= " ORDER BY pr.submitted_at DESC";
$res_list = mysqli_query($con, $q_list);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Online Payments Records</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#online_paymnt_records > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .portal-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 10px !important;
            width: 100%;
            margin-bottom: 15px;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }
        .proof-thumbnail {
            max-height: 80px;
            border-radius: 6px;
            border: 1px solid var(--glass-border);
            cursor: pointer;
            transition: transform 0.2s;
        }
        .proof-thumbnail:hover {
            transform: scale(1.05);
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

            <h2>Pending Registration & Payment Approvals</h2>
            <hr />

            <div class="portal-card" style="margin-bottom: 40px;">
                <div class="table-responsive">
                    <table class="table" style="width: 100%; border-collapse: collapse; color: var(--text-main);">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Submitted At</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Member Details</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Plan Details</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Amount</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600; text-align: center;">Payment Receipt</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pending_count = 0;
                            if ($res_pending && mysqli_num_rows($res_pending) > 0) {
                                while ($row_p = mysqli_fetch_assoc($res_pending)) {
                                    $pending_count++;
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding: 15px 12px; font-size: 13px; color: var(--text-muted);">
                                            <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row_p['submitted_at']))); ?>
                                        </td>
                                        <td style="padding: 15px 12px;">
                                            <strong style="color: #ffffff; display: block;"><?php echo htmlspecialchars($row_p['username']); ?></strong>
                                            <span style="font-size: 12px; color: var(--text-muted); display: block;">ID: <?php echo htmlspecialchars($row_p['uid']); ?></span>
                                            <span style="font-size: 12px; color: var(--text-muted); display: block;">Email: <?php echo htmlspecialchars($row_p['email']); ?></span>
                                            <span style="font-size: 12px; color: var(--text-muted); display: block;">Mobile: <?php echo htmlspecialchars($row_p['mobile']); ?></span>
                                            <?php if (!empty($row_p['utr'])): ?>
                                                <span style="font-size: 11px; margin-top: 5px; display: inline-block; background: rgba(255, 107, 0, 0.15); color: var(--accent-primary); border: 1px solid rgba(255, 107, 0, 0.3); padding: 2px 6px; border-radius: 4px; font-weight: bold; font-family: monospace;">UTR: <?php echo htmlspecialchars($row_p['utr']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 15px 12px; font-weight: 600;">
                                            <?php echo htmlspecialchars($row_p['planName']); ?>
                                        </td>
                                        <td style="padding: 15px 12px; font-weight: 700; color: var(--accent-primary); font-size: 15px;">
                                            ₹<?php echo number_format($row_p['amount']); ?>
                                        </td>
                                        <td style="padding: 15px 12px; text-align: center;">
                                            <?php 
                                            $clean_path = ltrim($row_p['screenshot'], './');
                                            $physical_path = __DIR__ . '/../../' . $clean_path;
                                            $url_path = '../../' . $clean_path;
                                            
                                            if (!empty($row_p['screenshot']) && file_exists($physical_path)): ?>
                                                <img class="proof-thumbnail" src="<?php echo htmlspecialchars($url_path); ?>" alt="Proof Screenshot" onclick="showModal('<?php echo htmlspecialchars($url_path); ?>')">
                                            <?php else: ?>
                                                <span style="color: var(--danger); font-size: 12px;">File missing or deleted</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 15px 12px; text-align: center; vertical-align: middle;">
                                            <div style="display: inline-flex; gap: 8px;">
                                                <form action="" method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to APPROVE this registration & payment?');">
                                                    <input type="hidden" name="approve_id" value="<?php echo $row_p['id']; ?>">
                                                    <button type="submit" class="btn btn-success" style="font-weight: 600; padding: 6px 12px; border-radius: 6px; background-color: var(--success); border-color: var(--success); color: #000000; font-size: 12px;">Approve</button>
                                                </form>
                                                <form action="" method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to REJECT this registration & payment?');">
                                                    <input type="hidden" name="reject_id" value="<?php echo $row_p['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="font-weight: 600; padding: 6px 12px; border-radius: 6px; background-color: var(--danger); border-color: var(--danger); color: #ffffff; font-size: 12px;">Reject</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            if ($pending_count === 0) {
                                ?>
                                <tr>
                                    <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                        No pending registration or payment approval requests found.
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <h2>Approved Online Payments Records</h2>
            <hr />

            <!-- Search Panel -->
            <form method="get" action="" style="margin-bottom: 25px;">
                <div style="display: flex; gap: 10px; max-width: 600px; align-items: center;">
                    <input class="form-control-premium" type="text" name="search" placeholder="Search by ID, Member Name, Plan, Date..." value="<?php echo htmlspecialchars($search); ?>" style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-weight: 600; background: var(--accent-primary); border-color: var(--accent-primary); color: #000000; height: 42px;">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="online_payments_records.php" class="btn btn-default" style="padding: 0 20px; display: inline-flex; align-items: center; justify-content: center; height: 42px; text-decoration: none;">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="portal-card">
                <div class="table-responsive">
                    <table class="table" style="width: 100%; border-collapse: collapse; color: var(--text-main);">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Transaction Date</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Member Details</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Plan Details</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Amount Paid</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600; text-align: center;">Payment Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $count = 0;
                            if ($res_list && mysqli_num_rows($res_list) > 0) {
                                while ($row = mysqli_fetch_assoc($res_list)) {
                                    $count++;
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding: 15px 12px; font-size: 13px; color: var(--text-muted);">
                                            <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['submitted_at']))); ?>
                                        </td>
                                        <td style="padding: 15px 12px;">
                                            <strong style="color: #ffffff; display: block;"><?php echo htmlspecialchars($row['username']); ?></strong>
                                            <span style="font-size: 12px; color: var(--text-muted); display: block;">ID: <?php echo htmlspecialchars($row['uid']); ?></span>
                                            <span style="font-size: 12px; color: var(--text-muted); display: block;">Email: <?php echo htmlspecialchars($row['email']); ?></span>
                                            <?php if (!empty($row['utr'])): ?>
                                                <span style="font-size: 11px; margin-top: 5px; display: inline-block; background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.3); padding: 2px 6px; border-radius: 4px; font-weight: bold; font-family: monospace;">UTR: <?php echo htmlspecialchars($row['utr']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 15px 12px; font-weight: 600;">
                                            <?php echo htmlspecialchars($row['planName']); ?>
                                        </td>
                                        <td style="padding: 15px 12px; font-weight: 700; color: var(--accent-primary); font-size: 15px;">
                                            ₹<?php echo number_format($row['amount']); ?>
                                        </td>
                                        <td style="padding: 15px 12px; text-align: center;">
                                            <?php 
                                            $clean_path = ltrim($row['screenshot'], './');
                                            $physical_path = __DIR__ . '/../../' . $clean_path;
                                            $url_path = '../../' . $clean_path;
                                            
                                            if (!empty($row['screenshot']) && file_exists($physical_path)): ?>
                                                <img class="proof-thumbnail" src="<?php echo htmlspecialchars($url_path); ?>" alt="Proof Screenshot" onclick="showModal('<?php echo htmlspecialchars($url_path); ?>')">
                                            <?php else: ?>
                                                <span style="color: var(--danger); font-size: 12px;">File missing or deleted</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            if ($count === 0) {
                                ?>
                                <tr>
                                    <td colspan="5" style="padding: 35px; text-align: center; color: var(--text-muted);">
                                        <i class="entypo-info-circled" style="font-size: 32px; display: block; margin-bottom: 15px;"></i>
                                        No online payments screenshot records found.
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <!-- Modal for Viewing Proof in Full Size -->
    <div id="proofModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.85); backdrop-filter: blur(8px); justify-content: center; align-items: center;">
        <span onclick="closeModal()" style="position: absolute; top: 20px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
        <img id="modalImg" style="margin: auto; display: block; max-width: 90%; max-height: 90%; border-radius: 12px; box-shadow: 0 4px 25px rgba(0,0,0,0.5);">
    </div>

    <script>
    function showModal(src) {
        document.getElementById("modalImg").src = src;
        document.getElementById("proofModal").style.display = "flex";
    }
    function closeModal() {
        document.getElementById("proofModal").style.display = "none";
    }
    window.onclick = function(event) {
        var modal = document.getElementById("proofModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    </script>
</body>
</html>
