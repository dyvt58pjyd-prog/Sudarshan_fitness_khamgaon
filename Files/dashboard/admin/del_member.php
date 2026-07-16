<?php
require '../../include/db_conn.php';
page_protect();

$msgid = $_POST['name'];
if (strlen($msgid) > 0) {
    $msgid = mysqli_real_escape_string($con, $msgid);
    
    // Delete associated records first to prevent foreign key constraint failures
    mysqli_query($con, "DELETE FROM enrolls_to WHERE uid='$msgid'");
    mysqli_query($con, "DELETE FROM address WHERE id='$msgid'");
    mysqli_query($con, "DELETE FROM health_status WHERE uid='$msgid'");
    mysqli_query($con, "DELETE FROM attendance WHERE uid='$msgid'");
    
    // Now delete the user and admin accounts
    $delete_user = mysqli_query($con, "DELETE FROM users WHERE userid='$msgid'");
    mysqli_query($con, "DELETE FROM admin WHERE username='$msgid' AND role='member'");
    
    if ($delete_user) {
        echo "<html><head><script>alert('Member Deleted');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=view_mem.php'>";
    } else {
        echo "<html><head><script>alert('ERROR! Delete Operation Unsuccessful');</script></head></html>";
        echo "error".mysqli_error($con);
    }
} else {
    echo "<html><head><script>alert('ERROR! Delete Opertaion Unsucessfull');</script></head></html>";
   echo "error".mysqli_error($con);
}

?>