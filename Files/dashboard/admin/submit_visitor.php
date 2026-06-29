<?php
require '../../include/db_conn.php';
page_protect();

if (!in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

if (isset($_POST['submit_visitor'])) {
    $v_name = mysqli_real_escape_string($con, $_POST['v_name']);
    $mobile = mysqli_real_escape_string($con, $_POST['mobile']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $notes = mysqli_real_escape_string($con, $_POST['notes']);
    
    date_default_timezone_set("Asia/Calcutta");
    $visit_date = date('Y-m-d H:i:s');
    
    $photo_path_db = "";

    // Handle the captured WebRTC photo
    if (!empty($_POST['captured_photo'])) {
        $base64_string = $_POST['captured_photo'];
        $image_parts = explode(";base64,", $base64_string);
        
        if (count($image_parts) == 2) {
            $image_base64 = base64_decode($image_parts[1]);
            
            $target_dir = "../../Sudarshan Data Folder/Visitors/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $photo_filename = "visitor_" . time() . "_" . rand(1000, 9999) . ".jpg";
            $photo_target = $target_dir . $photo_filename;
            
            if (file_put_contents($photo_target, $image_base64)) {
                $photo_path_db = "../../Sudarshan Data Folder/Visitors/" . $photo_filename;
            }
        }
    }

    $query = "INSERT INTO visitors (name, mobile, address, photo_path, visit_date, status, notes) 
              VALUES ('$v_name', '$mobile', '$address', '$photo_path_db', '$visit_date', 'visited', '$notes')";

    if (mysqli_query($con, $query)) {
        echo "<script>alert('Visitor logged successfully!'); window.location.href='visitors_list.php';</script>";
    } else {
        echo "<script>alert('Error logging visitor: " . mysqli_error($con) . "'); window.history.back();</script>";
    }
} else {
    header("Location: visitor_entry.php");
    exit();
}
?>
