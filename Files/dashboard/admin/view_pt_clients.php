<?php
require '../../include/db_conn.php';
page_protect();

// Handle deletion of PT Client assignment if requested
if (isset($_POST['delete_pt_id'])) {
    $del_id = mysqli_real_escape_string($con, $_POST['delete_pt_id']);
    
    // Get member uid before deleting to check if trainer_id in users needs update
    $q_find = mysqli_query($con, "SELECT uid, trainer_id FROM pt_enrollments WHERE pt_id = '$del_id'");
    if ($q_find && mysqli_num_rows($q_find) > 0) {
        $find_row = mysqli_fetch_assoc($q_find);
        $m_uid = $find_row['uid'];
        $m_trainer = $find_row['trainer_id'];
        
        // Delete enrollment
        if (mysqli_query($con, "DELETE FROM pt_enrollments WHERE pt_id = '$del_id'")) {
            // Check if there are other active enrollments for this member
            date_default_timezone_set("Asia/Calcutta");
            $today = date('Y-m-d');
            $q_active = mysqli_query($con, "SELECT trainer_id FROM pt_enrollments WHERE uid = '$m_uid' AND expire_date >= '$today' ORDER BY expire_date DESC LIMIT 1");
            if ($q_active && mysqli_num_rows($q_active) > 0) {
                $active_row = mysqli_fetch_assoc($q_active);
                $new_trainer = $active_row['trainer_id'];
                mysqli_query($con, "UPDATE users SET trainer_id = '$new_trainer' WHERE userid = '$m_uid'");
            } else {
                mysqli_query($con, "UPDATE users SET trainer_id = NULL WHERE userid = '$m_uid'");
            }
            echo "<script>alert('PT Client assignment deleted successfully.');</script>";
        } else {
            echo "<script>alert('Error deleting PT Client assignment: " . mysqli_escape_string($con, mysqli_error($con)) . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | PT Client Assignments</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#pthassubopen > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        .tab-btn {
            background: rgba(255, 107, 0, 0.1);
            border: 1px solid rgba(255, 107, 0, 0.2);
            color: var(--text-main);
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .tab-btn.active, .tab-btn:hover {
            background: #ff6b00;
            border-color: #ff6b00;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(255,107,0,0.25);
        }
        .badge-active {
            background-color: rgba(16, 185, 129, 0.15) !important;
            color: #10b981 !important;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-expired {
            background-color: rgba(239, 68, 68, 0.15) !important;
            color: #ef4444 !important;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .action-flex {
            display: flex;
            gap: 8px;
            align-items: center;
        }
    </style>
    <script>
        function filterClients(status) {
            // Update active state of buttons
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Filter table rows
            const rows = document.querySelectorAll('.client-row');
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (status === 'all') {
                    row.style.display = '';
                } else if (status === 'active' && rowStatus === 'active') {
                    row.style.display = '';
                } else if (status === 'expired' && rowStatus === 'expired') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function confirmDeletion() {
            return confirm("Are you sure you want to delete this PT client assignment? This will clear their trainer assignment unless they have another active enrollment.");
        }
    </script>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <!-- logo -->
                <div class="logo">
                    <a href="main.php">
                        <?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
                <!-- logo collapse icon -->
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
                        <li>Welcome <?php echo $_SESSION['full_name']; ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Personal Training Client Assignments</h2>
            <hr />

            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; gap: 10px;">
                    <button class="tab-btn active" onclick="filterClients('all')">All Clients</button>
                    <button class="tab-btn" onclick="filterClients('active')">Active PT</button>
                    <button class="tab-btn" onclick="filterClients('expired')">Expired PT</button>
                </div>
                
                <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'reception'): ?>
                    <a href="enroll_pt.php" class="a1-btn a1-blue" style="border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-weight: 600;">
                        <span>+</span> Enroll New PT Client
                    </a>
                <?php endif; ?>
            </div>

            <table class="table table-bordered datatable" id="table-1" border="1">
                <thead>
                    <tr>
                        <th style="width: 25%;">Client Details</th>
                        <th style="width: 20%;">Assigned Personal Trainer</th>
                        <th style="width: 20%;">Enrollment Validity</th>
                        <th style="width: 12%;">Fee Details</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 13%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    date_default_timezone_set("Asia/Calcutta");
                    $today = date('Y-m-d');
                    
                    $query = "SELECT p.*, u.username AS member_name, u.mobile, u.email, t.Full_name AS trainer_name,
                                     (SELECT COUNT(*) FROM pt_attendance a WHERE a.member_id = p.uid) as sessions_logged
                              FROM pt_enrollments p 
                              INNER JOIN users u ON p.uid = u.userid 
                              INNER JOIN admin t ON p.trainer_id = t.username 
                              ORDER BY p.expire_date DESC, p.pt_id DESC";
                    $result = mysqli_query($con, $query);

                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $is_active = ($row['expire_date'] >= $today);
                            $status_class = $is_active ? 'badge-active' : 'badge-expired';
                            $status_text = $is_active ? 'Active' : 'Expired';
                            $status_attr = $is_active ? 'active' : 'expired';
                            
                            echo "<tr class='client-row' data-status='" . $status_attr . "'>";
                            
                            // Client Details
                            echo "<td>";
                            echo "<strong>" . htmlspecialchars($row['member_name']) . "</strong><br>";
                            echo "<span style='font-size:11px;color:var(--text-muted);'>ID: " . htmlspecialchars($row['uid']) . "</span><br>";
                            echo "<span style='font-size:12px;'><i class='entypo-phone'></i> " . htmlspecialchars($row['mobile']) . "</span>";
                            echo "</td>";
                            
                            // Trainer Details
                            echo "<td>";
                            echo "<strong>" . htmlspecialchars($row['trainer_name']) . "</strong><br>";
                            echo "<span style='font-size:11px;color:var(--text-muted);'>Username: " . htmlspecialchars($row['trainer_id']) . "</span>";
                            echo "</td>";
                            
                            // Validity Dates & Sessions
                            echo "<td>";
                            echo "Start: " . htmlspecialchars($row['enroll_date']) . "<br>";
                            echo "End: " . htmlspecialchars($row['expire_date']) . "<br>";
                            echo "<span style='font-size:11px; color:#ff6b00;'>Sessions Logged: " . htmlspecialchars($row['sessions_logged']) . "</span>";
                            echo "</td>";
                            
                            // Fee details
                            echo "<td>";
                            echo "<strong>₹" . htmlspecialchars($row['amount']) . "</strong><br>";
                            echo "<span style='font-size:11px;text-transform:uppercase;color:var(--text-muted);'>" . htmlspecialchars($row['payment_mode']) . "</span>";
                            echo "</td>";
                            
                            // Status Badge
                            echo "<td>";
                            echo "<span class='" . $status_class . "'>" . $status_text . "</span>";
                            echo "</td>";
                            
                            // Actions
                            echo "<td>";
                            echo "<div class='action-flex' style='flex-wrap: wrap;'>";
                            
                            // Log Session Button
                            if ($is_active) {
                                echo "<a href='log_pt_session.php?uid=" . urlencode($row['uid']) . "&trainer_id=" . urlencode($row['trainer_id']) . "' class='a1-btn' style='background:#10b981; color:#fff; padding:4px 8px; font-size:12px; text-decoration:none;' title='Log a new session'>+ Log</a>";
                            }

                            echo "<a href='gen_pt_invoice.php?ptid=" . $row['pt_id'] . "' target='_blank' class='a1-btn a1-blue' style='padding:4px 8px; font-size:12px; text-decoration:none;'>Receipt</a>";
                            
                            if ($current_role === 'super_admin' || $current_role === 'owner') {
                                echo "<form method='post' onsubmit='return confirmDeletion()' style='display:inline;'>
                                        <input type='hidden' name='delete_pt_id' value='" . $row['pt_id'] . "'/>
                                        <input type='submit' value='Delete' class='a1-btn a1-orange' style='padding:4px 8px; font-size:12px;'/>
                                      </form>";
                            }
                            echo "</div>";
                            echo "</td>";
                            
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>No personal training clients found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
