<?php
require './include/db_conn.php';

$gym = get_gym_details($con);

// Fetch all active plans
$plans_query = "SELECT * FROM plan WHERE active = 'yes' AND pid != 'PTPLAN'";
$plans_res = mysqli_query($con, $plans_query);
$plans = [];
if ($plans_res) {
    while ($row = mysqli_fetch_assoc($plans_res)) {
        $plans[] = $row;
    }
}

// Check member count for discount eligibility
$member_count_q = mysqli_query($con, "SELECT COUNT(*) as count FROM users");
$member_count_data = mysqli_fetch_assoc($member_count_q);
$total_members = intval($member_count_data['count']);
$discount_active = ($total_members < 100);
$spots_remaining = 100 - $total_members;
if ($spots_remaining < 0) $spots_remaining = 0;

$logo_path = $gym['gym_logo'];
if (substr($logo_path, 0, 6) === '../../') {
    $logo_path = './' . substr($logo_path, 6);
}

$qr_path = $gym['payment_qr'];
if (substr($qr_path, 0, 6) === '../../') {
    $qr_path = './' . substr($qr_path, 6);
}

$success_message = "";
$error_message = "";
$generated_id = "";

if (isset($_POST['submit_registration'])) {
    $uname = mysqli_real_escape_string($con, $_POST['u_name']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $phn = mysqli_real_escape_string($con, $_POST['mobile']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $dob = mysqli_real_escape_string($con, $_POST['dob']);
    $password = '1234';
    
    $stname = mysqli_real_escape_string($con, $_POST['street_name']);
    $city = mysqli_real_escape_string($con, $_POST['city']);
    $state = mysqli_real_escape_string($con, $_POST['state']);
    $zipcode = mysqli_real_escape_string($con, $_POST['zipcode']);
    
    $plan = isset($_POST['plan_id']) ? mysqli_real_escape_string($con, $_POST['plan_id']) : '';
    
    // Check if email already exists
    $chk_email = mysqli_query($con, "SELECT email FROM users WHERE email = '$email'");
    if (empty($plan)) {
        $error_message = "Please select a membership plan before submitting.";
    } elseif ($chk_email && mysqli_num_rows($chk_email) > 0) {
        $error_message = "Duplicate Entry: Email address $email is already registered!";
    } elseif (empty($_POST['captured_photo']) && (!isset($_FILES['upload_photo']) || $_FILES['upload_photo']['error'] !== UPLOAD_ERR_OK)) {
        $error_message = "Please capture a live photo or upload a profile picture. This is mandatory for gym identification.";
    } else {
        // Handle payment proof screenshot upload
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['screenshot']['tmp_name'];
            $file_name = $_FILES['screenshot']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($file_ext, $allowed_exts)) {
                $target_dir = "./Sudarshan Data Folder/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $new_file_name = "payment_proof_new_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
                $target_file = $target_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $target_file)) {
                    // Fetch plan details
                    $plan_q = mysqli_query($con, "SELECT amount, validity FROM plan WHERE pid = '$plan'");
                    $plan_data = mysqli_fetch_assoc($plan_q);
                    $amount = intval($plan_data['amount']);
                    $validity = intval($plan_data['validity']);
                    
                    // Apply Discount Logic
                    $discount_amount = 0;
                    if ($discount_active) {
                        if ($validity == 12) {
                            $discount_amount = 2000;
                            $amount -= 2000;
                        } elseif ($validity == 6) {
                            $discount_amount = 1000;
                            $amount -= 1000;
                        }
                    }

                    $db_screenshot_path = "../../Sudarshan Data Folder/" . $new_file_name;
                    $utr = isset($_POST['utr']) ? mysqli_real_escape_string($con, $_POST['utr']) : '';

                    // Handle Profile Photo (Webcam Base64 OR Uploaded File)
                    $photo_path_db = "";
                    if (!empty($_POST['captured_photo'])) {
                        // It's a Base64 webcam image
                        $base64_string = $_POST['captured_photo'];
                        $image_parts = explode(";base64,", $base64_string);
                        if (count($image_parts) == 2) {
                            $image_base64 = base64_decode($image_parts[1]);
                            $photo_filename = "member_photo_" . time() . "_" . rand(1000, 9999) . ".jpg";
                            $photo_target = $target_dir . $photo_filename;
                            if (file_put_contents($photo_target, $image_base64)) {
                                $photo_path_db = "../../Sudarshan Data Folder/" . $photo_filename;
                            }
                        }
                    } elseif (isset($_FILES['upload_photo']) && $_FILES['upload_photo']['error'] === UPLOAD_ERR_OK) {
                        // It's a standard file upload
                        $p_file_tmp = $_FILES['upload_photo']['tmp_name'];
                        $p_file_name = $_FILES['upload_photo']['name'];
                        $p_file_ext = strtolower(pathinfo($p_file_name, PATHINFO_EXTENSION));
                        if (in_array($p_file_ext, array('jpg', 'jpeg', 'png'))) {
                            $photo_filename = "member_photo_" . time() . "_" . rand(1000, 9999) . "." . $p_file_ext;
                            $photo_target = $target_dir . $photo_filename;
                            if (move_uploaded_file($p_file_tmp, $photo_target)) {
                                $photo_path_db = "../../Sudarshan Data Folder/" . $photo_filename;
                            }
                        }
                    }
                    
                    // Instantly generate ID and create account
                    $next_id = 101;
                    $res_max = mysqli_query($con, "SELECT userid FROM users WHERE userid REGEXP '^[0-9]+$' AND CAST(userid AS UNSIGNED) < 100000000");
                    if ($res_max && mysqli_num_rows($res_max) > 0) {
                        $max_val = 100;
                        while ($row_max = mysqli_fetch_assoc($res_max)) {
                            $val = intval($row_max['userid']);
                            if ($val > $max_val) $max_val = $val;
                        }
                        $next_id = $max_val + 1;
                    }

                    // Generate random 6-digit gate code
                    $entry_code = strval(rand(100000, 999999));
                    
                    // Pre-Booking Logic
                    $today_date = date('Y-m-d');
                    $joining_date_val = "CURRENT_DATE()";
                    
                    // Insert into users
                    $query_user = "INSERT INTO users (username, gender, mobile, email, dob, joining_date, userid, entry_code, biometric_id, biometric_enabled, photo) 
                                   VALUES ('$uname', '$gender', '$phn', '$email', '$dob', $joining_date_val, '$next_id', '$entry_code', '$next_id', 1, '$photo_path_db')";
                    
                    if (mysqli_query($con, $query_user)) {
                        // Insert into address
                        mysqli_query($con, "INSERT INTO address (id, streetName, state, city, zipcode) 
                                            VALUES ('$next_id', '$stname', '$state', '$city', '$zipcode')");
                        
                        // Insert into health status
                        $weight = isset($_POST['weight']) ? mysqli_real_escape_string($con, $_POST['weight']) : '';
                        $height = isset($_POST['height']) ? mysqli_real_escape_string($con, $_POST['height']) : '';
                        mysqli_query($con, "INSERT INTO health_status (uid, weight, height) VALUES ('$next_id', '$weight', '$height')");
                        if (!empty($weight) || !empty($height)) {
                            mysqli_query($con, "INSERT INTO health_history (uid, weight, height, logged_date) VALUES ('$next_id', '$weight', '$height', CURRENT_DATE())");
                        }
                        
                        // Create admin login
                        mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) 
                                            VALUES ('$next_id', '$password', 'member', '$uname', 'member')");

                        // Insert active subscription
                        date_default_timezone_set("Asia/Calcutta");
                        $cdate = $today_date;
                        $d = strtotime("+" . $validity . " Months", strtotime($cdate));
                        $expiredate = date("Y-m-d", $d);
                        $payment_mode = 'UPI';
                        $received_by = 'Direct Approval';
                        
                        $ins_q = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount) 
                                  VALUES ('$plan', '$next_id', '$cdate', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount_amount, $amount)";
                        mysqli_query($con, $ins_q);
                        
                        // Log payment as approved
                        mysqli_query($con, "INSERT INTO payment_requests (uid, pid, amount, screenshot, status, utr, is_new_registration) 
                                            VALUES ('$next_id', '$plan', $amount, '$db_screenshot_path', 'approved', '$utr', 1)");
                        
                        // Send Welcome/Receipt Email Immediately
                        require_once 'include/smtp_mailer.php';
                        send_member_email($con, $next_id, 'new');
                        
                        // Send WhatsApp Welcome Message
                        require_once 'include/whatsapp_api.php';
                        $wa_msg = "🔥 Welcome to Sudarshan Fitness, $uname! 🔥\n\nYour Pre-Booking is confirmed!\n\nMembership ID: $next_id\nGym Entry PIN: $entry_code\nPlan Paid: ₹$amount\n\nShow this message at the front desk. Get ready to transform your life! 💪";
                        sendWhatsAppMessage($phn, $wa_msg);
                        
                        $success_message = "Registration complete. Your Member ID is: ";
                        $generated_id = $next_id;
                    } else {
                        $error_message = "Failed to register user details: " . mysqli_error($con);
                    }
                } else {
                    $error_message = "Failed to save payment proof screenshot.";
                }
            } else {
                $error_message = "Invalid screenshot format. Only JPG, JPEG, PNG, GIF are allowed.";
            }
        } else {
            $error_message = "Please select and upload a valid payment proof screenshot.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- OpenGraph SEO -->
    <meta property="og:title" content="<?php echo htmlspecialchars($gym['gym_name']); ?> | Grand Opening Pre-Booking">
    <meta property="og:description" content="Secure your spot today at <?php echo htmlspecialchars($gym['gym_name']); ?>. Exclusive Welcome Bonuses available for a limited time!">
    <meta property="og:image" content="<?php echo htmlspecialchars($logo_path); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Member Registration</title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>" type="image/jpeg">
    <link rel="stylesheet" href="./css/style.css"/>
    <link rel="stylesheet" type="text/css" href="./css/entypo.css">
    <link rel="stylesheet" href="./css/premium.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <style>
        body {
            background-color: #111;
            color: #fff;
        }
        .register-container {
            position: relative;
            max-width: 850px;
            margin: 40px auto;
            background: rgba(30, 30, 30, 0.9);
            border-radius: 15px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            padding: 50px;
                inset 0 0 20px rgba(255, 255, 255, 0.02);
            overflow: hidden;
        }

        /* Subtle ambient glow behind the form */
        .register-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 50% 50%, rgba(255, 107, 0, 0.08), transparent 60%);
            animation: pulseOpacity 6s infinite alternate;
            z-index: -1;
            pointer-events: none;
        }
        .form-section {
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 20px;
        }
        .form-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .form-section-title {
            color: #ffffff;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 0 15px rgba(255, 107, 0, 0.6);
        }
        .form-section-title i {
            color: var(--accent-primary);
            font-size: 22px;
            filter: drop-shadow(0 0 10px rgba(255, 107, 0, 0.8));
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .register-container { padding: 30px 20px; }
        }
        .form-group-premium {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .form-group-premium label {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        .form-control-premium {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            color: #ffffff !important;
            padding: 15px !important;
            width: 100%;
            box-sizing: border-box;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }
        .form-control-premium:focus {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(255, 107, 0, 0.5) !important;
            box-shadow: 
                0 0 0 4px rgba(255, 107, 0, 0.15),
                inset 0 2px 4px rgba(0,0,0,0.2) !important;
            outline: none;
            transform: translateY(-2px);
        }
        .qr-section {
            text-align: center;
            background: rgba(255,255,255,0.02);
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
        .btn-massive-pulse {
            background: linear-gradient(45deg, #ff6b00, #ff2a00);
            color: #fff;
            font-size: 24px;
            font-weight: 900;
            padding: 20px 40px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 0 30px rgba(255, 107, 0, 0.6);
            transition: all 0.3s ease;
            animation: pulse-glow 1.5s infinite;
            width: 100%;
            max-width: 400px;
        }
        .btn-massive-pulse:hover {
            transform: scale(1.05);
            box-shadow: 0 0 50px rgba(255, 107, 0, 0.9);
        }
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 30px rgba(255, 107, 0, 0.6); }
            50% { box-shadow: 0 0 50px rgba(255, 107, 0, 0.9); }
            100% { box-shadow: 0 0 30px rgba(255, 107, 0, 0.6); }
        }

        /* Holographic 3D Pricing Cards */
        .holo-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .holo-card {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 25px 20px;
            cursor: pointer;
            overflow: hidden;
            transition: transform 0.1s, box-shadow 0.1s, border-color 0.3s, background 0.3s;
            transform-style: preserve-3d;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }
        .holo-card.selected {
            border-color: #ff6b00;
            background: rgba(255, 107, 0, 0.15);
            box-shadow: 0 0 20px rgba(255, 107, 0, 0.5), inset 0 0 15px rgba(255, 107, 0, 0.3);
        }
        .holo-glare {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.4), transparent 60%);
            opacity: 0;
            pointer-events: none;
            mix-blend-mode: overlay;
            transition: opacity 0.3s ease;
        }
        .holo-card:hover .holo-glare {
            opacity: 1;
        }
        .holo-content {
            pointer-events: none;
            transform: translateZ(20px);
        }
        .holo-title { font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .holo-price { font-size: 26px; font-weight: 900; color: #ff6b00; margin-bottom: 5px; text-shadow: 0 0 10px rgba(255, 107, 0, 0.5); }
        .holo-validity { font-size: 14px; color: rgba(255,255,255,0.7); font-weight: 600; }
    </style>
</head>
<body class="page-body">

    <div id="container">
        <div class="register-container">
            
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Sudarshan Fitness Logo" style="max-height: 80px;" />
                <h1 style="color: #ff6b00; margin-top: 15px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;">SUDARSHAN FITNESS</h1>
                <h3 style="color: #ffffff; font-weight: 700; margin-top: 5px; letter-spacing: 1px;">NEW MEMBER REGISTRATION</h3>
                <p style="color: #ccc; font-size: 14px; margin-top: 10px;">Fill out the form below, pay via UPI, and start your fitness journey today.</p>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 20px; color: #ffffff; margin-bottom: 35px;">
                    <h4 style="color: var(--success); margin: 0 0 10px 0; font-weight: 700;">🎉 Registration Submitted!</h4>
                    <p style="margin: 0 0 15px 0;"><?php echo $success_message; ?> <strong style="color: var(--accent-primary); font-size: 18px; letter-spacing: 0.5px;"><?php echo $generated_id; ?></strong></p>
                    <p style="margin: 0; font-size: 13px; color: var(--text-muted);">Please note down your Member ID. Your portal login password has been set to <strong>1234</strong> by default. Staff will verify your payment screenshot and activate your account shortly.</p>
                    <div style="margin-top: 20px;">
                        <a href="index.php" class="btn btn-primary" style="display: inline-block; width: auto; font-weight: 600;">Go to Login Page</a>
                    </div>
                </div>
            <?php else: ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 15px; color: #ffffff; margin-bottom: 25px;">
                        ⚠️ <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post" enctype="multipart/form-data">
                    
                    <!-- Section 1: Profile Information -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="entypo-user"></i> 1. Profile Information</div>
                        <div class="form-grid">
                            <div class="form-group-premium">
                                <label>Full Name</label>
                                <input type="text" name="u_name" class="form-control-premium" placeholder="Enter full name" required>
                            </div>

                            <div class="form-group-premium">
                                <label>Gender</label>
                                <select name="gender" class="form-control-premium" required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Transgender">Transgender</option>
                                </select>
                            </div>
                            <div class="form-group-premium">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control-premium" required>
                            </div>
                            <div class="form-group-premium">
                                <label>Mobile Number</label>
                                <input type="tel" name="mobile" class="form-control-premium" placeholder="10-digit mobile number" pattern="[0-9]{10}" required>
                            </div>
                            <div class="form-group-premium">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control-premium" placeholder="Enter email address" required>
                            </div>
                            <div class="form-group-premium">
                                <label>Height (cm)</label>
                                <input type="number" name="height" class="form-control-premium" placeholder="e.g. 175" min="50" max="250" required>
                            </div>
                            <div class="form-group-premium">
                                <label>Weight (kg)</label>
                                <input type="number" name="weight" class="form-control-premium" placeholder="e.g. 70" min="10" max="300" required>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Address Details -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="entypo-location"></i> 2. Address Details</div>
                        <div class="form-grid">
                            <div class="form-group-premium">
                                <label>Street Address</label>
                                <input type="text" name="street_name" class="form-control-premium" placeholder="Street / House Number" required>
                            </div>
                            <div class="form-group-premium">
                                <label>City</label>
                                <input type="text" name="city" class="form-control-premium" placeholder="City" required>
                            </div>
                            <div class="form-group-premium">
                                <label>State</label>
                                <input type="text" name="state" class="form-control-premium" value="Maharashtra" readonly style="background-color: rgba(255,255,255,0.05); color: #888;" required>
                            </div>
                            <div class="form-group-premium">
                                <label>Zipcode / Pin Code</label>
                                <input type="text" name="zipcode" class="form-control-premium" placeholder="Enter 6-Digit Pin Code" pattern="[0-9]{6}" required>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Profile Photo (Webcam or File Upload) -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="entypo-camera"></i> 3. Profile Photo</div>
                        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border);">
                            
                            <div style="text-align: center; margin-bottom: 20px;">
                                <!-- Live Video Feed / Captured Snapshot -->
                                <video id="webcam-video" autoplay playsinline style="width: 100%; max-width: 300px; border-radius: 12px; display: none; margin: 0 auto; border: 2px solid var(--accent-primary);"></video>
                                <img id="photo-preview" style="width: 100%; max-width: 300px; border-radius: 12px; display: none; margin: 0 auto; border: 2px solid var(--success);" />
                                <canvas id="photo-canvas" style="display: none;"></canvas>
                                
                                <!-- Hidden input to hold Base64 data -->
                                <input type="hidden" name="captured_photo" id="captured_photo" value="">
                            </div>

                            <div class="row text-center">
                                <div class="col-xs-6">
                                    <button type="button" id="start-camera-btn" class="btn btn-primary" style="width: 100%; font-weight: bold;">
                                        <i class="entypo-camera"></i> Use Camera
                                    </button>
                                    <button type="button" id="capture-btn" class="btn btn-success" style="width: 100%; font-weight: bold; display: none;">
                                        <i class="entypo-record"></i> Take Photo
                                    </button>
                                    <button type="button" id="retake-btn" class="btn btn-warning" style="width: 100%; font-weight: bold; display: none; margin-top: 10px;">
                                        <i class="entypo-ccw"></i> Retake
                                    </button>
                                </div>
                                <div class="col-xs-6" style="border-left: 1px dashed rgba(255,255,255,0.2);">
                                    <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Or Upload File</label>
                                    <input type="file" name="upload_photo" id="upload_photo" accept="image/*" class="form-control-premium" style="padding: 6px !important; margin: 0;" onchange="previewUploadedPhoto(this)">
                                </div>
                            </div>
                            <div style="text-align: center; font-size: 11px; color: var(--text-muted); margin-top: 15px;">
                                *Please provide a clear face photo. You can either take a selfie now or upload a picture.
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Select Plan & Payment -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="entypo-credit-card"></i> 4. Select Membership & Pay</div>
                        
                        <div class="form-group-premium" style="margin-bottom: 20px; perspective: 1000px;">
                            <label style="font-size: 14px; color: #fff;">Choose Membership Plan <span style="color:var(--accent-primary)">*</span></label>
                            
                            <!-- Hidden input to submit the selected plan ID -->
                            <input type="hidden" name="plan_id" id="plan-select-hidden">
                            
                            <!-- Holographic Cards Grid -->
                            <div class="holo-cards-grid" id="holo-cards-grid">
                                <?php foreach ($plans as $p): 
                                    $original_price = $p['amount'];
                                    $discounted_price = $original_price;
                                    $has_discount = false;
                                    
                                    if ($discount_active) {
                                        if ($p['validity'] == 12) {
                                            $discounted_price -= 2000;
                                            $has_discount = true;
                                        } elseif ($p['validity'] == 6) {
                                            $discounted_price -= 1000;
                                            $has_discount = true;
                                        }
                                    }
                                ?>
                                    <div class="holo-card" 
                                         data-value="<?php echo htmlspecialchars($p['pid']); ?>" 
                                         data-amount="<?php echo $discounted_price; ?>" 
                                         data-validity="<?php echo $p['validity']; ?>" 
                                         data-desc="<?php echo htmlspecialchars($p['description']); ?>"
                                         data-name="<?php echo htmlspecialchars($p['planName']); ?>">
                                        <div class="holo-glare"></div>
                                        <div class="holo-content">
                                            <?php if ($has_discount): ?>
                                                <div style="background: linear-gradient(90deg, #ff6b00, #ff2a00); color: #fff; font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 10px; letter-spacing: 1px; animation: pulseOpacity 2s infinite; box-shadow: 0 0 10px rgba(255,107,0,0.5);">WELCOME BONUS</div>
                                            <?php endif; ?>
                                            <div class="holo-title"><?php echo htmlspecialchars($p['planName']); ?></div>
                                            <?php if ($has_discount): ?>
                                                <div class="holo-price">
                                                    <span style="text-decoration: line-through; color: #888; font-size: 16px; margin-right: 5px;">₹<?php echo number_format($original_price); ?></span>
                                                    ₹<?php echo number_format($discounted_price); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="holo-price">₹<?php echo number_format($original_price); ?></div>
                                            <?php endif; ?>
                                            <div class="holo-validity"><?php echo $p['validity']; ?> Months</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
                                <p style="color: var(--text-muted); font-size: 13px; margin: 0;">Scan the UPI QR code below and pay the exact amount using any UPI app.</p>
                                
                                <div>
                                    <img id="upi-qr-img" class="qr-image" src="<?php echo !empty($qr_path) && file_exists($qr_path) ? htmlspecialchars($qr_path) : ''; ?>" alt="Gym Payment QR Code" style="<?php echo (empty($gym['upi_id']) && (empty($qr_path) || !file_exists($qr_path))) ? 'display:none;' : ''; ?>">
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



                            <div class="form-group-premium" style="margin-top: 20px;">
                                <label>Upload Payment Screenshot (Receipt) <span style="color:var(--accent-primary)">*</span></label>
                                <input class="form-control-premium" type="file" name="screenshot" accept="image/*" required>
                                <span class="help-block" style="color: var(--text-muted); font-size: 12px; margin-bottom: 25px; display: block;">
                                    *Please upload a clear screenshot showing transaction details (amount, transaction ID, date).
                                </span>
                            </div>

                            <div style="display: flex; flex-direction: column; align-items: center; margin-top: 40px; gap: 20px;">
                                <button type="submit" name="submit_registration" class="btn btn-primary btn-lg" value="submit" style="width: 200px; font-weight: bold;">
                                    SECURE MY SPOT NOW!
                                </button>
                                <a href="index.php" class="link" style="font-weight: 600; color: #aaa; text-decoration: none; font-size: 14px;">&larr; Back to Login</a>
                            </div>
                        </div>
                    </div>

                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Holographic Cards 3D Tilt & Selection Logic
        const holoCards = document.querySelectorAll('.holo-card');
        const hiddenPlanInput = document.getElementById('plan-select-hidden');
        
        holoCards.forEach(card => {
            card.addEventListener('click', () => {
                holoCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                hiddenPlanInput.value = card.getAttribute('data-value');
                showPlanDetailsHolo(card);
            });
        });

        function showPlanDetailsHolo(card) {
            const detailsBox = document.getElementById('details-box');
            const qrPaymentArea = document.getElementById('qr-payment-area');
            const manualUpiBox = document.getElementById('manual-upi-box');

            if (!card) {
                detailsBox.style.display = 'none';
                qrPaymentArea.style.display = 'none';
                if(manualUpiBox) manualUpiBox.style.display = 'none';
                return;
            }

            const amount = card.getAttribute('data-amount');
            const validity = card.getAttribute('data-validity');
            const desc = card.getAttribute('data-desc');
            const name = card.getAttribute('data-name');

            document.getElementById('details-title').innerText = name;
            document.getElementById('details-desc').innerText = desc;
            document.getElementById('details-price').innerText = '₹' + parseInt(amount).toLocaleString('en-IN');
            document.getElementById('details-validity').innerText = validity + ' Month' + (parseInt(validity) > 1 ? 's' : '');

            // UPI dynamic loading config
            const upiId = "<?php echo isset($gym['upi_id']) ? htmlspecialchars($gym['upi_id']) : ''; ?>";
            const gymName = "<?php echo isset($gym['gym_name']) ? htmlspecialchars($gym['gym_name']) : 'Titan Gym'; ?>";
            const staticQrExists = <?php echo (!empty($qr_path) && file_exists($qr_path)) ? 'true' : 'false'; ?>;

            const qrImg = document.getElementById('upi-qr-img');
            const upiWrapper = document.getElementById('upi-app-link-wrapper');
            const noQrWarning = document.getElementById('no-qr-warning');

            if (upiId !== '') {
                // Generate dynamic UPI QR & Link
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
                // Fallback to static QR code image
                qrImg.src = "<?php echo !empty($qr_path) ? htmlspecialchars($qr_path) : ''; ?>";
                qrImg.style.display = 'inline-block';
                upiWrapper.style.display = 'none';
                noQrWarning.style.display = 'none';
            } else {
                // Hide QR and show warning
                qrImg.style.display = 'none';
                upiWrapper.style.display = 'none';
                noQrWarning.style.display = 'block';
            }

            detailsBox.style.display = 'block';
            qrPaymentArea.style.display = 'block';
        }

        // Auto-select first plan on load to generate QR automatically
        document.addEventListener("DOMContentLoaded", function() {
            if (holoCards.length > 0) {
                // Auto click the first plan to initialize it
                holoCards[0].click();
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
        
        // ==========================================
        // PHOTO CAPTURE & WEBRTC LOGIC
        // ==========================================
        const video = document.getElementById('webcam-video');
        const canvas = document.getElementById('photo-canvas');
        const photoPreview = document.getElementById('photo-preview');
        const capturedInput = document.getElementById('captured_photo');
        const uploadInput = document.getElementById('upload_photo');
        
        const startBtn = document.getElementById('start-camera-btn');
        const captureBtn = document.getElementById('capture-btn');
        const retakeBtn = document.getElementById('retake-btn');

        let stream = null;

        startBtn.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" }, audio: false });
                video.srcObject = stream;
                video.style.display = 'block';
                photoPreview.style.display = 'none';
                
                startBtn.style.display = 'none';
                captureBtn.style.display = 'inline-block';
                retakeBtn.style.display = 'none';
                uploadInput.value = ''; // clear upload if using camera
            } catch (err) {
                alert("Camera access denied or unavailable. Please upload a file instead.");
                console.error(err);
            }
        });

        captureBtn.addEventListener('click', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
            photoPreview.src = dataUrl;
            capturedInput.value = dataUrl; // Store base64 in hidden input
            
            // Stop camera stream
            stream.getTracks().forEach(track => track.stop());
            
            video.style.display = 'none';
            photoPreview.style.display = 'block';
            captureBtn.style.display = 'none';
            retakeBtn.style.display = 'inline-block';
        });

        retakeBtn.addEventListener('click', () => {
            capturedInput.value = '';
            startBtn.click(); // Restart camera
        });

        // If they upload a file, clear the webcam data and show preview
        function previewUploadedPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    photoPreview.style.display = 'block';
                    video.style.display = 'none';
                    
                    // Clear camera states
                    capturedInput.value = '';
                    if (stream) stream.getTracks().forEach(track => track.stop());
                    
                    startBtn.style.display = 'inline-block';
                    captureBtn.style.display = 'none';
                    retakeBtn.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

    </script>
    
    <div style="text-align: center; margin-top: 50px; padding-bottom: 30px; color: #94a3b8; font-size: 12px; font-weight: 500;">
        System Engineered by <strong style="color: #cbd5e1;">Anurag Bawaskar</strong> <br>
        <a href="tel:8459962390" style="color: #ff6b00; text-decoration: none; margin-top: 5px; display: inline-block;">📞 8459962390</a>
    </div>
</body>
</html>
