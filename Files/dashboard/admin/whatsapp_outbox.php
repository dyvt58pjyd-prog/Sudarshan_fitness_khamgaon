<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'retry' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        mysqli_query($con, "UPDATE whatsapp_outbox SET status='pending' WHERE id=$id");
        echo "<script>alert('Message marked for retry.');</script>";
        echo "<meta http-equiv='refresh' content='0; url=whatsapp_outbox.php'>";
        exit();
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        mysqli_query($con, "DELETE FROM whatsapp_outbox WHERE id=$id");
        echo "<script>alert('Message deleted.');</script>";
        echo "<meta http-equiv='refresh' content='0; url=whatsapp_outbox.php'>";
        exit();
    } elseif ($_POST['action'] === 'clear_all') {
        mysqli_query($con, "TRUNCATE TABLE whatsapp_outbox");
        echo "<script>alert('Queue cleared completely.');</script>";
        echo "<meta http-equiv='refresh' content='0; url=whatsapp_outbox.php'>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Titan Gym | WhatsApp Queue</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <style>
        .page-body {
            background-color: #0B0F19;
            color: #F3F4F6;
            font-family: 'Inter', -apple-system, sans-serif;
        }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .table {
            color: #fff;
        }
        .table>thead>tr>th {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #9CA3AF;
        }
        .table>tbody>tr>td {
            border-top: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-pending { background: #F59E0B; color: #fff; }
        .badge-sent { background: #10B981; color: #fff; }
        .badge-failed { background: #EF4444; color: #fff; }
        .btn-custom {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            margin-right: 5px;
        }
        .btn-retry { background: #3B82F6; }
        .btn-delete { background: #EF4444; }
        .btn-clear { background: #EF4444; padding: 10px 20px; font-weight: bold; border-radius: 8px;}
    </style>
</head>
<body class="page-body page-fade">
    <div class="page-container">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <img src="../../logo.png" alt="" width="120" />
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-6">
                    <h2 style="color: #fff; font-weight: 700;"><i class="entypo-paper-plane"></i> WhatsApp Message Queue</h2>
                </div>
                <div class="col-md-6 text-right">
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to clear all messages?');">
                        <input type="hidden" name="action" value="clear_all">
                        <button type="submit" class="btn-custom btn-clear"><i class="entypo-trash"></i> Clear Queue</button>
                    </form>
                </div>
            </div>

            <div class="premium-card">
                <table class="table table-bordered table-responsive">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mobile</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Attempts</th>
                            <th>Last Attempt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM whatsapp_outbox ORDER BY id DESC LIMIT 100";
                        $result = mysqli_query($con, $query);
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $status = htmlspecialchars($row['status']);
                                $badge = 'badge-pending';
                                if ($status === 'sent') $badge = 'badge-sent';
                                if ($status === 'failed') $badge = 'badge-failed';
                                
                                echo "<tr>";
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($row['number']) . "</td>";
                                echo "<td><div style='max-height: 100px; overflow-y: auto; white-space: pre-wrap; font-size: 12px;'>" . htmlspecialchars($row['message']) . "</div></td>";
                                echo "<td><span class='badge {$badge}'>" . strtoupper($status) . "</span></td>";
                                echo "<td>" . $row['attempts'] . "</td>";
                                echo "<td>" . ($row['last_attempt'] ? $row['last_attempt'] : 'N/A') . "</td>";
                                echo "<td>";
                                if ($status !== 'sent') {
                                    echo "<form method='POST' style='display:inline;'>
                                            <input type='hidden' name='action' value='retry'>
                                            <input type='hidden' name='id' value='{$row['id']}'>
                                            <button type='submit' class='btn-custom btn-retry' title='Retry'><i class='entypo-arrows-ccw'></i></button>
                                          </form>";
                                }
                                echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this message?\");'>
                                        <input type='hidden' name='action' value='delete'>
                                        <input type='hidden' name='id' value='{$row['id']}'>
                                        <button type='submit' class='btn-custom btn-delete' title='Delete'><i class='entypo-trash'></i></button>
                                      </form>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Queue is empty.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
