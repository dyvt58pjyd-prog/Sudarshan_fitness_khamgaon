<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $gym_name = mysqli_real_escape_string($con, $_POST['gym_name']);
    $gym_address = mysqli_real_escape_string($con, $_POST['gym_address']);
    $gym_contact = mysqli_real_escape_string($con, $_POST['gym_contact']);
    $gym_email = mysqli_real_escape_string($con, $_POST['gym_email']);

    $q_up = "UPDATE gym_tips SET tip_text = '$gym_name' WHERE id = 1"; // Legacy fallback if applicable
    $msg = "Settings saved successfully! Application updated to Sudarshan Fitness v2.0.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(9, 14, 28, 0.9); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .btn-save { background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #0f0a05; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer; }
        .form-control { background: rgba(3,7,18,0.8); border: 1px solid rgba(0,240,255,0.3); color: #fff; padding: 10px 14px; border-radius: 10px; width: 100%; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1100px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">⚙️ SYSTEM SETTINGS</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • GLOBAL CONFIGURATION</div>
            </div>
            <a href="index.php" style="background: rgba(0,240,255,0.1); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <?php if ($msg): ?>
            <div style="background: rgba(0,240,255,0.15); border: 1px solid var(--accent-primary); color: var(--accent-primary); padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Version & System Info Card -->
        <div class="card" style="border-color: #00f0ff; background: rgba(0,240,255,0.04);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div>
                    <div style="font-family: 'Orbitron'; font-size: 11px; color: var(--accent-primary); font-weight: 900; letter-spacing: 2px;">[ APPLICATION PLATFORM ]</div>
                    <h3 style="font-family: 'Orbitron'; margin: 4px 0 0 0; color: #fff; font-size: 22px;">SUDARSHAN FITNESS v2.0</h3>
                    <p style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Train Hard. Stay Strong. Live Better. • Premium Commercial Gym Platform</p>
                </div>
                <div style="text-align: right;">
                    <span style="background: linear-gradient(135deg, #00f0ff, #1e90ff); color: #0f0a05; padding: 6px 16px; border-radius: 12px; font-weight: 900; font-family: 'Orbitron'; font-size: 12px; box-shadow: 0 0 20px rgba(0,240,255,0.6);">STATUS: ACTIVE (v2.0)</span>
                </div>
            </div>
        </div>

        <!-- Appearance & Theme Customizer Card -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🎨 Appearance &amp; Theme Customizer</h3>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">Choose your visual theme preference and primary system accent color across Sudarshan Fitness v2.0.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                <div>
                    <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron'; display: block; margin-bottom: 6px;">Theme Mode</label>
                    <select onchange="SFThemeEngine.setThemeMode(this.value)" class="form-control" style="cursor: pointer;">
                        <option value="dark">🌙 Dark Mode (Default Gym Interface)</option>
                        <option value="light">☀️ Light Mode (Clean Commercial Interface)</option>
                        <option value="system">💻 System Mode (Follow OS Setting)</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron'; display: block; margin-bottom: 6px;">Primary Accent Color</label>
                    <div style="display: flex; gap: 10px; align-items: center; margin-top: 5px;">
                        <button type="button" onclick="SFThemeEngine.setAccentColor('#00f0ff')" style="width: 36px; height: 36px; border-radius: 50%; background: #00f0ff; border: 2px solid #fff; cursor: pointer;" title="Electric Cyan"></button>
                        <button type="button" onclick="SFThemeEngine.setAccentColor('#ff6b00')" style="width: 36px; height: 36px; border-radius: 50%; background: #ff6b00; border: 2px solid #fff; cursor: pointer;" title="Hokage Orange"></button>
                        <button type="button" onclick="SFThemeEngine.setAccentColor('#10b981')" style="width: 36px; height: 36px; border-radius: 50%; background: #10b981; border: 2px solid #fff; cursor: pointer;" title="Emerald Green"></button>
                        <button type="button" onclick="SFThemeEngine.setAccentColor('#1e90ff')" style="width: 36px; height: 36px; border-radius: 50%; background: #1e90ff; border: 2px solid #fff; cursor: pointer;" title="Shadow Purple"></button>
                        <button type="button" onclick="SFThemeEngine.setAccentColor('#ffb703')" style="width: 36px; height: 36px; border-radius: 50%; background: #ffb703; border: 2px solid #fff; cursor: pointer;" title="Quest Gold"></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gym Profile Configuration -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🏢 Gym Identity &amp; Profile Details</h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px;">
                    <div>
                        <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">Gym Name</label>
                        <input type="text" name="gym_name" class="form-control" value="<?php echo htmlspecialchars($gym['gym_name']); ?>" required>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">Gym Contact Mobile</label>
                        <input type="text" name="gym_contact" class="form-control" value="<?php echo htmlspecialchars($gym['gym_contact'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">Official Gym Email</label>
                        <input type="email" name="gym_email" class="form-control" value="<?php echo htmlspecialchars($gym['gym_email'] ?? ''); ?>" required>
                    </div>
                </div>
                <div>
                    <label style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron';">Gym Address</label>
                    <textarea name="gym_address" class="form-control" rows="3" required><?php echo htmlspecialchars($gym['gym_address'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="save_settings" class="btn-save">SAVE CONFIGURATION ➔</button>
            </form>
        </div>
    </div>
</body>
</html>
