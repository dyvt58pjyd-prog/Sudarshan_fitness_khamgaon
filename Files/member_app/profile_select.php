<?php
session_start();
require '../include/db_conn.php';

if (!isset($_SESSION['login_auth_uid'])) {
    header("Location: index.php");
    exit;
}

$auth_uid = $_SESSION['login_auth_uid'];

// Find both users in the couple
$u1 = null;
$u2 = null;

$q = mysqli_query($con, "SELECT * FROM users WHERE userid='$auth_uid'");
if (mysqli_num_rows($q) > 0) {
    $row = mysqli_fetch_assoc($q);
    if (!empty($row['partner_uid'])) {
        $u1 = $row;
        $q2 = mysqli_query($con, "SELECT * FROM users WHERE userid='{$row['partner_uid']}'");
        if (mysqli_num_rows($q2) > 0) {
            $u2 = mysqli_fetch_assoc($q2);
        }
    } else {
        $u2 = $row;
        $q1 = mysqli_query($con, "SELECT * FROM users WHERE partner_uid='$auth_uid'");
        if (mysqli_num_rows($q1) > 0) {
            $u1 = mysqli_fetch_assoc($q1);
        }
    }
}

if (!$u1 || !$u2) {
    // Fallback if partner data is corrupted
    $_SESSION['member_uid'] = $auth_uid;
    unset($_SESSION['login_auth_uid']);
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['select_profile'])) {
    $selected_uid = mysqli_real_escape_string($con, $_POST['selected_uid']);
    $selected_name = mysqli_real_escape_string($con, $_POST['selected_name']);
    
    $_SESSION['member_uid'] = $selected_uid;
    $_SESSION['member_name'] = $selected_name;
    unset($_SESSION['login_auth_uid']);
    
    header("Location: dashboard.php");
    exit;
}

function getAvatar($gender) {
    if (strtolower($gender) == 'female') return '../images/avatar_female.png';
    return '../images/avatar_male.png';
}

// Fallback images if not exist
$av1 = "https://ui-avatars.com/api/?name=".urlencode($u1['username'])."&background=10b981&color=fff&size=150";
$av2 = "https://ui-avatars.com/api/?name=".urlencode($u2['username'])."&background=ff6b00&color=fff&size=150";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Who is working out?</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ff6b00">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #000; color: #fff; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        
        .title { font-size: 32px; font-weight: 800; margin-bottom: 50px; text-align: center; letter-spacing: -0.5px; }
        
        .profiles-container { display: flex; gap: 40px; justify-content: center; flex-wrap: wrap; }
        
        .profile-card {
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .avatar-wrap {
            width: 130px;
            height: 130px;
            border-radius: 24px;
            overflow: hidden;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5);
            margin-bottom: 15px;
        }
        
        .profile-card:hover .avatar-wrap {
            border-color: #ff6b00;
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(255,107,0,0.3);
        }
        
        .avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-name {
            font-size: 18px;
            font-weight: 600;
            color: #94a3b8;
            transition: all 0.3s ease;
        }
        
        .profile-card:hover .profile-name {
            color: #fff;
        }
        
        .couple-badge {
            margin-top: 60px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <h1 class="title">Who is working out today?</h1>
    
    <div class="profiles-container">
        <!-- Profile 1 -->
        <form method="POST" action="">
            <input type="hidden" name="selected_uid" value="<?php echo $u1['userid']; ?>">
            <input type="hidden" name="selected_name" value="<?php echo htmlspecialchars($u1['username']); ?>">
            <button type="submit" name="select_profile" class="profile-card">
                <div class="avatar-wrap">
                    <img src="<?php echo $av1; ?>" alt="Avatar">
                </div>
                <div class="profile-name"><?php echo explode(' ', $u1['username'])[0]; ?></div>
            </button>
        </form>
        
        <!-- Profile 2 -->
        <form method="POST" action="">
            <input type="hidden" name="selected_uid" value="<?php echo $u2['userid']; ?>">
            <input type="hidden" name="selected_name" value="<?php echo htmlspecialchars($u2['username']); ?>">
            <button type="submit" name="select_profile" class="profile-card">
                <div class="avatar-wrap">
                    <img src="<?php echo $av2; ?>" alt="Avatar">
                </div>
                <div class="profile-name"><?php echo explode(' ', $u2['username'])[0]; ?></div>
            </button>
        </form>
    </div>
    
    <div class="couple-badge">✨ Couple Plan Member</div>

</body>
</html>
