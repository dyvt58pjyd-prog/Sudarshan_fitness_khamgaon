<?php
require '../../include/db_conn.php';
page_protect();

$current_role = $_SESSION['role'];
if ($current_role !== 'super_admin' && $current_role !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Handle delete action
if (isset($_GET['del'])) {
    $del_user = mysqli_real_escape_string($con, $_GET['del']);
    
    // Safety checks
    if ($del_user === $_SESSION['user_data']) {
        echo "<script>alert('You cannot delete your own account!');</script>";
    } else {
        // Fetch role of user to delete
        $check_del = mysqli_query($con, "SELECT role FROM admin WHERE username='$del_user'");
        if ($check_del && mysqli_num_rows($check_del) > 0) {
            $del_row = mysqli_fetch_assoc($check_del);
            $del_role = $del_row['role'];

            // Owners can only delete trainers and receptionists
            if ($current_role === 'owner' && $del_role !== 'trainer' && $del_role !== 'reception') {
                echo "<script>alert('As an Owner, you can only delete Trainers and Receptionists!');</script>";
            } else {
                $del_query = "DELETE FROM admin WHERE username='$del_user'";
                if (mysqli_query($con, $del_query)) {
                    echo "<script>alert('Staff account deleted successfully.');</script>";
                    echo "<meta http-equiv='refresh' content='0; url=manage_staff.php'>";
                    exit();
                } else {
                    echo "<script>alert('Failed to delete staff account.');</script>";
                }
            }
        }
    }
}

// Handle add staff action
if (isset($_POST['add_staff'])) {
    $username_input = mysqli_real_escape_string($con, trim($_POST['username']));
    $password_input = mysqli_real_escape_string($con, trim($_POST['password']));
    $fullname_input = mysqli_real_escape_string($con, trim($_POST['fullname']));
    $role_input = mysqli_real_escape_string($con, $_POST['role']);
    $secure_key = mysqli_real_escape_string($con, trim($_POST['securekey']));
    $mobile_input = mysqli_real_escape_string($con, trim($_POST['mobile']));

    // Role safety check
    if ($current_role === 'owner' && $role_input !== 'trainer' && $role_input !== 'reception') {
        echo "<script>alert('Unauthorized role assignment. Owners can only add Trainers and Receptionists.');</script>";
    } else {
        // Check if username already exists
        $check_exist = mysqli_query($con, "SELECT username FROM admin WHERE username='$username_input'");
        if (mysqli_num_rows($check_exist) > 0) {
            echo "<script>alert('Username already exists! Choose another.');</script>";
        } else {
            $add_query = "INSERT INTO admin (username, pass_key, securekey, Full_name, role, mobile) 
                          VALUES ('$username_input', '$password_input', '$secure_key', '$fullname_input', '$role_input', '$mobile_input')";
            if (mysqli_query($con, $add_query)) {
                echo "<script>alert('New staff registered successfully!');</script>";
                echo "<meta http-equiv='refresh' content='0; url=manage_staff.php'>";
                exit();
            } else {
                echo "<script>alert('Error registering staff.');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Staff Administration</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <!-- Load Outfits font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        .page-container .sidebar-menu #main-menu li#staffmanage > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 12px !important;
            color: var(--text-main) !important;
            padding: 12px 15px !important;
            width: 100%;
            margin-bottom: 20px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.25) !important;
            background: rgba(15, 23, 42, 0.8) !important;
        }
        .staff-container {
            display: grid;
            grid-template-columns: 1fr 1.6fr;
            gap: 30px;
            margin-top: 25px;
            align-items: start;
        }
        @media(max-width: 1100px) {
            .staff-container {
                grid-template-columns: 1fr;
            }
        }
        .glass-card-premium {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }
        .glass-card-premium::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ff6b00, #ea580c);
        }
        .section-header-premium {
            margin-top: 0;
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 15px;
        }
        .section-header-premium h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-header-premium h3 i {
            color: var(--accent-primary);
        }
        .section-header-premium p {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: var(--text-muted);
        }
        .premium-btn {
            background: linear-gradient(135deg, #ff6b00, #ea580c);
            border: none;
            color: white !important;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
            transition: all 0.2s ease;
            width: 100%;
        }
        .premium-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(234, 88, 12, 0.3);
        }
        .table-premium {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .table-premium th {
            background: rgba(15, 23, 42, 0.8);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 15px 20px;
            border: none;
        }
        .table-premium th:first-child { border-radius: 10px 0 0 10px; }
        .table-premium th:last-child { border-radius: 0 10px 10px 0; }
        .table-premium tr.staff-row {
            background: rgba(255, 255, 255, 0.02);
            transition: all 0.2s;
        }
        .table-premium tr.staff-row:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-1px);
        }
        .table-premium td {
            padding: 15px 20px;
            border: none;
            vertical-align: middle;
            font-size: 14px;
        }
        .table-premium td:first-child { border-radius: 10px 0 0 10px; }
        .table-premium td:last-child { border-radius: 0 10px 10px 0; }
        
        .avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 107, 0, 0.15), rgba(255, 107, 0, 0.05));
            border: 1px solid rgba(255, 107, 0, 0.3);
            color: var(--accent-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
        }
        .badge-privilege {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-super_admin { background: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid var(--danger); }
        .badge-owner { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid var(--success); }
        .badge-trainer { background: rgba(14, 165, 233, 0.15); color: var(--info); border: 1px solid var(--info); }
        .badge-reception { background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid #a855f7; }
        
        .delete-btn-premium {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #ef4444 !important;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .delete-btn-premium:hover {
            background: #ef4444;
            color: white !important;
        }
        
        /* Stats dashboard row */
        .stats-grid-staff {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        @media(max-width: 768px) {
            .stats-grid-staff {
                grid-template-columns: 1fr 1fr;
            }
        }
        .stat-card-staff {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card-staff i {
            font-size: 24px;
            color: var(--accent-primary);
        }
        .stat-card-staff .info {
            display: flex;
            flex-direction: column;
        }
        .stat-card-staff .count {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
        }
        .stat-card-staff .label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 4px;
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

            <h2>Staff Administration</h2>
            <hr />

            <?php
            // Calculate stats for staff counts
            $q_tot = mysqli_query($con, "SELECT COUNT(*) as cnt FROM admin WHERE role != 'member'");
            $total_staff = ($q_tot && $r = mysqli_fetch_assoc($q_tot)) ? intval($r['cnt']) : 0;

            $q_adm = mysqli_query($con, "SELECT COUNT(*) as cnt FROM admin WHERE role = 'super_admin'");
            $total_admins = ($q_adm && $r = mysqli_fetch_assoc($q_adm)) ? intval($r['cnt']) : 0;

            $q_own = mysqli_query($con, "SELECT COUNT(*) as cnt FROM admin WHERE role = 'owner'");
            $total_owners = ($q_own && $r = mysqli_fetch_assoc($q_own)) ? intval($r['cnt']) : 0;

            $q_tr = mysqli_query($con, "SELECT COUNT(*) as cnt FROM admin WHERE role = 'trainer'");
            $total_trainers = ($q_tr && $r = mysqli_fetch_assoc($q_tr)) ? intval($r['cnt']) : 0;

            $q_rec = mysqli_query($con, "SELECT COUNT(*) as cnt FROM admin WHERE role = 'reception'");
            $total_reception = ($q_rec && $r = mysqli_fetch_assoc($q_rec)) ? intval($r['cnt']) : 0;
            ?>

            <!-- Stats dashboard row -->
            <div class="stats-grid-staff">
                <div class="stat-card-staff">
                    <i class="entypo-users"></i>
                    <div class="info">
                        <span class="count"><?php echo $total_staff; ?></span>
                        <span class="label">Total Staff</span>
                    </div>
                </div>
                <div class="stat-card-staff">
                    <i class="entypo-user" style="color: #ef4444;"></i>
                    <div class="info">
                        <span class="count"><?php echo $total_admins + $total_owners; ?></span>
                        <span class="label">Admins &amp; Owners</span>
                    </div>
                </div>
                <div class="stat-card-staff">
                    <i class="entypo-heart" style="color: #0ea5e9;"></i>
                    <div class="info">
                        <span class="count"><?php echo $total_trainers; ?></span>
                        <span class="label">Trainers</span>
                    </div>
                </div>
                <div class="stat-card-staff">
                    <i class="entypo-monitor" style="color: #a855f7;"></i>
                    <div class="info">
                        <span class="count"><?php echo $total_reception; ?></span>
                        <span class="label">Receptionists</span>
                    </div>
                </div>
            </div>

            <div class="staff-container">
                <!-- Add Staff Form -->
                <div class="glass-card-premium">
                    <div class="section-header-premium">
                        <h3><i class="entypo-user-add"></i> Add Staff Account</h3>
                        <p>Register a new system user login with selected access role privilege</p>
                    </div>
                    
                    <form method="post" action="">
                        <label style="font-weight: 500; font-size: 12.5px; display: block; margin-bottom: 5px;">Full Name *</label>
                        <input class="form-control-premium" type="text" name="fullname" placeholder="E.g. Jane Doe" required>

                        <label style="font-weight: 500; font-size: 12.5px; display: block; margin-bottom: 5px;">Login Username ID *</label>
                        <input class="form-control-premium" type="text" name="username" placeholder="E.g. janedoe1" required>

                        <label style="font-weight: 500; font-size: 12.5px; display: block; margin-bottom: 5px;">Password *</label>
                        <input class="form-control-premium" type="password" name="password" placeholder="At least 6 characters" required>

                        <label style="font-weight: 500; font-size: 12.5px; display: block; margin-bottom: 5px;">Security Key (For recovery) *</label>
                        <input class="form-control-premium" type="text" name="securekey" placeholder="Recovery word or phrase" required>

                        <label style="font-weight: 500; font-size: 12.5px; display: block; margin-bottom: 5px;">Mobile Number (For WhatsApp Alerts) *</label>
                        <input class="form-control-premium" type="text" name="mobile" placeholder="E.g. 918459962390" required>

                        <label style="font-weight: 500; font-size: 12.5px; display: block; margin-bottom: 5px;">Role Privilege *</label>
                        <select class="form-control-premium" name="role" required>
                            <?php if ($current_role === 'super_admin'): ?>
                                <option value="super_admin">Super Admin (Full Access)</option>
                                <option value="owner">Owner (Gym Business Access)</option>
                            <?php endif; ?>
                            <option value="reception">Reception (Desk &amp; Registrations)</option>
                            <option value="trainer" selected>Trainer (Routines &amp; Health Access)</option>
                        </select>

                        <button type="submit" name="add_staff" class="premium-btn">Register Staff Profile</button>
                    </form>
                </div>

                <!-- Staff List Table -->
                <div class="glass-card-premium">
                    <div class="section-header-premium">
                        <h3><i class="entypo-list"></i> Staff Directory</h3>
                        <p>Currently configured administrator, manager, trainer, and desk logins</p>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th style="width: 8%;"></th>
                                    <th>Name / Username</th>
                                    <th>Mobile Phone</th>
                                    <th>Role Privilege</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch users
                                if ($current_role === 'super_admin') {
                                    $staff_query = "SELECT * FROM admin WHERE role != 'member' ORDER BY role ASC";
                                } else {
                                    $staff_query = "SELECT * FROM admin WHERE role IN ('owner', 'trainer', 'reception') ORDER BY role ASC";
                                }
                                
                                $res = mysqli_query($con, $staff_query);
                                if ($res && mysqli_num_rows($res) > 0) {
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        $fullname = $row['Full_name'] ?? '';
                                        $username = $row['username'] ?? '';
                                        $mobile = $row['mobile'] ?? '';
                                        $role = $row['role'] ?? 'trainer';
                                        
                                        // Compute initials for avatar
                                        $initials = '';
                                        if (!empty($fullname)) {
                                            $parts = explode(' ', $fullname);
                                            $initials = strtoupper(substr($parts[0], 0, 1));
                                            if (count($parts) > 1) {
                                                $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
                                            }
                                        } else {
                                            $initials = strtoupper(substr($username, 0, 2));
                                        }

                                        echo "<tr class='staff-row'>";
                                        echo "<td><div class='avatar-circle'>{$initials}</div></td>";
                                        echo "<td><strong>" . htmlspecialchars($fullname) . "</strong><br><span style='font-size: 11px; color: var(--text-muted); font-family: monospace;'>ID: " . htmlspecialchars($username) . "</span></td>";
                                        echo "<td>" . (empty($mobile) ? "<span style='color: var(--text-muted); font-style:italic;'>Not set</span>" : htmlspecialchars($mobile)) . "</td>";
                                        echo "<td><span class='badge-privilege badge-{$role}'>" . str_replace('_', ' ', $role) . "</span></td>";
                                        
                                        // Don't allow deletion of self
                                        if ($username === $_SESSION['user_data']) {
                                            echo "<td style='text-align: right;'><span style='color: var(--text-muted); font-style: italic; font-size: 12px; font-weight: 500;'>Logged In</span></td>";
                                        } else {
                                            // Owner can't delete another owner or super_admin
                                            if ($current_role === 'owner' && $role !== 'trainer' && $role !== 'reception') {
                                                echo "<td style='text-align: right;'>-</td>";
                                            } else {
                                                echo "<td style='text-align: right;'><a href='manage_staff.php?del=" . urlencode($username) . "' onclick=\"return confirm('Are you sure you want to delete this staff login?')\" class='delete-btn-premium'>Delete</a></td>";
                                            }
                                        }
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='text-align:center; padding: 30px;'>No staff accounts configured.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
