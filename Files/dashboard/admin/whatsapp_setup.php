<?php
require '../../include/db_conn.php';
require '../../include/whatsapp_core.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Handle Save Config
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_meta') {
    $phone_id = mysqli_real_escape_string($con, $_POST['phone_number_id']);
    $business_id = mysqli_real_escape_string($con, $_POST['business_account_id']);
    $access_token = mysqli_real_escape_string($con, $_POST['access_token']);

    $check = mysqli_query($con, "SELECT id FROM whatsapp_config LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($con, "UPDATE whatsapp_config SET phone_number_id='$phone_id', business_account_id='$business_id', access_token='$access_token'");
    } else {
        mysqli_query($con, "INSERT INTO whatsapp_config (phone_number_id, business_account_id, access_token) VALUES ('$phone_id', '$business_id', '$access_token')");
    }
    echo "<script>alert('Meta API Credentials Saved Successfully!');</script>";
    echo "<meta http-equiv='refresh' content='0; url=whatsapp_setup.php'>";
    exit();
}

// Handle Test/Manual Send via AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === 'send_manual') {
    header('Content-Type: application/json');
    $raw_post = file_get_contents('php://input');
    $input = json_decode($raw_post, true);
    
    $mobile = isset($input['mobile']) ? $input['mobile'] : '';
    $name = isset($input['name']) ? $input['name'] : '';
    $plan = isset($input['plan']) ? $input['plan'] : '';
    $expire = isset($input['expire']) ? $input['expire'] : '';
    $days = isset($input['days_left']) ? intval($input['days_left']) : 0;
    
    // Check if it's a test message or a real alert
    if (isset($input['is_test']) && $input['is_test']) {
        $msg = $input['message'];
    } else {
        $day_word = ($days === 1) ? "day" : "days";
        $gym_name = $gym['gym_name'];
        $msg = "Hello $name,\n\nYour membership for '$plan' at $gym_name is expiring in $days $day_word on $expire.\n\nPlease renew it soon to continue your fitness journey!\n\nRegards,\n$gym_name";
    }

    $result = send_meta_whatsapp_message($con, $mobile, $msg);
    echo json_encode($result);
    exit();
}

// Get current config
$config = get_whatsapp_config($con);

