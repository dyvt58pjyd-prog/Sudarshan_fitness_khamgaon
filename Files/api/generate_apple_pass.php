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

// Generate Apple pass.json data
$pass_data = [
    "formatVersion" => 1,
    "passTypeIdentifier" => "pass.com.sudarshanfitness.gympass",
    "serialNumber" => "SF-" . $user['userid'] . "-" . time(),
    "teamIdentifier" => "SUDARSHAN26",
    "organizationName" => $gym['gym_name'],
    "description" => $gym['gym_name'] . " Official Gate Pass",
    "logoText" => $gym['gym_name'],
    "foregroundColor" => "rgb(255, 255, 255)",
    "backgroundColor" => "rgb(15, 23, 42)",
    "labelColor" => "rgb(56, 189, 248)",
    "generic" => [
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
                "key" => "status",
                "label" => "PASS STATUS",
                "value" => $status_text
            ],
            [
                "key" => "uid",
                "label" => "MEMBER ID",
                "value" => "#" . $user['userid']
            ]
        ],
        "backFields" => [
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
                "key" => "instructions",
                "label" => "GYM ACCESS RULES",
                "value" => "Please scan your QR code at entrance gate. Carry clean training shoes and a gym towel at all times."
            ]
        ]
    ],
    "barcodes" => [
        [
            "message" => "MEMBER_ID:" . $user['userid'],
            "format" => "PKBarcodeFormatQR",
            "messageEncoding" => "iso-8859-1",
            "altText" => "MEMBER ID: " . $user['userid']
        ]
    ]
];

$json_content = json_encode($pass_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Create in-memory ZIP package (.pkpass)
$zip = new ZipArchive();
$temp_file = tempnam(sys_get_temp_dir(), 'pkpass_');

if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $zip->addFromString('pass.json', $json_content);
    
    // Add default icon/logo images if available
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
    header('Content-Disposition: attachment; filename="Sudarshan_Pass_' . $user['userid'] . '.pkpass"');
    header('Content-Length: ' . filesize($temp_file));
    
    readfile($temp_file);
    @unlink($temp_file);
    exit;
} else {
    // Fallback direct JSON download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="pass.json"');
    echo $json_content;
    exit;
}
