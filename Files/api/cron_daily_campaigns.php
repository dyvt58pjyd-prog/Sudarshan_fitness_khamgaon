<?php
require '../include/db_conn.php';
require '../include/whatsapp_core.php';

// Prevent execution from non-CLI or non-cron if necessary, but we'll leave it open for manual testing.
// However, ensure this script is hidden or protected in a real env.

// ==========================================
// 1. BIOMETRIC ACCESS EXPIRATION SYNC
// ==========================================
$today = date('Y-m-d');
// Find members whose latest plan expired before today, and whose biometrics are still enabled
$exp_sql = "SELECT u.userid, u.biometric_id FROM users u 
            JOIN (SELECT uid, MAX(expire) as last_expire FROM enrolls_to GROUP BY uid) e ON u.userid = e.uid 
            WHERE e.last_expire < '$today' AND u.biometric_enabled = 1";
$exp_res = mysqli_query($con, $exp_sql);

if ($exp_res && mysqli_num_rows($exp_res) > 0) {
    while ($row = mysqli_fetch_assoc($exp_res)) {
        $uid = $row['userid'];
        $bio_id = $row['biometric_id'];
        
        // 1. Disable access in DB
        mysqli_query($con, "UPDATE users SET biometric_enabled = 0 WHERE userid = '$uid'");
        
        // 2. Queue command to wipe/block on machine
        if (!empty($bio_id)) {
            $cmd_payload = json_encode(['reason' => 'membership_expired']);
            mysqli_query($con, "INSERT INTO biometric_commands (command_type, target_uid, payload, status) 
                                VALUES ('DELETE_USER', '$bio_id', '$cmd_payload', 'pending')");
        }
    }
    echo "Biometric Expiration Sync: Disabled " . mysqli_num_rows($exp_res) . " expired members.<br>";
}

// ==========================================
// 2. DAILY FESTIVAL CAMPAIGNS
// ==========================================

$today = date('Y-m-d');
$sql = "SELECT * FROM festival_campaigns WHERE scheduled_date = '$today' AND status = 'Pending'";
$res = mysqli_query($con, $sql);

if (mysqli_num_rows($res) == 0) {
    echo "No pending campaigns for today.<br>";
    exit();
}

$gym = get_gym_details($con);
$public_base_url = "https://sudarshanfitness.de/Files"; 
// Adjust the base URL if needed, depending on how they access the script.

while ($campaign = mysqli_fetch_assoc($res)) {
    $cid = $campaign['id'];
    
    // Generate the Poster Image to Disk
    $save_path = "../images/campaign_{$cid}.png";
    $image_url = $public_base_url . "/images/campaign_{$cid}.png";
    
    // Call the generator locally via shell or curl?
    // The easiest way is to include generate_poster logic, but it's isolated.
    // Let's use curl to hit our own API and save the output.
    $generator_url = "http://localhost/Files/api/generate_poster.php?campaign_id={$cid}&save_path=" . urlencode($save_path);
    // Since localhost might not resolve perfectly depending on Hostinger setup, let's use the public URL
    $generator_url = $public_base_url . "/api/generate_poster.php?campaign_id={$cid}&save_path=../images/campaign_{$cid}.png";
    
    // Make the request to generate the image
    $ch = curl_init($generator_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    
    // Fetch all active members
    $members_sql = "SELECT username, mobile, email FROM users";
    $members_res = mysqli_query($con, $members_sql);
    
    $message = "Hey " . "Member" . ",\n\n" . $campaign['message_text'] . "\n\nWarm Regards,\n" . $gym['gym_name'];
    
    while ($member = mysqli_fetch_assoc($members_res)) {
        // Send WhatsApp
        if (!empty($member['mobile'])) {
            $personalized_msg = str_replace("Member", $member['username'], $message);
            send_meta_whatsapp_image($con, $member['mobile'], $personalized_msg, $image_url);
        }
        
        // Send Email (if they have smtp_mailer configured, we can include it)
        // require_once '../include/smtp_mailer.php';
        // if (!empty($member['email'])) {
        //     smtp_mailer($member['email'], $campaign['campaign_name'], $personalized_msg); 
        // }
    }
    
    // Mark as Sent
    mysqli_query($con, "UPDATE festival_campaigns SET status = 'Sent' WHERE id = $cid");
    echo "Campaign {$cid} executed successfully.<br>";
}
?>
