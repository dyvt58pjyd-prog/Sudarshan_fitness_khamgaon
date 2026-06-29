<?php
require '../../include/db_conn.php';
page_protect();

if (!in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}
$gym = get_gym_details($con);

// Handle Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$query = "SELECT * FROM visitors";
if (!empty($search)) {
    $query .= " WHERE name LIKE '%$search%' OR mobile LIKE '%$search%' OR address LIKE '%$search%'";
}
$query .= " ORDER BY visit_date DESC";
$res = mysqli_query($con, $query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Visitor Logs</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .page-container .sidebar-menu #main-menu li#visitors_list > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .portal-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 10px !important;
            width: 100%;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }
        .visitor-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-primary);
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
                <div class="col-md-6 col-sm-8 clearfix">
                    <a href="visitor_entry.php" class="btn btn-primary" style="background: var(--accent-primary); color: #000; border: none; font-weight: bold;"><i class="entypo-plus"></i> New Visitor Entry</a>
                </div>
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

            <h2 style="margin-top: 20px;">Visitor & Inquiry Logs</h2>
            <hr />

            <!-- Search Panel -->
            <form method="get" action="" style="margin-bottom: 25px;">
                <div style="display: flex; gap: 10px; max-width: 600px; align-items: center;">
                    <input class="form-control-premium" type="text" name="search" placeholder="Search by Name, Mobile, or Address..." value="<?php echo htmlspecialchars($search); ?>" style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-weight: 600; background: var(--accent-primary); border-color: var(--accent-primary); color: #000000; height: 42px;">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="visitors_list.php" class="btn btn-default" style="padding: 0 20px; display: inline-flex; align-items: center; justify-content: center; height: 42px; text-decoration: none;">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="portal-card">
                <div class="table-responsive">
                    <table class="table" style="width: 100%; border-collapse: collapse; color: var(--text-main);">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Photo</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Visitor Info</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Address & Notes</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Visit Date</th>
                                <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($res && mysqli_num_rows($res) > 0) {
                                while ($row = mysqli_fetch_assoc($res)) {
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding: 15px 12px; vertical-align: middle;">
                                            <?php 
                                            if (!empty($row['photo_path'])) {
                                                $clean_path = ltrim($row['photo_path'], './');
                                                $physical_path = __DIR__ . '/../../' . $clean_path;
                                                $url_path = '../../' . $clean_path;
                                                
                                                if (file_exists($physical_path)) {
                                                    echo '<img src="'.htmlspecialchars($url_path).'" class="visitor-photo" onclick="showModal(\''.htmlspecialchars($url_path).'\')" style="cursor: pointer;" alt="Photo">';
                                                } else {
                                                    echo '<div style="width: 60px; height: 60px; border-radius: 50%; background: #333; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #888;">No Photo</div>';
                                                }
                                            } else {
                                                echo '<div style="width: 60px; height: 60px; border-radius: 50%; background: #333; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #888;">N/A</div>';
                                            }
                                            ?>
                                        </td>
                                        <td style="padding: 15px 12px; vertical-align: middle;">
                                            <strong style="color: #ffffff; display: block; font-size: 15px;"><?php echo htmlspecialchars($row['name']); ?></strong>
                                            <span style="font-size: 13px; color: var(--accent-primary); display: block; margin-top: 5px;"><i class="entypo-phone"></i> <?php echo htmlspecialchars($row['mobile']); ?></span>
                                        </td>
                                        <td style="padding: 15px 12px; vertical-align: middle; max-width: 250px;">
                                            <div style="font-size: 13px; color: #e2e8f0; margin-bottom: 5px;"><i class="entypo-location"></i> <?php echo htmlspecialchars($row['address']); ?></div>
                                            <?php if (!empty($row['notes'])): ?>
                                                <div style="font-size: 12px; color: var(--text-muted); background: rgba(0,0,0,0.3); padding: 5px 8px; border-radius: 4px;">
                                                    <strong>Note:</strong> <?php echo htmlspecialchars($row['notes']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 15px 12px; vertical-align: middle; font-size: 13px; color: var(--text-muted);">
                                            <?php echo date('M d, Y', strtotime($row['visit_date'])); ?><br>
                                            <span style="font-size: 11px;"><?php echo date('h:i A', strtotime($row['visit_date'])); ?></span>
                                        </td>
                                        <td style="padding: 15px 12px; text-align: center; vertical-align: middle;">
                                            <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">
                                                <?php echo strtoupper(htmlspecialchars($row['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">No visitor records found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; flex-direction:column; align-items:center; justify-content:center;">
        <span onclick="document.getElementById('imageModal').style.display='none'" style="position:absolute; top:20px; right:30px; color:#fff; font-size:40px; cursor:pointer;">&times;</span>
        <img id="modalImage" style="max-width:90%; max-height:80%; border-radius:10px; border: 3px solid var(--accent-primary);">
    </div>
    <script>
        function showModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').style.display = 'flex';
        }
    </script>
</body>
</html>
