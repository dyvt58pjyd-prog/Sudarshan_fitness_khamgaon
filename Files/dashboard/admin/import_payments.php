<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    die("Unauthorized access.");
}

if (isset($_POST['import_payments_btn'])) {
    $filename = $_FILES['payments_file']['tmp_name'];
    $real_filename = $_FILES['payments_file']['name'];
    $ext = strtolower(pathinfo($real_filename, PATHINFO_EXTENSION));
    
    if ($_FILES['payments_file']['size'] > 0) {
        $target_dir = "../../Sudarshan Data Folder/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $new_filename = 'import_payments_' . time() . '_' . basename($real_filename);
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
        
        // Try to parse as XLS next if not yet parsed
        if (!$is_excel && $ext === 'xls') {
            if ($xls = \Shuchkin\SimpleXLS::parse($filename)) {
                $rows = $xls->rows();
                if (count($rows) > 0) {
                    array_shift($rows); // Remove header row
                    $is_excel = true;
                }
            }
        }
        
        // Fallback to CSV
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
            // Data fields mapping: ID, MemberID, PlanName, PaidDate, ExpiryDate, Amount, Mode, ReceivedBy
            $uid = mysqli_real_escape_string($con, trim($data[1]));
            $plan_name = mysqli_real_escape_string($con, trim($data[2]));
            
            $paid_date = mysqli_real_escape_string($con, trim($data[3]));
            // Try normalizing date format (e.g. Excel serial date, DD/MM/YYYY, or DD-MM-YYYY)
            if (is_numeric($paid_date) && strlen($paid_date) < 6 && (int)$paid_date > 0) {
                $pd_time = ($paid_date - 25569) * 86400;
                $paid_date = date('Y-m-d', $pd_time);
            } else {
                $pd_clean = str_replace('/', '-', $paid_date);
                $pd_time = @strtotime($pd_clean);
                if ($pd_time !== FALSE && $pd_time > 0) {
                    $paid_date = date('Y-m-d', $pd_time);
                }
            }
            
            $expire = mysqli_real_escape_string($con, trim($data[4]));
            // Try normalizing date format (e.g. Excel serial date, DD/MM/YYYY, or DD-MM-YYYY)
            if (is_numeric($expire) && strlen($expire) < 6 && (int)$expire > 0) {
                $exp_time = ($expire - 25569) * 86400;
                $expire = date('Y-m-d', $exp_time);
            } else {
                $exp_clean = str_replace('/', '-', $expire);
                $exp_time = @strtotime($exp_clean);
                if ($exp_time !== FALSE && $exp_time > 0) {
                    $expire = date('Y-m-d', $exp_time);
                }
            }
            
            $payment_mode = mysqli_real_escape_string($con, trim($data[6]));
            $received_by = mysqli_real_escape_string($con, trim($data[7]));
            
            if (empty($uid) || empty($plan_name)) {
                $skipped++;
                continue;
            }
            
            // Check if member exists
            $check_user = mysqli_query($con, "SELECT * FROM users WHERE userid='$uid'");
            if (mysqli_num_rows($check_user) == 0) {
                $skipped++;
                continue;
            }
            
            // Find Plan ID
            $check_plan = mysqli_query($con, "SELECT pid FROM plan WHERE planName='$plan_name' LIMIT 1");
            if ($check_plan && mysqli_num_rows($check_plan) > 0) {
                $p_row = mysqli_fetch_assoc($check_plan);
                $pid = $p_row['pid'];
            } else {
                // If plan doesn't exist, create it temporarily
                $pid = strtoupper(substr(md5($plan_name), 0, 8));
                mysqli_query($con, "INSERT IGNORE INTO plan (pid, planName, description, validity, amount, active) 
                                    VALUES ('$pid', '$plan_name', 'Imported Plan Description', 1, 0, 'yes')");
            }
            
            // Update previous renewals for this user to 'no'
            mysqli_query($con, "UPDATE enrolls_to SET renewal='no' WHERE uid='$uid'");
            
            // Insert enrollment
            $q_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by) 
                         VALUES ('$pid', '$uid', '$paid_date', '$expire', 'yes', '$payment_mode', '$received_by')";
            if (mysqli_query($con, $q_enroll)) {
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
