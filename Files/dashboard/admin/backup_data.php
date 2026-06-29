<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Unauthorized access! Only Super Admins and Owners can manage backups.');</script></head>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Data Import/Export</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .page-container .sidebar-menu #main-menu li#databackup > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        .backup-card {
            margin-bottom: 30px;
        }
        .backup-section {
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            border: 1px solid rgba(255, 107, 0, 0.1);
            margin-top: 15px;
        }
        .backup-section h4 {
            color: #ff6b00;
            margin-top: 0;
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        .file-input {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            outline: none;
        }
        .file-input:focus {
            border-color: #ff6b00;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
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

            <h2>Data Import / Export (Excel/CSV Backups)</h2>
            <hr />

            <div class="row">
                <!-- Export Card -->
                <div class="col-md-6">
                    <div class="a1-card-8 backup-card">
                        <div class="a1-container a1-dark-gray a1-center">
                            <h6>EXPORT GYM DATA</h6>
                        </div>
                        <div class="a1-container" style="padding: 20px 10px;">
                            <p style="color: #a3a3a3; font-size: 13px;">Generate Excel-compatible CSV downloads of all active membership and payment records to store as local backups.</p>
                            
                            <div class="backup-section">
                                <h4>Members List</h4>
                                <p style="font-size: 12px; color: #888;">Exports: IDs, contact details, dates of birth, street addresses, and joining dates.</p>
                                <a href="export_members.php" class="a1-btn a1-blue" style="width: 100%; text-align: center; text-decoration: none;">Export Members to Excel</a>
                            </div>

                            <div class="backup-section" style="margin-top: 25px;">
                                <h4>Payments Ledger</h4>
                                <p style="font-size: 12px; color: #888;">Exports: Transactions, membership links, plans, amounts, payment modes, and processor logs.</p>
                                <a href="export_payments.php" class="a1-btn a1-blue" style="width: 100%; text-align: center; text-decoration: none;">Export Payments to Excel</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import Card -->
                <div class="col-md-6">
                    <div class="a1-card-8 backup-card">
                        <div class="a1-container a1-dark-gray a1-center">
                            <h6>IMPORT GYM DATA</h6>
                        </div>
                        <div class="a1-container" style="padding: 20px 10px;">
                            <p style="color: #a3a3a3; font-size: 13px;">Restore database records or batch-register records by uploading formatted CSV or Excel (.xls, .xlsx) templates.</p>

                            <div class="backup-section">
                                <h4>Import Members</h4>
                                <form action="import_members.php" method="POST" enctype="multipart/form-data">
                                    <div class="file-upload-wrapper">
                                        <input type="file" name="members_file" class="file-input" accept=".csv,.xls,.xlsx" required />
                                    </div>
                                    <input type="submit" name="import_members_btn" value="Upload & Import Members" class="a1-btn a1-blue" style="width: 100%;" />
                                </form>
                                <p style="font-size: 11px; color: #666; margin-top: 8px;">Template fields: Membership ID, Name, Gender, Mobile, Email, DOB, Joining Date, Street, State, City, Zip</p>
                            </div>

                            <div class="backup-section" style="margin-top: 25px;">
                                <h4>Import Payments</h4>
                                <form action="import_payments.php" method="POST" enctype="multipart/form-data">
                                    <div class="file-upload-wrapper">
                                        <input type="file" name="payments_file" class="file-input" accept=".csv,.xls,.xlsx" required />
                                    </div>
                                    <input type="submit" name="import_payments_btn" value="Upload & Import Payments" class="a1-btn a1-blue" style="width: 100%;" />
                                </form>
                                <p style="font-size: 11px; color: #666; margin-top: 8px;">Template fields: Transaction ID, Membership ID, Plan Name, Paid Date, Expiry Date, Amount, Payment Mode, Processor</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
