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
    
    if (!$u2 && $u1 && !empty($u1['mobile'])) {
        $q_mob = mysqli_query($con, "SELECT * FROM users WHERE mobile='{$u1['mobile']}' AND userid != '{$u1['userid']}' LIMIT 1");
        if ($q_mob && mysqli_num_rows($q_mob) > 0) {
            $u2 = mysqli_fetch_assoc($q_mob);
            mysqli_query($con, "UPDATE users SET partner_uid='{$u2['userid']}' WHERE userid='{$u1['userid']}'");
            mysqli_query($con, "UPDATE users SET partner_uid='{$u1['userid']}' WHERE userid='{$u2['userid']}'");
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

function getGenderBadge($gender) {
    if (strtolower($gender) == 'female') {
        return '<span style="background: rgba(236,72,153,0.2); color: #ec4899; border: 1px solid #ec4899; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; margin-top: 6px; display: inline-block;">♀️ Female</span>';
    }
    return '<span style="background: rgba(59,130,246,0.2); color: #3b82f6; border: 1px solid #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; margin-top: 6px; display: inline-block;">♂️ Male</span>';
}

function getAvatarUrl($user) {
    $gender = strtolower($user['gender'] ?? 'male');
    $bg = ($gender == 'female') ? 'ec4899' : '3b82f6';
    if (!empty($user['photo'])) {
        return htmlspecialchars($user['photo']);
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&background=" . $bg . "&color=fff&size=150&font-size=0.45&bold=true";
}

$av1 = getAvatarUrl($u1);
$av2 = getAvatarUrl($u2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Select Profile | Sudarshan Fitness</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ff6b00">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0b0f19; color: #fff; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        
        .title { font-size: 28px; font-weight: 800; margin-bottom: 40px; text-align: center; letter-spacing: -0.5px; color: #fff; }
        .subtitle { font-size: 14px; color: #94a3b8; margin-top: -30px; margin-bottom: 40px; text-align: center; }
        
        .profiles-container { display: flex; gap: 30px; justify-content: center; flex-wrap: wrap; max-width: 500px; width: 100%; }
        
        .profile-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 25px 20px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 180px;
        }
        
        .avatar-wrap {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5);
            margin-bottom: 15px;
        }
        
        .profile-card:hover {
            background: rgba(255, 107, 0, 0.08);
            border-color: #ff6b00;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255,107,0,0.2);
        }
        
        .profile-card:hover .avatar-wrap {
            border-color: #ff6b00;
            transform: scale(1.05);
        }
        
        .avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-name {
            font-size: 17px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
            text-align: center;
        }

        .member-id-tag {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
        }
        
        .couple-badge {
            margin-top: 50px;
            padding: 8px 18px;
            background: rgba(255,107,0,0.1);
            border: 1px solid rgba(255,107,0,0.3);
            border-radius: 20px;
            font-size: 12px;
            color: #ff6b00;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
        }
    </style>
</head>
<body>

    <h1 class="title">Who is working out today?</h1>
    <p class="subtitle">Select a profile to load individual BMI, workout &amp; diet plan</p>
    
    <div class="profiles-container">
        <!-- Profile 1 -->
        <form method="POST" action="" style="flex:1;">
            <input type="hidden" name="selected_uid" value="<?php echo $u1['userid']; ?>">
            <input type="hidden" name="selected_name" value="<?php echo htmlspecialchars($u1['username']); ?>">
            <button type="submit" name="select_profile" class="profile-card" style="width: 100%;">
                <div class="avatar-wrap">
                    <img src="<?php echo $av1; ?>" alt="Avatar">
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($u1['username']); ?></div>
                <div class="member-id-tag">Member ID: <?php echo $u1['userid']; ?></div>
                <?php echo getGenderBadge($u1['gender'] ?? 'Male'); ?>
            </button>
        </form>
        
        <!-- Profile 2 -->
        <form method="POST" action="" style="flex:1;">
            <input type="hidden" name="selected_uid" value="<?php echo $u2['userid']; ?>">
            <input type="hidden" name="selected_name" value="<?php echo htmlspecialchars($u2['username']); ?>">
            <button type="submit" name="select_profile" class="profile-card" style="width: 100%;">
                <div class="avatar-wrap">
                    <img src="<?php echo $av2; ?>" alt="Avatar">
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($u2['username']); ?></div>
                <div class="member-id-tag">Member ID: <?php echo $u2['userid']; ?></div>
                <?php echo getGenderBadge($u2['gender'] ?? 'Female'); ?>
            </button>
        </form>
    </div>
    
    <div class="couple-badge">💑 Couple Fitness Duo Account</div>

</body>
</html>
