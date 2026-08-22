<?php
session_start();
require '../include/db_conn.php';

$uid = $_SESSION['member_uid'] ?? $_SESSION['user_data'] ?? ($_GET['uid'] ?? '');
$uid = mysqli_real_escape_string($con, preg_replace('/[^a-zA-Z0-9_-]/', '', $uid));

if (empty($uid)) {
    die("Error: Member ID required.");
}

$gym = get_gym_details($con);

// Fetch user data
$q_user = mysqli_query($con, "SELECT * FROM users WHERE userid='$uid'");
if (!$q_user || mysqli_num_rows($q_user) === 0) {
    die("Error: Member not found.");
}
$user = mysqli_fetch_assoc($q_user);

// Fetch Active Membership
$q_plan = mysqli_query($con, "SELECT p.planName, e.expire, e.paid_date FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$uid' AND e.renewal='yes' ORDER BY e.expire DESC LIMIT 1");
$plan_name = "Standard Membership";
$expire_date = "2026-12-31";
$status_text = "ACTIVE PASS";
if ($q_plan && mysqli_num_rows($q_plan) > 0) {
    $plan_row = mysqli_fetch_assoc($q_plan);
    $plan_name = $plan_row['planName'];
    $expire_date = date('Y-m-d', strtotime($plan_row['expire']));
    if (strtotime($plan_row['expire']) < strtotime(date('Y-m-d'))) {
        $status_text = "EXPIRED";
    }
}

// Google Wallet Generic Pass definition
$google_pass_object = [
    "id" => "3388000000022218888.SF_" . $user['userid'],
    "classId" => "3388000000022218888.sudarshan_gym_pass",
    "logo" => [
        "sourceUri" => [
            "uri" => "https://sudarshanfitness.de/images/logo.png"
        ],
        "contentDescription" => [
            "defaultValue" => [
                "language" => "en-US",
                "value" => "Sudarshan Fitness"
            ]
        ]
    ],
    "cardTitle" => [
        "defaultValue" => [
            "language" => "en-US",
            "value" => $gym['gym_name'] . " Gate Pass"
        ]
    ],
    "subheader" => [
        "defaultValue" => [
            "language" => "en-US",
            "value" => "Athlete"
        ]
    ],
    "header" => [
        "defaultValue" => [
            "language" => "en-US",
            "value" => $user['username']
        ]
    ],
    "barcode" => [
        "type" => "QR_CODE",
        "value" => "MEMBER_ID:" . $user['userid'],
        "alternateText" => "#" . $user['userid']
    ],
    "hexBackgroundColor" => "#0f172a"
];

// If requested via JSON, return data
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($google_pass_object, JSON_PRETTY_PRINT);
    exit;
}

// Redirect Android / Google Pay Wallet URL or show save dialog
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add to Google Wallet | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { background: #030712; color: #fff; font-family: 'Outfit', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 30px 25px; text-align: center; max-width: 380px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .btn-google { background: #fff; color: #000; font-weight: 800; padding: 14px 20px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; width: 100%; margin-top: 15px; font-size: 14px; box-shadow: 0 4px 15px rgba(255,255,255,0.2); }
    </style>
</head>
<body>
    <div class="card">
        <img src="../images/logo.png" alt="Logo" style="max-height: 60px; margin-bottom: 15px;">
        <h2 style="font-size: 20px; font-weight: 800;"><?php echo htmlspecialchars($user['username']); ?></h2>
        <p style="color: #94a3b8; font-size: 13px; margin: 4px 0 20px 0;">Member ID #<?php echo htmlspecialchars($user['userid']); ?> • <?php echo htmlspecialchars($plan_name); ?></p>
        
        <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 14px; margin-bottom: 20px; text-align: left; font-size: 12px;">
            <div style="color: #94a3b8;">Status: <strong style="color: #10b981;"><?php echo $status_text; ?></strong></div>
            <div style="color: #94a3b8; margin-top: 4px;">Valid Till: <strong style="color: #fff;"><?php echo date('d M Y', strtotime($expire_date)); ?></strong></div>
        </div>

        <a href="https://pay.google.com/gp/v/save/eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.SF_<?php echo $user['userid']; ?>" class="btn-google" onclick="alert('✅ Digital Pass ready! Google Wallet will sync your gym card.');">
            <svg width="22" height="22" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
            Save to Google Wallet
        </a>

        <a href="../member_app/wallet_pass.php" style="display: block; color: #94a3b8; font-size: 12px; margin-top: 15px; text-decoration: none;">
            ← Return to Pass
        </a>
    </div>
</body>
</html>
