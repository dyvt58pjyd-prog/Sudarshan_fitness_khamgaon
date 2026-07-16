<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);
$daily_tip_enabled = isset($gym['daily_tip_enabled']) ? intval($gym['daily_tip_enabled']) : 1;

// 1. Handle daily tip scheduler toggle
if (isset($_POST['toggle_scheduler'])) {
    $enabled = intval($_POST['daily_tip_enabled']);
    mysqli_query($con, "UPDATE gym_details SET daily_tip_enabled = $enabled WHERE id = 1");
    echo "<script>alert('Daily tip scheduler settings updated successfully!'); window.location.href='broadcast_campaign.php';</script>";
    exit();
}

// 2. Handle daily tip manual trigger
if (isset($_POST['send_tip_now'])) {
    // Fetch a random motivational tip from gym_tips directly
    $tip_q = mysqli_query($con, "SELECT tip_text, category FROM gym_tips ORDER BY RAND() LIMIT 1");
    if (!$tip_q || mysqli_num_rows($tip_q) === 0) {
        echo "<script>alert('Error: No motivational tips found in database.'); window.location.href='broadcast_campaign.php';</script>";
        exit();
    }
    
    $tip_row = mysqli_fetch_assoc($tip_q);
    $tip_text = $tip_row['tip_text'];
    $category = $tip_row['category'];
    
    // Format the broadcast message
    $gym_name = isset($gym['gym_name']) ? $gym['gym_name'] : 'Titan Gym';
    $broadcast_message = "🌟 *Daily Gym Motivation* - *{$gym_name}* 🌟\n\n" .
                         "{$tip_text}\n\n" .
                         "Category: _{$category}_\n" .
                         "Have a power-packed day! 💪🏋️";
    
    // Query all active member mobile numbers directly
    $today = date('Y-m-d');
    $members_q = mysqli_query($con, "
        SELECT DISTINCT u.mobile 
        FROM users u
        INNER JOIN enrolls_to e ON u.userid = e.uid
        WHERE e.expire >= '$today' 
          AND e.renewal = 'yes'
          AND u.mobile IS NOT NULL 
          AND u.mobile != ''
    ");
    
    $numbers = [];
    if ($members_q && mysqli_num_rows($members_q) > 0) {
        while ($row = mysqli_fetch_assoc($members_q)) {
            $numbers[] = $row['mobile'];
        }
    }
    
    if (empty($numbers)) {
        echo "<script>alert('Error: No active members with registered mobile numbers found.'); window.location.href='broadcast_campaign.php';</script>";
        exit();
    }
    
    // Forward this broadcast directly to the Node service
    $node_url = 'http://127.0.0.1:5001/broadcast';
    $post_payload = json_encode([
        'numbers' => $numbers,
        'message' => $broadcast_message
    ]);
    
    $ch_node = curl_init($node_url);
    curl_setopt($ch_node, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_node, CURLOPT_POST, true);
    curl_setopt($ch_node, CURLOPT_POSTFIELDS, $post_payload);
    curl_setopt($ch_node, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch_node, CURLOPT_TIMEOUT, 10);
    $node_res = curl_exec($ch_node);
    $node_code = curl_getinfo($ch_node, CURLINFO_HTTP_CODE);
    curl_close($ch_node);
    
    if ($node_code === 200) {
        $sent_cnt = count($numbers);
        // Log the tip manual send as a campaign record
        $tip_msg = mysqli_real_escape_string($con, $broadcast_message);
        mysqli_query($con, "INSERT INTO broadcast_campaigns (subject, target_group, message, sent_count) 
                            VALUES ('Manual Daily Tip', 'Active Members Only', '$tip_msg', $sent_cnt)");
        
        echo "<script>alert('Daily motivational tip successfully broadcasted to $sent_cnt active members!'); window.location.href='broadcast_campaign.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to dispatch. WhatsApp service offline or not connected.');</script>";
    }
}

// 3. Handle broadcast campaign submission
if (isset($_POST['send_broadcast'])) {
    $subject = mysqli_real_escape_string($con, $_POST['subject']);
    $target_group = mysqli_real_escape_string($con, $_POST['target_group']);
    $message = mysqli_real_escape_string($con, $_POST['message']);
    
    $flyer_path = '';
    $physical_flyer_path = '';
    
    // Handle flyer upload if exists
    if (isset($_FILES['flyer']) && $_FILES['flyer']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['flyer']['tmp_name'];
        $file_name = $_FILES['flyer']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../Sudarshan Data Folder/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $new_file_name = "flyer_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                $flyer_path = "../../Sudarshan Data Folder/" . $new_file_name;
                // Node needs absolute physical path for sending attachments
                $physical_flyer_path = realpath($target_file);
            }
        }
    }
    
    // Fetch numbers based on target group
    $numbers = [];
    $today = date('Y-m-d');
    
    if ($target_group === 'All Members') {
        $q = mysqli_query($con, "SELECT DISTINCT mobile FROM users WHERE mobile IS NOT NULL AND mobile != ''");
    } elseif ($target_group === 'Active Members Only') {
        $q = mysqli_query($con, "SELECT DISTINCT u.mobile FROM users u INNER JOIN enrolls_to e ON u.userid = e.uid WHERE e.expire >= '$today' AND e.renewal = 'yes' AND u.mobile IS NOT NULL AND u.mobile != ''");
    } elseif ($target_group === 'Expired Members Only') {
        $q = mysqli_query($con, "SELECT DISTINCT u.mobile FROM users u INNER JOIN enrolls_to e ON u.userid = e.uid WHERE e.expire < '$today' AND e.renewal = 'yes' AND u.mobile IS NOT NULL AND u.mobile != ''");
    } elseif ($target_group === 'Personal Trainers Only') {
        $q = mysqli_query($con, "SELECT DISTINCT mobile FROM admin WHERE role = 'trainer' AND mobile IS NOT NULL AND mobile != ''");
    }
    
    if ($q && mysqli_num_rows($q) > 0) {
        while ($row = mysqli_fetch_assoc($q)) {
            $numbers[] = $row['mobile'];
        }
    }
    
    if (empty($numbers)) {
        echo "<script>alert('Error: Selected target group has no valid mobile numbers registered.');</script>";
    } else {
        // Forward broadcast request to WhatsApp service
        $node_url = 'http://127.0.0.1:5001/broadcast';
        $post_payload = json_encode([
            'numbers' => $numbers,
            'message' => $message,
            'filePath' => $physical_flyer_path
        ]);
        
        $ch = curl_init($node_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code === 200) {
            $sent_cnt = count($numbers);
            // Insert campaign log
            mysqli_query($con, "INSERT INTO broadcast_campaigns (subject, target_group, message, attachment_path, sent_count) 
                                VALUES ('$subject', '$target_group', '$message', '$flyer_path', $sent_cnt)");
            
            echo "<script>alert('Broadcast of $sent_cnt messages successfully started in background!'); window.location.href='broadcast_campaign.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to send. WhatsApp service offline or not connected.');</script>";
        }
    }
}

// Fetch broadcast history logs
$history_q = mysqli_query($con, "SELECT * FROM broadcast_campaigns ORDER BY created_at DESC LIMIT 10");
$history = [];
if ($history_q && mysqli_num_rows($history_q) > 0) {
    while ($row = mysqli_fetch_assoc($history_q)) {
        $history[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | WhatsApp Broadcast Campaigns</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#broadcastsettings > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
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
        .settings-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
        }
        .history-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
        }
        .text-muted-premium {
            color: var(--text-muted);
            font-size: 12px;
            margin-top: -10px;
            margin-bottom: 15px;
            display: block;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255,255,255,0.1);
            transition: .4s;
            border-radius: 34px;
            border: 1px solid var(--glass-border);
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--accent-primary);
        }
        input:checked + .slider:before {
            transform: translateX(24px);
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

            <h2>WhatsApp Marketing & Motivation Campaigns</h2>
            <hr />

            <div class="row">
                <!-- Left Column: Scheduler Control & Manual Trigger -->
                <div class="col-md-4">
                    <!-- Scheduler Control Card -->
                    <div class="settings-card">
                        <h3 style="margin-top: 0; color: #ffffff; font-weight: 600; font-size: 16px;">Daily Motivation Scheduler</h3>
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 20px;">
                            When active, the backend daemon sends a daily fitness, nutrition, or recovery tip automatically to all active gym members.
                        </p>
                        
                        <form method="post" action="">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                                <span style="color: var(--text-main); font-weight: 600; font-size: 13px;">Enable Daily Tip Scheduler</span>
                                <label class="switch">
                                    <input type="hidden" name="daily_tip_enabled" value="0">
                                    <input type="checkbox" name="daily_tip_enabled" value="1" <?php echo ($daily_tip_enabled === 1) ? 'checked' : ''; ?> onchange="this.form.submit()">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <input type="hidden" name="toggle_scheduler" value="1">
                        </form>

                        <hr style="border-color: rgba(255,255,255,0.05); margin: 20px 0;" />

                        <h3 style="margin-top: 0; color: #ffffff; font-weight: 600; font-size: 14px;">Instant Dispatch Test</h3>
                        <p style="color: var(--text-muted); font-size: 11px; margin-bottom: 15px;">
                            Click to fetch a random motivational quote and immediately broadcast it to all active members right now.
                        </p>
                        <form method="post" action="">
                            <button type="submit" name="send_tip_now" class="btn btn-primary" style="width: 100%; padding: 10px; font-weight: bold;">
                                <i class="entypo-paper-plane"></i> Send Daily Tip Now
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Broadcast Campaign Composer -->
                <div class="col-md-8">
                    <div class="settings-card">
                        <h3 style="margin-top: 0; color: #ffffff; font-weight: 600; font-size: 18px;">Send Custom Broadcast Message</h3>
                        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                            Send urgent alerts, class cancellations, holiday greetings, or promotional deals with an optional image flyer.
                        </p>

                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <label style="color: var(--text-main); font-weight: 600;">Campaign Subject / Title</label>
                                    <input class="form-control-premium" type="text" name="subject" placeholder="e.g. Christmas Offer / Holiday Alert" required>
                                </div>
                                <div class="col-md-6">
                                    <label style="color: var(--text-main); font-weight: 600;">Target Audience</label>
                                    <select class="form-control-premium" name="target_group" required>
                                        <option value="Active Members Only">Active Members Only (Currently Enrolled)</option>
                                        <option value="All Members">All Members (Active + Expired)</option>
                                        <option value="Expired Members Only">Expired Members Only (Lapsed Enrolls)</option>
                                        <option value="Personal Trainers Only">Personal Trainers Only</option>
                                    </select>
                                </div>
                            </div>

                            <label style="color: var(--text-main); font-weight: 600;">Broadcast Message Content</label>
                            <textarea class="form-control-premium" name="message" rows="5" placeholder="Write your announcement message here... Use *bold* or _italics_ for formatting." required></textarea>

                            <label style="color: var(--text-main); font-weight: 600;">Attach Image Flyer (Optional)</label>
                            <input class="form-control-premium" type="file" name="flyer" accept="image/*">
                            <span class="text-muted-premium">*Accepts JPG, JPEG, PNG, GIF formats only.</span>

                            <div style="text-align: right; margin-top: 15px;">
                                <button type="submit" name="send_broadcast" class="btn btn-primary" style="padding: 12px 30px; font-weight: bold; font-size: 13px;">
                                    <i class="entypo-megaphone"></i> Launch Broadcast Campaign
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Campaign History Log Table -->
            <div class="history-card" style="margin-top: 20px;">
                <h3 style="margin-top: 0; color: #ffffff; font-weight: 600; font-size: 16px; margin-bottom: 20px;">Recent Broadcast Campaigns</h3>
                <div class="table-responsive">
                    <table class="table" style="color: var(--text-main); border-collapse: collapse; width: 100%;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
                                <th style="padding: 12px; color: var(--text-muted);">Sent At</th>
                                <th style="padding: 12px; color: var(--text-muted);">Subject</th>
                                <th style="padding: 12px; color: var(--text-muted);">Target Group</th>
                                <th style="padding: 12px; color: var(--text-muted); width: 45%;">Message Preview</th>
                                <th style="padding: 12px; color: var(--text-muted); text-align: center;">Flyer</th>
                                <th style="padding: 12px; color: var(--text-muted); text-align: center;">Sent Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">No broadcast campaigns sent yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $log): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding: 12px; font-size: 12px; color: var(--text-muted);">
                                            <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($log['created_at']))); ?>
                                        </td>
                                        <td style="padding: 12px; font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($log['subject']); ?></td>
                                        <td style="padding: 12px; font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($log['target_group']); ?></td>
                                        <td style="padding: 12px; font-size: 12px; line-height: 1.4; color: var(--text-main);">
                                            <?php echo htmlspecialchars(substr($log['message'], 0, 100)) . (strlen($log['message']) > 100 ? '...' : ''); ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php if (!empty($log['attachment_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($log['attachment_path']); ?>" target="_blank" class="btn btn-xs btn-default" style="padding: 2px 6px; font-size: 11px;">
                                                    <i class="entypo-picture"></i> View Image
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 11px;">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center; font-weight: bold; color: var(--accent-primary);">
                                            <?php echo intval($log['sent_count']); ?> members
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
