<?php
require_once __DIR__ . '/include/db_conn.php';
$gym = get_gym_details($con);

$submitted = false;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_enquiry') {
    $name = mysqli_real_escape_string($con, trim($_POST['username']));
    $gender = mysqli_real_escape_string($con, trim($_POST['gender']));
    $mobile = mysqli_real_escape_string($con, trim($_POST['mobile']));
    $email = mysqli_real_escape_string($con, trim($_POST['email']));
    $dob = !empty($_POST['dob']) ? mysqli_real_escape_string($con, $_POST['dob']) : NULL;
    $dob_sql = $dob ? "'$dob'" : "NULL";

    $height = mysqli_real_escape_string($con, trim($_POST['height']));
    $weight = mysqli_real_escape_string($con, trim($_POST['weight']));
    $fitness_goal = mysqli_real_escape_string($con, trim($_POST['fitness_goal']));

    $street = mysqli_real_escape_string($con, trim($_POST['street_name']));
    $city = !empty($_POST['city']) ? mysqli_real_escape_string($con, trim($_POST['city'])) : 'Khamgaon';
    $state = !empty($_POST['state']) ? mysqli_real_escape_string($con, trim($_POST['state'])) : 'Maharashtra';
    $zipcode = !empty($_POST['zipcode']) ? mysqli_real_escape_string($con, trim($_POST['zipcode'])) : '444303';

    // Couple Details
    $is_couple = isset($_POST['is_couple']) && $_POST['is_couple'] === '1' ? 1 : 0;
    $partner_name = mysqli_real_escape_string($con, trim($_POST['partner_name'] ?? ''));
    $partner_gender = mysqli_real_escape_string($con, trim($_POST['partner_gender'] ?? ''));
    $partner_mobile = mysqli_real_escape_string($con, trim($_POST['partner_mobile'] ?? ''));
    $partner_dob = !empty($_POST['partner_dob']) ? mysqli_real_escape_string($con, $_POST['partner_dob']) : NULL;
    $partner_dob_sql = $partner_dob ? "'$partner_dob'" : "NULL";
    $partner_height = mysqli_real_escape_string($con, trim($_POST['partner_height'] ?? ''));
    $partner_weight = mysqli_real_escape_string($con, trim($_POST['partner_weight'] ?? ''));

    // Handle Photo Upload
    $photo_path = '';
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/member_photos/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['photo_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $filename = 'enquiry_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo_file']['tmp_name'], $upload_dir . $filename)) {
                $photo_path = 'uploads/member_photos/' . $filename;
            }
        }
    } elseif (!empty($_POST['webcam_photo'])) {
        // Base64 camera photo
        $img_data = $_POST['webcam_photo'];
        $img_data = str_replace('data:image/png;base64,', '', $img_data);
        $img_data = str_replace('data:image/jpeg;base64,', '', $img_data);
        $img_data = str_replace(' ', '+', $img_data);
        $data = base64_decode($img_data);
        if ($data) {
            $upload_dir = __DIR__ . '/uploads/member_photos/';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            $filename = 'enquiry_cam_' . time() . '_' . rand(1000, 9999) . '.png';
            if (file_put_contents($upload_dir . $filename, $data)) {
                $photo_path = 'uploads/member_photos/' . $filename;
            }
        }
    }

    if (empty($name) || empty($mobile)) {
        $error_msg = "Please enter your Name and Mobile Number.";
    } else {
        $sql = "INSERT INTO walkin_enquiries (username, gender, mobile, email, dob, height, weight, fitness_goal, photo_path, is_couple, partner_name, partner_gender, partner_mobile, partner_dob, partner_height, partner_weight, street_name, city, state, zipcode, status) 
                VALUES ('$name', '$gender', '$mobile', '$email', $dob_sql, '$height', '$weight', '$fitness_goal', '$photo_path', $is_couple, '$partner_name', '$partner_gender', '$partner_mobile', $partner_dob_sql, '$partner_height', '$partner_weight', '$street', '$city', '$state', '$zipcode', 'pending')";
        
        if (mysqli_query($con, $sql)) {
            $submitted = true;
            
            // Queue WhatsApp notification to admin/reception if function exists
            if (function_exists('enqueue_whatsapp_message')) {
                $q_admin = mysqli_query($con, "SELECT mobile FROM users WHERE role IN ('super_admin', 'owner', 'reception') AND mobile IS NOT NULL AND mobile != '' LIMIT 3");
                if ($q_admin && mysqli_num_rows($q_admin) > 0) {
                    $wa_msg = "📩 *NEW GYM TOUR & ENQUIRY RECEIVED!* 📩\n\n" .
                               "Name: *{$name}*\n" .
                               "Mobile: *{$mobile}*\n" .
                               "Gender: *{$gender}*\n" .
                               "Goal: *{$fitness_goal}*\n\n" .
                               "Please guide client for gym tour & plan selection on Reception Terminal!";
                    while ($ad = mysqli_fetch_assoc($q_admin)) {
                        enqueue_whatsapp_message($con, $ad['mobile'], $wa_msg);
                    }
                }
            }
        } else {
            $error_msg = "Submission error: " . mysqli_error($con);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Visitor Registration | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(30, 41, 59, 0.8);
            --accent: #ff6b00;
            --accent-hover: #ff8800;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }

        body {
            background: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background-image: radial-gradient(circle at 50% 0%, rgba(255,107,0,0.15) 0%, transparent 60%);
        }

        .container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header img {
            max-height: 60px;
            margin-bottom: 12px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 13px;
            color: var(--accent);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 30px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .section-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: rgba(15, 23, 42, 0.8);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 15px rgba(255,107,0,0.3);
        }

        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 500px) {
            .row-2 { grid-template-columns: 1fr; }
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            color: #fff;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(255,107,0,0.4);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(255,107,0,0.6);
        }

        /* Success Overlay Screen */
        .success-box {
            text-align: center;
            padding: 40px 20px;
        }

        .success-icon {
            width: 90px;
            height: 90px;
            background: rgba(16, 185, 129, 0.2);
            border: 2px solid #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
            color: #10b981;
            margin: 0 auto 20px auto;
        }

        .couple-box {
            background: rgba(236, 72, 153, 0.1);
            border: 1px solid rgba(236, 72, 153, 0.3);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="Gym Logo">
            <h1><?php echo htmlspecialchars($gym['gym_name']); ?></h1>
            <p>📝 Visitor Gym Tour &amp; Registration Form</p>
        </div>

        <?php if ($submitted): ?>
            <div class="form-card success-box">
                <div class="success-icon">✓</div>
                <h2 style="font-size: 24px; font-weight: 900; margin-bottom: 10px;">Registration Received!</h2>
                <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6; margin-bottom: 25px;">
                    Thank you, <strong><?php echo htmlspecialchars($name); ?></strong>! Your visitor information has been registered at reception.<br><br>
                    Please inform our reception team for your guided gym tour &amp; plan activation.
                </p>
                <a href="guest_enquiry.php" class="btn-submit" style="display: inline-block; text-decoration: none; text-align: center; width: auto; padding: 12px 30px;">Fill Another Form</a>
            </div>
        <?php else: ?>

            <?php if (!empty($error_msg)): ?>
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; font-size: 13px;">
                    ⚠️ <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="form-card">
                <input type="hidden" name="action" value="submit_enquiry">
                <input type="hidden" name="webcam_photo" id="webcam_photo_input" value="">

                <!-- Personal Information -->
                <div class="section-title">👤 Visitor Details</div>

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter your full name" required>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="mobile" class="form-control" maxlength="10" placeholder="10-digit Mobile Number" required>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male" selected>Male ♂️</option>
                            <option value="Female">Female ♀️</option>
                            <option value="Transgender">Transgender</option>
                        </select>
                    </div>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="name@example.com">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-control">
                    </div>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" name="height" class="form-control" placeholder="e.g. 175">
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" name="weight" class="form-control" placeholder="e.g. 70">
                    </div>
                </div>

                <div class="form-group">
                    <label>Fitness Goal / Target</label>
                    <select name="fitness_goal" class="form-control">
                        <option value="Weight Loss">Weight Loss &amp; Fat Burn 🔥</option>
                        <option value="Muscle Building" selected>Muscle Building &amp; Strength 💪</option>
                        <option value="Body Transformation">Full Body Transformation ⚡</option>
                        <option value="General Fitness">General Health &amp; Stamina 🏃</option>
                    </select>
                </div>

                <!-- Photo Capture / Upload -->
                <div class="section-title" style="margin-top: 25px;">📸 Profile Photo</div>

                <div class="form-group">
                    <label>Upload Photo or Take Selfie</label>
                    <input type="file" name="photo_file" accept="image/*" class="form-control">
                </div>

                <!-- Couple Registration Toggle -->
                <div class="couple-box">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #ec4899; font-weight: 800; font-size: 14px;">
                        <input type="checkbox" name="is_couple" value="1" id="couple_chk" onchange="toggleCoupleSection()" style="width: 18px; height: 18px; accent-color: #ec4899;">
                        💑 Inquiring for Couple / Duo Membership?
                    </label>
                </div>

                <div id="partner-section" style="display: none; background: rgba(0,0,0,0.3); border: 1px solid rgba(236,72,153,0.3); border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                    <div class="section-title" style="color: #ec4899; border-color: rgba(236,72,153,0.3);">👩‍❤️‍👨 Partner Information</div>

                    <div class="form-group">
                        <label>Partner Full Name</label>
                        <input type="text" name="partner_name" class="form-control" placeholder="Partner full name">
                    </div>

                    <div class="row-2">
                        <div class="form-group">
                            <label>Partner Phone</label>
                            <input type="tel" name="partner_mobile" class="form-control" maxlength="10" placeholder="Partner mobile">
                        </div>
                        <div class="form-group">
                            <label>Partner Gender</label>
                            <select name="partner_gender" class="form-control">
                                <option value="Female" selected>Female ♀️</option>
                                <option value="Male">Male ♂️</option>
                            </select>
                        </div>
                    </div>

                    <div class="row-2">
                        <div class="form-group">
                            <label>Partner Height (cm)</label>
                            <input type="number" name="partner_height" class="form-control" placeholder="e.g. 165">
                        </div>
                        <div class="form-group">
                            <label>Partner Weight (kg)</label>
                            <input type="number" name="partner_weight" class="form-control" placeholder="e.g. 60">
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="section-title">📍 Address Details</div>

                <div class="form-group">
                    <label>Street / Area Name</label>
                    <input type="text" name="street_name" class="form-control" placeholder="Address / Landmark">
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" value="Khamgaon">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state" class="form-control" value="Maharashtra">
                    </div>
                </div>

                <button type="submit" class="btn-submit">Submit Registration ➔</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function toggleCoupleSection() {
            const chk = document.getElementById('couple_chk');
            const sec = document.getElementById('partner-section');
            sec.style.display = chk.checked ? 'block' : 'none';
        }
    </script>
</body>
</html>
