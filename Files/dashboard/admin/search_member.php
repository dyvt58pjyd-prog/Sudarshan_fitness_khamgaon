<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] === 'member') {
    header("Location: ../member/");
    exit();
}

$gym = get_gym_details($con);
$q = isset($_GET['q']) ? trim(mysqli_real_escape_string($con, $_GET['q'])) : '';

$results = [];
$error_message = '';

if ($q !== '') {
    $sql = "SELECT * FROM users WHERE userid LIKE '%$q%' OR username LIKE '%$q%' OR mobile LIKE '%$q%' ORDER BY username ASC";
    $res = mysqli_query($con, $sql);
    if ($res) {
        $count = mysqli_num_rows($res);
        if ($count === 1) {
            $row = mysqli_fetch_assoc($res);
            header("Location: read_member.php?name=" . urlencode($row['userid']));
            exit();
        } elseif ($count > 1) {
            while ($row = mysqli_fetch_assoc($res)) {
                $results[] = $row;
            }
        } else {
            $error_message = "No members found matching your search query: '" . htmlspecialchars($q) . "'";
        }
    } else {
        $error_message = "Database error: " . mysqli_error($con);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Search Member</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .page-container .sidebar-menu #main-menu li#searchmem > a {
            background-color: rgba(255, 107, 0, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .search-box-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            max-width: 650px;
            margin: 0 auto 30px auto;
            box-shadow: var(--glass-shadow);
        }
        .search-input-group {
            display: flex;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 4px 8px;
            transition: all 0.3s ease;
        }
        .search-input-group:focus-within {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.2);
        }
        .search-input-group input {
            background: transparent !important;
            border: none !important;
            color: var(--text-main) !important;
            font-size: 16px !important;
            padding: 10px !important;
            flex-grow: 1;
        }
        .search-input-group input:focus {
            outline: none !important;
            box-shadow: none !important;
        }
        .search-btn {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover));
            border: none;
            color: white !important;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.4);
        }
        .results-container {
            margin-top: 20px;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="../../images/logo.png" alt="" style="max-height: 60px; max-width: 180px;" />
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

            <h2>Search Gym Member</h2>
            <hr />

            <div class="search-box-card">
                <form method="GET" action="">
                    <label style="margin-bottom: 10px;">Enter Member Name, ID Number, or Mobile Number</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <div class="search-input-group" style="flex-grow: 1;">
                            <div style="display: flex; align-items: center; padding-left: 8px; color: var(--text-muted);">
                                <i class="entypo-search" style="font-size: 18px;"></i>
                            </div>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="e.g. John Doe, 101, 9876543210" required autocomplete="off">
                        </div>
                        <button type="submit" class="search-btn">Search</button>
                    </div>
                </form>
            </div>

            <?php if ($error_message !== ''): ?>
                <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); color: var(--warning); border-radius: 10px; padding: 15px; max-width: 650px; margin: 0 auto 30px auto;">
                    <strong>Notice:</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
                <div class="results-container">
                    <h3>Multiple Matches Found</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Membership ID</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Joining Date</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['userid']); ?></td>
                                    <td style="font-weight: 600; color: var(--accent-primary);"><?php echo htmlspecialchars($member['username']); ?></td>
                                    <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($member['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo htmlspecialchars($member['joining_date']); ?></td>
                                    <td style="text-align: center;">
                                        <a href="read_member.php?name=<?php echo urlencode($member['userid']); ?>" class="a1-btn a1-blue" style="text-decoration: none;">View Profile</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
