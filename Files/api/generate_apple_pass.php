<?php
session_start();
require '../include/db_conn.php';

// Check member session or token query param
$uid = $_SESSION['member_uid'] ?? $_SESSION['user_data'] ?? ($_GET['uid'] ?? '');
$uid = mysqli_real_escape_string($con, preg_replace('/[^a-zA-Z0-9_-]/', '', $uid));

if (empty($uid)) {
    die("Error: Member ID required.");
}

$gym = get_gym_details($con);

// Fetch user data
$q_user = mysqli_query($con, "SELECT u.*, a.city, a.state FROM users u LEFT JOIN address a ON u.userid = a.id WHERE u.userid='$uid'");
if (!$q_user || mysqli_num_rows($q_user) === 0) {
    die("Error: Member not found.");
}
$user = mysqli_fetch_assoc($q_user);

// Fetch Active Membership
$q_plan = mysqli_query($con, "SELECT p.planName, e.expire, e.paid_date FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$uid' AND e.renewal='yes' ORDER BY e.expire DESC LIMIT 1");
$plan_name = "Standard Membership";
$expire_date = "2026-12-31";
$status_text = "ACTIVE VIP";
if ($q_plan && mysqli_num_rows($q_plan) > 0) {
    $plan_row = mysqli_fetch_assoc($q_plan);
    $plan_name = $plan_row['planName'];
    $expire_date = date('Y-m-d', strtotime($plan_row['expire']));
    if (strtotime($plan_row['expire']) < strtotime(date('Y-m-d'))) {
        $status_text = "EXPIRED";
    }
}

// Fetch Trainer Name
$trainer_name = "General Floor Coach";
if (!empty($user['trainer_id'])) {
    $tr_id = $user['trainer_id'];
    $q_tr = mysqli_query($con, "SELECT Full_name FROM admin WHERE username='$tr_id'");
    if ($q_tr && $r_tr = mysqli_fetch_assoc($q_tr)) {
        $trainer_name = $r_tr['Full_name'];
    }
}

// Generate Apple pass.json data with all details & logo
$pass_data = [
    "formatVersion" => 1,
    "passTypeIdentifier" => "pass.com.sudarshanfitness.gympass",
    "serialNumber" => "SF-" . $user['userid'] . "-" . time(),
    "teamIdentifier" => "SUDARSHAN26",
    "organizationName" => $gym['gym_name'],
    "description" => $gym['gym_name'] . " Official VIP Gym Pass",
    "logoText" => $gym['gym_name'],
    "foregroundColor" => "rgb(255, 255, 255)",
    "backgroundColor" => "rgb(15, 23, 42)",
    "labelColor" => "rgb(56, 189, 248)",
    "generic" => [
        "headerFields" => [
            [
                "key" => "status",
                "label" => "STATUS",
                "value" => $status_text
            ]
        ],
        "primaryFields" => [
            [
                "key" => "member",
                "label" => "ATHLETE NAME",
                "value" => $user['username']
            ]
        ],
        "secondaryFields" => [
            [
                "key" => "plan",
                "label" => "MEMBERSHIP PLAN",
                "value" => $plan_name
            ],
            [
                "key" => "expiry",
                "label" => "VALID TILL",
                "value" => date('d M Y', strtotime($expire_date))
            ]
        ],
        "auxiliaryFields" => [
            [
                "key" => "uid",
                "label" => "MEMBER ID",
                "value" => "#" . $user['userid']
            ],
            [
                "key" => "coach",
                "label" => "TRAINER",
                "value" => $trainer_name
            ],
            [
                "key" => "mobile",
                "label" => "MOBILE",
                "value" => $user['mobile']
            ]
        ],
        "backFields" => [
            [
                "key" => "gym_name",
                "label" => "FITNESS CENTER",
                "value" => $gym['gym_name'] . " (Khamgaon)"
            ],
            [
                "key" => "gym_location",
                "label" => "FACILITY ADDRESS",
                "value" => $gym['gym_address']
            ],
            [
                "key" => "gym_phone",
                "label" => "HELPLINE / SUPPORT",
                "value" => $gym['gym_contact']
            ],
            [
                "key" => "joining",
                "label" => "MEMBER SINCE",
                "value" => !empty($user['joining_date']) ? date('d M Y', strtotime($user['joining_date'])) : '2024'
            ],
            [
                "key" => "instructions",
                "label" => "GYM ACCESS & SAFETY RULES",
                "value" => "1. Scan your QR code at the entrance optical scanner.\n2. Always carry clean indoor gym shoes and a sweat towel.\n3. Re-rack all weights and dumbbells after use.\n4. For renewal or batch changes, contact the front desk."
            ]
        ]
    ],
    "barcodes" => [
        [
            "message" => "MEMBER_ID:" . $user['userid'],
            "format" => "PKBarcodeFormatQR",
            "messageEncoding" => "iso-8859-1",
            "altText" => "MEMBER ID: #" . $user['userid']
        ]
    ]
];

$json_content = json_encode($pass_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Create in-memory ZIP package (.pkpass)
$zip = new ZipArchive();
$temp_file = tempnam(sys_get_temp_dir(), 'pkpass_');

if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $zip->addFromString('pass.json', $json_content);
    
    // Add default icon/logo images
    $logo_path = __DIR__ . '/../images/logo.png';
    if (file_exists($logo_path)) {
        $zip->addFile($logo_path, 'icon.png');
        $zip->addFile($logo_path, 'icon@2x.png');
        $zip->addFile($logo_path, 'logo.png');
        $zip->addFile($logo_path, 'logo@2x.png');
    }
    
    // Manifest sha1 hashes
    $manifest = [
        'pass.json' => sha1($json_content)
    ];
    $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    
    $zip->close();

    // Stream Apple Wallet Pass (.pkpass)
    header('Pragma: no-cache');
    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    header('Content-Type: application/vnd.apple.pkpass');
    header('Content-Disposition: attachment; filename="Sudarshan_VIP_Pass_' . $user['userid'] . '.pkpass"');
    header('Content-Length: ' . filesize($temp_file));
    
    readfile($temp_file);
    @unlink($temp_file);
    exit;
} else {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="pass.json"');
    echo $json_content;
    exit;
}
