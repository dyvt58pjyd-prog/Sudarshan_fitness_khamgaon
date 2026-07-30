<?php
require '../../include/db_conn.php';
page_protect();

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $mobile = mysqli_real_escape_string($con, $_POST['mobile']);
    $spec = mysqli_real_escape_string($con, $_POST['specialization']);
    $exp = mysqli_real_escape_string($con, $_POST['experience']);
    
    // Add trainer entry to admin table as trainer role
    $username = 'tr_' . strtolower(str_replace(' ', '', $name)) . rand(100, 999);
    $pass = '123456'; // Default security PIN
    
    $q_add = "INSERT INTO admin (username, pass_key, Full_name, mobile, role) VALUES ('$username', '$pass', '$name', '$mobile', 'trainer')";
    if (mysqli_query($con, $q_add)) {
        $msg = "Trainer '$name' successfully added! Username: $username (Default PIN: 123456)";
    } else {
        $msg = "Error adding trainer: " . mysqli_error($con);
    }
}

// Fetch trainers
$trainers_res = mysqli_query($con, "SELECT * FROM admin WHERE role = 'trainer'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainer Management | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(9, 14, 28, 0.9); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .btn-add { background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #030712; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer; }
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-custom th, .table-custom td { padding: 14px; text-align: left; border-bottom: 1px solid rgba(0,240,255,0.15); }
        .table-custom th { color: var(--accent-primary); font-family: 'Orbitron'; font-size: 13px; text-transform: uppercase; }
        .form-control { background: rgba(3,7,18,0.8); border: 1px solid rgba(0,240,255,0.3); color: #fff; padding: 10px 14px; border-radius: 10px; width: 100%; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">🏋️ TRAINER MANAGEMENT</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • COACHES &amp; ASSIGNMENTS</div>
            </div>
            <a href="index.php" style="background: rgba(0,240,255,0.1); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <?php if ($msg): ?>
            <div style="background: rgba(0,240,255,0.15); border: 1px solid var(--accent-primary); color: var(--accent-primary); padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">➕ Register New Gym Trainer</h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                    <div>
                        <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" placeholder="10-digit mobile" required>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">Specialization</label>
                        <input type="text" name="specialization" class="form-control" placeholder="e.g. Bodybuilding &amp; Weight Loss">
                    </div>
                    <div>
                        <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">Experience</label>
                        <input type="text" name="experience" class="form-control" placeholder="e.g. 5 Years">
                    </div>
                </div>
                <button type="submit" name="add_trainer" class="btn-add">REGISTER TRAINER ➔</button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">📋 Active Gym Trainers Directory</h3>
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Trainer Name</th>
                        <th>Mobile</th>
                        <th>Role</th>
                        <th>Assigned Clients</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($trainers_res && mysqli_num_rows($trainers_res) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($trainers_res)): ?>
                            <?php 
                            $uname = $row['username'];
                            $q_cnt = mysqli_query($con, "SELECT COUNT(*) as c FROM users WHERE trainer_id = '$uname'");
                            $c_row = mysqli_fetch_assoc($q_cnt);
                            $client_count = $c_row ? $c_row['c'] : 0;
                            ?>
                            <tr>
                                <td style="font-family: 'Orbitron'; color: var(--accent-primary);"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td style="font-weight: 700;"><?php echo htmlspecialchars($row['Full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                <td><span style="background: rgba(112,0,255,0.2); color: #a78bfa; border: 1px solid #7000ff; padding: 2px 8px; border-radius: 8px; font-size: 11px; font-weight: bold; font-family: 'Orbitron';">TRAINER</span></td>
                                <td><strong style="color: #ffb703; font-family: 'Orbitron';"><?php echo $client_count; ?> Members</strong></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No trainers registered yet. Add one above!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