// Query upcoming expiries
$upcoming = [];
for ($days = 1; $days <= 5; $days++) {
    $target_date = date('Y-m-d', strtotime("+$days days"));
    $q = "SELECT u.username, u.mobile, e.expire, p.planName 
          FROM users u
          INNER JOIN enrolls_to e ON u.userid = e.uid
          INNER JOIN plan p ON e.pid = p.pid
          WHERE e.expire = '$target_date'
            AND e.renewal = 'yes'
            AND e.expire = (
                SELECT MAX(e2.expire) 
                FROM enrolls_to e2 
                WHERE e2.uid = u.userid
            )";
    $res = mysqli_query($con, $q);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $row['days_left'] = $days;
            $upcoming[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Titan Gym | WhatsApp Official API</title>
    <link rel="stylesheet" href="../../neon/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">
    <link rel="stylesheet" href="../../neon/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="../../neon/css/bootstrap.css">
    <link rel="stylesheet" href="../../neon/css/neon-core.css">
    <link rel="stylesheet" href="../../neon/css/neon-theme.css">
    <link rel="stylesheet" href="../../neon/css/neon-forms.css">
    <link rel="stylesheet" href="../../neon/css/custom.css">
    <script src="../../neon/js/jquery-1.11.0.min.js"></script>
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg-dark: #0B0F19;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #F3F4F6;
            --text-muted: #9CA3AF;
        }

        body.page-body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, sans-serif;
        }

        .premium-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-premium-action {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-premium-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        .form-control-premium {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            border-radius: 8px;
            padding: 12px 16px;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 15px;
            transition: border-color 0.2s;
        }

        .form-control-premium:focus {
            border-color: var(--primary);
            outline: none;
        }

        label {
            display: block;
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            gap: 6px;
        }

        .status-connected {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-disconnected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body class="page-body page-fade">
    <div class="page-container">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <img src="../../logo.png" alt="Titan Gym Logo" width="120" style="filter: drop-shadow(0 0 10px rgba(255,255,255,0.2));" />
                    </a>
                </div>
                <div class="sidebar-collapse">
                    <a href="#" class="sidebar-collapse-icon with-animation">
                        <i class="entypo-menu"></i>
                    </a>
                </div>
                <div class="sidebar-mobile-menu visible-xs">
                    <a href="#" class="with-animation">
                        <i class="entypo-menu"></i>
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div class="col-md-6 col-sm-8 clearfix">
                    <h2 style="font-weight: 700; margin: 0; color: #fff; letter-spacing: -0.5px;">
                        <i class="entypo-chat" style="color: #25D366;"></i> WhatsApp Official API
                    </h2>
                </div>
                <div class="col-md-6 col-sm-4 clearfix hidden-xs" style="text-align: right;">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome, <strong><?php echo $_SESSION['full_name']; ?></strong></li>
                        <li class="sep"></li>
                        <li><a href="logout.php"><i class="entypo-logout right"></i> Log Out</a></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <!-- Meta Config Form -->
                <div class="col-md-6">
                    <div class="premium-card">
                        <h4 style="color: var(--text-main); font-weight: 600; margin-bottom: 20px;">
                            Meta API Configuration
                            <?php if ($config && !empty($config['access_token'])): ?>
                                <span class="status-badge status-connected pull-right">Configured</span>
                            <?php else: ?>
                                <span class="status-badge status-disconnected pull-right">Not Configured</span>
                            <?php endif; ?>
                        </h4>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="save_meta">
                            
                            <label>Phone Number ID (From Meta Dashboard)</label>
                            <input type="text" name="phone_number_id" class="form-control-premium" required value="<?php echo $config ? htmlspecialchars($config['phone_number_id']) : ''; ?>" placeholder="e.g. 108395925344444">
                            
                            <label>WhatsApp Business Account ID</label>
                            <input type="text" name="business_account_id" class="form-control-premium" required value="<?php echo $config ? htmlspecialchars($config['business_account_id']) : ''; ?>" placeholder="e.g. 116661701234444">
                            
                            <label>Permanent Access Token (Bearer Token)</label>
                            <textarea name="access_token" class="form-control-premium" style="height: 100px;" required placeholder="EAAL..."><?php echo $config ? htmlspecialchars($config['access_token']) : ''; ?></textarea>
                            
                            <button type="submit" class="btn-premium-action" style="width: 100%; justify-content: center;">
                                <i class="entypo-floppy"></i> Save Credentials
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Test Message Sender -->
                <div class="col-md-6">
                    <div class="premium-card">
                        <h4 style="color: var(--text-main); font-weight: 600; margin-bottom: 20px;">Send Test Message</h4>
                        
                        <label>Receiver Mobile Number (with country code)</label>
                        <input type="text" id="test-mobile" class="form-control-premium" placeholder="e.g. 919876543210">
                        
                        <label>Message Content</label>
                        <textarea id="test-message" class="form-control-premium" style="height: 80px;" placeholder="Hello from Titan Gym!"></textarea>
                        
                        <button class="btn-premium-action" onclick="sendTestMessage()" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #10B981, #059669);">
                            <i class="entypo-paper-plane"></i> Send via Meta Cloud
                        </button>
                        
                        <div id="test-result" style="margin-top: 15px; font-weight: 600; text-align: center;"></div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Expiring Members -->
            <div class="row">
                <div class="col-md-12">
                    <div class="premium-card" style="padding: 0;">
                        <div style="padding: 25px; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="color: var(--text-main); font-weight: 600; margin: 0;">
                                Upcoming Expiries (Next 5 Days)
                            </h4>
                        </div>
                        
                        <div style="overflow-x: auto;">
                            <table class="table table-bordered table-hover" style="margin-bottom: 0; border: none;">
                                <thead style="background: rgba(0,0,0,0.2);">
                                    <tr>
                                        <th style="border-color: var(--glass-border); color: var(--text-muted); padding: 15px;">Member Name</th>
                                        <th style="border-color: var(--glass-border); color: var(--text-muted); padding: 15px;">Mobile</th>
                                        <th style="border-color: var(--glass-border); color: var(--text-muted); padding: 15px;">Plan</th>
                                        <th style="border-color: var(--glass-border); color: var(--text-muted); padding: 15px;">Expiry Date</th>
                                        <th style="border-color: var(--glass-border); color: var(--text-muted); padding: 15px;">Status</th>
                                        <th style="border-color: var(--glass-border); color: var(--text-muted); padding: 15px; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($upcoming)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px; border-color: var(--glass-border);">No members expiring in the next 5 days.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($upcoming as $member): ?>
                                            <tr>
                                                <td style="border-color: var(--glass-border); font-weight: 600; padding: 15px;"><?php echo htmlspecialchars($member['username']); ?></td>
                                                <td style="border-color: var(--glass-border); padding: 15px;"><?php echo htmlspecialchars($member['mobile']); ?></td>
                                                <td style="border-color: var(--glass-border); padding: 15px;"><?php echo htmlspecialchars($member['planName']); ?></td>
                                                <td style="border-color: var(--glass-border); padding: 15px;"><?php echo htmlspecialchars($member['expire']); ?></td>
                                                <td style="border-color: var(--glass-border); padding: 15px;">
                                                    <span class="badge badge-warning" style="background-color: var(--warning); color: #000; padding: 6px 10px; border-radius: 6px;"><?php echo $member['days_left']; ?> days left</span>
                                                </td>
                                                <td style="border-color: var(--glass-border); padding: 15px; text-align: center;">
                                                    <button class="btn btn-primary" style="padding: 6px 12px; border-radius: 6px;" 
                                                            onclick="sendManualAlert('<?php echo htmlspecialchars($member['mobile']); ?>', '<?php echo htmlspecialchars($member['username']); ?>', '<?php echo htmlspecialchars($member['planName']); ?>', '<?php echo htmlspecialchars($member['expire']); ?>', <?php echo $member['days_left']; ?>, this)">
                                                        <i class="entypo-paper-plane"></i> Send Alert
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script>
        async function sendTestMessage() {
            const mobile = document.getElementById('test-mobile').value.trim();
            const message = document.getElementById('test-message').value.trim();
            const resultDiv = document.getElementById('test-result');
            
            if (!mobile || !message) {
                resultDiv.innerHTML = '<span style="color: var(--danger);">Please fill in both fields.</span>';
                return;
            }
            
            resultDiv.innerHTML = '<span style="color: var(--text-muted);"><i class="entypo-arrows-ccw" style="animation: spin 2s linear infinite; display: inline-block; margin-right: 5px;"></i> Dispatching via Meta...</span>';
            
            try {
                const response = await fetch('whatsapp_setup.php?ajax=send_manual', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mobile: mobile, message: message, is_test: true })
                });
                
                const data = await response.json();
                if (response.ok && data.success) {
                    resultDiv.innerHTML = '<span style="color: var(--success);"><i class="entypo-check"></i> ' + data.message + '</span>';
                } else {
                    resultDiv.innerHTML = `<span style="color: var(--danger);">Meta Error: ${data.message}</span>`;
                }
            } catch (err) {
                resultDiv.innerHTML = `<span style="color: var(--danger);">Error connecting to Meta API.</span>`;
            }
        }

        async function sendManualAlert(mobile, name, plan, expire, daysLeft, btnElement) {
            const originalText = btnElement.innerHTML;
            btnElement.disabled = true;
            btnElement.innerHTML = '<i class="entypo-arrows-ccw" style="animation: spin 2s linear infinite; display: inline-block;"></i> Sending...';
            
            try {
                const response = await fetch('whatsapp_setup.php?ajax=send_manual', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        mobile: mobile,
                        name: name,
                        plan: plan,
                        expire: expire,
                        days_left: daysLeft
                    })
                });
                
                const data = await response.json();
                if (response.ok && data.success) {
                    btnElement.innerHTML = '<i class="entypo-check"></i> Sent';
                    btnElement.classList.replace('btn-primary', 'btn-success');
                } else {
                    alert('Meta Error: ' + data.message);
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalText;
                }
            } catch (err) {
                alert('Network error while dispatching.');
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;
            }
        }
    </script>
    <script src="../../neon/js/bootstrap.js"></script>
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
