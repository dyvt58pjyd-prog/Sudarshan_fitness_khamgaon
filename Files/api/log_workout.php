<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';
page_protect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
if (!$input) {
    $input = $_POST;
}

$userid = $_SESSION['user_data'];
$muscle = isset($input['muscle']) ? trim($input['muscle']) : '';
$intensity = isset($input['intensity']) ? intval($input['intensity']) : 5;

if (empty($muscle)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Muscle group is required.']);
    exit();
}

$uid_esc = mysqli_real_escape_string($con, $userid);
$muscle_esc = mysqli_real_escape_string($con, $muscle);
$today_str = date('Y-m-d');

// Check if already logged this muscle today to prevent XP spam
$check_sql = "SELECT id FROM workout_logs WHERE uid = '$uid_esc' AND muscle_group = '$muscle_esc' AND DATE(log_date) = '$today_str'";
$check_res = mysqli_query($con, $check_sql);

$xp_awarded = 0;
$new_rank = '';
if ($check_res && mysqli_num_rows($check_res) === 0) {
    // Award 10 XP for logging a new muscle group today
    $xp_data = add_member_xp($con, $userid, 10);
    if ($xp_data) {
        $xp_awarded = 10;
        $new_rank = $xp_data['new_rank'];
    }
}

// Insert log
$insert_sql = "INSERT INTO workout_logs (uid, muscle_group, intensity) VALUES ('$uid_esc', '$muscle_esc', $intensity)";
if (mysqli_query($con, $insert_sql)) {
    echo json_encode([
        'success' => true,
        'message' => 'Workout logged successfully!',
        'muscle' => $muscle,
        'xp_earned' => $xp_awarded,
        'new_rank' => $new_rank
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($con)
    ]);
}
?>
