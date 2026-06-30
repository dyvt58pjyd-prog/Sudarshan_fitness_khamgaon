<?php
require '../../include/db_conn.php';
page_protect();

// Auto-heal: Ensure all numeric users have their biometric_id synced to their userid by default
mysqli_query($con, "UPDATE users SET biometric_id = userid, biometric_enabled = 1 WHERE biometric_id IS NULL AND userid REGEXP '^[0-9]+$'");

// Handle AJAX POST requests for inline modifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header("Content-Type: application/json; charset=UTF-8");
    $action = $_POST['ajax_action'];
    $uid = mysqli_real_escape_string($con, trim($_POST['uid']));
    
    if ($action === 'toggle_access') {
        $enabled = intval($_POST['enabled']) === 1 ? 1 : 0;
        $sql = "UPDATE users SET biometric_enabled = $enabled WHERE userid = '$uid'";
        if (mysqli_query($con, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
        }
        exit();
    }
    
    if ($action === 'save_bio_id') {
        $val = trim($_POST['biometric_id']);
        $bio_id = $val === '' ? 'NULL' : intval($val);
        
        // Validate uniqueness if not null
        if ($bio_id !== 'NULL') {
            $chk = mysqli_query($con, "SELECT userid, username FROM users WHERE biometric_id = $bio_id AND userid != '$uid' LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) {
                $conflict = mysqli_fetch_assoc($chk);
                echo json_encode([
                    'success' => false, 
                    'message' => "Biometric ID $bio_id is already assigned to member: " . htmlspecialchars($conflict['username']) . " (ID: " . htmlspecialchars($conflict['userid']) . ")"
                ]);
                exit();
            }
        }
        
        $sql = "UPDATE users SET biometric_id = $bio_id WHERE userid = '$uid'";
        if (mysqli_query($con, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Biometric ID updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
        }
        exit();
    }
    if ($action === 'reenroll_fingerprint') {
        $bio_id = mysqli_real_escape_string($con, trim($_POST['biometric_id']));
        if (!empty($bio_id) && $bio_id !== 'NULL') {
            $cmd_payload = json_encode(['reason' => 'reenroll_requested']);
            // Queue the CLEAR_FINGERPRINT command
            mysqli_query($con, "INSERT INTO biometric_commands (command_type, target_uid, payload, status) VALUES ('CLEAR_FINGERPRINT', '$bio_id', '$cmd_payload', 'pending')");
            echo json_encode(['success' => true, 'message' => 'Command queued! The machine will wipe their fingerprint on its next sync, forcing them to re-register.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Biometric ID.']);
        }
        exit();
    }
    
    echo json_encode(['success' => false, 'message' => 'Unknown AJAX action.']);
    exit();
}

$gym = get_gym_details($con);

// Fetch members
$sql_mems = "SELECT u.userid, u.username, u.photo, u.biometric_id, u.biometric_enabled,
                    (SELECT MAX(e.expire) FROM enrolls_to e WHERE e.uid = u.userid) AS plan_expire
             FROM users u
             ORDER BY u.username ASC";
$res_mems = mysqli_query($con, $sql_mems);
$members = [];
if ($res_mems) {
    while ($row = mysqli_fetch_assoc($res_mems)) {
        $members[] = $row;
    }
}

// Check Device Heartbeat
$heartbeat_file = '../../include/last_sync_heartbeat.txt';
$last_heartbeat = file_exists($heartbeat_file) ? (int)trim(@file_get_contents($heartbeat_file)) : 0;
$time_diff = time() - $last_heartbeat;
$is_online = ($last_heartbeat > 0 && $time_diff <= 120); // 2 minutes threshold for online
$status_color = $is_online ? '#10b981' : '#ef4444';
$status_bg = $is_online ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)';
$status_text = $is_online ? 'ONLINE & SYNCING' : 'OFFLINE / DISCONNECTED';
$last_sync_str = $last_heartbeat > 0 ? date("d M Y, h:i A", $last_heartbeat) : 'Never';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Biometric Access Control</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#biometric_manage > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        
        .management-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }

        .search-wrapper {
            margin-bottom: 25px;
            position: relative;
        }

        .search-wrapper input {
            padding-left: 40px !important;
        }

        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        .table-premium {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table-premium th, .table-premium td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }

        .table-premium th {
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
        }

        .table-premium tr:hover td {
            background: rgba(255,255,255,0.01);
        }

        .member-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .badge-premium {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-active {
            background: rgba(16, 185, 129, 0.12);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .badge-expired {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .badge-no-plan {
            background: rgba(148, 163, 184, 0.12);
            color: var(--text-muted);
            border: 1px solid rgba(148, 163, 184, 0.25);
        }

        /* Toggle Switch Styling */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }
        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255,255,255,0.08);
            transition: .3s;
            border-radius: 24px;
            border: 1px solid var(--glass-border);
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: #94a3b8;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.4);
        }
        input:checked + .slider:before {
            transform: translateX(24px);
            background-color: #10b981;
        }
        
        .bio-id-input {
            width: 90px;
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 6px !important;
            color: var(--text-main) !important;
            padding: 5px 8px !important;
            text-align: center;
            font-family: monospace;
            font-weight: bold;
            font-size: 13px;
            display: inline-block;
            margin-right: 5px;
        }

        .bio-id-input:focus {
            border-color: var(--accent-primary) !important;
        }

        .btn-save-bio {
            background: rgba(255, 107, 0, 0.15);
            color: #ff6b00;
            border: 1px solid rgba(255,107,0,0.3);
            border-radius: 6px;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-save-bio:hover {
            background: #ff6b00;
            color: white;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 60px; max-width: 180px;" />
                    </a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()">
                    <a href="#" class="sidebar-collapse-icon with-animation">
                        <i class="entypo-menu"></i>
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-6 col-sm-8 clearfix"></div>
                <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Biometric Access Control</h2>
            <hr />

            <!-- Device Status Card -->
            <div style="background: <?php echo $status_bg; ?>; border: 1px solid <?php echo $status_color; ?>; border-radius: 12px; padding: 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: <?php echo $status_color; ?>; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; box-shadow: 0 0 15px <?php echo $status_color; ?>;">
                        <i class="entypo-signal"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--text-main); font-weight: 700;">Live Device Status</h4>
                        <p style="margin: 5px 0 0 0; color: var(--text-muted); font-size: 13px;">Python ADMS Sync Agent on LAN</p>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 800; font-size: 18px; color: <?php echo $status_color; ?>; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; justify-content: flex-end;">
                        <?php if($is_online): ?><span style="width:10px; height:10px; border-radius:50%; background:<?php echo $status_color; ?>; display:inline-block; animation: pulse 1.5s infinite;"></span><?php endif; ?>
                        <?php echo $status_text; ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 12px; margin-top: 5px;">
                        Last synchronized: <?php echo $last_sync_str; ?>
                    </div>
                </div>
            </div>
            
            <style>
                @keyframes pulse {
                    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
                    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
                    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
                }
            </style>

            <div class="management-card">
                <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="entypo-key" style="color: var(--accent-primary);"></i> Mappings &amp; Access Statuses
                </h3>
                <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 25px; line-height: 1.5;">
                    Assign fingerprint scanner **Biometric IDs** to gym members and toggle their door access. Expired membership plans will automatically block door check-ins.
                </p>

                <!-- Real-time Filter Search Box -->
                <div class="search-wrapper">
                    <i class="entypo-search"></i>
                    <input type="text" id="search_input" class="form-control-premium" placeholder="Filter by member name, ID, or biometric code..." onkeyup="filterMembers()" />
                </div>

                <div class="table-responsive" style="border: 1px solid var(--glass-border); border-radius: 12px; background: rgba(0,0,0,0.15); overflow: hidden;">
                    <table class="table-premium">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.25);">
                                <th style="width: 8%;">Photo</th>
                                <th style="width: 25%;">Member Details</th>
                                <th style="width: 20%;">Membership Status</th>
                                <th style="width: 27%;">Fingerprint Biometric ID</th>
                                <th style="width: 20%; text-align: center;">Door Access State</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($members) > 0): ?>
                                <?php foreach ($members as $m): 
                                    $photo_src = !empty($m['photo']) ? htmlspecialchars($m['photo']) : '../../images/default_avatar.jpg';
                                    $has_expire = !empty($m['plan_expire']);
                                    $is_active = false;
                                    
                                    if ($has_expire) {
                                        date_default_timezone_set("Asia/Calcutta");
                                        $is_active = (strtotime($m['plan_expire']) >= strtotime(date('Y-m-d')));
                                    }
                                ?>
                                    <tr class="member-item-row" id="row_<?php echo htmlspecialchars($m['userid']); ?>" 
                                        data-name="<?php echo htmlspecialchars($m['username']); ?>" 
                                        data-uid="<?php echo htmlspecialchars($m['userid']); ?>" 
                                        data-bio="<?php echo htmlspecialchars($m['biometric_id'] ? $m['biometric_id'] : ''); ?>">
                                        <td>
                                            <img src="<?php echo $photo_src; ?>" class="member-avatar" alt="Avatar">
                                        </td>
                                        <td>
                                            <strong style="color: #ffffff; font-size: 14px;"><?php echo htmlspecialchars($m['username']); ?></strong>
                                            <div style="color: var(--text-muted); font-size: 11.5px; font-family: monospace; margin-top: 1px;">
                                                Member ID: <?php echo htmlspecialchars($m['userid']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($has_expire): ?>
                                                <?php if ($is_active): ?>
                                                    <span class="badge-premium badge-active">Active</span>
                                                    <div style="color: var(--text-muted); font-size: 10.5px; margin-top: 2px;">
                                                        Expires: <?php echo htmlspecialchars($m['plan_expire']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge-premium badge-expired">Expired</span>
                                                    <div style="color: var(--text-muted); font-size: 10.5px; margin-top: 2px;">
                                                        Ended: <?php echo htmlspecialchars($m['plan_expire']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge-premium badge-no-plan">No Subscribed Plan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center;">
                                                <input type="number" class="bio-id-input" id="bio_id_input_<?php echo htmlspecialchars($m['userid']); ?>" value="<?php echo $m['biometric_id'] !== NULL ? intval($m['biometric_id']) : ''; ?>" placeholder="Not Set" min="1" />
                                                <button class="btn-save-bio" onclick="saveBiometricId('<?php echo htmlspecialchars($m['userid']); ?>')" title="Save Biometric ID">
                                                    <i class="entypo-check"></i> Save
                                                </button>
                                                <?php if ($m['biometric_id'] !== NULL): ?>
                                                <button class="btn-save-bio" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.4); color: #ef4444; margin-left: 5px;" onclick="reenrollFingerprint('<?php echo htmlspecialchars($m['userid']); ?>', '<?php echo htmlspecialchars($m['biometric_id']); ?>')" title="Wipe Fingerprint on Machine for Re-registration">
                                                    <i class="entypo-ccw"></i> Re-Enroll
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                <label class="switch">
                                                    <input type="checkbox" id="access_switch_<?php echo htmlspecialchars($m['userid']); ?>" <?php echo intval($m['biometric_enabled']) === 1 ? 'checked' : ''; ?> onchange="toggleBiometricAccess('<?php echo htmlspecialchars($m['userid']); ?>', this)" />
                                                    <span class="slider"></span>
                                                </label>
                                                <?php if (!$is_active && $has_expire): ?>
                                                    <span style="color: #ef4444; font-size: 9px; font-weight: bold; border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05); padding: 1px 4px; border-radius: 4px;" title="This access is automatically overridden/blocked on the device because the membership plan has expired.">BLOCKED</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        No gym members registered in the database.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script>
    // JS Real-time filtering
    function filterMembers() {
        const query = document.getElementById('search_input').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.member-item-row');
        rows.forEach(row => {
            const name = row.getAttribute('data-name').toLowerCase();
            const uid = row.getAttribute('data-uid').toLowerCase();
            const bio = row.getAttribute('data-bio').toLowerCase();
            if (name.includes(query) || uid.includes(query) || bio.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Toggle fingerprint door access manually
    function toggleBiometricAccess(uid, checkbox) {
        const enabled = checkbox.checked ? 1 : 0;
        const formData = new FormData();
        formData.append('ajax_action', 'toggle_access');
        formData.append('uid', uid);
        formData.append('enabled', enabled);
        
        fetch('biometric_management.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert('Error updating status: ' + data.message);
                checkbox.checked = !checkbox.checked;
            } else {
                showToast('Manual biometric access updated successfully.');
            }
        })
        .catch(err => {
            alert('Network error updating status.');
            checkbox.checked = !checkbox.checked;
        });
    }

    // Save numeric Biometric ID mapping
    function saveBiometricId(uid) {
        const input = document.getElementById('bio_id_input_' + uid);
        const val = input.value.trim();
        
        const formData = new FormData();
        formData.append('ajax_action', 'save_bio_id');
        formData.append('uid', uid);
        formData.append('biometric_id', val);
        
        fetch('biometric_management.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert('Error saving Biometric ID: ' + data.message);
                input.focus();
            } else {
                // Update search filter attribute values
                const row = document.getElementById('row_' + uid);
                row.setAttribute('data-bio', val);
                showToast('Biometric ID mapped successfully.');
            }
        })
        .catch(err => {
            alert('Network error saving Biometric ID.');
        });
    }

    function reenrollFingerprint(uid, bio_id) {
        if (!confirm("Are you sure you want to re-enroll this member's fingerprint? This will send a command to wipe their existing fingerprint from the machine.")) {
            return;
        }

        const formData = new FormData();
        formData.append('ajax_action', 'reenroll_fingerprint');
        formData.append('uid', uid);
        formData.append('biometric_id', bio_id);

        fetch('biometric_management.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                showToast(data.message);
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Network error updating system.");
        });
    }

    // Modern toast notification system
    function showToast(message) {
        let toast = document.getElementById('custom_toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'custom_toast';
            toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: rgba(255, 107, 0, 0.95); border: 1px solid #ff6b00; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 9999; transition: all 0.3s ease; opacity: 0; transform: translateY(10px);';
            document.body.appendChild(toast);
        }
        toast.innerText = message;
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
        }, 2500);
    }
    </script>
</body>
</html>
