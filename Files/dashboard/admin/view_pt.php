<?php
require '../../include/db_conn.php';
page_protect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Personal Training Logs</title>
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
        #boxxe {
            width: 100px;
        }
    </style>
    <script>
        function ConfirmDelete() {
            return confirm("Are you sure you want to delete this Personal Training record?");
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

            <h2>Personal Training Session Logs</h2>
            <hr />

            <table class="table table-bordered datatable" id="table-1" border="1">
                <thead>
                    <tr>
                        <th style="width: 10%;">Date</th>
                        <th style="width: 15%;">Gym Member</th>
                        <th style="width: 15%;">Trainer</th>
                        <th style="width: 20%;">Workouts Done</th>
                        <th style="width: 15%;">Nutrition Guidelines</th>
                        <th style="width: 15%;">Remarks & Milestones</th>
                        <th style="width: 10%;">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT pt.*, u.username AS member_name, a.Full_name AS trainer_name 
                              FROM personal_training pt 
                              INNER JOIN users u ON pt.uid = u.userid 
                              INNER JOIN admin a ON pt.trainer_id = a.username 
                              ORDER BY pt.date DESC, pt.id DESC";
                    $result = mysqli_query($con, $query);

                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['member_name']) . "</strong><br><span style='font-size:11px;color:var(--text-muted);'>ID: " . htmlspecialchars($row['uid']) . "</span></td>";
                            echo "<td>" . htmlspecialchars($row['trainer_name']) . "</td>";
                            echo "<td>" . nl2br(htmlspecialchars($row['workout_details'])) . "</td>";
                            echo "<td>" . nl2br(htmlspecialchars($row['nutrition_notes'])) . "</td>";
                            echo "<td>";
                            if (!empty($row['trainer_remarks'])) {
                                echo "<strong>Remarks:</strong> " . nl2br(htmlspecialchars($row['trainer_remarks'])) . "<br><br>";
                            }
                            if (!empty($row['achievements'])) {
                                echo "<strong>PRs/Milestones:</strong> " . nl2br(htmlspecialchars($row['achievements']));
                            }
                            echo "</td>";
                            echo "<td>
                                    <form action='del_pt.php' method='post' onsubmit='return ConfirmDelete()'>
                                        <input type='hidden' name='id' value='" . $row['id'] . "'/>
                                        <input type='submit' value='Delete' id='boxxe' class='a1-btn a1-orange'/>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center;'>No personal training sessions recorded yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
