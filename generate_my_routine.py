import os

file_content = """<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'member') {
    die("Access Denied");
}

$gym = get_gym_details($con);
$uid = $_SESSION['user_data'];

$workout = "No workout plan assigned yet.";
$diet = "No diet plan assigned yet.";
$trainer_name = "Not Assigned";
$last_updated = "";

$rq = mysqli_query($con, "SELECT r.*, a.Full_name as trainer_name FROM member_routines r LEFT JOIN admin a ON r.trainer_id = a.username WHERE r.uid = '$uid'");
if ($rq && mysqli_num_rows($rq) > 0) {
    $row = mysqli_fetch_assoc($rq);
    if (!empty($row['workout_plan'])) $workout = $row['workout_plan'];
    if (!empty($row['diet_plan'])) $diet = $row['diet_plan'];
    if (!empty($row['trainer_name'])) $trainer_name = $row['trainer_name'];
    $last_updated = $row['updated_at'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | My Smart Routine</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="../../js/Script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <style>
        body { background: #030407; font-family: 'Inter', sans-serif; color: #f8fafc; }
        .page-container .sidebar-menu #main-menu li#my_routine > a {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 #10b981;
        }

        .header-banner {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(30, 41, 59, 0.9) 100%);
            border: 2px solid #10b981;
            border-radius: 20px;
            padding: 35px 40px;
            margin-bottom: 40px;
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            margin: 0;
            color: #ffffff;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 32px;
            letter-spacing: -1px;
        }

        .header-subtitle {
            color: #34d399;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .meta-tag {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 13px;
            color: #94a3b8;
            display: inline-block;
            margin-top: 15px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 1100px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        .protocol-card {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }

        /* Beautiful Markdown Rendering */
        .md-render {
            color: #cbd5e1;
            font-size: 16px;
            line-height: 1.8;
        }

        .md-render h1, .md-render h2 {
            color: #ffffff;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 15px;
            margin-bottom: 25px;
            margin-top: 0;
        }

        .md-render h3 {
            color: #38bdf8; /* Blue for Workout, handled in CSS below */
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 20px;
            margin-top: 35px;
            margin-bottom: 15px;
        }

        .card-diet .md-render h3 {
            color: #10b981; /* Green for Diet */
        }
        
        .card-diet .md-render h1, .card-diet .md-render h2 {
            color: #10b981;
            border-bottom: 1px solid rgba(16, 185, 129, 0.2);
        }

        .card-workout .md-render h1, .card-workout .md-render h2 {
            color: #38bdf8;
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
        }

        .md-render strong {
            color: #ffffff;
            font-weight: 700;
            background: rgba(255,255,255,0.05);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .md-render ul {
            padding-left: 20px;
            margin-bottom: 25px;
            list-style-type: none;
        }

        .md-render li {
            margin-bottom: 15px;
            position: relative;
            padding-left: 15px;
        }

        .md-render li::before {
            content: "•";
            position: absolute;
            left: -10px;
            top: -2px;
            color: #ff6b00;
            font-size: 20px;
            font-family: Arial;
        }

        .card-diet .md-render li::before { color: #10b981; }
        .card-workout .md-render li::before { color: #38bdf8; }

        .btn-print {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 12px 24px;
            border-radius: 10px;
            font-family: 'JetBrains Mono', monospace;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-print:hover {
            background: rgba(255,255,255,0.15);
            border-color: #fff;
        }

        /* Print CSS */
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .sidebar-menu, .header-banner, .btn-print, footer { display: none !important; }
            .content-grid { display: block; }
            .protocol-card { background: #fff !important; border: none !important; box-shadow: none !important; color: #000 !important; padding: 0 !important; margin-bottom: 40px !important; }
            .md-render { color: #000 !important; }
            .md-render h1, .md-render h2, .md-render h3, .md-render strong { color: #000 !important; border-bottom: 1px solid #ccc !important; }
            .md-render li::before { color: #000 !important; }
            .md-render strong { background: none !important; }
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 60px;" />
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content" style="padding: 40px;">
            
            <div class="header-banner">
                <div>
                    <div class="header-subtitle"><i class="fa-solid fa-bolt"></i> Official Protocol</div>
                    <h1 class="header-title">YOUR SMART ROUTINE</h1>
                    <div class="meta-tag">
                        <i class="fa-solid fa-user-shield"></i> Assigned by: <strong style="color: #fff;"><?php echo htmlspecialchars($trainer_name); ?></strong>
                        <?php if($last_updated): ?>
                            &nbsp; | &nbsp; <i class="fa-regular fa-clock"></i> Updated: <?php echo date('M d, Y', strtotime($last_updated)); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="btn-print" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Print Protocol
                </button>
            </div>

            <div class="content-grid">
                <!-- Diet Protocol -->
                <div class="protocol-card card-diet">
                    <div style="position: absolute; top: -30px; right: -30px; font-size: 150px; opacity: 0.03;">🥗</div>
                    <div class="md-render" id="diet_render"></div>
                </div>

                <!-- Workout Protocol -->
                <div class="protocol-card card-workout">
                    <div style="position: absolute; top: -30px; right: -30px; font-size: 150px; opacity: 0.03;">🏋️</div>
                    <div class="md-render" id="workout_render"></div>
                </div>
            </div>

            <script>
                // Safely parse the markdown injected from PHP
                document.getElementById("diet_render").innerHTML = marked.parse(<?php echo json_encode($diet); ?>);
                document.getElementById("workout_render").innerHTML = marked.parse(<?php echo json_encode($workout); ?>);
            </script>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>
</body>
</html>
"""

filepath = "./Files/dashboard/member/my_routine.php"
with open(filepath, "w", encoding="utf-8") as f:
    f.write(file_content)
print(f"Created {filepath}")
