<?php
require '../../include/db_conn.php';
page_protect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $uid = mysqli_real_escape_string($con, $_POST['m_id']);
    $trainer_id = mysqli_real_escape_string($con, $_POST['trainer_id']);
    $enroll_date = mysqli_real_escape_string($con, $_POST['enroll_date']);
    $expire_date = mysqli_real_escape_string($con, $_POST['expire_date']);
    $amount = intval($_POST['amount']);
    $payment_mode = mysqli_real_escape_string($con, $_POST['payment_mode']);
    $received_by = mysqli_real_escape_string($con, $_SESSION['full_name']);

    // Start transaction or basic execute
    // 1. Insert into pt_enrollments table
    $q_insert = "INSERT INTO pt_enrollments (uid, trainer_id, enroll_date, expire_date, amount, payment_mode, received_by) 
                 VALUES ('$uid', '$trainer_id', '$enroll_date', '$expire_date', $amount, '$payment_mode', '$received_by')";
    
    if (mysqli_query($con, $q_insert)) {
        $pt_id = mysqli_insert_id($con);
        
        // 2. Update users.trainer_id column
        $q_update = "UPDATE users SET trainer_id = '$trainer_id' WHERE userid = '$uid'";
        mysqli_query($con, $q_update);

        // 3. Retrieve member details & trainer name for email confirmation
        $q_mem = mysqli_query($con, "SELECT username, email FROM users WHERE userid = '$uid'");
        $member = mysqli_fetch_assoc($q_mem);
        $mem_name = $member['username'];
        $mem_email = $member['email'];

        $q_tr = mysqli_query($con, "SELECT Full_name FROM admin WHERE username = '$trainer_id'");
        $trainer = mysqli_fetch_assoc($q_tr);
        $trainer_fullname = $trainer['Full_name'];

        // 4. Send email receipt (which automatically triggers WhatsApp receipt with PDF to member)
        if (!empty($mem_email)) {
            send_pt_email($con, $mem_email, $mem_name, $uid, $trainer_fullname, $amount, $expire_date, $payment_mode, $received_by);
        }

        // 5. Send WhatsApp assignment alert to trainer
        send_whatsapp_trainer_pt_notification($con, $trainer_id, $mem_name, $uid);

        echo "<script>alert('Personal Training successfully enrolled! Receipt sent via Email/WhatsApp.'); window.location.href = 'read_member.php?name=" . urlencode($uid) . "';</script>";
        exit();
    } else {
        echo "<script>alert('Error enrolling PT: " . mysqli_error($con) . "'); window.history.back();</script>";
    }
} else {
    header("Location: payments.php");
    exit();
}
?>
