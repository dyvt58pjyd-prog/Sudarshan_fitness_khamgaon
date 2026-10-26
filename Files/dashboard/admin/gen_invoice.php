<?php
require '../../include/db_conn.php';
page_protect();

// Security check: members can only access their own invoices
if (isset($_SESSION['role']) && $_SESSION['role'] === 'member') {
    $requested_uid = isset($_GET['id']) ? trim($_GET['id']) : '';
    if ($_SESSION['user_data'] !== $requested_uid) {
        echo "<head><script>alert('Access Denied: You cannot view other members\' invoices.');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=../member/index.php'>";
        exit();
    }
}

$etid = mysqli_real_escape_string($con, $_GET['etid']);
$pid  = mysqli_real_escape_string($con, $_GET['pid']);
$uid  = mysqli_real_escape_string($con, $_GET['id']);

$sql = "SELECT * FROM users u 
        INNER JOIN enrolls_to e ON u.userid = e.uid 
        INNER JOIN plan p ON p.pid = e.pid 
        WHERE u.userid = '$uid' AND e.et_id = '$etid'";
$res = mysqli_query($con, $sql);
if ($res) {
    $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
}

$gym = get_gym_details($con);

// Parse pricing and discount details
$discount = isset($row['discount_amount']) ? intval($row['discount_amount']) : 0;
$plan_price = intval($row['amount']);
$total_payable = $plan_price - $discount;

$paid_amount = (isset($row['paid_amount']) && $row['paid_amount'] !== null) ? intval($row['paid_amount']) : $plan_price;

// Fix legacy overpayment calculation glitches (clamp to max payable)
if ($paid_amount > $total_payable) {
    $paid_amount = $total_payable;
}

