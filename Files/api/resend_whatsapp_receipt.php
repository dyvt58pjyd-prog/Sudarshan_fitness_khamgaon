<?php
// API endpoint to manually re-send payment receipt via WhatsApp
require_once __DIR__ . '/../include/db_conn.php';
page_protect();

// Limit to authorized roles
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

$uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'membership';

if (empty($uid)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit();
}

$uid_clean = mysqli_real_escape_string($con, $uid);

require_once __DIR__ . '/../include/pdf_generator.php';

if ($type === 'pt') {
    $ptid_clean = isset($_GET['ptid']) ? mysqli_real_escape_string($con, $_GET['ptid']) : '';
    
    // Retrieve PT details
    $sql = "SELECT p.*, u.username, u.mobile, t.Full_name AS trainer_name 
            FROM pt_enrollments p 
            INNER JOIN users u ON p.uid = u.userid 
            INNER JOIN admin t ON p.trainer_id = t.username 
            WHERE p.uid = '$uid_clean'" . (!empty($ptid_clean) ? " AND p.pt_id = '$ptid_clean'" : "") . "
            ORDER BY p.pt_id DESC LIMIT 1";
            
    $res = mysqli_query($con, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $name = $row['username'];
        $mobile = $row['mobile'];
        $trainer_name = $row['trainer_name'];
        $amount = $row['amount'];
        $expire_date = $row['expire_date'];
        $payment_mode = $row['payment_mode'];
        $actual_ptid = $row['pt_id'];
        
        // Generate PT receipt PDF
        $pdf_path = generate_pt_receipt_pdf_file($con, $uid_clean, $actual_ptid);
        
        if ($pdf_path && file_exists($pdf_path)) {
            $sent = send_whatsapp_payment_confirmation(
                $con, 
                $mobile, 
                $name, 
                "Personal Training ($trainer_name)", 
                $amount, 
                $expire_date, 
                $payment_mode, 
                $pdf_path
            );
            
            // Cleanup PDF from disk
            @unlink($pdf_path);
            
            if ($sent) {
                echo json_encode(['success' => true, 'message' => 'PT Receipt sent successfully via WhatsApp!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to deliver PT Receipt. Is WhatsApp daemon offline?']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to generate PT Receipt PDF.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'PT enrollment record not found.']);
    }
} else {
    // Default to membership receipt
    $etid_clean = isset($_GET['etid']) ? mysqli_real_escape_string($con, $_GET['etid']) : '';
    
    $sql = "SELECT e.*, u.username, u.mobile, p.planName FROM users u 
            INNER JOIN enrolls_to e ON u.userid = e.uid 
            INNER JOIN plan p ON p.pid = e.pid 
            WHERE u.userid = '$uid_clean'" . (!empty($etid_clean) ? " AND e.et_id = '$etid_clean'" : "") . "
            ORDER BY e.et_id DESC LIMIT 1";
            
    $res = mysqli_query($con, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $name = $row['username'];
        $mobile = $row['mobile'];
        $plan_name = $row['planName'];
        $expire_date = $row['expire'];
        $payment_mode = $row['payment_mode'];
        $actual_etid = $row['et_id'];
        
        $discount = isset($row['discount_amount']) ? intval($row['discount_amount']) : 0;
        $paid_amount = (isset($row['paid_amount']) && $row['paid_amount'] !== null) ? intval($row['paid_amount']) : intval($row['amount']);
        $total_paid = $paid_amount;
        
        // Generate Membership receipt PDF
        $pdf_path = generate_receipt_pdf_file($con, $uid_clean, $actual_etid, $row['pid']);
        
        if ($pdf_path && file_exists($pdf_path)) {
            $sent = send_whatsapp_payment_confirmation(
                $con, 
                $mobile, 
                $name, 
                $plan_name, 
                $total_paid, 
                $expire_date, 
                $payment_mode, 
                $pdf_path
            );
            
            // Cleanup PDF from disk
            @unlink($pdf_path);
            
            if ($sent) {
                echo json_encode(['success' => true, 'message' => 'Receipt sent successfully via WhatsApp!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to deliver Receipt. Is WhatsApp daemon offline?']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to generate Receipt PDF.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Membership enrollment record not found.']);
    }
}
?>
