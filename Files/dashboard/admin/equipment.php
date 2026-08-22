<?php
require '../../include/db_conn.php';
page_protect();
$gym = get_gym_details($con);

$msg = '';
$msg_type = '';

// Handle Add Equipment
if (isset($_POST['add_equipment'])) {
    $name = mysqli_real_escape_string($con, trim($_POST['equipment_name']));
    $cat = mysqli_real_escape_string($con, trim($_POST['category']));
    $muscle = mysqli_real_escape_string($con, trim($_POST['muscle_group']));
    $inst = mysqli_real_escape_string($con, trim($_POST['instructions']));

    if (!empty($name)) {
        mysqli_query($con, "INSERT INTO gym_equipment (equipment_name, category, muscle_group, instructions, status, last_serviced) 
                            VALUES ('$name', '$cat', '$muscle', '$inst', 'operational', CURRENT_DATE())");
        $msg = "✅ Equipment <strong>" . htmlspecialchars($name) . "</strong> registered successfully!";
        $msg_type = "success";
    }
}

// Handle Ticket Resolution
if (isset($_POST['resolve_ticket'])) {
    $t_id = intval($_POST['ticket_id']);
    $eq_id = intval($_POST['eq_id']);
    mysqli_query($con, "UPDATE equipment_tickets SET status = 'resolved', resolved_at = NOW() WHERE id = $t_id");
    // Check if remaining open tickets for this machine
    $chk_open = mysqli_query($con, "SELECT id FROM equipment_tickets WHERE equipment_id = $eq_id AND status = 'open'");
    if (!$chk_open || mysqli_num_rows($chk_open) === 0) {
        mysqli_query($con, "UPDATE gym_equipment SET status = 'operational' WHERE id = $eq_id");
    }
    $msg = "✅ Maintenance issue marked as resolved!";
    $msg_type = "success";
}

// Handle Status Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $eq_id = intval($_GET['id']);
    $new_st = ($_GET['toggle_status'] === 'operational') ? 'under_maintenance' : 'operational';
    mysqli_query($con, "UPDATE gym_equipment SET status = '$new_st' WHERE id = $eq_id");
    header("Location: equipment.php?msg=updated");
    exit;
}

