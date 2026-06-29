<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    die("Unauthorized access.");
}

if (isset($_POST['import_members_btn'])) {
    $filename = $_FILES['members_file']['tmp_name'];
    $real_filename = $_FILES['members_file']['name'];
    $ext = strtolower(pathinfo($real_filename, PATHINFO_EXTENSION));
    
    if ($_FILES['members_file']['size'] > 0) {
        $target_dir = "../../Sudarshan Data Folder/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $new_filename = 'import_members_' . time() . '_' . basename($real_filename);
        $target_path = $target_dir . $new_filename;
        if (move_uploaded_file($filename, $target_path)) {
            $filename = $target_path;
        }
        $rows = [];
        $is_excel = false;
        
        // Include Excel helpers
        require_once '../../include/SimpleXLSX.php';
        require_once '../../include/SimpleXLS.php';
        
        // Try to parse as XLSX first
        if ($ext === 'xlsx') {
            if ($xlsx = \Shuchkin\SimpleXLSX::parse($filename)) {
                $rows = $xlsx->rows();
                if (count($rows) > 0) {
                    array_shift($rows); // Remove header row
                    $is_excel = true;
                }
            }
        }
        
        // Try to parse as XLS if not parsed yet
        if (!$is_excel && $ext === 'xls') {
            if ($xls = \Shuchkin\SimpleXLS::parse($filename)) {
                $rows = $xls->rows();
                if (count($rows) > 0) {
                    array_shift($rows); // Remove header row
                    $is_excel = true;
                }
            }
        }
        
        // Fallback to CSV parsing
        if (!$is_excel) {
            $file = fopen($filename, 'r');
            // Skip BOM if present
            $bom = fread($file, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($file);
            }
            
            // Skip header row
            fgetcsv($file, 0, ",", "\"", "\\");
            
            while (($data = fgetcsv($file, 10000, ",", "\"", "\\")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($file);
        }
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($rows as $data) {
            // Data fields mapping: ID, Name, Gender, Mobile, Email, DOB, Joining Date, Street, State, City, Zip
            $userid = mysqli_real_escape_string($con, trim($data[0]));
            $username = mysqli_real_escape_string($con, trim($data[1]));
            $gender = mysqli_real_escape_string($con, trim($data[2]));
            $mobile = mysqli_real_escape_string($con, trim($data[3]));
            $email = mysqli_real_escape_string($con, trim($data[4]));
            
            $dob = mysqli_real_escape_string($con, trim($data[5]));
            // Try normalizing date format (e.g. Excel serial date, DD/MM/YYYY, or DD-MM-YYYY)
            if (is_numeric($dob) && strlen($dob) < 6 && (int)$dob > 0) {
                $dob_time = ($dob - 25569) * 86400;
                $dob = date('Y-m-d', $dob_time);
            } else {
                $dob_clean = str_replace('/', '-', $dob);
                $dob_time = @strtotime($dob_clean);
                if ($dob_time !== FALSE && $dob_time > 0) {
                    $dob = date('Y-m-d', $dob_time);
                }
            }
            
            $joining_date = mysqli_real_escape_string($con, trim($data[6]));
            // Try normalizing date format (e.g. Excel serial date, DD/MM/YYYY, or DD-MM-YYYY)
            if (is_numeric($joining_date) && strlen($joining_date) < 6 && (int)$joining_date > 0) {
                $jd_time = ($joining_date - 25569) * 86400;
                $joining_date = date('Y-m-d', $jd_time);
            } else {
                $jd_clean = str_replace('/', '-', $joining_date);
                $jd_time = @strtotime($jd_clean);
                if ($jd_time !== FALSE && $jd_time > 0) {
                    $joining_date = date('Y-m-d', $jd_time);
                }
            }
            
            $street = mysqli_real_escape_string($con, trim($data[7]));
            $state = mysqli_real_escape_string($con, trim($data[8]));
            $city = mysqli_real_escape_string($con, trim($data[9]));
            $zipcode = mysqli_real_escape_string($con, trim($data[10]));
            
            if (empty($userid) || empty($username)) {
                $skipped++;
                continue;
            }
            
            // Check if user already exists
            $check = mysqli_query($con, "SELECT * FROM users WHERE userid='$userid'");
            if (mysqli_num_rows($check) > 0) {
                $skipped++;
                continue;
            }
            
            // Insert into users
            $q_user = "INSERT INTO users (userid, username, gender, mobile, email, dob, joining_date, biometric_id, biometric_enabled) 
                       VALUES ('$userid', '$username', '$gender', '$mobile', '$email', '$dob', '$joining_date', '$userid', 1)";
            if (mysqli_query($con, $q_user)) {
                // Insert into address
                mysqli_query($con, "INSERT IGNORE INTO address (id, streetName, state, city, zipcode) 
                                    VALUES ('$userid', '$street', '$state', '$city', '$zipcode')");
                // Insert into health status
                mysqli_query($con, "INSERT IGNORE INTO health_status (uid) VALUES ('$userid')");
                
                // Create user login auth in admin table (default password is user's mobile or 'member123')
                $password = !empty($mobile) ? $mobile : 'member123';
                mysqli_query($con, "INSERT IGNORE INTO admin (username, pass_key, securekey, Full_name, role) 
                                    VALUES ('$userid', '$password', 'member', '$username', 'member')");
                
                $imported++;
            } else {
                $skipped++;
            }
        }
        echo "<head><script>alert('Import finished! Imported: $imported, Skipped: $skipped');</script></head>";
    }
}
echo "<meta http-equiv='refresh' content='0; url=backup_data.php'>";
?>
