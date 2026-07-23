<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] === 'member') {
    header("Location: ../member/");
    exit();
}

$id = '';
if (isset($_REQUEST['name']) && !empty($_REQUEST['name'])) {
    $id = mysqli_real_escape_string($con, $_REQUEST['name']);
} elseif (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
    $id = mysqli_real_escape_string($con, $_REQUEST['id']);
}

// Handle Couple Partner Actions (Add/Link/Unlink)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $primary_uid = mysqli_real_escape_string($con, $_POST['primary_uid']);
    
    if ($_POST['action'] === 'add_new_partner') {
        $p_name = mysqli_real_escape_string($con, trim($_POST['partner_name']));
        $p_gender = mysqli_real_escape_string($con, trim($_POST['partner_gender']));
        $p_mobile = mysqli_real_escape_string($con, trim($_POST['partner_mobile']));
        $p_dob = isset($_POST['partner_dob']) ? mysqli_real_escape_string($con, $_POST['partner_dob']) : '';
        $jdate = date('Y-m-d');
        
        // Generate partner ID
        $res_p_max = mysqli_query($con, "SELECT MAX(CAST(userid AS UNSIGNED)) as maxid FROM users WHERE userid REGEXP '^[0-9]+$'");
        $p_max_row = mysqli_fetch_assoc($res_p_max);
        $partner_uid = ($p_max_row['maxid'] > 100) ? $p_max_row['maxid'] + 1 : 101;
        
        // Insert partner user
        $q_partner = "INSERT INTO users (username, gender, mobile, dob, joining_date, userid, partner_uid, biometric_id, biometric_enabled) 
                      VALUES ('$p_name', '$p_gender', '$p_mobile', '$p_dob', '$jdate', '$partner_uid', '$primary_uid', '$partner_uid', 1)";
        
        if (mysqli_query($con, $q_partner)) {
            // Bi-directionally link primary user to partner
            mysqli_query($con, "UPDATE users SET partner_uid = '$partner_uid' WHERE userid = '$primary_uid'");
            
            // Enroll partner in primary user's active plan if available
            $qp_plan = mysqli_query($con, "SELECT pid, expire, paid_date FROM enrolls_to WHERE uid = '$primary_uid' ORDER BY expire DESC LIMIT 1");
            if ($qp_plan && mysqli_num_rows($qp_plan) > 0) {
                $p_plan_row = mysqli_fetch_assoc($qp_plan);
                $pid = $p_plan_row['pid'];
                $expire = $p_plan_row['expire'];
                $paid_date = $p_plan_row['paid_date'];
                
                $q_partner_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance) 
                                     VALUES ('$pid', '$partner_uid', '$paid_date', '$expire', 'yes', 'Couple Plan Split', 'System', 0, 0, 0)";
                mysqli_query($con, $q_partner_enroll);
            }
            
            // Create login credentials for partner in admin table
            $p_pass = '1234';
            mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$partner_uid', '$p_pass', 'member', '$p_name', 'member') ON DUPLICATE KEY UPDATE Full_name='$p_name'");
            
            echo "<head><script>alert('Partner successfully registered and linked to Member ID $primary_uid!');</script></head></html>";
            echo "<meta http-equiv='refresh' content='0; url=read_member.php?name=" . urlencode($primary_uid) . "'>";
            exit();
        }
    }
    
    if ($_POST['action'] === 'link_existing_partner') {
        $existing_partner_uid = mysqli_real_escape_string($con, trim($_POST['existing_partner_uid']));
        
        $q_chk = mysqli_query($con, "SELECT userid, username FROM users WHERE userid = '$existing_partner_uid' OR mobile = '$existing_partner_uid'");
        if ($q_chk && mysqli_num_rows($q_chk) > 0) {
            $p_found = mysqli_fetch_assoc($q_chk);
            $real_partner_id = $p_found['userid'];
            
            mysqli_query($con, "UPDATE users SET partner_uid = '$real_partner_id' WHERE userid = '$primary_uid'");
            mysqli_query($con, "UPDATE users SET partner_uid = '$primary_uid' WHERE userid = '$real_partner_id'");
            
            $qp_plan = mysqli_query($con, "SELECT pid, expire, paid_date FROM enrolls_to WHERE uid = '$primary_uid' ORDER BY expire DESC LIMIT 1");
            if ($qp_plan && mysqli_num_rows($qp_plan) > 0) {
                $p_plan_row = mysqli_fetch_assoc($qp_plan);
                $pid = $p_plan_row['pid'];
                $expire = $p_plan_row['expire'];
                $paid_date = $p_plan_row['paid_date'];
                
                $chk_partner_enr = mysqli_query($con, "SELECT * FROM enrolls_to WHERE uid = '$real_partner_id' AND pid = '$pid'");
                if (!$chk_partner_enr || mysqli_num_rows($chk_partner_enr) == 0) {
                    $q_partner_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance) 
                                         VALUES ('$pid', '$real_partner_id', '$paid_date', '$expire', 'yes', 'Couple Plan Split', 'System', 0, 0, 0)";
                    mysqli_query($con, $q_partner_enroll);
                }
            }
            
            echo "<head><script>alert('Successfully linked " . addslashes($p_found['username']) . " as Couple Partner!');</script></head></html>";
            echo "<meta http-equiv='refresh' content='0; url=read_member.php?name=" . urlencode($primary_uid) . "'>";
            exit();
        } else {
            echo "<head><script>alert('Member ID or Mobile not found!');</script></head></html>";
            echo "<meta http-equiv='refresh' content='0; url=read_member.php?name=" . urlencode($primary_uid) . "'>";
            exit();
        }
    }
    
    if ($_POST['action'] === 'unlink_partner') {
        $partner_uid = mysqli_real_escape_string($con, $_POST['partner_uid']);
        
        mysqli_query($con, "UPDATE users SET partner_uid = NULL WHERE userid = '$primary_uid'");
        mysqli_query($con, "UPDATE users SET partner_uid = NULL WHERE userid = '$partner_uid'");
        
        echo "<head><script>alert('Couple partner unlinked successfully.');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=read_member.php?name=" . urlencode($primary_uid) . "'>";
        exit();
    }
}

