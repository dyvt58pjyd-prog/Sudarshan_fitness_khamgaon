<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo "<head><script>alert('Unauthorized access! Only Super Admins can delete payment records.');</script></head>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

if (isset($_GET['etid']) && isset($_GET['uid'])) {
    $etid = mysqli_real_escape_string($con, $_GET['etid']);
    $uid  = mysqli_real_escape_string($con, $_GET['uid']);

    // Check if the record to be deleted is currently the active renewal record
    $check_query = "SELECT * FROM enrolls_to WHERE et_id='$etid'";
    $check_result = mysqli_query($con, $check_query);
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $row = mysqli_fetch_assoc($check_result);
        $was_renewal_yes = ($row['renewal'] === 'yes');

        // Delete the payment record
        $delete_query = "DELETE FROM enrolls_to WHERE et_id='$etid'";
        if (mysqli_query($con, $delete_query)) {
            // If it was the active renewal, find the most recent remaining payment and set its renewal to 'yes'
            if ($was_renewal_yes) {
                $find_prev_query = "SELECT * FROM enrolls_to WHERE uid='$uid' ORDER BY expire DESC LIMIT 1";
                $find_prev_res = mysqli_query($con, $find_prev_query);
                if ($find_prev_res && mysqli_num_rows($find_prev_res) > 0) {
                    $prev_row = mysqli_fetch_assoc($find_prev_res);
                    $prev_etid = $prev_row['et_id'];
                    mysqli_query($con, "UPDATE enrolls_to SET renewal='yes' WHERE et_id='$prev_etid'");
                }
            }
            echo "<head><script>alert('Payment record deleted successfully.');</script></head>";
        } else {
            echo "<head><script>alert('Failed to delete payment record: " . mysqli_error($con) . "');</script></head>";
        }
    } else {
        echo "<head><script>alert('Payment record not found.');</script></head>";
    }
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : "read_member.php?name=" . $uid;
    echo "<meta http-equiv='refresh' content='0; url=" . $redirect . "'>";
} else {
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
}
?>
