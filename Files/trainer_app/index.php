<?php
session_start();
require '../include/db_conn.php';

$msg = "";
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    
    $query = "SELECT * FROM admin WHERE username='$username' AND pass_key='$password'";
    $res = mysqli_query($con, $query);
    
    if (mysqli_num_rows($res) == 1) {
        $row = mysqli_fetch_assoc($res);
        $_SESSION['trainer_id'] = $row['username'];
        $_SESSION['trainer_name'] = $row['Full_name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $msg = "Invalid Trainer ID or Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Trainer Login</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#10b981">
    <link rel="apple-touch-icon" href="../images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 24px; padding: 40px 30px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center; }
        .logo { max-width: 150px; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #cbd5e1; font-size: 13px; font-weight: 500; }
        .form-control { width: 100%; background: #0f172a; border: 1px solid #334155; padding: 14px 15px; border-radius: 12px; color: #fff; font-size: 15px; transition: all 0.3s; }
        .form-control:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15); }
        .login-btn { width: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border: none; padding: 15px; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.3); }
        .error { color: #ef4444; font-size: 13px; margin-bottom: 15px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="../images/logo.png" alt="Titan Gym" class="logo">
        <h2 style="margin-bottom: 5px;">Trainer Portal</h2>
        <p style="color:#8ba3cb; font-size:13px; margin-bottom:25px;">Log in to manage your clients</p>
        
        <?php if ($msg != "") { echo "<div class='error'>$msg</div>"; } ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Trainer Username</label>
                <input type="text" name="username" class="form-control" placeholder="e.g. admin" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" name="login" class="login-btn">Login</button>
        </form>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>
