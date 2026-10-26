<?php
require '../../include/db_conn.php';
page_protect();

if (!in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    echo "Access Denied. Only authorized staff can setup Face ID.";
    exit();
}

$gym = get_gym_details($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Face ID Biometric Setup</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css?v=<?php echo time(); ?>">
    <style>
        .badge-active {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.4);
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .action-btn-mac {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff !important;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3);
            transition: all 0.2s;
        }
        .action-btn-mac:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.45);
        }
        .action-btn-link {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8 !important;
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .action-btn-link:hover {
            background: rgba(56, 189, 248, 0.25);
            border-color: #38bdf8;
        }
    </style>
</head>
<body class="page-body page-fade" style="background-color: #0b0c10;">
    <div class="page-container sidebar-collapsed">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <img src="../../images/logo.png" alt="" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>
        <div class="main-content">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0; color: #fff; font-weight: 800; font-size: 26px; display: flex; align-items: center; gap: 10px;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 3H5a2 2 0 0 0-2 2v2M17 3h2a2 2 0 0 1 2 2v2M3 17v2a2 2 0 0 0 2 2h2M21 17v2a2 2 0 0 1-2 2h-2"/>
                            <circle cx="9" cy="9" r="1.2" fill="#38bdf8"/>
                            <circle cx="15" cy="9" r="1.2" fill="#38bdf8"/>
                            <path d="M10 13a3 3 0 0 0 4 0"/>
                        </svg>
                        In-App Face ID Biometric Registry
                    </h2>
                    <p style="margin: 6px 0 0; color: var(--text-muted, #94a3b8); font-size: 13.5px;">
                        Manage facial recognition profiles for gym owners, coaches, and members. Fast offline local verification with 128-d biometric vectors.
                    </p>
                </div>
                <div style="background: rgba(14, 165, 233, 0.12); border: 1px solid rgba(56, 189, 248, 0.3); padding: 8px 16px; border-radius: 12px; color: #38bdf8; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    ⚡ Precision Matcher: Euclidean Distance &lt; 0.48
                </div>
            </div>
            
            <!-- ADMIN & STAFF SECTION -->
            <div class="row" style="margin-bottom: 30px;">
                <div class="col-md-12">
                    <div class="panel panel-primary" style="background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass-border); border-radius: 16px; overflow: hidden;">
                        <div class="panel-heading" style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.08); padding: 16px 20px;">
                            <div class="panel-title" style="color: #fff; font-weight: 800; font-size: 16px;">
                                🛡️ Admin &amp; Staff Biometric Credentials
                            </div>
                        </div>
                        <div class="panel-body" style="padding: 20px;">
                            <table class="table table-bordered datatable" style="color: #e2e8f0; margin-bottom: 0;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.03);">
                                        <th>Role</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Face ID Status</th>
                                        <th>Enrolled Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query  = "SELECT username, Full_name, role, securekey, webauthn_credential, face_descriptor, face_enrolled_at FROM admin WHERE role IN ('owner', 'super_admin', 'reception', 'trainer') ORDER BY role ASC, username ASC";
                                    $result = mysqli_query($con, $query);
                                    
                                    if ($result) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $is_enrolled = (!empty($row['face_descriptor']) || !empty($row['webauthn_credential']));
                                            $status = $is_enrolled 
                                                ? '<span class="badge-active"><span style="width:6px; height:6px; background:#10b981; border-radius:50%; display:inline-block;"></span> Active</span>' 
                                                : '<span class="badge-pending">Not Set</span>';
                                            
                                            $enrolled_txt = (!empty($row['face_enrolled_at'])) 
                                                ? date('d M Y, h:i A', strtotime($row['face_enrolled_at'])) 
                                                : ($is_enrolled ? 'Legacy Enrolled' : '—');

                                            $token = hash('sha256', $row['username'] . $row['securekey']);
                                            $query_string = "?u=" . urlencode($row['username']) . "&token=" . $token;
                                            
                                            echo "<tr>";
                                            echo "<td><span style='text-transform: uppercase; font-size: 11px; font-weight: 800; color: #38bdf8;'>" . htmlspecialchars($row['role']) . "</span></td>";
                                            echo "<td style='font-weight: 700; color: #fff;'>" . htmlspecialchars($row['username']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['Full_name']) . "</td>";
                                            echo "<td>" . $status . "</td>";
                                            echo "<td style='color: #94a3b8; font-size: 12px;'>" . $enrolled_txt . "</td>";
                                            echo "<td>
                                                    <a href='../../enroll_face_id.php" . $query_string . "' target='_blank' class='action-btn-mac' style='margin-right: 6px;'><i class='entypo-camera'></i> Enroll on this Mac</a>
                                                    <button class='action-btn-link' onclick='showEnrollLink(\"" . addslashes($query_string) . "\", \"" . addslashes($row['Full_name']) . "\")'><i class='entypo-link'></i> Share Link</button>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MEMBERS SECTION -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-primary" style="background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass-border); border-radius: 16px; overflow: hidden;">
                        <div class="panel-heading" style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.08); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div class="panel-title" style="color: #fff; font-weight: 800; font-size: 16px;">
                                🏋️ Member Face ID Biometric Registry
                            </div>
                            <span style="color: #94a3b8; font-size: 12px;">Members can also self-enroll directly from their Member Dashboard</span>
                        </div>
                        <div class="panel-body" style="padding: 20px;">
                            <table class="table table-bordered datatable" style="color: #e2e8f0; margin-bottom: 0;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.03);">
                                        <th>User ID</th>
                                        <th>Member Name</th>
                                        <th>Contact</th>
                                        <th>Face ID Status</th>
                                        <th>Enrolled Date</th>
                                        <th>Desk Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $m_query = "SELECT userid, username, mobile, password, face_descriptor, face_enrolled_at FROM users ORDER BY userid DESC LIMIT 40";
                                    $m_result = mysqli_query($con, $m_query);
                                    
                                    if ($m_result && mysqli_num_rows($m_result) > 0) {
                                        while ($m = mysqli_fetch_assoc($m_result)) {
                                            $m_enrolled = !empty($m['face_descriptor']);
                                            $m_status = $m_enrolled 
                                                ? '<span class="badge-active"><span style="width:6px; height:6px; background:#10b981; border-radius:50%; display:inline-block;"></span> Active</span>' 
                                                : '<span class="badge-pending">Not Set</span>';
                                            
                                            $m_date = (!empty($m['face_enrolled_at'])) 
                                                ? date('d M Y, h:i A', strtotime($m['face_enrolled_at'])) 
                                                : '—';

                                            $m_token = hash('sha256', $m['userid'] . $m['password']);
                                            $m_qs = "?u=" . urlencode($m['userid']) . "&token=" . $m_token;
                                            
                                            echo "<tr>";
                                            echo "<td style='font-family: monospace; color: #38bdf8; font-weight: 700;'>" . htmlspecialchars($m['userid']) . "</td>";
                                            echo "<td style='font-weight: 700; color: #fff;'>" . htmlspecialchars($m['username']) . "</td>";
                                            echo "<td style='color: #94a3b8; font-size: 12px;'>" . htmlspecialchars($m['mobile'] ?? 'N/A') . "</td>";
                                            echo "<td>" . $m_status . "</td>";
                                            echo "<td style='color: #94a3b8; font-size: 12px;'>" . $m_date . "</td>";
                                            echo "<td>
                                                    <a href='../../enroll_face_id.php" . $m_qs . "' target='_blank' class='action-btn-mac' style='margin-right: 6px;'><i class='entypo-camera'></i> Scan Face at Desk</a>
                                                    <button class='action-btn-link' onclick='showEnrollLink(\"" . addslashes($m_qs) . "\", \"" . addslashes($m['username']) . "\")'><i class='entypo-link'></i> WhatsApp Link</button>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center' style='color:#94a3b8;'>No members registered yet.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <!-- Modal / Drawer Link Box -->
                            <div id="linkBox" style="display:none; margin-top: 24px; padding: 20px; background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(56, 189, 248, 0.35); border-radius: 14px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #38bdf8; font-weight: 800; font-size: 15px;">
                                        🔗 Biometric Enrollment Link (<span id="linkRecipientName">User</span>)
                                    </h4>
                                    <button type="button" onclick="document.getElementById('linkBox').style.display='none'" style="background: none; border: none; color: #94a3b8; font-size: 16px; cursor: pointer;">✕</button>
                                </div>
                                <p style="color: #cbd5e1; font-size: 12.5px; margin-bottom: 12px;">
                                    Share this secure link via WhatsApp or SMS. Opening it on any phone or computer with a webcam will allow instant biometric face registration.
                                </p>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="enrollLinkText" class="form-control" readonly style="background: rgba(0,0,0,0.6); color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;">
                                    <button class="btn btn-primary" onclick="copyLink()" style="font-weight: 700; border-radius: 8px; white-space: nowrap;">📋 Copy Link</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include('footer.php'); ?>
        </div>
    </div>
    
    <script>
    function showEnrollLink(queryString, name) {
        const currentUrl = window.location.href;
        const rootUrl = currentUrl.substring(0, currentUrl.indexOf('/dashboard/admin/face_id_setup.php'));
        const link = rootUrl + '/enroll_face_id.php' + queryString;
        
        document.getElementById('linkRecipientName').innerText = name || 'User';
        document.getElementById('linkBox').style.display = 'block';
        document.getElementById('enrollLinkText').value = link;
        document.getElementById('linkBox').scrollIntoView({ behavior: 'smooth' });
    }
    
    function copyLink() {
        var copyText = document.getElementById("enrollLinkText");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        alert("Copied enrollment link to clipboard:\n" + copyText.value);
    }
    </script>
</body>
</html>
