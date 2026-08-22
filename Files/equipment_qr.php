<?php
require 'include/db_conn.php';
$gym = get_gym_details($con);

$eq_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$q_eq = mysqli_query($con, "SELECT * FROM gym_equipment WHERE id = $eq_id");
$eq = ($q_eq && mysqli_num_rows($q_eq) > 0) ? mysqli_fetch_assoc($q_eq) : null;

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_issue'])) {
    $reported_by = mysqli_real_escape_string($con, trim($_POST['reporter_name'] ?? 'Gym Member'));
    $desc = mysqli_real_escape_string($con, trim($_POST['issue_desc'] ?? ''));
    $severity = mysqli_real_escape_string($con, $_POST['severity'] ?? 'medium');

    if (!empty($desc)) {
        mysqli_query($con, "INSERT INTO equipment_tickets (equipment_id, reported_by, issue_description, severity, status) 
                            VALUES ($eq_id, '$reported_by', '$desc', '$severity', 'open')");
        // Update equipment status if high severity
        if ($severity === 'high') {
            mysqli_query($con, "UPDATE gym_equipment SET status = 'under_maintenance' WHERE id = $eq_id");
        }
        $msg = "✅ Thank you! Maintenance issue reported to gym technicians. We will inspect it immediately.";
        $msg_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $eq ? htmlspecialchars($eq['equipment_name']) : 'Gym Equipment'; ?> | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: #0b0f19; color: #f8fafc; min-height: 100vh; padding: 20px 15px; display: flex; justify-content: center; }
        .card { width: 100%; max-width: 440px; background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 24px; box-shadow: 0 15px 40px rgba(0,0,0,0.5); }
        .badge { padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .badge-op { background: rgba(16,185,129,0.2); color: #10b981; border: 1px solid #10b981; }
        .badge-maint { background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid #ef4444; }
        .form-input { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; color: #fff; margin-bottom: 12px; font-size: 13px; }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #ef4444, #b91c1c); color: #fff; font-weight: 800; border-radius: 10px; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <span style="font-family: 'Orbitron', sans-serif; font-size: 11px; color: #38bdf8; font-weight: 800;">🏋️ SMART EQUIPMENT QR</span>
            <span class="badge <?php echo ($eq && $eq['status'] === 'operational') ? 'badge-op' : 'badge-maint'; ?>">
                <?php echo ($eq && $eq['status'] === 'operational') ? '🟢 OPERATIONAL' : '🔴 MAINTENANCE'; ?>
            </span>
        </div>

        <?php if ($eq): ?>
            <h2 style="font-size: 22px; font-weight: 900; color: #fff; margin-bottom: 6px;"><?php echo htmlspecialchars($eq['equipment_name']); ?></h2>
            <div style="font-size: 12px; color: #94a3b8; margin-bottom: 16px;">Category: <strong style="color: #cbd5e1;"><?php echo htmlspecialchars($eq['category']); ?></strong> | Target: <strong style="color: #10b981;"><?php echo htmlspecialchars($eq['muscle_group']); ?></strong></div>

            <!-- Targeted Muscles Box -->
            <div style="background: rgba(0,0,0,0.3); padding: 14px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 18px;">
                <div style="font-size: 11px; font-weight: 800; color: #f59e0b; text-transform: uppercase; margin-bottom: 6px;">💪 Targeted Muscle Groups</div>
                <div style="font-size: 14px; color: #fff; font-weight: 700;"><?php echo htmlspecialchars($eq['muscle_group']); ?></div>
            </div>

            <!-- Proper Form Instructions -->
            <div style="background: rgba(0,0,0,0.3); padding: 14px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 22px;">
                <div style="font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; margin-bottom: 6px;">📖 Proper Form &amp; Execution Tips</div>
                <p style="font-size: 13px; color: #cbd5e1; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($eq['instructions'] ?: 'Maintain proper spinal alignment, control the eccentric phase, and breathe out during exertion.')); ?></p>
            </div>

            <?php if (!empty($msg)): ?>
                <div style="background: rgba(16,185,129,0.2); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: bold; margin-bottom: 15px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- ⚠️ Report Maintenance / Broken Issue Form -->
            <div style="background: rgba(239,68,68,0.08); border: 1px dashed rgba(239,68,68,0.3); padding: 16px; border-radius: 16px;">
                <div style="font-size: 12px; font-weight: 800; color: #ef4444; text-transform: uppercase; margin-bottom: 10px;">⚠️ Notice an Issue with this Machine?</div>
                <form method="POST">
                    <input type="text" name="reporter_name" class="form-input" placeholder="Your Name or Member ID (Optional)">
                    <textarea name="issue_desc" rows="2" class="form-input" placeholder="Describe issue (e.g., loose cable, torn pad, tight pulley)" required></textarea>
                    <select name="severity" class="form-input">
                        <option value="low">Minor (Squeaking / Cosmetic)</option>
                        <option value="medium" selected>Medium (Tight / Loose Cable)</option>
                        <option value="high">Urgent (Broken / Out of Order)</option>
                    </select>
                    <button type="submit" name="report_issue" class="btn-submit">
                        Report Issue to Technicians
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #ef4444;">
                <h3>Equipment Not Found</h3>
                <p style="color: #94a3b8; font-size: 13px;">This QR code is not mapped to an active machine.</p>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px; font-size: 11px; color: #64748b;">
            <?php echo htmlspecialchars($gym['gym_name']); ?> • Smart Gym Facility Management
        </div>
    </div>

</body>
</html>
