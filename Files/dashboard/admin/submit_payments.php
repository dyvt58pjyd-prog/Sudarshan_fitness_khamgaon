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
          $d=strtotime("+".$value[3]." Months", strtotime($cdate));
          $expiredate=date("Y-m-d",$d); //adding validity retrieve from plan to current date
          $discount = isset($_POST['discount']) ? intval($_POST['discount']) : 0;
           $plan_price = intval($value[4]);
           $paid_amount = $plan_price - $discount;
           if ($paid_amount < 0) {
               $paid_amount = 0;
           }
           $payment_mode = isset($_POST['payment_mode']) ? mysqli_real_escape_string($con, $_POST['payment_mode']) : 'Cash';
           $received_by = isset($_SESSION['full_name']) ? mysqli_real_escape_string($con, $_SESSION['full_name']) : 'System';
           $query2="insert into enrolls_to(pid,uid,paid_date,expire,renewal,payment_mode,received_by,discount_amount,paid_amount) values('$plan','$memID','$cdate','$expiredate','yes','$payment_mode','$received_by',$discount,$paid_amount)";
           if(mysqli_query($con,$query2)==1){
                // Generate new random 6-digit entry code for gate access on renewal
                $new_entry_code = strval(rand(100000, 999999));
                mysqli_query($con, "UPDATE users SET entry_code = '$new_entry_code' WHERE userid = '$memID'");

                 // Fetch user email and username for payment notification (which automatically triggers WhatsApp receipt with PDF)
                 $user_q = mysqli_query($con, "SELECT username, email FROM users WHERE userid='$memID'");
                 if ($user_q && mysqli_num_rows($user_q) > 0) {
                     $user_row = mysqli_fetch_assoc($user_q);
                     
                     $uname = $user_row['username'];
                     
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