// Query full member info with address and health status using LEFT JOINs
$query = "SELECT u.*, a.streetName, a.city, a.state, a.zipcode, h.calorie, h.height, h.weight, h.fat, h.remarks 
          FROM users u 
          LEFT JOIN address a ON u.userid = a.id 
          LEFT JOIN health_status h ON u.userid = h.uid 
          WHERE u.userid = '$id'";
$result = mysqli_query($con, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<head><script>alert('Member not found!');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=search_member.php'>";
    exit();
}

$member = mysqli_fetch_assoc($result);

// Fetch Couple Partner Data if linked
$partner_data = null;
if (!empty($member['partner_uid'])) {
    $p_id = $member['partner_uid'];
    $qp = mysqli_query($con, "SELECT * FROM users WHERE userid='$p_id'");
    if ($qp && mysqli_num_rows($qp) > 0) {
        $partner_data = mysqli_fetch_assoc($qp);
    }
} else {
    $m_id = $member['userid'];
    $qp = mysqli_query($con, "SELECT * FROM users WHERE partner_uid='$m_id'");
    if ($qp && mysqli_num_rows($qp) > 0) {
        $partner_data = mysqli_fetch_assoc($qp);
    }
}

// Fetch assigned routine / workout package
$routine = null;
if (!empty($member['tid'])) {
    $tid = intval($member['tid']);
    $r_query = mysqli_query($con, "SELECT * FROM timetable WHERE tid = $tid");
    if ($r_query && mysqli_num_rows($r_query) > 0) {
        $routine = mysqli_fetch_assoc($r_query);
    }
}

