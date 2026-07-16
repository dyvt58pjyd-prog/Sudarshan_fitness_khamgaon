<?php
require '../include/db_conn.php';

// Disable error reporting to prevent HTML warnings from corrupting the JSON payload
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$gym = get_gym_details($con);
$daily_tip_enabled = isset($gym['daily_tip_enabled']) ? intval($gym['daily_tip_enabled']) : 1;
$last_tip_sent = isset($gym['last_tip_sent']) ? $gym['last_tip_sent'] : '';
$today = date('Y-m-d');
$is_manual = (isset($_GET['manual']) && $_GET['manual'] == 1);

// 1. Check if daily tips are globally enabled
if ($daily_tip_enabled !== 1) {
    echo json_encode([
        'success' => false,
        'send' => false,
        'message' => 'Daily motivation tips are disabled in settings.'
    ]);
    exit();
}

// 2. Check if a tip was already sent today (bypass on manual trigger)
if (!$is_manual && !empty($last_tip_sent) && $last_tip_sent === $today) {
    echo json_encode([
        'success' => true,
        'send' => false,
        'message' => 'Daily motivation tip already sent today.'
    ]);
    exit();
}

// 3. Fetch a random motivational tip from gym_tips
$tip_q = mysqli_query($con, "SELECT tip_text, category FROM gym_tips ORDER BY RAND() LIMIT 1");
if (!$tip_q || mysqli_num_rows($tip_q) === 0) {
    echo json_encode([
        'success' => false,
        'send' => false,
        'message' => 'No motivational tips found in database.'
    ]);
    exit();
}

$tip_row = mysqli_fetch_assoc($tip_q);
$tip_text = $tip_row['tip_text'];
$category = $tip_row['category'];

// Format the broadcast message
$gym_name = isset($gym['gym_name']) ? $gym['gym_name'] : 'Titan Gym';
$broadcast_message = "🌟 *Daily Gym Motivation* - *{$gym_name}* 🌟\n\n" .
                     "{$tip_text}\n\n" .
                     "Category: _{$category}_\n" .
                     "Have a power-packed day! 💪🏋️";

// 4. Query all active member mobile numbers
$members_q = mysqli_query($con, "
    SELECT DISTINCT u.mobile 
    FROM users u
    INNER JOIN enrolls_to e ON u.userid = e.uid
    WHERE e.expire >= '$today' 
      AND e.renewal = 'yes'
      AND u.mobile IS NOT NULL 
      AND u.mobile != ''
");

$numbers = [];
if ($members_q && mysqli_num_rows($members_q) > 0) {
    while ($row = mysqli_fetch_assoc($members_q)) {
        $numbers[] = $row['mobile'];
    }
}

// If no active members found, return empty numbers
if (empty($numbers)) {
    echo json_encode([
        'success' => true,
        'send' => false,
        'message' => 'No active members with registered mobile numbers found.'
    ]);
    exit();
}

// 5. If not manual test check, lock/update the daily tip timestamp immediately in database to prevent double sending
if (!$is_manual) {
    mysqli_query($con, "UPDATE gym_details SET last_tip_sent = '$today' WHERE id = 1");
}

echo json_encode([
    'success' => true,
    'send' => true,
    'message' => $broadcast_message,
    'numbers' => $numbers
]);
exit();
