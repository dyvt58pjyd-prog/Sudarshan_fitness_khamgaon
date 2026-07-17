<?php
require '../../include/db_conn.php';
page_protect();

 $memID=$_POST['m_id'];
 $plan=$_POST['plan'];

//updating renewal from yes to no from enrolls_to table
$query="update enrolls_to set renewal='no' where uid='$memID'";
    if(mysqli_query($con,$query)){
      //inserting new payment data into enrolls_to table
      $query1="select * from plan where pid='$plan'";
      $result=mysqli_query($con,$query1);

        if($result){
          $value=mysqli_fetch_row($result);
          date_default_timezone_set("Asia/Calcutta"); 
          $cdate = isset($_SESSION['working_year']) ? ($_SESSION['working_year'] . '-' . date('m-d')) : date('Y-m-d');
          $launch_date = '2026-07-08';
          $cdate = ($cdate < $launch_date) ? $launch_date : $cdate;
          $d = calculate_expiration_date($cdate, $value[3]);
          $expiredate=date("Y-m-d",$d); //adding validity retrieve from plan to current date
          $discount = isset($_POST['discount']) ? intval($_POST['discount']) : 0;
          $transaction_date = isset($_POST['transaction_date']) && !empty($_POST['transaction_date']) ? mysqli_real_escape_string($con, $_POST['transaction_date']) : date('Y-m-d');
           $plan_price = intval($value[4]);
           $total_payable = $plan_price - $discount;
           if ($total_payable < 0) {
               $total_payable = 0;
           }
           
           $paid_now = isset($_POST['paid_amount']) && $_POST['paid_amount'] !== '' ? floatval($_POST['paid_amount']) : $total_payable;
           $balance = $total_payable - $paid_now;
           if ($balance < 0) $balance = 0;
           
           $balance_due_date = isset($_POST['balance_due_date']) ? mysqli_real_escape_string($con, $_POST['balance_due_date']) : NULL;
           $due_date_val = $balance > 0 && !empty($balance_due_date) ? "'$balance_due_date'" : "NULL";

           $payment_mode = isset($_POST['payment_mode']) ? mysqli_real_escape_string($con, $_POST['payment_mode']) : 'Cash';
           $received_by = isset($_SESSION['full_name']) ? mysqli_real_escape_string($con, $_SESSION['full_name']) : 'System';
           $query2="insert into enrolls_to(pid,uid,paid_date,expire,renewal,payment_mode,received_by,discount_amount,paid_amount,balance,balance_due_date) values('$plan','$memID','$transaction_date','$expiredate','yes','$payment_mode','$received_by',$discount,$paid_now,$balance,$due_date_val)";
           if(mysqli_query($con,$query2)==1){
                // Generate new random 6-digit entry code for gate access on renewal
                $new_entry_code = strval(rand(100000, 999999));
                mysqli_query($con, "UPDATE users SET entry_code = '$new_entry_code' WHERE userid = '$memID'");

                 // Fetch user email, username, and bio_id for payment notification (which automatically triggers WhatsApp receipt with PDF)
                 $user_q = mysqli_query($con, "SELECT username, email, biometric_id FROM users WHERE userid='$memID'");
                 if ($user_q && mysqli_num_rows($user_q) > 0) {
                     $user_row = mysqli_fetch_assoc($user_q);
                     
                     $uname = $user_row['username'];
                     $bio_id = $user_row['biometric_id'];
                     
                     if (!empty($bio_id)) {
                         $cmd_payload = json_encode(['reason' => 'renewed_plan', 'pin' => $new_entry_code, 'name' => $uname]);
                         mysqli_query($con, "INSERT INTO biometric_commands (command_type, target_uid, payload, status) VALUES ('UPDATE_USERINFO', '$bio_id', '$cmd_payload', 'pending')");
                     }
                     
                     send_payment_email($con, $user_row['email'], $uname, $memID, $value[1], $value[4], $expiredate, $payment_mode, $received_by, $new_entry_code, $discount, $paid_amount);
                     
                 }

               echo "<head><script>alert('Payment Successfully updated & confirmation email sent!');</script></head></html>";
               echo "<meta http-equiv='refresh' content='0; url=payments.php?success=1'>";
            }
             
            else{
               echo "<head><script>alert('Payment update Failed');</script></head></html>";
              echo "error: ".mysqli_error($con);
            }
            
          }
          else{
            echo "<head><script>alert('Payment update Failed');</script></head></html>";
            echo "error: ".mysqli_error($con);
          }

         
        }
        else
        {
          echo "<head><script>alert('Payment update Failed');</script></head></html>";
          echo "error: ".mysqli_error($con);
        }

?>
