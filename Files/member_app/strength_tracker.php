<?php
session_start();
require '../include/db_conn.php';

if (!isset($_SESSION['member_uid'])) {
    header("Location: index.php");
    exit;
}

$uid = $_SESSION['member_uid'];
$gym = get_gym_details($con);

// Handle new log entry
$msg = '';
if (isset($_POST['log_strength'])) {
    $exercise = mysqli_real_escape_string($con, $_POST['exercise']);
    $weight = floatval($_POST['weight_kg']);
    $reps = intval($_POST['reps']);
    $notes = mysqli_real_escape_string($con, trim($_POST['notes'] ?? ''));
    $log_date = date('Y-m-d');

    // Epley formula: 1RM = weight * (1 + reps/30)
    $calc_1rm = ($reps === 1) ? $weight : round($weight * (1 + ($reps / 30)), 2);

    if ($weight > 0 && $reps > 0) {
        mysqli_query($con, "INSERT INTO member_strength_logs (uid, exercise, weight_kg, reps, calculated_1rm, log_date, notes) 
                            VALUES ('$uid', '$exercise', $weight, $reps, $calc_1rm, '$log_date', '$notes')");
        $msg = "✅ Personal Record logged! Estimated 1RM: <strong>{$calc_1rm} kg</strong>";
    }
}

// Fetch member history
$logs_res = mysqli_query($con, "SELECT * FROM member_strength_logs WHERE uid='$uid' ORDER BY log_date DESC, id DESC LIMIT 20");
$best_1rm = mysqli_query($con, "SELECT exercise, MAX(calculated_1rm) as max_1rm FROM member_strength_logs WHERE uid='$uid' GROUP BY exercise");
$records_map = [];
if ($best_1rm) {
    while ($r = mysqli_fetch_assoc($best_1rm)) {
        $records_map[$r['exercise']] = $r['max_1rm'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>1RM Strength Tracker | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: #030712; color: #f8fafc; min-height: 100vh; padding: 20px 15px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card { background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
        .form-input { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; color: #fff; margin-bottom: 12px; font-size: 13px; }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-weight: 800; border-radius: 12px; border: none; cursor: pointer; }
        .pr-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 14px; text-align: center; }
    </style>
</head>
<body>

    <div class="header-bar">
        <a href="dashboard.php" style="color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 700;">← Back to App</a>
        <span style="font-family: 'Orbitron', sans-serif; font-size: 11px; color: #f59e0b; font-weight: 800;">⚡ 1RM STRENGTH MATRIX</span>
    </div>

    <div style="margin-bottom: 20px;">
        <h2 style="font-size: 24px; font-weight: 900; color: #fff; text-transform: uppercase;">📈 Progressive Overload &amp; 1RM Tracker</h2>
        <p style="color: #94a3b8; font-size: 12px; margin-top: 3px;">Log your working sets and track your estimated One-Rep Max progression across key lifts.</p>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: 12px; font-size: 13px; font-weight: bold; margin-bottom: 15px;">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- Personal Records Hall of Fame -->
    <div class="card">
        <h3 style="font-size: 14px; font-weight: 800; text-transform: uppercase; color: #f59e0b; margin-bottom: 12px; letter-spacing: 0.5px;">
            🏆 Your All-Time 1RM Personal Records
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px;">
            <div class="pr-card">
                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Bench Press</div>
                <div style="font-size: 20px; font-weight: 900; color: #38bdf8; margin-top: 4px;"><?php echo isset($records_map['Bench Press']) ? $records_map['Bench Press'] . ' kg' : '--'; ?></div>
            </div>
            <div class="pr-card">
                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Barbell Squat</div>
                <div style="font-size: 20px; font-weight: 900; color: #10b981; margin-top: 4px;"><?php echo isset($records_map['Barbell Squat']) ? $records_map['Barbell Squat'] . ' kg' : '--'; ?></div>
            </div>
            <div class="pr-card">
                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Deadlift</div>
                <div style="font-size: 20px; font-weight: 900; color: #f59e0b; margin-top: 4px;"><?php echo isset($records_map['Deadlift']) ? $records_map['Deadlift'] . ' kg' : '--'; ?></div>
            </div>
            <div class="pr-card">
                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Overhead Press</div>
                <div style="font-size: 20px; font-weight: 900; color: #ec4899; margin-top: 4px;"><?php echo isset($records_map['Overhead Press']) ? $records_map['Overhead Press'] . ' kg' : '--'; ?></div>
            </div>
        </div>
    </div>

    <!-- Log PR Form -->
    <div class="card">
        <h3 style="font-size: 14px; font-weight: 800; text-transform: uppercase; color: #fff; margin-bottom: 12px;">
            + Log Working Set / PR
        </h3>
        <form method="POST">
            <label style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Exercise</label>
            <select name="exercise" class="form-input">
                <option value="Bench Press">Barbell Flat Bench Press</option>
                <option value="Barbell Squat">Barbell Back Squat</option>
                <option value="Deadlift">Conventional / Sumo Deadlift</option>
                <option value="Overhead Press">Barbell Overhead Press (OHP)</option>
                <option value="Barbell Row">Barbell Bent-Over Row</option>
                <option value="Incline Dumbbell Press">Incline Dumbbell Press</option>
                <option value="Leg Press">45° Incline Leg Press</option>
            </select>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Weight (kg)</label>
                    <input type="number" step="0.5" name="weight_kg" class="form-input" placeholder="e.g. 80" required>
                </div>
                <div>
                    <label style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Reps Completed</label>
                    <input type="number" name="reps" class="form-input" placeholder="e.g. 6" min="1" max="30" required>
                </div>
            </div>

            <label style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Notes (Optional)</label>
            <input type="text" name="notes" class="form-input" placeholder="e.g. Felt smooth, clean form RPE 8">

            <button type="submit" name="log_strength" class="btn-submit">
                🔥 Calculate &amp; Save 1RM
            </button>
        </form>
    </div>

    <!-- Recent Logs History -->
    <div class="card">
        <h3 style="font-size: 14px; font-weight: 800; text-transform: uppercase; color: #fff; margin-bottom: 12px;">
            Recent Strength Activity
        </h3>
        <?php if ($logs_res && mysqli_num_rows($logs_res) > 0): ?>
            <div style="display: grid; gap: 8px;">
                <?php while ($log = mysqli_fetch_assoc($logs_res)): ?>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); padding: 12px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #fff; font-size: 14px;"><?php echo htmlspecialchars($log['exercise']); ?></strong>
                        <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                            <?php echo $log['weight_kg']; ?> kg × <?php echo $log['reps']; ?> reps
                            <?php if (!empty($log['notes'])): ?>
                                • <span style="font-style: italic; color: #cbd5e1;"><?php echo htmlspecialchars($log['notes']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 14px; font-weight: 900; color: #10b981;">1RM ~ <?php echo $log['calculated_1rm']; ?> kg</div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 2px;"><?php echo date('d M Y', strtotime($log['log_date'])); ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #94a3b8; font-size: 13px; padding: 20px;">
                No strength sets logged yet. Record your first working set above! 💪
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
