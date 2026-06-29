<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'member') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
    exit();
}

$gym = get_gym_details($con);
$userid = $_SESSION['user_data'];

// Fetch all active plans
$plans_query = "SELECT * FROM plan WHERE active = 'yes'";
$plans_res = mysqli_query($con, $plans_query);
$plans = [];
if ($plans_res) {
    while ($row = mysqli_fetch_assoc($plans_res)) {
        $plans[] = $row;
    }
}

// Fetch all active trainers
$trainers_query = "SELECT username, Full_name FROM admin WHERE role='trainer' ORDER BY Full_name ASC";
$trainers_res = mysqli_query($con, $trainers_query);
$trainers = [];
if ($trainers_res) {
    while ($row = mysqli_fetch_assoc($trainers_res)) {
        $trainers[] = $row;
    }
}

// Handle payment form submission (Secure Pending State)
if (isset($_POST['submit_payment'])) {
    $payment_type = mysqli_real_escape_string($con, $_POST['payment_type']);
    $utr = isset($_POST['utr']) ? mysqli_real_escape_string($con, $_POST['utr']) : '';
    
    // Handle screenshot file upload
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['screenshot']['tmp_name'];
        $file_name = $_FILES['screenshot']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../Sudarshan Data Folder/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $new_file_name = "payment_proof_" . $userid . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                
                if ($payment_type === 'membership') {
                    $pid = mysqli_real_escape_string($con, $_POST['plan_id']);
                    
                    // Get plan details
                    $plan_q = mysqli_query($con, "SELECT amount, validity FROM plan WHERE pid = '$pid'");
                    if ($plan_q && mysqli_num_rows($plan_q) > 0) {
                        $plan_data = mysqli_fetch_assoc($plan_q);
                        $amount = intval($plan_data['amount']);
                        $validity = intval($plan_data['validity']);
                        
                        // Check Authenticity Automatically via AI OCR
                        require_once '../../include/auto_verifier.php';
                        $physical_path = __DIR__ . '/../../Sudarshan Data Folder/' . $new_file_name;
                        $is_authentic = verify_payment_screenshot_ai($physical_path, $amount);
                        
                        if ($is_authentic) {
                            // Automatically Approve Payment & Renew Membership
                            date_default_timezone_set("Asia/Calcutta");
                            $cdate = date('Y-m-d');
                            $d = strtotime("+" . $validity . " Months", strtotime($cdate));
                            $expiredate = date("Y-m-d", $d);
                            
                            $payment_mode = 'UPI';
                            $received_by = 'Auto-Approved by AI';
                            
                            // Set old plans to not renewing
                            mysqli_query($con, "UPDATE enrolls_to SET renewal = 'no' WHERE uid = '$userid'");
                            
                            // Insert active subscription
                            mysqli_query($con, "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount) 
                                      VALUES ('$pid', '$userid', '$cdate', '$expiredate', 'yes', '$payment_mode', '$received_by', 0, $amount)");
                                      
                            // Log payment as approved
                            mysqli_query($con, "INSERT INTO payment_requests (uid, pid, amount, screenshot, status, utr) 
                                                VALUES ('$userid', '$pid', $amount, '$target_file', 'approved', '$utr')");
                                                
                            // Send renewal receipt
                            require_once '../../include/smtp_mailer.php';
                            send_member_email($con, $userid, 'renewal');
                            
                            echo "<script>alert('Payment Auto-Verified via AI! Your membership has been instantly renewed.'); window.location.href='index.php';</script>";
                            exit();
                        } else {
                            // Insert into payment_requests as pending
                            mysqli_query($con, "INSERT INTO payment_requests (uid, pid, amount, screenshot, status, utr) 
                                                VALUES ('$userid', '$pid', $amount, '$target_file', 'pending', '$utr')");
                            
                            echo "<script>alert('Payment submitted! Our AI could not automatically verify the screenshot. It has been sent to the Admin for manual approval.'); window.location.href='index.php';</script>";
                            exit();
                        }
                    } else {
                        echo "<script>alert('Invalid plan selected.');</script>";
                    }
                } elseif ($payment_type === 'pt') {
                    $trainer_id = mysqli_real_escape_string($con, $_POST['trainer_id']);
                    $duration = intval($_POST['pt_duration']);
                    
                    $pt_rates = [
                        1 => 3000,
                        2 => 6000,
                        3 => 9000,
                        6 => 18000,
                        12 => 35000
                    ];
                    $amount = isset($pt_rates[$duration]) ? $pt_rates[$duration] : ($duration * 3000);
                    
                    // Use a special prefix for PT plans to identify them in the approval queue
                    $pid = "PT_" . $trainer_id . "_" . $duration;
                    
                    // Check Authenticity Automatically via AI OCR
                    require_once '../../include/auto_verifier.php';
                    $physical_path = __DIR__ . '/../../Sudarshan Data Folder/' . $new_file_name;
                    $is_authentic = verify_payment_screenshot_ai($physical_path, $amount);

                    if ($is_authentic) {
                        // Automatically Approve PT Payment
                        date_default_timezone_set("Asia/Calcutta");
                        $cdate = date('Y-m-d');
                        $d = strtotime("+" . $duration . " Months", strtotime($cdate));
                        $expiredate = date("Y-m-d", $d);
                        
                        $payment_mode = 'UPI';
                        $received_by = 'Auto-Approved by AI';
                        
                        mysqli_query($con, "INSERT INTO pt_enrollments (uid, trainer_id, enroll_date, expire_date, amount, payment_mode, received_by) 
                                            VALUES ('$userid', '$trainer_id', '$cdate', '$expiredate', $amount, '$payment_mode', '$received_by')");
                        
                        mysqli_query($con, "INSERT INTO payment_requests (uid, pid, amount, screenshot, status, utr) 
                                            VALUES ('$userid', '$pid', $amount, '$target_file', 'approved', '$utr')");
                        
                        echo "<script>alert('PT Payment Auto-Verified via AI! Your PT session is now active.'); window.location.href='index.php';</script>";
                        exit();
                    } else {
                        mysqli_query($con, "INSERT INTO payment_requests (uid, pid, amount, screenshot, status, utr) 
                                            VALUES ('$userid', '$pid', $amount, '$target_file', 'pending', '$utr')");
                        
                        echo "<script>alert('PT Payment submitted! Our AI could not automatically verify the screenshot. It has been sent to the Admin for manual approval.'); window.location.href='index.php';</script>";
                        exit();
                    }
                }
            } else {
                echo "<script>alert('Failed to save payment proof screenshot.');</script>";
            }
        } else {
            echo "<script>alert('Invalid screenshot file format. Please upload JPG, PNG, or GIF.');</script>";
        }
    } else {
        echo "<script>alert('Please select and upload a valid payment proof screenshot.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Membership Renewal</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#renew > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .payment-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 35px;
            max-width: 750px;
            margin: 0 auto;
            box-shadow: var(--glass-shadow);
        }
        .qr-section {
            text-align: center;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .qr-image {
            max-width: 220px;
            border-radius: 8px;
            padding: 10px;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: inline-block;
            margin: 15px 0;
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 12px !important;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 15px;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }
        .plan-details {
            background: rgba(255, 107, 0, 0.05);
            border: 1px dashed rgba(255, 107, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }
        .plan-details h4 {
            color: var(--accent-primary);
            margin: 0 0 5px 0;
            font-weight: 700;
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
                            <a href="../admin/logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Membership & PT Renewal</h2>
            <hr />

            <div class="payment-card">
                <!-- Payment Renewal Request Form -->
                <h3 style="margin-top: 0; color: var(--text-main); font-weight: 600; margin-bottom: 10px;">Select Membership or PT Renewal</h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 25px;">
                    Select your preferred renewal package, scan the secure UPI QR code to make payment, and upload the payment proof. Your plan activates immediately!
                </p>

                <form action="" method="post" enctype="multipart/form-data">
                    <label style="color: var(--text-main); font-weight: 600; margin-bottom: 8px; display: block;">Choose Renewal Type</label>
                    <select class="form-control-premium" name="payment_type" id="payment-type" required onchange="toggleFormSection()">
                        <option value="membership">Gym Membership Renewal</option>
                        <option value="pt">Personal Training (PT) Renewal</option>
                    </select>

                    <!-- Membership Form Group -->
                    <div id="membership-group">
                        <label style="color: var(--text-main); font-weight: 600; margin-bottom: 8px; display: block;">Select Membership Plan</label>
                        <select class="form-control-premium" name="plan_id" id="plan-select" onchange="showPlanDetails()">
                            <option value="">-- Choose a package --</option>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['pid']); ?>" data-amount="<?php echo $p['amount']; ?>" data-validity="<?php echo $p['validity']; ?>" data-desc="<?php echo htmlspecialchars($p['description']); ?>">
                                    <?php echo htmlspecialchars($p['planName']); ?> - ₹<?php echo number_format($p['amount']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Personal Training Form Group -->
                    <div id="pt-group" style="display: none;">
                        <label style="color: var(--text-main); font-weight: 600; margin-bottom: 8px; display: block;">Select Personal Trainer</label>
                        <select class="form-control-premium" name="trainer_id" id="trainer-select" onchange="showPTDetails()">
                            <option value="">-- Choose a Trainer --</option>
                            <?php foreach ($trainers as $t): ?>
                                <option value="<?php echo htmlspecialchars($t['username']); ?>">
                                    <?php echo htmlspecialchars($t['Full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label style="color: var(--text-main); font-weight: 600; margin-bottom: 8px; display: block;">Select Duration</label>
                        <select class="form-control-premium" name="pt_duration" id="pt-duration" onchange="showPTDetails()">
                            <option value="1">1 Month - ₹3,000</option>
                            <option value="2">2 Months - ₹6,000</option>
                            <option value="3" selected>3 Months - ₹9,000</option>
                            <option value="6">6 Months - ₹18,000</option>
                            <option value="12">12 Months - ₹35,000</option>
                        </select>
                    </div>

                    <div class="plan-details" id="details-box">
                        <h4 id="details-title">Plan Name</h4>
                        <p id="details-desc" style="color: var(--text-muted); margin: 0 0 10px 0; font-size: 13px;"></p>
                        <div style="font-size: 13px; color: var(--text-main);">
                            Price: <strong style="color: var(--accent-primary);" id="details-price">₹0</strong> | Validity: <strong id="details-validity">0 Months</strong>
                        </div>
                    </div>

                    <div id="qr-payment-area" style="display: none;">
                        <div class="qr-section">
                            <h4 style="color: var(--text-main); font-weight: 600; margin-top: 0;">Scan QR Code to Pay</h4>
                            <p style="color: var(--text-muted); font-size: 13px; margin: 0;">
                                Scan the UPI QR code below and pay the exact amount using any UPI app.
                            </p>
                            
                            <div>
                                <img id="upi-qr-img" class="qr-image" src="<?php echo !empty($gym['payment_qr']) ? htmlspecialchars($gym['payment_qr']) : ''; ?>" alt="Gym Payment QR Code" style="<?php echo (empty($gym['upi_id']) && empty($gym['payment_qr'])) ? 'display:none;' : ''; ?>">
                            </div>

                            <div id="upi-app-link-wrapper" style="display: none; margin: 15px 0;">
                                <div id="android-upi-btn">
                                    <a id="upi-app-link" href="#" class="btn btn-primary" style="display: block; width: 100%; text-align: center; font-weight: 700; padding: 12px; font-size: 14px; background: var(--accent-primary); border-color: var(--accent-primary);">
                                        <i class="entypo-phone"></i> Pay Instantly via UPI App
                                    </a>
                                </div>
                                <div id="ios-upi-btns" style="display: none;">
                                    <p style="font-size: 13px; font-weight: bold; margin-bottom: 8px; text-align: center;">Select your UPI App:</p>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <a id="ios-phonepe" href="#" class="btn btn-default" style="font-weight: bold; color: #5e239d; border-color: #5e239d; display: flex; align-items: center; justify-content: center; gap: 5px;">PhonePe</a>
                                        <a id="ios-gpay" href="#" class="btn btn-default" style="font-weight: bold; color: #ea4335; border-color: #ea4335; display: flex; align-items: center; justify-content: center; gap: 5px;">GPay</a>
                                        <a id="ios-paytm" href="#" class="btn btn-default" style="font-weight: bold; color: #00baf2; border-color: #00baf2; display: flex; align-items: center; justify-content: center; gap: 5px;">Paytm</a>
                                        <a id="ios-other" href="#" class="btn btn-default" style="font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 5px;">Other</a>
                                    </div>
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 5px;">
                                    (Tap an app above to open it)
                                </div>
                            </div>

                            <div id="manual-upi-box" style="display: none; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin: 15px 0; text-align: left;">
                                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px; font-weight: bold;"><i class="entypo-info-circled"></i> Transaction limit exceeded? Copy UPI ID below and pay manually:</p>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="manual-upi-id" class="form-control-premium" style="margin: 0; padding: 10px; font-size: 14px; background: rgba(255,255,255,0.05) !important;" readonly>
                                    <button type="button" class="btn btn-default" onclick="copyUpiId()" style="padding: 10px 15px; font-weight: bold; white-space: nowrap;"><i class="entypo-docs"></i> Copy</button>
                                </div>
                            </div>

                            <div id="no-qr-warning" style="display: none; color: var(--warning); padding: 30px; font-weight: bold;">
                                ⚠️ Payment settings not fully configured by the gym administrator. Please pay in cash or ask support.
                            </div>
                            
                            <div style="font-size: 11px; color: var(--text-muted);">
                                *Secure UPI transaction interface for <?php echo htmlspecialchars($gym['gym_name']); ?>.
                            </div>
                        </div>

                        <div class="form-group-premium" style="margin-top: 20px; margin-bottom: 20px;">
                            <label style="color: var(--text-main); font-weight: 600; margin-bottom: 8px; display: block;">Enter 12-Digit UPI Ref No. / UTR <span style="color:var(--accent-primary)">*</span></label>
                            <input class="form-control-premium" type="text" name="utr" placeholder="e.g. 345678901234" pattern="\d{12}" title="Please enter the exact 12-digit UPI UTR / Transaction reference number" required>
                            <span class="help-block" style="color: var(--text-muted); font-size: 11px; display: block; margin-top: 5px;">
                                *Please enter the exact 12-digit reference number from your UPI receipt.
                            </span>
                        </div>

                        <label style="color: var(--text-main); font-weight: 600; margin-bottom: 8px; display: block;">Upload Payment Screenshot (Receipt) <span style="color:var(--accent-primary)">*</span></label>
                        <input class="form-control-premium" type="file" name="screenshot" accept="image/*" required>
                        <span class="help-block" style="color: var(--text-muted); font-size: 12px; margin-bottom: 25px; display: block;">
                            *Please upload a clear screenshot showing the transaction details (transaction ID, amount, and date).
                        </span>

                        <div style="text-align: right; margin-top: 20px;">
                            <input class="btn btn-primary" type="submit" name="submit_payment" value="Submit Payment Proof & Activate Plan" style="width: auto !important; display: inline-block;">
                        </div>
                    </div>
                </form>
            </div>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>

    <script>
        function toggleFormSection() {
            const payType = document.getElementById('payment-type').value;
            const membershipGroup = document.getElementById('membership-group');
            const ptGroup = document.getElementById('pt-group');
            const detailsBox = document.getElementById('details-box');
            const qrPaymentArea = document.getElementById('qr-payment-area');

            // Reset inputs & hide sections
            detailsBox.style.display = 'none';
            qrPaymentArea.style.display = 'none';

            if (payType === 'membership') {
                membershipGroup.style.display = 'block';
                ptGroup.style.display = 'none';
                document.getElementById('plan-select').required = true;
                document.getElementById('trainer-select').required = false;
                showPlanDetails();
            } else {
                membershipGroup.style.display = 'none';
                ptGroup.style.display = 'block';
                document.getElementById('plan-select').required = false;
                document.getElementById('trainer-select').required = true;
                showPTDetails();
            }
        }

        // Shared helper to generate UPI link & QR
        function loadUpiPayment(amount, orderPrefix) {
            const upiId = "<?php echo isset($gym['upi_id']) ? htmlspecialchars($gym['upi_id']) : ''; ?>";
            const gymName = "<?php echo isset($gym['gym_name']) ? htmlspecialchars($gym['gym_name']) : 'Titan Gym'; ?>";
            const staticQrExists = <?php echo (!empty($gym['payment_qr'])) ? 'true' : 'false'; ?>;

            const qrImg = document.getElementById('upi-qr-img');
            const upiWrapper = document.getElementById('upi-app-link-wrapper');
            const noQrWarning = document.getElementById('no-qr-warning');

            if (upiId !== '') {
                const timestamp = Date.now();
                const cleanUpiId = upiId.trim().replace(/\s+/g, '');
                const cleanAmount = parseFloat(String(amount).replace(/,/g, '')).toFixed(2);
                const isAndroid = /Android/i.test(navigator.userAgent);
                const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
                const isMobile = isAndroid || isIOS || /webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                
                const queryStr = `?pa=${cleanUpiId}&pn=${encodeURIComponent(gymName)}&am=${cleanAmount}&tn=${encodeURIComponent('Paying for Sudarshan Fitness Gym Khamgaon')}&cu=INR`;
                let upiUrl = '';
                if (isAndroid) {
                    upiUrl = `intent://pay${queryStr}#Intent;scheme=upi;end;`;
                } else {
                    upiUrl = `upi://pay${queryStr}`;
                }
                
                try {
                    if (typeof QRious !== 'undefined') {
                        const canvas = document.createElement('canvas');
                        new QRious({
                            element: canvas,
                            value: upiUrl,
                            size: 220,
                            background: 'white',
                            foreground: 'black',
                            level: 'H'
                        });
                        qrImg.src = canvas.toDataURL('image/png');
                    } else {
                        qrImg.src = `https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl=${encodeURIComponent(upiUrl)}`;
                    }
                } catch (e) {
                    qrImg.src = `https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl=${encodeURIComponent(upiUrl)}`;
                }

                qrImg.style.display = 'inline-block';
                
                const manualBox = document.getElementById('manual-upi-box');
                const manualInput = document.getElementById('manual-upi-id');
                if (manualBox && manualInput) {
                    manualInput.value = cleanUpiId;
                    manualBox.style.display = 'block';
                }
                
                if (isMobile) {
                    upiWrapper.style.display = 'block';
                    if (isIOS) {
                        document.getElementById('android-upi-btn').style.display = 'none';
                        document.getElementById('ios-upi-btns').style.display = 'block';
                        
                        document.getElementById('ios-phonepe').href = `phonepe://pay${queryStr}`;
                        document.getElementById('ios-gpay').href = `tez://upi/pay${queryStr}`;
                        document.getElementById('ios-paytm').href = `paytmmp://pay${queryStr}`;
                        document.getElementById('ios-other').href = `upi://pay${queryStr}`;
                    } else {
                        document.getElementById('android-upi-btn').style.display = 'block';
                        document.getElementById('ios-upi-btns').style.display = 'none';
                        const appLink = document.getElementById('upi-app-link');
                        appLink.setAttribute('data-upi-url', upiUrl);
                        appLink.href = upiUrl;
                    }
                } else {
                    upiWrapper.style.display = 'none';
                }
                noQrWarning.style.display = 'none';
            } else if (staticQrExists) {
                qrImg.src = "<?php echo !empty($gym['payment_qr']) ? htmlspecialchars($gym['payment_qr']) : ''; ?>";
                qrImg.style.display = 'inline-block';
                upiWrapper.style.display = 'none';
                noQrWarning.style.display = 'none';
            } else {
                qrImg.style.display = 'none';
                upiWrapper.style.display = 'none';
                noQrWarning.style.display = 'block';
            }
        }

        function showPlanDetails() {
            const select = document.getElementById('plan-select');
            const selectedOpt = select.options[select.selectedIndex];
            const detailsBox = document.getElementById('details-box');
            const qrPaymentArea = document.getElementById('qr-payment-area');

            if (!selectedOpt || selectedOpt.value === '') {
                detailsBox.style.display = 'none';
                qrPaymentArea.style.display = 'none';
                return;
            }

            const amount = selectedOpt.getAttribute('data-amount');
            const validity = selectedOpt.getAttribute('data-validity');
            const desc = selectedOpt.getAttribute('data-desc');
            const name = selectedOpt.text.split(' - ')[0];

            document.getElementById('details-title').innerText = name;
            document.getElementById('details-desc').innerText = desc;
            document.getElementById('details-price').innerText = '₹' + parseInt(amount).toLocaleString('en-IN');
            document.getElementById('details-validity').innerText = validity + ' Month' + (parseInt(validity) > 1 ? 's' : '');

            loadUpiPayment(parseInt(amount), 'SUB');

            detailsBox.style.display = 'block';
            qrPaymentArea.style.display = 'block';
        }

        function showPTDetails() {
            const trainerSelect = document.getElementById('trainer-select');
            const selectedTrainer = trainerSelect.options[trainerSelect.selectedIndex];
            const durationSelect = document.getElementById('pt-duration');
            const duration = parseInt(durationSelect.value);
            
            const detailsBox = document.getElementById('details-box');
            const qrPaymentArea = document.getElementById('qr-payment-area');

            if (!selectedTrainer || selectedTrainer.value === '') {
                detailsBox.style.display = 'none';
                qrPaymentArea.style.display = 'none';
                return;
            }

            const trainerName = selectedTrainer.text.trim();
            const pt_rates = {
                1: 3000,
                2: 6000,
                3: 9000,
                6: 18000,
                12: 35000
            };
            const amount = pt_rates[duration] || (duration * 3000);

            document.getElementById('details-title').innerText = 'Personal Training with ' + trainerName;
            document.getElementById('details-desc').innerText = 'Assigned one-on-one personal fitness coaching with personal trainer ' + trainerName + '.';
            document.getElementById('details-price').innerText = '₹' + amount.toLocaleString('en-IN');
            document.getElementById('details-validity').innerText = duration + ' Month' + (duration > 1 ? 's' : '');

            loadUpiPayment(amount, 'PT');

            detailsBox.style.display = 'block';
            qrPaymentArea.style.display = 'block';
        }

        // Initialize required options
        document.addEventListener("DOMContentLoaded", function() {
            toggleFormSection();
            // Automatically select the first plan and generate the QR code automatically on load
            const select = document.getElementById('plan-select');
            if (select && select.options.length > 1) {
                select.selectedIndex = 1;
                showPlanDetails();
            }
            
            // Programmatic launcher for mobile browsers
            const appLink = document.getElementById('upi-app-link');
            if (appLink) {
                appLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    const upiUrl = this.getAttribute('data-upi-url');
                    if (upiUrl) {
                        window.location.href = upiUrl;
                    }
                });
            }
            
            // Programmatic launcher for iOS buttons
            const iosBtns = ['ios-phonepe', 'ios-gpay', 'ios-paytm', 'ios-other'];
            iosBtns.forEach(id => {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const upiUrl = this.getAttribute('href');
                        if (upiUrl && upiUrl !== '#') {
                            window.location.href = upiUrl;
                        }
                    });
                }
            });
        });
        
        function copyUpiId() {
            var copyText = document.getElementById("manual-upi-id");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            alert("UPI ID Copied: " + copyText.value + "\n\nYou can now open any UPI app and paste this ID to complete the payment.");
        }
    </script>
</body>
</html>
