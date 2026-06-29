<?php
require '../../include/db_conn.php';
page_protect();

if (isset($_POST['id']) && strlen($_POST['id']) > 0) {
    $id = mysqli_real_escape_string($con, $_POST['id']);
    
    $query = "DELETE FROM personal_training WHERE id='$id'";
    if (mysqli_query($con, $query)) {
        echo "<html><head><script>alert('Personal Training record deleted successfully');</script></head></html>";
    } else {
        echo "<html><head><script>alert('ERROR! Delete operation failed');</script></head></html>";
    }
    echo "<meta http-equiv='refresh' content='0; url=view_pt.php'>";
} else {
    header("Location: view_pt.php");
    exit();
}
?>