// Decouple Personal Training (PT) from Membership Plan Invoice
$has_pt = false;
$pt_row = null;
$pt_amount = 0;
$total_paid = $paid_amount;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Official Tax Invoice #SF-<?php echo date('Y', strtotime($row['paid_date'])); ?>-<?php echo str_pad($row['et_id'], 4, '0', STR_PAD_LEFT); ?></title>
    <!-- Modern Typography: Inter + JetBrains Mono -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --bg-page: #0b0f19;
            --surface-card: #ffffff;
            --brand-primary: #0f172a;
            --brand-accent: #ff6b00;
            --brand-accent-glow: rgba(255, 107, 0, 0.25);
            --brand-emerald: #10b981;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --border-subtle: #e2e8f0;
            --border-card: #cbd5e1;
            --radius-main: 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: var(--text-body);
            background: #090d16;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 107, 0, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(56, 189, 248, 0.06) 0%, transparent 40%);
            margin: 0;
            padding: 35px 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Floating Top Action Toolbar */
        .actions-toolbar {
            width: 100%;
            max-width: 860px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .back-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            padding: 10px 18px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s;
        }
        .back-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-2px);
        }

        .btn-group-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            border: none;
            color: #ffffff !important;
            padding: 10px 22px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        .btn-print {
            background: linear-gradient(135deg, #ff6b00 0%, #e05300 100%);
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
        }
        .btn-pdf {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        }
        .btn-whatsapp {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.25);
        }

        /* Main Executive Invoice Sheet */
        .invoice-sheet {
            width: 100%;
            max-width: 860px;
            background: #ffffff;
            border-radius: var(--radius-main);
            padding: 45px 50px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Top Luxury Dual Stripe */
        .invoice-sheet::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 7px;
            background: linear-gradient(90deg, #ff6b00 0%, #ff9500 50%, #0f172a 100%);
        }

        /* Subtle Watermark */
        .sheet-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            width: 320px;
            opacity: 0.035;
            pointer-events: none;
            z-index: 0;
        }

        /* Header Section */
        .header-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 25px;
            align-items: center;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 30px;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .gym-branding {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .gym-logo-frame {
            background: #0f172a;
            padding: 10px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gym-logo-frame img {
            max-height: 72px;
            max-width: 130px;
            object-fit: contain;
            display: block;
        }

        .gym-identity h1 {
            font-size: 24px;
            font-weight: 900;
            color: var(--brand-primary);
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        .gym-identity .location-pill {
            display: inline-block;
            background: rgba(255, 107, 0, 0.1);
            color: var(--brand-accent);
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid rgba(255, 107, 0, 0.2);
            margin-bottom: 8px;
        }

        .gym-identity p {
            margin: 2px 0;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Right Invoice Metadata Card */
        .invoice-meta-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1.5px solid var(--border-subtle);
            border-radius: 18px;
            padding: 20px 24px;
            text-align: right;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .meta-invoice-title {
            font-size: 12px;
            font-weight: 900;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
        }

        .meta-invoice-no {
            font-family: 'JetBrains Mono', monospace;
            font-size: 20px;
            font-weight: 800;
            color: var(--brand-primary);
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .meta-invoice-date {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .status-pill-paid {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 30px;
        }

        /* Member & Billing Information Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .info-card {
            background: #f8fafc;
            border: 1.5px solid var(--border-subtle);
            border-radius: 16px;
            padding: 20px 22px;
            position: relative;
        }

        .info-card.accent-card {
            border-left: 4px solid var(--brand-accent);
        }

        .info-card.blue-card {
            border-left: 4px solid #0284c7;
        }

        .info-card-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .member-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--brand-primary);
            margin-bottom: 8px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 12.5px;
            border-bottom: 1px dashed rgba(226, 232, 240, 0.8);
        }
        .detail-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .detail-row .lbl {
            color: var(--text-muted);
            font-weight: 500;
        }

        .detail-row .val {
            color: var(--brand-primary);
            font-weight: 700;
            font-family: 'Inter', sans-serif;
        }

        .val-mono {
            font-family: 'JetBrains Mono', monospace !important;
            color: var(--brand-accent) !important;
        }

        /* Line Items Table */
        .table-invoice-wrapper {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .invoice-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .invoice-table th {
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 14px 18px;
            text-align: left;
            border: none;
        }
        .invoice-table th:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        .invoice-table th:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            text-align: right;
        }

        .invoice-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            vertical-align: middle;
        }

        .item-main-title {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--brand-primary);
            margin-bottom: 3px;
        }

        .item-sub-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .badge-mode {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Settlement & Financial Summary Section */
        .settlement-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 25px;
            align-items: start;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .verification-callout {
            background: #f8fafc;
            border: 1.5px solid var(--border-subtle);
            border-radius: 16px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .verification-qr img {
            width: 85px;
            height: 85px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            display: block;
        }

        .verification-text h4 {
            font-size: 13px;
            font-weight: 800;
            color: var(--brand-primary);
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .verification-text p {
            font-size: 11.5px;
            color: var(--text-muted);
            margin: 0 0 6px 0;
            line-height: 1.5;
        }

        .gate-access-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 107, 0, 0.08);
            border: 1px solid rgba(255, 107, 0, 0.25);
            color: var(--brand-accent);
            padding: 3px 10px;
            border-radius: 6px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 700;
        }

        /* Totals Breakdown */
        .financial-summary-card {
            background: #f8fafc;
            border: 1.5px solid var(--border-subtle);
            border-radius: 16px;
            padding: 20px 24px;
        }

        .calc-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }

        .calc-row .label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .calc-row .figure {
            color: var(--brand-primary);
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .calc-row.discount .figure {
            color: #ef4444;
        }

        .calc-divider {
            height: 1.5px;
            background: #e2e8f0;
            margin: 10px 0;
        }

        .grand-total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 6px 0;
        }

        .grand-total-row .lbl {
            font-size: 13px;
            font-weight: 800;
            color: var(--brand-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .grand-total-row .sum {
            font-family: 'JetBrains Mono', monospace;
            font-size: 26px;
            font-weight: 800;
            color: var(--brand-accent);
            line-height: 1;
        }

        .pending-due-card {
            margin-top: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #dc2626;
            font-weight: 700;
        }

        /* Signature & Seal Section */
        .signatory-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 2px solid #f1f5f9;
            padding-top: 25px;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        .received-agent {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .received-agent strong {
            color: var(--brand-primary);
        }

        .official-seal-wrap {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .digital-gym-seal {
            border: 2px solid #10b981;
            color: #10b981;
            border-radius: 50%;
            width: 76px;
            height: 76px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transform: rotate(-8deg);
            background: rgba(16, 185, 129, 0.05);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
        }
        .digital-gym-seal i {
            font-size: 18px;
            margin-bottom: 2px;
        }

        .sign-line {
            width: 170px;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* Terms & Operating Decorum */
        .policy-block {
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 16px 20px;
            font-size: 10px;
            color: #64748b;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .policy-block strong.title {
            color: var(--brand-primary);
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 6px;
        }

        .policy-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 20px;
        }

        .policy-item {
            display: flex;
            gap: 6px;
        }

        .policy-item i {
            color: var(--brand-accent);
            margin-top: 2px;
        }

        /* Print Mode Override */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .actions-toolbar {
                display: none !important;
            }
            .invoice-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 20px 25px !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .invoice-sheet::before {
                display: none !important;
            }
            .sheet-watermark {
                opacity: 0.04 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Actions Toolbar (Hidden on Print & PDF) -->
    <div class="actions-toolbar">
        <a href="invoices.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Invoices Ledger
        </a>
        <div class="btn-group-actions">
            <button class="btn-action btn-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Official Receipt
            </button>
            <button class="btn-action btn-pdf" onclick="downloadPDF()">
                <i class="fa-solid fa-file-arrow-down"></i> Download Clean PDF
            </button>
            <button class="btn-action btn-whatsapp" onclick="sendWhatsAppReceipt()" id="btn-wa">
                <i class="fa-brands fa-whatsapp"></i> Send via WhatsApp
            </button>
        </div>
    </div>

    <!-- Main Clean Printable Invoice Sheet -->
    <div class="invoice-sheet">
        
        <!-- Gym Brand Watermark Background -->
        <?php 
        $wm_path = file_exists('../../images/watermark.png') ? '../../images/watermark.png' : '../../images/logo.png';
        ?>
        <img class="sheet-watermark" src="<?php echo htmlspecialchars($wm_path); ?>" alt="" />

        <!-- Top Header Grid -->
        <div class="header-grid">
            <div class="gym-branding">
                <div class="gym-logo-frame">
                    <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="Gym Logo" />
                </div>
                <div class="gym-identity">
                    <span class="location-pill">Khamgaon • Premier Fitness &amp; Strength Hub</span>
                    <h1><?php echo htmlspecialchars($gym['gym_name']); ?></h1>
                    <p><i class="fa-solid fa-location-dot" style="color:var(--brand-accent);"></i> <?php echo htmlspecialchars($gym['gym_address']); ?></p>
                    <p><i class="fa-solid fa-phone" style="color:#0284c7;"></i> <?php echo htmlspecialchars($gym['gym_contact']); ?> &nbsp;|&nbsp; <i class="fa-solid fa-envelope" style="color:#10b981;"></i> <?php echo htmlspecialchars($gym['gym_email']); ?></p>
                </div>
            </div>

            <!-- Invoice Metadata Box -->
            <div class="invoice-meta-card">
                <div class="meta-invoice-title">Tax Invoice &amp; Payment Receipt</div>
                <div class="meta-invoice-no">#SF-<?php echo date('Y', strtotime($row['paid_date'])); ?>-<?php echo str_pad($row['et_id'], 4, '0', STR_PAD_LEFT); ?></div>
                <div class="meta-invoice-date">
                    <strong>Date:</strong> <?php echo date('d M, Y', strtotime($row['paid_date'])); ?>
                </div>
                <div class="status-pill-paid">
                    <i class="fa-solid fa-circle-check"></i> Paid &amp; Verified
                </div>
            </div>
        </div>

        <!-- Member & Billing Information Grid -->
        <div class="info-grid">
            
            <!-- Left Card: Member Particulars -->
            <div class="info-card accent-card">
                <div class="info-card-label">
                    <i class="fa-solid fa-id-card"></i> Member Information
                </div>
                <div class="member-title"><?php echo htmlspecialchars($row['username']); ?></div>
                <div class="detail-row">
                    <span class="lbl">Membership ID:</span>
                    <span class="val val-mono"><?php echo htmlspecialchars($row['userid']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Contact Mobile:</span>
                    <span class="val">+91 <?php echo htmlspecialchars($row['mobile']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Email Address:</span>
                    <span class="val"><?php echo htmlspecialchars($row['email'] ?: 'member@sudarshanfitness.de'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Gender &amp; Shift:</span>
                    <span class="val"><?php echo ucfirst(htmlspecialchars($row['gender'] ?? 'Male')); ?> • General Training</span>
                </div>
            </div>

            <!-- Right Card: Subscription & Terminal Clearance -->
            <div class="info-card blue-card">
                <div class="info-card-label">
                    <i class="fa-solid fa-shield-halved"></i> Subscription &amp; Gate Access
                </div>
                <div class="member-title" style="font-size:16px; color:#0284c7;"><?php echo htmlspecialchars($row['planName']); ?></div>
                <div class="detail-row">
                    <span class="lbl">Active Period:</span>
                    <span class="val"><?php echo date('d M Y', strtotime($row['paid_date'])); ?> ➔ <?php echo date('d M Y', strtotime($row['expire'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Payment Method:</span>
                    <span class="val"><span class="badge-mode"><?php echo strtoupper(htmlspecialchars($row['payment_mode'])); ?></span></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Biometric Gate PIN:</span>
                    <span class="val"><span class="gate-access-pill"><i class="fa-solid fa-key"></i> PIN: <?php echo htmlspecialchars($row['entry_code']); ?></span></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Entrance Status:</span>
                    <span class="val" style="color:#10b981;"><i class="fa-solid fa-bolt"></i> Terminal Authorized</span>
                </div>
            </div>

        </div>

        <!-- Items Breakdown Table -->
        <div class="table-invoice-wrapper">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Plan / Service Description</th>
                        <th style="width: 20%;">Validity Period</th>
                        <th style="width: 15%;">Payment Mode</th>
                        <th style="width: 20%;">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="item-main-title"><?php echo htmlspecialchars($row['planName']); ?></div>
                            <div class="item-sub-desc"><?php echo htmlspecialchars($row['description'] ?: 'Full access to weight room, cardio stations & locker rooms.'); ?></div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['validity']); ?> Month(s)</strong><br>
                            <span style="font-size: 11.5px; color: var(--text-muted);">Valid thru: <?php echo date('d M Y', strtotime($row['expire'])); ?></span>
                        </td>
                        <td>
                            <span class="badge-mode"><?php echo strtoupper(htmlspecialchars($row['payment_mode'])); ?></span>
                        </td>
                        <td style="text-align: right; font-weight: 800; font-family: 'JetBrains Mono', monospace; font-size: 15px; color: var(--brand-primary);">
                            ₹<?php echo number_format($row['amount']); ?>
                        </td>
                    </tr>
                    <?php if ($has_pt): ?>
                    <tr>
                        <td>
                            <div class="item-main-title">Personal Training (PT)</div>
                            <div class="item-sub-desc">Dedicated Coach: <strong><?php echo htmlspecialchars($pt_row['trainer_name']); ?></strong></div>
                        </td>
                        <td>
                            <strong>Thru: <?php echo htmlspecialchars($pt_row['expire_date']); ?></strong>
                        </td>
                        <td>
                            <span class="badge-mode"><?php echo strtoupper(htmlspecialchars($pt_row['payment_mode'])); ?></span>
                        </td>
                        <td style="text-align: right; font-weight: 800; font-family: 'JetBrains Mono', monospace; font-size: 15px; color: var(--brand-primary);">
                            ₹<?php echo number_format($pt_row['amount']); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Settlement & Financial Summary Section -->
        <div class="settlement-grid">
            
            <!-- Left: Verification Callout & QR -->
            <div class="verification-callout">
                <div class="verification-qr">
                    <?php 
                    $verify_qr_url = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode("https://sudarshanfitness.de/dashboard/admin/gen_invoice.php?id=" . $uid . "&etid=" . $etid . "&pid=" . $pid);
                    ?>
                    <img src="<?php echo $verify_qr_url; ?>" alt="Verification QR" />
                </div>
                <div class="verification-text">
                    <h4>Official Digital Verification</h4>
                    <p>Scan this QR code to verify the cryptographic authenticity of this payment record on the secure Sudarshan Fitness Portal.</p>
                    <div style="font-size: 10px; color: #94a3b8; font-weight: 600;">
                        ENGINEERED BY DRDO, MINISTRY OF DEFENCE
                    </div>
                </div>
            </div>

            <!-- Right: Financial Calculation Box -->
            <div class="financial-summary-card">
                <div class="calc-row">
                    <span class="label">Gross Plan Fee:</span>
                    <span class="figure">₹<?php echo number_format($plan_price); ?></span>
                </div>

                <?php if ($discount > 0): ?>
                <div class="calc-row discount">
                    <span class="label">Special Privilege Discount:</span>
                    <span class="figure">- ₹<?php echo number_format($discount); ?></span>
                </div>
                <?php endif; ?>

                <div class="calc-divider"></div>

                <div class="grand-total-row">
                    <span class="lbl">Net Paid:</span>
                    <span class="sum">₹<?php echo number_format($total_paid); ?></span>
                </div>

                <?php if (isset($row['balance']) && $row['balance'] > 0): ?>
                <div class="pending-due-card">
                    <span><i class="fa-solid fa-triangle-exclamation"></i> Balance Due:</span>
                    <span>₹<?php echo number_format($row['balance']); ?> (By: <?php echo date('d M Y', strtotime($row['balance_due_date'])); ?>)</span>
                </div>
                <?php else: ?>
                <div style="margin-top: 10px; font-size: 11.5px; color: #15803d; font-weight: 700; text-align: right;">
                    <i class="fa-solid fa-circle-check"></i> Zero Dues • Paid in Full
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Official Signatory & Seal Row -->
        <div class="signatory-section">
            <div class="received-agent">
                Payment Authorized &amp; Logged By: <strong><?php echo htmlspecialchars($row['received_by'] ? $row['received_by'] : 'Admin Central Desk'); ?></strong><br>
                Official Member Support: <strong>info@sudarshanfitness.de</strong> | Member Hub: <strong>https://sudarshanfitness.de</strong>
            </div>

            <div class="official-seal-wrap">
                <div class="digital-gym-seal">
                    <i class="fa-solid fa-stamp"></i>
                    <span>VERIFIED<br><?php echo date('Y'); ?></span>
                </div>
                <div class="sign-line">Authorized Signatory</div>
            </div>
        </div>

        <!-- Terms of Service & Club Decorum Policy -->
        <div class="policy-block">
            <strong class="title"><i class="fa-solid fa-file-contract"></i> Membership Terms, Safety &amp; Legal Policies:</strong>
            <div class="policy-grid">
                <div class="policy-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div><strong>Equipment Liability:</strong> Members causing intentional or negligent damage to gym machinery, dumbbells, or facility assets will be billed 100% of replacement or repair costs.</div>
                </div>
                <div class="policy-item">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <div><strong>Health &amp; Liability Waiver:</strong> Members exercise at their own discretion. The gym management, trainers, and staff are indemnified against any personal health complications or injuries.</div>
                </div>
                <div class="policy-item">
                    <i class="fa-solid fa-ban"></i>
                    <div><strong>Fee Non-Refundability:</strong> All registration, personal training, and subscription dues paid are strictly non-refundable and non-transferable under all circumstances.</div>
                </div>
                <div class="policy-item">
                    <i class="fa-solid fa-shoe-prints"></i>
                    <div><strong>Facility Decorum:</strong> Clean, indoor-only athletic footwear and sweat towels are strictly mandatory. Always return all weights and dumbbells to designated racks.</div>
                </div>
            </div>
        </div>

    </div>

    <script>
    function downloadPDF() {
        const element = document.querySelector('.invoice-sheet');
        const opt = {
            margin:       [6, 6, 6, 6],
            filename:     'Sudarshan_Fitness_Tax_Invoice_#SF-' + '<?php echo date('Y', strtotime($row['paid_date'])); ?>' + '-' + '<?php echo str_pad($row['et_id'], 4, '0', STR_PAD_LEFT); ?>' + '.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }

    function sendWhatsAppReceipt() {
        const btn = document.getElementById('btn-wa');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        
        const url = '../../api/resend_whatsapp_receipt.php?uid=<?php echo urlencode($uid); ?>&etid=<?php echo urlencode($etid); ?>&type=membership';
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = origText;
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error sending receipt:', error);
                btn.disabled = false;
                btn.innerHTML = origText;
                alert('❌ Network error. Please verify server connection.');
            });
    }

    <?php if (isset($_GET['download_pdf']) && $_GET['download_pdf'] == 1): ?>
    window.onload = function() {
        downloadPDF();
    }
    <?php endif; ?>
    </script>
</body>
</html>