// Fetch active subscription (latest renewal entry)
$active_plan = null;
$p_query = mysqli_query($con, "SELECT e.*, p.planName, p.description, p.amount, p.validity 
                               FROM enrolls_to e 
                               INNER JOIN plan p ON e.pid = p.pid 
                               WHERE e.uid = '$id' 
                               ORDER BY e.expire DESC LIMIT 1");
if ($p_query && mysqli_num_rows($p_query) > 0) {
    $active_plan = mysqli_fetch_assoc($p_query);
}

// Check if member has or had a Couple Plan
$is_couple_plan = false;
if ($active_plan && !empty($active_plan['planName'])) {
    if (stripos($active_plan['planName'], 'couple') !== false) {
        $is_couple_plan = true;
    }
}
if (!$is_couple_plan) {
    $cp_check = mysqli_query($con, "SELECT p.planName FROM enrolls_to e JOIN plan p ON e.pid = p.pid WHERE e.uid = '$id' AND LOWER(p.planName) LIKE '%couple%' LIMIT 1");
    if ($cp_check && mysqli_num_rows($cp_check) > 0) {
        $is_couple_plan = true;
    }
}

$gym = get_gym_details($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Member Profile</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .page-container .sidebar-menu #main-menu li#searchmem > a {
            background-color: rgba(255, 107, 0, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .profile-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-icon {
            font-size: 36px;
            color: var(--accent-primary);
            background: rgba(255, 107, 0, 0.1);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 107, 0, 0.3);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .info-section {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 107, 0, 0.08);
            border-radius: 16px;
            padding: 20px;
        }
        .info-section h4 {
            color: var(--accent-primary);
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 107, 0, 0.15);
            padding-bottom: 8px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
            font-size: 14px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: var(--text-muted);
            font-weight: 500;
        }
        .detail-value {
            color: var(--text-main);
            font-weight: 600;
        }
        .routine-day-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 107, 0, 0.15);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }
        .routine-day-card h5 {
            color: var(--accent-primary);
            font-weight: 700;
            margin: 0 0 6px 0;
            font-size: 12px;
            text-transform: uppercase;
        }
        .routine-day-card p {
            font-size: 13px;
            color: var(--text-main);
            margin: 0;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-active {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--success);
            color: var(--success);
        }
        .status-expired {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="../../images/logo.png" alt="" style="max-height: 60px; max-width: 180px;" />
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

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Member Profile & History</h2>
                <a href="search_member.php" class="a1-btn" style="background: rgba(255,255,255,0.08) !important; color: #fff !important; text-decoration: none;">&larr; Back to Search</a>
            </div>
            <hr />

            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-title">
                        <?php if (!empty($member['photo'])): ?>
                            <img src="<?php echo str_replace(' ', '%20', htmlspecialchars($member['photo'])); ?>" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-primary); box-shadow: 0 0 10px rgba(255,107,0,0.3);">
                        <?php else: ?>
                            <div class="profile-icon">
                                <i class="entypo-user"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #fff; font-weight: 700;"><?php echo htmlspecialchars($member['username']); ?></h3>
                            <span style="color: var(--text-muted); font-size: 14px;">Membership ID: <strong><?php echo htmlspecialchars($member['userid']); ?></strong></span>
                        </div>
                    </div>
                    <div>
                        <?php 
                        $status_class = 'status-expired';
                        $status_label = 'No Active Plan';
                        if ($active_plan) {
                            $today = date('Y-m-d');
                            if ($active_plan['expire'] >= $today) {
                                $status_class = 'status-active';
                                $status_label = 'Active Plan';
                            } else {
                                $status_class = 'status-expired';
                                $status_label = 'Subscription Expired';
                            }
                        }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                    </div>
                </div>

                <?php if ($partner_data): ?>
                <div style="background: linear-gradient(135deg, rgba(255, 107, 0, 0.15), rgba(255, 107, 0, 0.05)); border: 1px dashed #ff6b00; border-radius: 18px; padding: 20px; margin-top: 20px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span style="font-size: 36px;">💑</span>
                            <div>
                                <span style="color: #ff6b00; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">✨ Couple Plan Partner Linked</span>
                                <h3 style="margin: 4px 0 2px 0; color: #fff; font-weight: 800; font-size: 18px;"><?php echo htmlspecialchars($partner_data['username']); ?></h3>
                                <span style="color: #cbd5e1; font-size: 13px;">Membership ID: <strong style="color: #38bdf8;"><?php echo htmlspecialchars($partner_data['userid']); ?></strong> | Mobile: <strong><?php echo htmlspecialchars($partner_data['mobile']); ?></strong></span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <a href="read_member.php?name=<?php echo urlencode($partner_data['userid']); ?>" class="a1-btn a1-blue" style="font-size: 12px; padding: 8px 16px; text-decoration: none; border-radius: 8px;">View Partner Profile &rarr;</a>
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to unlink this couple partner?');">
                                <input type="hidden" name="action" value="unlink_partner">
                                <input type="hidden" name="primary_uid" value="<?php echo htmlspecialchars($member['userid']); ?>">
                                <input type="hidden" name="partner_uid" value="<?php echo htmlspecialchars($partner_data['userid']); ?>">
                                <button type="submit" class="a1-btn a1-orange" style="font-size: 12px; padding: 8px 14px; border-radius: 8px;">Unlink Partner</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php elseif ($is_couple_plan): ?>
                <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.05)); border: 2px dashed #f59e0b; border-radius: 18px; padding: 22px; margin-top: 20px; margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span style="font-size: 36px;">⚠️</span>
                            <div>
                                <h4 style="margin: 0 0 4px 0; color: #f59e0b; font-weight: 800; font-size: 16px; text-transform: uppercase;">
                                    COUPLE PLAN DETECTED — NO PARTNER LINKED YET
                                </h4>
                                <p style="margin: 0; color: #cbd5e1; font-size: 13px;">
                                    <strong><?php echo htmlspecialchars($member['username']); ?></strong> is subscribed to <strong><?php echo htmlspecialchars($active_plan['planName'] ?? 'Couple Plan'); ?></strong>, but no partner is assigned yet. Register a new partner or link an existing member below!
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Buttons -->
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <button type="button" class="a1-btn a1-blue" id="btn_new_p" onclick="showPForm('new')">+ Register New Partner</button>
                        <button type="button" class="a1-btn a1-orange" id="btn_ex_p" onclick="showPForm('existing')">🔗 Link Existing Member</button>
                    </div>

                    <!-- Form A: Add New Partner -->
                    <div id="form_new_p" style="background: rgba(0,0,0,0.3); border-radius: 12px; padding: 18px; border: 1px solid rgba(255,255,255,0.1);">
                        <h5 style="color: #38bdf8; margin: 0 0 12px 0; font-weight: 700;">Register &amp; Link New Partner Account</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_new_partner">
                            <input type="hidden" name="primary_uid" value="<?php echo htmlspecialchars($member['userid']); ?>">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 15px;">
                                <div>
                                    <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 4px;">Partner Full Name *</label>
                                    <input type="text" name="partner_name" class="form-control-custom" placeholder="e.g. Spouse / Partner Name" required style="width:100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 8px 12px; border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 4px;">Partner Mobile *</label>
                                    <input type="number" name="partner_mobile" class="form-control-custom" placeholder="10-digit Mobile" required style="width:100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 8px 12px; border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 4px;">Gender *</label>
                                    <select name="partner_gender" class="form-control-custom" required style="width:100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 8px 12px; border-radius: 8px;">
                                        <option value="Female">Female</option>
                                        <option value="Male">Male</option>
                                        <option value="Transgender">Transgender</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 4px;">Date of Birth</label>
                                    <input type="date" name="partner_dob" class="form-control-custom" style="width:100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 8px 12px; border-radius: 8px;">
                                </div>
                            </div>
                            <button type="submit" class="a1-btn a1-green" style="padding: 9px 20px;">Create &amp; Link Partner Account</button>
                        </form>
                    </div>

                    <!-- Form B: Link Existing Member -->
                    <div id="form_ex_p" style="display: none; background: rgba(0,0,0,0.3); border-radius: 12px; padding: 18px; border: 1px solid rgba(255,255,255,0.1);">
                        <h5 style="color: #f59e0b; margin: 0 0 12px 0; font-weight: 700;">Link Existing Member ID</h5>
                        <form method="POST" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="link_existing_partner">
                            <input type="hidden" name="primary_uid" value="<?php echo htmlspecialchars($member['userid']); ?>">
                            <div style="flex: 1; min-width: 220px;">
                                <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 4px;">Partner Member ID or Mobile Number</label>
                                <input type="text" name="existing_partner_uid" class="form-control-custom" placeholder="e.g. 204 or Mobile" required style="width:100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 8px 12px; border-radius: 8px;">
                            </div>
                            <button type="submit" class="a1-btn a1-orange" style="padding: 9px 20px;">Link Member as Partner</button>
                        </form>
                    </div>
                </div>

                <script>
                function showPForm(type) {
                    if (type === 'new') {
                        document.getElementById('form_new_p').style.display = 'block';
                        document.getElementById('form_ex_p').style.display = 'none';
                    } else {
                        document.getElementById('form_new_p').style.display = 'none';
                        document.getElementById('form_ex_p').style.display = 'block';
                    }
                }
                </script>
                <?php endif; ?>

                <div class="info-grid">
                    <!-- Column 1: Personal & Contact Information -->
                    <div class="info-section">
                        <h4>Personal & Contact Info</h4>
                        <div class="detail-row">
                            <span class="detail-label">Full Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($member['username']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Gender:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($member['gender']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Mobile Number:</span>
                            <span class="detail-value">
                                <?php echo htmlspecialchars($member['mobile']); ?>
                                <a href="sip:+91<?php echo htmlspecialchars($member['mobile']); ?>" title="Call via BSNL VoIP Softphone" style="margin-left: 8px; color: var(--accent-primary); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: bold; background: rgba(255, 107, 0, 0.15); border: 1px solid rgba(255,107,0,0.3); padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                                    <i class="entypo-phone" style="font-size: 10px;"></i> Call
                                </a>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email Address:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($member['email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date of Birth:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($member['dob']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Joining Date:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($member['joining_date']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value"><?php 
                                $address = [];
                                if (!empty($member['streetName'])) $address[] = $member['streetName'];
                                if (!empty($member['city'])) $address[] = $member['city'];
                                if (!empty($member['state'])) $address[] = $member['state'];
                                if (!empty($member['zipcode'])) $address[] = $member['zipcode'];
                                echo htmlspecialchars(implode(', ', $address));
                            ?></span>
                        </div>
                    </div>

                    <!-- Column 2: Package Information -->
                    <div class="info-section">
                        <h4>Active Membership Package</h4>
                        <?php if ($active_plan): ?>
                            <div class="detail-row">
                                <span class="detail-label">Plan Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($active_plan['planName']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Plan Cost:</span>
                                <span class="detail-value">₹<?php echo htmlspecialchars($active_plan['amount']); ?></span>
                            </div>
                            <?php if (isset($active_plan['discount_amount']) && intval($active_plan['discount_amount']) > 0): ?>
                            <div class="detail-row">
                                <span class="detail-label">Discount Applied:</span>
                                <span class="detail-value" style="color: #ef4444;">- ₹<?php echo htmlspecialchars($active_plan['discount_amount']); ?></span>
                            </div>
                            <?php 
                            $act_price = intval($active_plan['amount']);
                            $act_disc = isset($active_plan['discount_amount']) ? intval($active_plan['discount_amount']) : 0;
                            $act_max = $act_price - $act_disc;
                            $act_paid = isset($active_plan['paid_amount']) && $active_plan['paid_amount'] !== null ? intval($active_plan['paid_amount']) : $act_max;
                            if ($act_paid > $act_max) { $act_paid = $act_max; }
                            ?>
                            <div class="detail-row">
                                <span class="detail-label">Total Paid:</span>
                                <span class="detail-value" style="color: #10b981; font-weight: 700;">₹<?php echo htmlspecialchars($act_paid); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="detail-label">Expiry Date:</span>
                                <span class="detail-value" style="color: var(--accent-primary); font-weight: 700;"><?php echo htmlspecialchars($active_plan['expire']); ?></span>
                            </div>
                        <?php else: ?>
                            <p style="font-size: 13px; color: var(--text-muted); margin: 5px 0 0 0;">No active subscription package record found.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Column 3: Health Metrics & BMI -->
                    <div class="info-section">
                        <h4>Health Metrics & BMI</h4>
                        <?php
                        $height = isset($member['height']) ? $member['height'] : '';
                        $weight = isset($member['weight']) ? $member['weight'] : '';
                        $calorie = isset($member['calorie']) ? $member['calorie'] : '';
                        $fat = isset($member['fat']) ? $member['fat'] : '';
                        $remarks = isset($member['remarks']) ? $member['remarks'] : '';

                        $bmi_val = "--";
                        $bmi_category = "No Data";
                        $bmi_color = "#a3a3a3";

                        if (!empty($height) && !empty($weight) && floatval($height) > 0 && floatval($weight) > 0) {
                            $height_m = floatval($height) / 100;
                            $bmi_val = round(floatval($weight) / ($height_m * $height_m), 1);
                            
                            if ($bmi_val < 18.5) {
                                $bmi_category = "Underweight";
                                $bmi_color = "#38bdf8";
                            } elseif ($bmi_val < 25) {
                                $bmi_category = "Normal Weight";
                                $bmi_color = "#10b981";
                            } elseif ($bmi_val < 30) {
                                $bmi_category = "Overweight";
                                $bmi_color = "#ffb000";
                            } else {
                                $bmi_category = "Obese";
                                $bmi_color = "#ef4444";
                            }
                        }
                        ?>
                        <div class="detail-row">
                            <span class="detail-label">Height:</span>
                            <span class="detail-value"><?php echo !empty($height) ? htmlspecialchars($height) . ' cm' : 'Not Recorded'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Weight:</span>
                            <span class="detail-value"><?php echo !empty($weight) ? htmlspecialchars($weight) . ' kg' : 'Not Recorded'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Body Mass Index (BMI):</span>
                            <span class="detail-value" style="font-weight: 700; color: <?php echo $bmi_color; ?>;">
                                <?php echo $bmi_val; ?> 
                                <?php if ($bmi_category !== 'No Data'): ?>
                                    <span style="font-size: 10px; background: <?php echo $bmi_color; ?>15; border: 1px solid <?php echo $bmi_color; ?>30; padding: 2px 6px; border-radius: 4px; margin-left: 5px;"><?php echo $bmi_category; ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Calorie Target:</span>
                            <span class="detail-value"><?php echo !empty($calorie) ? htmlspecialchars($calorie) . ' kcal' : 'Not Recorded'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Body Fat:</span>
                            <span class="detail-value"><?php echo !empty($fat) ? htmlspecialchars($fat) . '%' : 'Not Recorded'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Remarks:</span>
                            <span class="detail-value"><?php echo !empty($remarks) ? nl2br(htmlspecialchars($remarks)) : 'None'; ?></span>
                        </div>
                        <?php
                        require_once '../../include/bmi_helper.php';
                        $bmi_sug = get_bmi_suggestions($height, $weight);
                        if ($bmi_sug['category'] !== 'No Data'):
                        ?>
                        <div style="margin-top: 20px; border-top: 1px solid rgba(255, 107, 0, 0.2); padding-top: 15px;">
                            <h5 style="color: #ff6b00; font-weight: 700; margin: 0 0 10px 0;">BMI Suggestions & Plan</h5>
                            <div style="font-size: 13px; line-height: 1.4; margin-bottom: 10px;">
                                <strong style="color: #ffffff;">Goal:</strong> <?php echo htmlspecialchars($bmi_sug['goal']); ?>
                            </div>
                            
                            <div style="margin-bottom: 12px;">
                                <strong style="color: #ff6b00; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 4px;">Recommended Workouts:</strong>
                                <div style="font-size: 12px; color: #cbd5e1; background: rgba(255,255,255,0.03); padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); white-space: pre-line;">
                                    <?php echo htmlspecialchars($bmi_sug['workouts']); ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 140px; background: rgba(16, 185, 129, 0.05); padding: 8px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.15);">
                                    <strong style="color: #10b981; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 4px;">Vegetarian Diet:</strong>
                                    <div style="font-size: 11px; color: #cbd5e1; white-space: pre-line;">
                                        <?php echo htmlspecialchars($bmi_sug['veg_diet']); ?>
                                    </div>
                                </div>
                                <div style="flex: 1; min-width: 140px; background: rgba(239, 68, 68, 0.05); padding: 8px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.15);">
                                    <strong style="color: #ef4444; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 4px;">Non-Veg Diet:</strong>
                                    <div style="font-size: 11px; color: #cbd5e1; white-space: pre-line;">
                                        <?php echo htmlspecialchars($bmi_sug['nonveg_diet']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'owner' || $_SESSION['role'] === 'trainer'): ?>
                            <div style="margin-top: 15px; text-align: right;">
                                <a href="bmi_calc.php?uid=<?php echo urlencode($member['userid']); ?>" class="a1-btn a1-blue" style="text-decoration: none; font-size: 11px; padding: 4px 10px !important;">Update BMI</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Workout Routine Section -->
                <div class="info-section" style="margin-top: 30px; background: rgba(255, 107, 0, 0.02); border-color: rgba(255, 107, 0, 0.15);">
                    <h4 style="border-color: rgba(255,107,0,0.25);">Workout Routine Package</h4>
                    <?php if ($routine): ?>
                        <p style="font-size: 14px; margin-bottom: 15px;">Assigned Workout Package: <strong style="color: var(--accent-primary);"><?php echo htmlspecialchars($routine['tname']); ?></strong></p>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                            <div class="routine-day-card">
                                <h5>Monday</h5>
                                <p><?php echo !empty($routine['day1']) ? htmlspecialchars($routine['day1']) : 'Rest Day'; ?></p>
                            </div>
                            <div class="routine-day-card">
                                <h5>Tuesday</h5>
                                <p><?php echo !empty($routine['day2']) ? htmlspecialchars($routine['day2']) : 'Rest Day'; ?></p>
                            </div>
                            <div class="routine-day-card">
                                <h5>Wednesday</h5>
                                <p><?php echo !empty($routine['day3']) ? htmlspecialchars($routine['day3']) : 'Rest Day'; ?></p>
                            </div>
                            <div class="routine-day-card">
                                <h5>Thursday</h5>
                                <p><?php echo !empty($routine['day4']) ? htmlspecialchars($routine['day4']) : 'Rest Day'; ?></p>
                            </div>
                            <div class="routine-day-card">
                                <h5>Friday</h5>
                                <p><?php echo !empty($routine['day5']) ? htmlspecialchars($routine['day5']) : 'Rest Day'; ?></p>
                            </div>
                            <div class="routine-day-card">
                                <h5>Saturday</h5>
                                <p><?php echo !empty($routine['day6']) ? htmlspecialchars($routine['day6']) : 'Rest Day'; ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px 0;">
                            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 10px;">No workout package assigned to this member yet.</p>
                            <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'owner' || $_SESSION['role'] === 'trainer'): ?>
                                <a href="edit_member.php?id=<?php echo urlencode($member['userid']); ?>" class="a1-btn a1-blue" style="text-decoration: none; font-size: 11px;">Assign Routine / Edit Profile</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Payment History Table -->
                <div style="margin-top: 35px;">
                    <h3 style="margin-bottom: 15px; color: #fff; font-weight: 700; font-size: 18px;">Subscription & Payment Ledger</h3>
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Sl.No</th>
                                <th>Plan Name</th>
                                <th>Validity</th>
                                <th>Plan Price</th>
                                <th>Discount</th>
                                <th>Paid Amount</th>
                                <th>Payment Date</th>
                                <th>Expire Date</th>
                                <th>Processed By</th>
                                <th style="text-align: center; width: 180px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_ledger = "SELECT e.*, p.planName, p.amount, p.validity 
                                             FROM enrolls_to e 
                                             INNER JOIN plan p ON e.pid = p.pid 
                                             WHERE e.uid = '$id' 
                                             ORDER BY e.paid_date DESC";
                            $res_ledger = mysqli_query($con, $query_ledger);
                            $sno = 1;
                            
                            if (mysqli_num_rows($res_ledger) > 0) {
                                while ($row = mysqli_fetch_assoc($res_ledger)) {
                                    $p_price = intval($row['amount']);
                                    $p_disc = isset($row['discount_amount']) ? intval($row['discount_amount']) : 0;
                                    $p_max_payable = $p_price - $p_disc;
                                    
                                    $p_paid = isset($row['paid_amount']) && $row['paid_amount'] !== null ? intval($row['paid_amount']) : $p_max_payable;
                                    if ($p_paid > $p_max_payable) {
                                        $p_paid = $p_max_payable;
                                    }

                                    echo "<tr>";
                                    echo "<td style='text-align: center;'>" . $sno . "</td>";
                                    echo "<td style='font-weight: 600; color: var(--accent-primary);'>" . htmlspecialchars($row['planName']) . "</td>";
                                    echo "<td style='text-align: center;'>" . htmlspecialchars($row['validity']) . " Months</td>";
                                    echo "<td style='text-align: right;'>₹" . htmlspecialchars($row['amount']) . "</td>";
                                    echo "<td style='text-align: right; color: #ef4444;'>₹" . htmlspecialchars($p_disc) . "</td>";
                                    echo "<td style='text-align: right; color: #10b981; font-weight: bold;'>₹" . htmlspecialchars($p_paid) . "</td>";
                                    echo "<td style='text-align: center;'>" . htmlspecialchars($row['paid_date']) . "</td>";
                                    echo "<td style='text-align: center;'>" . htmlspecialchars($row['expire']) . "</td>";
                                    echo "<td>" . htmlspecialchars(!empty($row['received_by']) ? $row['received_by'] : 'System') . "</td>";
                                    
                                    echo "<td style='text-align: center;'>";
                                    echo '<a href="gen_invoice.php?id='.$row['uid'].'&pid='.$row['pid'].'&etid='.$row['et_id'].'"><input type="button" class="a1-btn a1-blue" value="Memo" style="margin-right:5px; font-size: 10px; padding: 5px 12px !important;"></a>';
                                    
                                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
                                        echo '<a href="del_payment.php?etid='.$row['et_id'].'&uid='.$row['uid'].'" onclick="return confirm(\'Are you sure you want to delete this payment record?\')"><input type="button" class="a1-btn a1-orange" value="Delete" style="font-size: 10px; padding: 5px 12px !important;"></a>';
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                    $sno++;
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align: center; color: var(--text-muted);'>No payment transactions found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Personal Training Ledger Table -->
                <div style="margin-top: 35px;">
                    <h3 style="margin-bottom: 15px; color: #fff; font-weight: 700; font-size: 18px;">Personal Training Ledger</h3>
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Sl.No</th>
                                <th>Trainer Name</th>
                                <th>Enrollment Date</th>
                                <th>Expiry Date</th>
                                <th>Amount Paid</th>
                                <th>Payment Mode</th>
                                <th>Processed By</th>
                                <th style="text-align: center; width: 180px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_pt_ledger = "SELECT p.*, t.Full_name AS trainer_name 
                                                 FROM pt_enrollments p 
                                                 INNER JOIN admin t ON p.trainer_id = t.username 
                                                 WHERE p.uid = '$id' 
                                                 ORDER BY p.enroll_date DESC";
                            $res_pt_ledger = mysqli_query($con, $query_pt_ledger);
                            $pt_sno = 1;
                            
                            if ($res_pt_ledger && mysqli_num_rows($res_pt_ledger) > 0) {
                                while ($row_pt = mysqli_fetch_assoc($res_pt_ledger)) {
                                    echo "<tr>";
                                    echo "<td style='text-align: center;'>" . $pt_sno . "</td>";
                                    echo "<td style='font-weight: 600; color: var(--accent-primary);'>" . htmlspecialchars($row_pt['trainer_name']) . "</td>";
                                    echo "<td style='text-align: center;'>" . htmlspecialchars($row_pt['enroll_date']) . "</td>";
                                    echo "<td style='text-align: center;'>" . htmlspecialchars($row_pt['expire_date']) . "</td>";
                                    echo "<td style='text-align: right;'>₹" . htmlspecialchars($row_pt['amount']) . "</td>";
                                    echo "<td style='text-align: center;'>" . htmlspecialchars($row_pt['payment_mode']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_pt['received_by']) . "</td>";
                                    
                                    echo "<td style='text-align: center;'>";
                                    echo '<a href="gen_pt_invoice.php?ptid='.$row_pt['pt_id'].'" target="_blank"><input type="button" class="a1-btn a1-blue" value="PT Memo" style="margin-right:5px; font-size: 10px; padding: 5px 12px !important;"></a>';
                                    echo "</td>";
                                    echo "</tr>";
                                    $pt_sno++;
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align: center; color: var(--text-muted);'>No personal training enrollment transactions found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Face Attendance History Section -->
                <div style="margin-top: 35px;">
                    <h3 style="margin-bottom: 15px; color: #fff; font-weight: 700; font-size: 18px;">Face ID Attendance Ledger</h3>
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 60px; text-align: center;">Sl.No</th>
                                <th style="text-align: center;">Date</th>
                                <th style="text-align: center;">Day</th>
                                <th style="text-align: center; color: var(--success);">Time of Entry</th>
                                <th style="text-align: center; color: var(--warning);">Time of Exit</th>
                                <th style="text-align: center;">Duration</th>
                                <th style="text-align: center; width: 120px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_att = "SELECT * FROM attendance WHERE uid = '$id' ORDER BY date DESC, entry_time DESC";
                            $res_att = mysqli_query($con, $query_att);
                            $att_sno = 1;
                            
                            if ($res_att && mysqli_num_rows($res_att) > 0) {
                                while ($row_att = mysqli_fetch_assoc($res_att)) {
                                    $date_val = htmlspecialchars($row_att['date']);
                                    $day_name = date('l', strtotime($date_val));
                                    $entry_time = date('h:i A', strtotime($row_att['entry_time']));
                                    
                                    $exit_time = '--:--';
                                    $duration_text = '--';
                                    $status_label = 'Active In Gym';
                                    $status_class = 'var(--success)';
                                    $status_bg = 'rgba(16, 185, 129, 0.15)';
                                    
                                    if ($row_att['exit_time']) {
                                        $exit_time = date('h:i A', strtotime($row_att['exit_time']));
                                        $status_label = 'Checked Out';
                                        $status_class = 'var(--info)';
                                        $status_bg = 'rgba(59, 130, 246, 0.15)';
                                        
                                        // Calculate duration
                                        $start = new DateTime($row_att['entry_time']);
                                        $end = new DateTime($row_att['exit_time']);
                                        $interval = $start->diff($end);
                                        $duration_text = $interval->format('%h hr %i min');
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td style='text-align: center;'>" . $att_sno . "</td>";
                                    echo "<td style='text-align: center;'>" . date('d-M-Y', strtotime($date_val)) . "</td>";
                                    echo "<td style='text-align: center;'>" . $day_name . "</td>";
                                    echo "<td style='text-align: center; color: var(--success); font-weight: 600;'>" . $entry_time . "</td>";
                                    echo "<td style='text-align: center; color: var(--warning); font-weight: 600;'>" . $exit_time . "</td>";
                                    echo "<td style='text-align: center;'>" . $duration_text . "</td>";
                                    echo "<td style='text-align: center;'>";
                                    echo "<span style='display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; border: 1px solid " . $status_class . "; background: " . $status_bg . "; color: " . $status_class . ";'>" . $status_label . "</span>";
                                    echo "</td>";
                                    echo "</tr>";
                                    $att_sno++;
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align: center; color: var(--text-muted);'>No Face ID check-in/out records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
