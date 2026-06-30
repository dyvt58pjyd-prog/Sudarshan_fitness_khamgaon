<?php
// $a = $_SERVER['HTTP_REFERER'];

// if (strpos($a, '/e-has/') !== false) {
    
// } else {
//     header("Location: ./");
// }

?>
<?php
// include 'index.php';
include './include/db_conn.php';
$key          = rtrim($_POST['login_key']);
$pass         = rtrim($_POST['pwfield']);
$user_id_auth = rtrim($_POST['login_id']);
$passconfirm= rtrim($_POST['confirmfield']);
if($pass==$passconfirm){
if (isset($user_id_auth) && isset($pass) && isset($key)) {
    $stmt = mysqli_prepare($con, "SELECT * FROM admin WHERE username=? AND securekey=?");
    mysqli_stmt_bind_param($stmt, "ss", $user_id_auth, $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count  = mysqli_num_rows($result);
    
    if ($count == 1) {
        $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);
        $update_stmt = mysqli_prepare($con, "UPDATE admin SET pass_key=? WHERE username=?");
        mysqli_stmt_bind_param($update_stmt, "ss", $hashed_pass, $user_id_auth);
        mysqli_stmt_execute($update_stmt);
        echo "<html><head><script>alert('Password Updated ,Login Again ');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    } else {
        echo "<html><head><script>alert('Change Unsuccessful');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    }
} else {
    echo "<html><head><script>alert('Change Unsuccessful');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
}
}
else{
    echo "<html><head><script>alert('Confirm Password Mismatch');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=forgot_password.php'>";
}
?>
<center>
<img src="loading.gif">
</center>
