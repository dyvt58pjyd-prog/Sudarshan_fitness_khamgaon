<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    header("Location: index.php");
    exit();
}

$gym = get_gym_details($con);
$msg = '';

// Handle Conversion & Approval from Enquiry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'convert_enquiry') {
    $enquiry_id = intval($_POST['enquiry_id']);
    
    // Fetch Enquiry details
    $q_e = mysqli_query($con, "SELECT * FROM walkin_enquiries WHERE id = $enquiry_id");
    if ($q_e && mysqli_num_rows($q_e) > 0) {
        $e_row = mysqli_fetch_assoc($q_e);
        
        $uname = mysqli_real_escape_string($con, $e_row['username']);
        $gender = mysqli_real_escape_string($con, $e_row['gender']);
        $mobile = mysqli_real_escape_string($con, $e_row['mobile']);
        $email = mysqli_real_escape_string($con, $e_row['email']);
        $dob = $e_row['dob'];
        $dob_val = !empty($dob) ? "'$dob'" : "NULL";
        
        $height = mysqli_real_escape_string($con, $e_row['height']);
        $weight = mysqli_real_escape_string($con, $e_row['weight']);
        $fitness_goal = mysqli_real_escape_string($con, $e_row['fitness_goal']);
        $photo_path = mysqli_real_escape_string($con, $e_row['photo_path']);
        
        $street = mysqli_real_escape_string($con, $e_row['street_name']);
        $city = mysqli_real_escape_string($con, $e_row['city']);
        $state = mysqli_real_escape_string($con, $e_row['state']);
        $zipcode = mysqli_real_escape_string($con, $e_row['zipcode']);

        // Plan & Payment details filled by Staff/Admin
        $plan_id = mysqli_real_escape_string($con, $_POST['plan_id']);
        $payment_mode = mysqli_real_escape_string($con, $_POST['payment_mode']);
        $discount = isset($_POST['discount']) && $_POST['discount'] !== '' ? floatval($_POST['discount']) : 0;
        $jdate = isset($_POST['jdate']) && !empty($_POST['jdate']) ? mysqli_real_escape_string($con, $_POST['jdate']) : date('Y-m-d');
        
        // Fetch Plan Info
        $q_plan = mysqli_query($con, "SELECT * FROM plan WHERE pid = '$plan_id'");
        $plan_row = mysqli_fetch_assoc($q_plan);
        $plan_price = floatval($plan_row['amount']);
        $plan_name = $plan_row['planName'];

        $total_payable = $plan_price - $discount;
        if ($total_payable < 0) $total_payable = 0;

        $paid_amount = isset($_POST['paid_amount']) && $_POST['paid_amount'] !== '' ? floatval($_POST['paid_amount']) : $total_payable;
        
        if (function_exists('calculate_expiration_date')) {
            $expire_timestamp = calculate_expiration_date($jdate, $plan_row['validity']);
            $expiredate = date('Y-m-d', $expire_timestamp);
        } else {
            $validity_months = intval($plan_row['validity']);
            $expiredate = date('Y-m-d', strtotime("+$validity_months months", strtotime($jdate)));
        }

        if ($payment_mode === 'Complimentary') {
            $paid_amount = 0;
            $discount = $plan_price;
            $total_payable = 0;
        }

        $balance = $total_payable - $paid_amount;
        if ($balance < 0) $balance = 0;

        // Generate Next Member ID
        $res_max = mysqli_query($con, "SELECT MAX(CAST(userid AS UNSIGNED)) as maxid FROM users WHERE userid REGEXP '^[0-9]+$' AND CAST(userid AS UNSIGNED) < 100000000");
        $max_row = mysqli_fetch_assoc($res_max);
        $next_id = ($max_row['maxid'] > 100) ? $max_row['maxid'] + 1 : 101;
        
        $entry_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $received_by = isset($_SESSION['full_name']) ? mysqli_real_escape_string($con, $_SESSION['full_name']) : 'System';

        if (empty($email)) {
            $email = "member_" . $next_id . "_" . time() . "@sudarshanfitness.local";
        }

        $dob_insert = ($dob_val !== "NULL") ? $dob_val : "'1995-01-01'";

        // 1. Insert Member User with standard schema compatibility
        $q_user = "INSERT INTO users (username, gender, mobile, email, dob, joining_date, userid) 
                   VALUES ('$uname', '$gender', '$mobile', '$email', $dob_insert, '$jdate', '$next_id')";
        
        if (mysqli_query($con, $q_user)) {
            // Update additional features if columns exist
            @mysqli_query($con, "UPDATE users SET biometric_id='$next_id', biometric_enabled=1, fitness_goal='$fitness_goal', member_photo='$photo_path' WHERE userid='$next_id'");

            // 2. Insert Enrolls_To
            mysqli_query($con, "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance) 
                                VALUES ('$plan_id', '$next_id', '$jdate', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount, $paid_amount, $balance)");
            
            // 3. Health & Address
            mysqli_query($con, "INSERT INTO health_status (uid, weight, height) VALUES ('$next_id', '$weight', '$height')");
            mysqli_query($con, "INSERT INTO address (id, streetName, state, city, zipcode) VALUES ('$next_id', '$street', '$state', '$city', '$zipcode')");

            // 4. Admin Auth
            $password = '1234';
            mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$next_id', '$password', 'member', '$uname', 'member')");

            // Couple partner handling if applicable
            if ($e_row['is_couple'] == 1 && !empty($e_row['partner_name'])) {
                $p_name = mysqli_real_escape_string($con, $e_row['partner_name']);
                $p_gender = mysqli_real_escape_string($con, $e_row['partner_gender']);
                $p_mobile = !empty($e_row['partner_mobile']) ? mysqli_real_escape_string($con, $e_row['partner_mobile']) : $mobile;
                $p_dob = $e_row['partner_dob'];
                $p_dob_val = !empty($p_dob) ? "'$p_dob'" : "'1995-01-01'";
                
                $p_height = mysqli_real_escape_string($con, $e_row['partner_height']);
                $p_weight = mysqli_real_escape_string($con, $e_row['partner_weight']);

                $partner_uid = $next_id + 1;
                $p_email = "partner_" . $partner_uid . "_" . time() . "@sudarshanfitness.local";

                $q_partner = "INSERT INTO users (username, gender, mobile, email, dob, joining_date, userid) 
                              VALUES ('$p_name', '$p_gender', '$p_mobile', '$p_email', $p_dob_val, '$jdate', '$partner_uid')";
                if (mysqli_query($con, $q_partner)) {
                    mysqli_query($con, "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance) 
                                        VALUES ('$plan_id', '$partner_uid', '$jdate', '$expiredate', 'yes', 'Couple Plan', '$received_by', 0, 0, 0)");
                    mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$partner_uid', '1234', 'member', '$p_name', 'member')");
                    mysqli_query($con, "INSERT INTO health_status (uid, weight, height) VALUES ('$partner_uid', '$p_weight', '$p_height')");
                }
            }

            // 5. Update Enquiry Status
            mysqli_query($con, "UPDATE walkin_enquiries SET status = 'approved', converted_uid = '$next_id' WHERE id = $enquiry_id");

            // Dispatch Welcome Registration Email
            if (file_exists('../../include/smtp_mailer.php')) {
                require_once '../../include/smtp_mailer.php';
                @send_member_email($con, $email, $uname, $next_id, '1234', $plan_name, 0, $expiredate, '', 0, NULL, '');
            }

            $msg = "<div class='alert alert-success' style='background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;'>✅ Member Registered &amp; Enrolled Successfully! Member ID: <strong>#$next_id</strong></div>";
        } else {
            $msg = "<div class='alert alert-danger' style='background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;'>Error enrolling member: " . mysqli_error($con) . "</div>";
        }
    }
}

// Fetch Pending Enquiries
$q_pending = mysqli_query($con, "SELECT * FROM walkin_enquiries WHERE status = 'pending' ORDER BY id DESC");
$pending_count = mysqli_num_rows($q_pending);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Walk-In Visitor Enquiries</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/entypo.css">
    <style>
        :root {
            --bg: #0b0f19;
            --card-bg: rgba(30, 41, 59, 0.7);
            --accent: #ff6b00;
            --accent-green: #10b981;
            --border: rgba(255, 255, 255, 0.1);
        }
        body { background: var(--bg); color: #fff; font-family: 'Outfit', sans-serif; padding: 25px; margin: 0; }
        .header-box { display: flex; justify-content: space-between; align-items: center; background: var(--card-bg); padding: 20px 30px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 25px; }
        .header-title h2 { margin: 0; font-size: 22px; font-weight: 800; color: #fff; }
        .badge-pending { background: rgba(255,107,0,0.2); color: var(--accent); border: 1px solid var(--accent); padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 13px; }
        .enquiry-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .enquiry-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 25px; position: relative; backdrop-filter: blur(10px); }
        .visitor-photo { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); background: #000; }
        .card-header { display: flex; gap: 15px; align-items: center; margin-bottom: 15px; }
        .visitor-name { font-size: 18px; font-weight: 800; color: #fff; }
        .visitor-phone { font-size: 13px; color: #38bdf8; font-weight: 700; }
        .info-table { width: 100%; font-size: 12px; margin-bottom: 15px; }
        .info-table td { padding: 4px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .info-label { color: #94a3b8; font-weight: 600; }
        .info-val { color: #fff; font-weight: 700; text-align: right; }
        .btn-approve { width: 100%; background: linear-gradient(135deg, var(--accent), #ff8800); color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer; }
        
        /* Modal Style */
        .modal { display: none; position: fixed; z-index: 9999; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); align-items: center; justify-content: center; padding: 20px; }
        .modal-content { background: #1e293b; border: 2px solid var(--accent); border-radius: 24px; padding: 30px; max-width: 550px; width: 100%; color: #fff; }
        .modal-title { font-size: 20px; font-weight: 800; color: var(--accent); margin-bottom: 15px; }
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; font-size: 12px; font-weight: 700; color: #94a3b8; margin-bottom: 5px; text-transform: uppercase; }
        .form-input { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: #0f172a; color: #fff; font-size: 14px; outline: none; }
        .alert-success { background: rgba(16,185,129,0.2); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header-box">
        <div class="header-title">
            <h2>📝 Walk-In Visitor Enquiries &amp; Gym Tours</h2>
            <span style="font-size: 12px; color: #94a3b8;">Review client tour registrations and approve membership activation</span>
        </div>
        <div class="badge-pending">
            ⚡ <?php echo $pending_count; ?> Pending Enquiries
        </div>
        <a href="../../guest_enquiry.php" target="_blank" style="background: rgba(255,255,255,0.1); color: #fff; padding: 8px 16px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: bold;">📱 Public Registration QR Form</a>
    </div>

    <?php echo $msg; ?>

    <?php if ($pending_count === 0): ?>
        <div style="text-align: center; padding: 50px; background: var(--card-bg); border-radius: 20px; border: 1px dashed var(--border);">
            <div style="font-size: 40px; margin-bottom: 10px;">🎉</div>
            <h3 style="margin: 0; color: #94a3b8;">No pending walk-in enquiries.</h3>
            <p style="font-size: 12px; color: #64748b; margin-top: 5px;">New visitor submissions from your QR code will appear here in real-time.</p>
        </div>
    <?php else: ?>
        <div class="enquiry-grid">
            <?php while ($row = mysqli_fetch_assoc($q_pending)): ?>
                <div class="enquiry-card">
                    <div class="card-header">
                        <img src="../../<?php echo !empty($row['photo_path']) ? htmlspecialchars($row['photo_path']) : 'img/default_avatar.png'; ?>" class="visitor-photo" alt="Visitor Photo">
                        <div>
                            <div class="visitor-name"><?php echo htmlspecialchars($row['username']); ?></div>
                            <div class="visitor-phone">📞 <?php echo htmlspecialchars($row['mobile']); ?></div>
                            <span style="font-size: 11px; background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 6px; font-weight: bold;"><?php echo htmlspecialchars($row['gender']); ?></span>
                        </div>
                    </div>

                    <table class="info-table">
                        <tr><td class="info-label">Email</td><td class="info-val"><?php echo htmlspecialchars($row['email'] ?: 'N/A'); ?></td></tr>
                        <tr><td class="info-label">DOB</td><td class="info-val"><?php echo htmlspecialchars($row['dob'] ?: 'N/A'); ?></td></tr>
                        <tr><td class="info-label">Height / Weight</td><td class="info-val"><?php echo htmlspecialchars($row['height']); ?> cm / <?php echo htmlspecialchars($row['weight']); ?> kg</td></tr>
                        <tr><td class="info-label">Fitness Goal</td><td class="info-val" style="color: var(--accent);"><?php echo htmlspecialchars($row['fitness_goal']); ?></td></tr>
                        <?php if ($row['is_couple'] == 1): ?>
                            <tr><td class="info-label">Couple Partner</td><td class="info-val" style="color: #ec4899;">💑 <?php echo htmlspecialchars($row['partner_name']); ?> (<?php echo htmlspecialchars($row['partner_gender']); ?>)</td></tr>
                        <?php endif; ?>
                        <tr><td class="info-label">Registered Date</td><td class="info-val"><?php echo date('d-M-Y h:i A', strtotime($row['created_at'])); ?></td></tr>
                    </table>

                    <button class="btn-approve" onclick="openApprovalModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                        ⚡ Tour Done &amp; Activate Membership ➔
                    </button>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- Approval Modal -->
    <div class="modal" id="approveModal">
        <div class="modal-content">
            <div class="modal-title">🏋️ Activate Membership for <span id="m_name" style="color: #fff;">Client</span></div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="convert_enquiry">
                <input type="hidden" name="enquiry_id" id="m_enquiry_id">

                <div class="form-row">
                    <label>Select Membership Plan *</label>
                    <select name="plan_id" id="m_plan_select" class="form-input" required onchange="updateApprovalModalAmounts()">
                        <?php
                        $q_plans = mysqli_query($con, "SELECT * FROM plan WHERE active = 'yes'");
                        while ($p = mysqli_fetch_assoc($q_plans)) {
                            echo "<option value='{$p['pid']}' data-price='" . intval($p['amount']) . "'>" . htmlspecialchars($p['planName']) . " - ₹" . intval($p['amount']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <label>Payment Mode *</label>
                    <select name="payment_mode" id="m_paymode_select" class="form-input" required onchange="updateApprovalModalAmounts()">
                        <option value="Cash" selected>Cash</option>
                        <option value="UPI">UPI</option>
                        <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'owner'): ?>
                            <option value="Complimentary">🌟 Superadmin Complimentary / VIP Pass (₹0)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label>Amount Paid Now (₹)</label>
                        <input type="number" name="paid_amount" id="m_paid_amount" class="form-input" placeholder="Auto calculated if empty">
                    </div>
                    <div>
                        <label>Discount Amount (₹)</label>
                        <input type="number" name="discount" id="m_discount_input" class="form-input" value="0" oninput="updateApprovalModalAmounts()">
                    </div>
                </div>

                <!-- UPI Payment QR Code Container -->
                <div id="modal-upi-qr-box" style="display: none; background: rgba(0,0,0,0.3); border: 1px dashed rgba(255,107,0,0.5); padding: 15px; border-radius: 16px; text-align: center; margin: 15px 0;">
                    <h4 style="color: #fff; margin: 0 0 5px 0; font-size: 14px;">Scan to Pay UPI: <span id="upi-qr-amount-text" style="color: #ff6b00; font-weight: 800; font-size: 16px;">₹0</span></h4>
                    <p style="color: #94a3b8; font-size: 11px; margin-bottom: 12px;">Ask client to scan &amp; pay via Google Pay, PhonePe, Paytm, or BHIM.</p>
                    <div style="background: #ffffff; padding: 12px; border-radius: 14px; display: inline-block; box-shadow: 0 8px 20px rgba(0,0,0,0.4);">
                        <canvas id="modal-upi-qr-canvas"></canvas>
                    </div>
                </div>

                <div class="form-row">
                    <label>Joining Date</label>
                    <input type="date" name="jdate" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-approve" style="flex: 1;">Confirm &amp; Register Member 🚀</button>
                    <button type="button" onclick="document.getElementById('approveModal').style.display='none'" style="background: rgba(255,255,255,0.1); color: #fff; border: none; padding: 12px 20px; border-radius: 12px; font-weight: bold; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
        let upiQrObj = null;
        const gymUpiId = <?php echo json_encode(!empty($gym['upi_id']) ? $gym['upi_id'] : ''); ?>;
        const gymName = <?php echo json_encode(!empty($gym['gym_name']) ? $gym['gym_name'] : 'Sudarshan Fitness'); ?>;

        function openApprovalModal(data) {
            document.getElementById('m_enquiry_id').value = data.id;
            document.getElementById('m_name').textContent = data.username;
            document.getElementById('approveModal').style.display = 'flex';
            updateApprovalModalAmounts();
        }

        function updateApprovalModalAmounts() {
            const planSelect = document.getElementById('m_plan_select');
            const paySelect = document.getElementById('m_paymode_select');
            const paidInput = document.getElementById('m_paid_amount');
            const discountInput = document.getElementById('m_discount_input');
            const qrBox = document.getElementById('modal-upi-qr-box');
            const qrAmtText = document.getElementById('upi-qr-amount-text');

            if (!planSelect || planSelect.selectedIndex < 0) return;

            const opt = planSelect.options[planSelect.selectedIndex];
            const planPrice = parseFloat(opt.getAttribute('data-price')) || 0;
            const payMode = paySelect.value;

            if (payMode === 'Complimentary') {
                discountInput.value = planPrice;
                paidInput.value = 0;
                qrBox.style.display = 'none';
                return;
            }

            let discount = parseFloat(discountInput.value) || 0;
            let netAmount = planPrice - discount;
            if (netAmount < 0) netAmount = 0;

            if (payMode === 'UPI' && netAmount > 0 && gymUpiId) {
                qrBox.style.display = 'block';
                qrAmtText.textContent = `₹` + netAmount.toLocaleString('en-IN');
                
                const upiString = `upi://pay?pa=${encodeURIComponent(gymUpiId)}&pn=${encodeURIComponent(gymName)}&am=${netAmount}&cu=INR`;
                
                if (typeof QRious !== 'undefined') {
                    if (!upiQrObj) {
                        upiQrObj = new QRious({
                            element: document.getElementById('modal-upi-qr-canvas'),
                            size: 180,
                            background: 'white',
                            foreground: 'black'
                        });
                    }
                    upiQrObj.value = upiString;
                }
            } else {
                qrBox.style.display = 'none';
            }
        }
    </script>
</body>
</html>
