<?php
require '../../include/db_conn.php';
page_protect();

if (isset($_POST['uid']) && isset($_POST['date'])) {
    $uid = mysqli_real_escape_string($con, $_POST['uid']);
    $date = mysqli_real_escape_string($con, $_POST['date']);
    $workout_details = isset($_POST['workout_details']) ? mysqli_real_escape_string($con, $_POST['workout_details']) : '';
    $nutrition_notes = isset($_POST['nutrition_notes']) ? mysqli_real_escape_string($con, $_POST['nutrition_notes']) : '';
    $trainer_remarks = isset($_POST['trainer_remarks']) ? mysqli_real_escape_string($con, $_POST['trainer_remarks']) : '';
    $achievements = isset($_POST['achievements']) ? mysqli_real_escape_string($con, $_POST['achievements']) : '';
    
    // Logged in trainer username
    $trainer_id = mysqli_real_escape_string($con, $_SESSION['username']);
    
    $query = "INSERT INTO personal_training (uid, trainer_id, date, workout_details, nutrition_notes, trainer_remarks, achievements) 
              VALUES ('$uid', '$trainer_id', '$date', '$workout_details', '$nutrition_notes', '$trainer_remarks', '$achievements')";
              
    if (mysqli_query($con, $query)) {
        echo "<head><script>alert('Personal Training Session Logged Successfully!');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=view_pt.php'>";
    } else {
        echo "<head><script>alert('Failed to log personal training session.');</script></head></html>";
        echo "error: " . mysqli_error($con);
    }
} else {
    header("Location: add_pt.php");
    exit();
}
?>
