<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin') {
    echo "Access Denied. Only App Developers can setup Face ID.";
    exit();
}

$gym = get_gym_details($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Face ID Setup</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
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
            <h2>Face ID Enrollment Setup for Owners</h2>
            <hr>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-primary" style="background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass-border);">
                        <div class="panel-heading">
                            <div class="panel-title">Owner Accounts</div>
                        </div>
                        <div class="panel-body">
                            <table class="table table-bordered datatable">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Face ID Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Auto-upgrade database for Face ID columns if they don't exist
                                    $check_col = mysqli_query($con, "SHOW COLUMNS FROM admin LIKE 'webauthn_credential'");
                                    if (mysqli_num_rows($check_col) == 0) {
                                        mysqli_query($con, "ALTER TABLE admin ADD COLUMN webauthn_credential TEXT DEFAULT NULL");
                                        mysqli_query($con, "ALTER TABLE admin ADD COLUMN webauthn_challenge VARCHAR(255) DEFAULT NULL");
                                    }

                                    $query  = "SELECT username, Full_name, securekey, webauthn_credential FROM admin WHERE role IN ('owner', 'super_admin')";
                                    $result = mysqli_query($con, $query);
                                    
                                    if ($result) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                        $status = empty($row['webauthn_credential']) ? '<span class="badge badge-warning">Not Enrolled</span>' : '<span class="badge badge-success">Enrolled</span>';
                                        
                                        // Generate an enrollment link using their username and securekey as an auth token
                                        // Usually, you'd use a temporary token in a separate table, but this works for demonstration.
                                        $token = hash('sha256', $row['username'] . $row['securekey']);
                                        $query_string = "?u=" . urlencode($row['username']) . "&token=" . $token;
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['Full_name']) . "</td>";
                                        echo "<td>" . $status . "</td>";
                                        echo "<td>
                                                <button class='btn btn-info btn-sm' onclick='showEnrollLink(\"" . addslashes($query_string) . "\")'><i class='entypo-link'></i> Get Enrollment Link</button>
                                              </td>";
                                        echo "</tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                            
                            <div id="linkBox" style="display:none; margin-top: 20px; padding: 20px; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 10px;">
                                <h4>Enrollment Link</h4>
                                <p>Send this link to the owner. They must open this link on their mobile device to register their Face ID natively.</p>
                                <textarea id="enrollLinkText" class="form-control" rows="3" readonly style="background: rgba(0,0,0,0.5); color: #fff;"></textarea>
                                <button class="btn btn-primary" onclick="copyLink()" style="margin-top: 10px;">Copy Link</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include('footer.php'); ?>
        </div>
    </div>
    
    <script>
    function showEnrollLink(queryString) {
        // Find the root by removing '/dashboard/admin/face_id_setup.php' from the current path
        const currentUrl = window.location.href;
        const rootUrl = currentUrl.substring(0, currentUrl.indexOf('/dashboard/admin/face_id_setup.php'));
        const link = rootUrl + '/enroll_face_id.php' + queryString;
        
        document.getElementById('linkBox').style.display = 'block';
        document.getElementById('enrollLinkText').value = link;
    }
    
    function copyLink() {
        var copyText = document.getElementById("enrollLinkText");
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");
        alert("Copied the link: " + copyText.value);
    }
    </script>
</body>
</html>
