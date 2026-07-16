<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'member') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
    exit();
}

$gym = get_gym_details($con);
$userid = mysqli_real_escape_string($con, $_SESSION['user_data']);

// Fetch trainer details
$q_trainer = "SELECT u.trainer_id, a.Full_name as trainer_name, a.mobile as trainer_mobile 
              FROM users u 
              LEFT JOIN admin a ON u.trainer_id = a.username 
              WHERE u.userid = '$userid'";
$res_trainer = mysqli_query($con, $q_trainer);
$trainer_info = mysqli_fetch_assoc($res_trainer);

$trainer_id = isset($trainer_info['trainer_id']) ? $trainer_info['trainer_id'] : '';
$trainer_name = isset($trainer_info['trainer_name']) ? $trainer_info['trainer_name'] : '';

$today = date('Y-m-d');
$msg = '';
$msg_type = '';

// Handle Booking POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_slot'])) {
    if (empty($trainer_id)) {
        $msg = "Error: You do not have an assigned trainer to book a slot with.";
        $msg_type = "danger";
    } else {
        $booking_date = mysqli_real_escape_string($con, $_POST['booking_date']);
        $booking_time = mysqli_real_escape_string($con, $_POST['booking_time']);

        // Validate date (must be today or future, up to 7 days)
        $max_date = date('Y-m-d', strtotime('+7 days'));
        if ($booking_date < $today || $booking_date > $max_date) {
            $msg = "Error: You can only book slots for the next 7 days.";
            $msg_type = "danger";
        } else {
            // Check for slot conflicts for this trainer
            $q_conflict = "SELECT COUNT(*) as cnt FROM pt_bookings 
                           WHERE trainer_id = '$trainer_id' 
                             AND booking_date = '$booking_date' 
                             AND booking_time = '$booking_time' 
                             AND status = 'confirmed'";
            $res_conflict = mysqli_query($con, $q_conflict);
            $conflict_data = mysqli_fetch_assoc($res_conflict);

            if ($conflict_data['cnt'] > 0) {
                $msg = "Conflict: This slot is already booked for {$trainer_name}. Please choose another time.";
                $msg_type = "danger";
            } else {
                // Insert booking
                $q_insert = "INSERT INTO pt_bookings (uid, trainer_id, booking_date, booking_time, status) 
                             VALUES ('$userid', '$trainer_id', '$booking_date', '$booking_time', 'confirmed')";
                if (mysqli_query($con, $q_insert)) {
                    // Send WhatsApp notifications
                    send_whatsapp_pt_booking_notification($con, $userid, $trainer_id, $booking_date, $booking_time);
                    $msg = "Success: Personal Training session scheduled successfully! Notifications sent.";
                    $msg_type = "success";
                } else {
                    $msg = "Error scheduling session: " . mysqli_error($con);
                    $msg_type = "danger";
                }
            }
        }
    }
}

// Handle Cancel POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    // Verify booking belongs to this user
    $q_check = "SELECT * FROM pt_bookings WHERE id = $booking_id AND uid = '$userid'";
    $res_check = mysqli_query($con, $q_check);
    if ($res_check && mysqli_num_rows($res_check) > 0) {
        $q_cancel = "UPDATE pt_bookings SET status = 'cancelled' WHERE id = $booking_id";
        if (mysqli_query($con, $q_cancel)) {
            $msg = "Success: Session booking has been cancelled.";
            $msg_type = "success";
        } else {
            $msg = "Error cancelling session: " . mysqli_error($con);
            $msg_type = "danger";
        }
    }
}

// Query user's bookings
$q_bookings = "SELECT b.id, b.booking_date, b.booking_time, b.status, a.Full_name as trainer_name 
               FROM pt_bookings b 
               LEFT JOIN admin a ON b.trainer_id = a.username 
               WHERE b.uid = '$userid' 
               ORDER BY b.booking_date DESC, b.booking_time DESC";
$res_bookings = mysqli_query($con, $q_bookings);
$bookings = [];
if ($res_bookings) {
    while ($row = mysqli_fetch_assoc($res_bookings)) {
        $bookings[] = $row;
    }
}

