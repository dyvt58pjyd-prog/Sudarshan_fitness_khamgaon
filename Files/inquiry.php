<?php
require 'include/db_conn.php';
$msg = "";
$msgClass = "";

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $mobile = mysqli_real_escape_string($con, $_POST['mobile']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $interest = mysqli_real_escape_string($con, $_POST['interest']);
    
    $q = "INSERT INTO visitors (name, mobile, email, interest_level) VALUES ('$name', '$mobile', '$email', '$interest')";
    
    if (mysqli_query($con, $q)) {
        $msg = "Thank you! Our SalesBot has registered your inquiry. We will contact you soon.";
        $msgClass = "success";
    } else {
        $msg = "Error registering inquiry: " . mysqli_error($con);
        $msgClass = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gymshim SalesBot | Inquiry</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .bot-container { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 107, 0, 0.3); border-radius: 20px; padding: 40px; width: 100%; max-width: 450px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .bot-header { text-align: center; margin-bottom: 30px; }
        .bot-header h2 { color: #fff; font-size: 24px; font-weight: 700; margin-bottom: 10px; }
        .bot-header p { color: #8ba3cb; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #cbd5e1; font-size: 13px; font-weight: 500; }
        .form-control { width: 100%; background: #0f172a; border: 1px solid #334155; padding: 12px 15px; border-radius: 10px; color: #fff; font-size: 15px; transition: all 0.3s; }
        .form-control:focus { outline: none; border-color: #ff6b00; box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.15); }
        .submit-btn { width: 100%; background: linear-gradient(135deg, #ff6b00 0%, #ff8800 100%); color: #fff; border: none; padding: 14px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,107,0,0.3); }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert.success { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .alert.error { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    </style>
</head>
<body>
    <div class="bot-container">
        <div class="bot-header">
            <h2>Gymshim SalesBot</h2>
            <p>Welcome! Drop your details below and our team will get in touch to start your fitness journey.</p>
        </div>
        
        <?php if ($msg != "") { ?>
            <div class="alert <?php echo $msgClass; ?>">
                <?php echo $msg; ?>
            </div>
        <?php } ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required placeholder="Enter your full name">
            </div>
            
            <div class="form-group">
                <label>Mobile Number</label>
                <input type="tel" name="mobile" class="form-control" required placeholder="Enter your phone number">
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="Enter your email address">
            </div>
            
            <div class="form-group">
                <label>What are you looking for?</label>
                <select name="interest" class="form-control" required>
                    <option value="" disabled selected>Select your goal</option>
                    <option value="Weight Loss">Weight Loss</option>
                    <option value="Muscle Gain">Muscle Gain</option>
                    <option value="General Fitness">General Fitness</option>
                    <option value="Personal Training">Personal Training</option>
                </select>
            </div>
            
            <button type="submit" name="submit" class="submit-btn">Send Inquiry</button>
        </form>
    </div>
</body>
</html>