$equipment_list = mysqli_query($con, "SELECT * FROM gym_equipment ORDER BY id ASC");
$open_tickets = mysqli_query($con, "SELECT t.*, e.equipment_name FROM equipment_tickets t INNER JOIN gym_equipment e ON t.equipment_id = e.id WHERE t.status = 'open' ORDER BY t.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Smart Equipment &amp; QR Telemetry</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <style>
        .badge { padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; text-transform: uppercase; }
        .badge-op { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid #10b981; }
        .badge-maint { background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid #ef4444; }
        .form-control-dark { width: 100%; padding: 10px; background: #0f172a; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; margin-bottom: 10px; }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar();">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php"><img src="../../images/logo.png" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" /></a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()"><a href="#" class="sidebar-collapse-icon with-animation"><i class="entypo-menu"></i></a></div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: #fff;">🏷️ Smart Equipment &amp; QR Telemetry System</h2>
                    <p style="color: #94a3b8; font-size: 13px; margin-top: 4px;">Manage gym machinery, generate printable QR stickers, and track maintenance issues.</p>
                </div>
                <div>
                    <a href="#addEqBox" class="a1-btn a1-blue" style="font-size: 12px; font-weight: bold; border-radius: 8px;">+ Register New Machine</a>
                </div>
            </div>

            <?php if (!empty($msg)): ?>
                <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; color: #10b981; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- Open Maintenance Tickets Bar -->
            <?php if ($open_tickets && mysqli_num_rows($open_tickets) > 0): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; border-radius: 16px; padding: 18px 20px; margin-bottom: 25px;">
                <h3 style="color: #ef4444; margin-top: 0; font-size: 15px; font-weight: 800; text-transform: uppercase;">⚠️ Active Maintenance Issues Reported</h3>
                <div style="display: grid; gap: 10px; margin-top: 12px;">
                    <?php while ($tk = mysqli_fetch_assoc($open_tickets)): ?>
                    <div style="background: rgba(0,0,0,0.3); padding: 12px 16px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <strong style="color: #fff;"><?php echo htmlspecialchars($tk['equipment_name']); ?></strong> — 
                            <span style="color: #fca5a5;"><?php echo htmlspecialchars($tk['issue_description']); ?></span>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Reported by: <?php echo htmlspecialchars($tk['reported_by']); ?> • <?php echo date('d M Y, h:i A', strtotime($tk['created_at'])); ?></div>
                        </div>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="ticket_id" value="<?php echo $tk['id']; ?>">
                            <input type="hidden" name="eq_id" value="<?php echo $tk['equipment_id']; ?>">
                            <button type="submit" name="resolve_ticket" class="a1-btn a1-green" style="font-size: 11px; font-weight: bold; border-radius: 6px; padding: 4px 10px; cursor: pointer;">
                                Mark Fixed ✓
                            </button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Equipment Inventory Grid -->
            <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 22px; margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: #fff; font-size: 16px; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
                    Gym Machinery Inventory &amp; Smart QR Pass
                </h3>

                <table class="table table-bordered table-striped" style="font-size: 13px; width: 100%;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.05); color: #94a3b8; text-transform: uppercase; font-size: 11px;">
                            <th>ID</th>
                            <th>Equipment Name</th>
                            <th>Category</th>
                            <th>Muscle Group</th>
                            <th>Status</th>
                            <th style="text-align: center;">Smart QR Code</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($eq = mysqli_fetch_assoc($equipment_list)): ?>
                        <tr>
                            <td>#<?php echo $eq['id']; ?></td>
                            <td><strong style="color: #fff; font-size: 14px;"><?php echo htmlspecialchars($eq['equipment_name']); ?></strong></td>
                            <td><span style="background: rgba(255,255,255,0.08); padding: 3px 8px; border-radius: 6px;"><?php echo htmlspecialchars($eq['category']); ?></span></td>
                            <td><strong style="color: #38bdf8;"><?php echo htmlspecialchars($eq['muscle_group']); ?></strong></td>
                            <td>
                                <a href="equipment.php?id=<?php echo $eq['id']; ?>&toggle_status=<?php echo $eq['status']; ?>" title="Click to toggle status" style="text-decoration: none;">
                                    <span class="badge <?php echo $eq['status'] === 'operational' ? 'badge-op' : 'badge-maint'; ?>">
                                        <?php echo $eq['status'] === 'operational' ? '🟢 Operational' : '🔴 Maintenance'; ?>
                                    </span>
                                </a>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" onclick="printQrModal(<?php echo $eq['id']; ?>, '<?php echo addslashes($eq['equipment_name']); ?>')" class="a1-btn a1-blue" style="font-size: 11px; font-weight: bold; border-radius: 6px; padding: 4px 10px; cursor: pointer;">
                                    <i class="entypo-popup"></i> View QR Sticker
                                </button>
                            </td>
                            <td style="text-align: center;">
                                <a href="../../equipment_qr.php?id=<?php echo $eq['id']; ?>" target="_blank" class="a1-btn a1-default" style="font-size: 11px; font-weight: bold; border-radius: 6px; padding: 4px 10px;">
                                    🔗 Open Mobile View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Machine Box -->
            <div id="addEqBox" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 22px; max-width: 600px;">
                <h3 style="margin-top: 0; color: #fff; font-size: 16px; font-weight: 800; text-transform: uppercase;">
                    + Register New Gym Machine
                </h3>
                <form method="POST">
                    <label style="color: #cbd5e1; font-size: 12px; font-weight: bold;">Equipment Name *</label>
                    <input type="text" name="equipment_name" class="form-control-dark" placeholder="e.g. Incline Dumbbell Bench" required>

                    <label style="color: #cbd5e1; font-size: 12px; font-weight: bold;">Category</label>
                    <select name="category" class="form-control-dark">
                        <option value="Strength">Strength (Free Weights / Plate Loaded)</option>
                        <option value="Cables">Cables &amp; Pulleys</option>
                        <option value="Cardio">Cardio (Treadmill, Cycle, Elliptical)</option>
                        <option value="Selectorized">Selectorized Pin-Loaded Machines</option>
                    </select>

                    <label style="color: #cbd5e1; font-size: 12px; font-weight: bold;">Targeted Muscle Groups *</label>
                    <input type="text" name="muscle_group" class="form-control-dark" placeholder="e.g. Upper Chest, Triceps" required>

                    <label style="color: #cbd5e1; font-size: 12px; font-weight: bold;">Form Execution Instructions</label>
                    <textarea name="instructions" rows="2" class="form-control-dark" placeholder="Tips for safe form and biomechanics"></textarea>

                    <button type="submit" name="add_equipment" class="a1-btn a1-green" style="font-weight: bold; border-radius: 10px; padding: 10px 20px; cursor: pointer; margin-top: 5px;">
                        Save Equipment
                    </button>
                </form>
            </div>

            <!-- Print QR Sticker Modal Container -->
            <div id="qrPrintModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
                <div style="background: #fff; color: #000; border-radius: 20px; padding: 30px; text-align: center; max-width: 320px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                    <h3 id="modalEqTitle" style="margin-top: 0; font-size: 16px; font-weight: 900; color: #000;"></h3>
                    <div style="margin: 15px 0;">
                        <canvas id="stickerCanvas"></canvas>
                    </div>
                    <div style="font-size: 11px; color: #666; margin-bottom: 15px;">Scan for Machine Form Tips &amp; Support</div>
                    <button onclick="window.print()" class="a1-btn a1-blue" style="width: 100%; border-radius: 8px; font-weight: bold; margin-bottom: 8px;">🖨️ Print Sticker</button>
                    <button onclick="document.getElementById('qrPrintModal').style.display='none'" class="a1-btn a1-default" style="width: 100%; border-radius: 8px; font-weight: bold;">Close</button>
                </div>
            </div>

            <script>
            function printQrModal(id, title) {
                document.getElementById('modalEqTitle').innerText = title;
                const qrUrl = "https://sudarshanfitness.de/Files/equipment_qr.php?id=" + id;
                new QRious({
                    element: document.getElementById('stickerCanvas'),
                    value: qrUrl,
                    size: 180,
                    background: 'white',
                    foreground: 'black',
                    level: 'H'
                });
                document.getElementById('qrPrintModal').style.display = 'flex';
            }
            </script>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