// Time Slots list
$time_slots = [
    '06:00:00' => '06:00 AM - 07:00 AM',
    '07:00:00' => '07:00 AM - 08:00 AM',
    '08:00:00' => '08:00 AM - 09:00 AM',
    '09:00:00' => '09:00 AM - 10:00 AM',
    '10:00:00' => '10:00 AM - 11:00 AM',
    '11:00:00' => '11:00 AM - 12:00 PM',
    '12:00:00' => '12:00 PM - 01:00 PM',
    '13:00:00' => '01:00 PM - 02:00 PM',
    '16:00:00' => '04:00 PM - 05:00 PM',
    '17:00:00' => '05:00 PM - 06:00 PM',
    '18:00:00' => '06:00 PM - 07:00 PM',
    '19:00:00' => '07:00 PM - 08:00 PM',
    '20:00:00' => '08:00 PM - 09:00 PM',
    '21:00:00' => '09:00 PM - 10:00 PM'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Book PT Slot</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#pt_booking > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .pt-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }
        .form-control-premium {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: #ffffff;
            border-radius: 8px;
            padding: 10px 15px;
            width: 100%;
            margin-bottom: 15px;
            outline: none;
            transition: all 0.3s ease;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 10px rgba(255, 107, 0, 0.2);
        }
        .btn-premium {
            background: var(--accent-primary);
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.4);
        }
        .table-premium {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .table-premium th, .table-premium td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }
        .table-premium th {
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
        }
        .badge-premium {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-confirmed {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }
        .badge-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
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

            <h2>Personal Training Booking</h2>
            <hr />

            <?php if (!empty($msg)): ?>
                <div class="alert alert-<?php echo $msg_type; ?>" style="border-radius: 10px; padding: 15px; margin-bottom: 25px;">
                    <strong><?php echo $msg; ?></strong>
                </div>
            <?php endif; ?>

            <?php if (empty($trainer_id)): ?>
                <div class="pt-card" style="text-align: center; padding: 50px 30px;">
                    <i class="entypo-attention" style="font-size: 48px; color: var(--accent-primary); display: block; margin-bottom: 15px;"></i>
                    <h3 style="color: #ffffff; margin-bottom: 10px;">No Trainer Assigned</h3>
                    <p style="color: var(--text-muted); font-size: 15px; max-width: 500px; margin: 0 auto; line-height: 1.6;">
                        You do not have an active Personal Training (PT) subscription or a trainer assigned to your profile. Please contact the front desk to enroll in PT and start booking your training sessions.
                    </p>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Booking Form -->
                    <div class="col-md-5">
                        <div class="pt-card">
                            <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; margin-bottom: 5px;">Schedule Session</h3>
                            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 25px;">
                                Book an hourly slot with your assigned trainer: <strong><?php echo htmlspecialchars($trainer_name); ?></strong>
                            </p>

                            <form method="POST" action="">
                                <label style="color: #ffffff; font-weight: 600; display: block; margin-bottom: 8px;">Select Date</label>
                                <input type="date" name="booking_date" class="form-control-premium" 
                                       min="<?php echo $today; ?>" 
                                       max="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" 
                                       value="<?php echo $today; ?>" required />

                                <label style="color: #ffffff; font-weight: 600; display: block; margin-bottom: 8px;">Select Time Slot</label>
                                <select name="booking_time" class="form-control-premium" required>
                                    <option value="">-- Choose Slot --</option>
                                    <?php foreach ($time_slots as $val => $label): ?>
                                        <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit" name="book_slot" class="btn-premium" style="width: 100%; margin-top: 10px;">
                                    Confirm Session Booking
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- My Bookings Table -->
                    <div class="col-md-7">
                        <div class="pt-card">
                            <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; margin-bottom: 5px;">My Bookings</h3>
                            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 25px;">
                                View and manage your upcoming and past training sessions.
                            </p>

                            <div class="table-responsive" style="border: 1px solid var(--glass-border); border-radius: 12px; background: rgba(0,0,0,0.15); overflow: hidden;">
                                <table class="table-premium">
                                    <thead>
                                        <tr style="background: rgba(0,0,0,0.25);">
                                            <th>Date &amp; Day</th>
                                            <th>Time Slot</th>
                                            <th>Trainer</th>
                                            <th>Status</th>
                                            <th style="text-align: right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($bookings) > 0): ?>
                                            <?php foreach ($bookings as $b): 
                                                $day_str = date('l', strtotime($b['booking_date']));
                                                $date_formatted = date('d-M-Y', strtotime($b['booking_date']));
                                                $time_formatted = isset($time_slots[$b['booking_time']]) ? $time_slots[$b['booking_time']] : date('h:i A', strtotime($b['booking_time']));
                                                $is_future = ($b['booking_date'] >= $today);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong style="color: #ffffff;"><?php echo $date_formatted; ?></strong>
                                                        <div style="color: var(--text-muted); font-size: 11px;"><?php echo $day_str; ?></div>
                                                    </td>
                                                    <td>
                                                        <span style="font-weight: 600; color: var(--accent-primary);"><?php echo $time_formatted; ?></span>
                                                    </td>
                                                    <td>
                                                        <span style="color: #ffffff;"><?php echo htmlspecialchars($b['trainer_name']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge-premium badge-<?php echo $b['status']; ?>">
                                                            <?php echo ucfirst($b['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td style="text-align: right;">
                                                        <?php if ($b['status'] === 'confirmed' && $is_future): ?>
                                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>" />
                                                                <button type="submit" name="cancel_booking" class="btn btn-red btn-xs" style="margin: 0; padding: 4px 8px; border-radius: 4px;">
                                                                    Cancel
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span style="color: var(--text-muted); font-size: 12px;">--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                                    No bookings found. Schedule your first session using the form!
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>
</body>
</html>
