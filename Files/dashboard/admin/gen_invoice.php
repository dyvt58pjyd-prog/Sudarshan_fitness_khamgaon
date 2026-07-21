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
<<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Payment Receipt</title>
    <!-- Load modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #ea580c;
            --accent-gradient: linear-gradient(135deg, #ff6b00, #ea580c);
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            --text-main: #334155;
            --text-light: #64748b;
            --border-color: rgba(226, 232, 240, 0.8);
            --card-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.05), 0 0 0 1px rgba(15, 23, 42, 0.03);
        }
        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            margin: 40px 20px;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .actions-wrapper {
            width: 100%;
            max-width: 850px;
            margin-bottom: 25px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .btn-print {
            background: var(--accent-gradient);
            border: none;
            color: white !important;
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-print:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(234, 88, 12, 0.3);
        }
        .btn-download {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        .btn-download:hover {
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
        }
        .btn-whatsapp {
            background: linear-gradient(135deg, #25d366, #128c7e);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.2);
        }
        .btn-whatsapp:hover {
            box-shadow: 0 6px 16px rgba(37, 211, 102, 0.3);
        }
        .invoice-container {
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 24px;
            padding: 45px 50px;
            width: 100%;
            max-width: 850px;
            box-sizing: border-box;
            background: #ffffff;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        /* Top Banner Decorator */
        .invoice-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #0f172a, #ff6b00, #ea580c);
        }
        .watermark-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: auto;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px dashed rgba(226, 232, 240, 0.9);
            padding-bottom: 30px;
            margin-bottom: 30px;
            z-index: 1;
            position: relative;
        }
        .gym-brand {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .gym-logo {
            max-height: 80px;
            max-width: 180px;
            object-fit: contain;
        }
        .gym-details h3 {
            margin: 0 0 5px 0;
            color: var(--primary);
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .gym-quote {
            margin: 0 0 6px 0;
            font-size: 10.5px;
            color: var(--accent);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .gym-details p {
            margin: 3px 0;
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.4;
        }
        .receipt-badge {
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 16px;
            padding: 18px 24px;
            text-align: right;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        }
        .receipt-badge h2 {
            margin: 0 0 8px 0;
            color: var(--primary);
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .receipt-badge p {
            margin: 4px 0;
            font-size: 12.5px;
            color: var(--text-main);
        }
        .client-section {
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 16px;
            padding: 22px 26px;
            margin-bottom: 30px;
            z-index: 1;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        }
        .client-section h4 {
            margin: 0 0 14px 0;
            color: var(--text-light);
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .client-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 24px;
            font-size: 13.5px;
        }
        .client-col {
            line-height: 1.6;
        }
        .client-col strong {
            color: var(--primary-light);
            font-weight: 600;
        }
        .client-col span {
            color: var(--text-main);
            margin-left: 4px;
        }
        .table-invoice {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 35px;
            z-index: 1;
            position: relative;
        }
        .table-invoice th, .table-invoice td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
        .table-invoice th {
            background: var(--primary);
            font-weight: 600;
            color: #ffffff;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        .table-invoice th:first-child {
            border-radius: 12px 0 0 12px;
        }
        .table-invoice th:last-child {
            border-radius: 0 12px 12px 0;
        }
        .plan-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }
        .plan-desc {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
            display: block;
            line-height: 1.4;
        }
        .mode-badge {
            background: rgba(15, 23, 42, 0.05);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            display: inline-block;
        }
        .total-row td {
            font-size: 15px;
            border-bottom: none;
            padding-top: 20px;
        }
        .total-amount-box {
            background: rgba(234, 88, 12, 0.05);
            border: 1px solid rgba(234, 88, 12, 0.15);
            border-radius: 12px;
            padding: 10px 18px;
            display: inline-block;
        }
        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: var(--accent);
        }
        /* PAID stamp */
        .watermark-stamp {
            position: absolute;
            top: 230px;
            right: 80px;
            border: 2px solid #059669;
            border-radius: 12px;
            color: #059669;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 14px;
            transform: rotate(-10deg);
            background: rgba(5, 150, 105, 0.04);
            letter-spacing: 1.5px;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.08);
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            pointer-events: none;
        }
        .watermark-stamp span {
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1px;
            opacity: 0.9;
        }
        .received-badge {
            background: rgba(15, 23, 42, 0.03);
            border-left: 4px solid var(--accent);
            padding: 12px 18px;
            border-radius: 0 12px 12px 0;
            margin-top: 30px;
            font-size: 13.5px;
            z-index: 1;
            position: relative;
        }
        .received-badge strong {
            color: var(--accent);
            font-weight: 700;
        }
        .footer-receipt {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
            z-index: 1;
            position: relative;
        }
        .signature-block {
            text-align: center;
            width: 190px;
        }
        .signature-line {
            border-top: 1px solid rgba(148, 163, 184, 0.3);
            padding-top: 10px;
            font-size: 12.5px;
            color: var(--text-main);
            font-weight: 600;
        }
        .digital-seal {
            margin-bottom: 6px;
            font-size: 11px;
            color: var(--text-light);
            font-weight: 700;
            letter-spacing: 0.5px;
            opacity: 0.7;
        }
        @media print {
            body {
                margin: 0;
                background: #ffffff;
            }
            .actions-wrapper {
                display: none;
            }
            .invoice-container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .invoice-container::before {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="actions-wrapper">
        <button class="btn-print" onclick="window.print()">
            <span style="font-size: 14px;">⎙</span> PRINT
        </button>
        <button class="btn-print btn-download" onclick="downloadPDF()">
            <span style="font-size: 14px;">⬇</span> DOWNLOAD PDF
        </button>
        <button class="btn-print btn-whatsapp" onclick="sendWhatsAppReceipt()" id="btn-wa">
            <span style="font-size: 14px;">💬</span> SEND TO WHATSAPP
        </button>
    </div>

    <div class="invoice-container">
        <!-- Gym Logo Watermark Background -->
        <img class="watermark-bg" src="../../images/watermark.png" alt="" />

        <!-- Verification Stamp -->
        <div class="watermark-stamp">
            PAID
            <span>✓ VERIFIED</span>
        </div>

        <!-- Header -->
        <div class="invoice-header">
            <div class="gym-brand">
                <img class="gym-logo" src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="Gym Logo" />
                <div class="gym-details">
                    <h3><?php echo htmlspecialchars($gym['gym_name']); ?></h3>
                    <div class="gym-quote">Your transformation begins from today</div>
                    <p><?php echo htmlspecialchars($gym['gym_address']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($gym['gym_contact']); ?> | Email: <?php echo htmlspecialchars($gym['gym_email']); ?></p>
                </div>
            </div>
            <div class="receipt-badge">
                <h2>Payment Memo</h2>
                <p><strong>Receipt ID:</strong> #<?php echo 100 + intval($row['et_id']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($row['paid_date']); ?></p>
            </div>
        </div>

        <!-- Client info -->
        <div class="client-section">
            <h4>Billing Information</h4>
            <div class="client-grid">
                <div class="client-col">
                    <strong>Member Name:</strong> <span><?php echo htmlspecialchars($row['username']); ?></span><br>
                    <strong>Membership ID:</strong> <span><?php echo htmlspecialchars($row['userid']); ?></span><br>
                    <strong>Starting Date:</strong> <span><?php echo htmlspecialchars($row['paid_date']); ?></span>
                </div>
                <div class="client-col">
                    <strong>Contact Phone:</strong> <span><?php echo htmlspecialchars($row['mobile']); ?></span><br>
                    <strong>Email Address:</strong> <span><?php echo htmlspecialchars($row['email']); ?></span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table-invoice">
            <thead>
                <tr>
                    <th style="width: 45%;">Membership Plan Details</th>
                    <th style="width: 25%;">Validity Period</th>
                    <th style="width: 15%;">Payment Mode</th>
                    <th style="text-align: right; width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span class="plan-name"><?php echo htmlspecialchars($row['planName']); ?></span>
                        <span class="plan-desc"><?php echo htmlspecialchars($row['description']); ?></span>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['validity']); ?> Month(s)</strong><br>
                        <span style="font-size: 12px; color: var(--text-light);">Expires: <?php echo htmlspecialchars($row['expire']); ?></span>
                    </td>
                    <td>
                        <span class="mode-badge"><?php echo htmlspecialchars($row['payment_mode']); ?></span>
                    </td>
                    <td style="text-align: right; font-weight: 600; font-size: 15px;">₹<?php echo htmlspecialchars($row['amount']); ?></td>
                </tr>
                <?php if ($has_pt): ?>
                <tr>
                    <td>
                        <span class="plan-name">Personal Training (PT)</span>
                        <span class="plan-desc">Trainer: <strong><?php echo htmlspecialchars($pt_row['trainer_name']); ?></strong></span>
                    </td>
                    <td>
                        <strong>Expires: <?php echo htmlspecialchars($pt_row['expire_date']); ?></strong>
                    </td>
                    <td>
                        <span class="mode-badge"><?php echo htmlspecialchars($pt_row['payment_mode']); ?></span>
                    </td>
                    <td style="text-align: right; font-weight: 600; font-size: 15px;">₹<?php echo htmlspecialchars($pt_row['amount']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($discount > 0): ?>
                <tr>
                    <td colspan="2"></td>
                    <td style="text-align: right; font-weight: 600; color: var(--text-light); padding-top: 12px;">Discount:</td>
                    <td style="text-align: right; font-weight: 600; color: #ef4444; padding-top: 12px;">- ₹<?php echo $discount; ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="2" style="border: none; padding-top: 15px; vertical-align: top; text-align: left;">
                        <div class="upi-pay-card" style="display: inline-flex; align-items: center; gap: 15px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); padding: 12px 20px; border-radius: 12px; max-width: 320px; text-align: left; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;">
                            <div style="background: #22c55e; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                ✓
                            </div>
                            <div>
                                <span style="display: block; font-size: 13px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Payment Verified</span>
                                <span style="display: block; font-size: 11px; color: #15803d; margin-top: 2px;">Thank you for your payment.</span>
                            </div>
                        </div>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: var(--text-light); padding-top: 15px; vertical-align: top;">Total Paid:</td>
                    <td style="text-align: right; padding-top: 15px; vertical-align: top;">
                        <div class="total-amount-box" style="<?php echo (isset($row['balance']) && $row['balance'] > 0) ? 'background-color: #fef2f2; border: 1px solid #fecaca;' : ''; ?>">
                            <span class="total-amount" style="<?php echo (isset($row['balance']) && $row['balance'] > 0) ? 'color: #ef4444;' : ''; ?>">₹<?php echo $total_paid; ?></span>
                            <?php if (isset($row['balance']) && $row['balance'] > 0): ?>
                                <span style="display: block; font-size: 13px; color: #dc2626; margin-top: 5px; font-weight: 600;">Pending: ₹<?php echo $row['balance']; ?></span>
                                <span style="display: block; font-size: 11px; color: #ef4444; margin-top: 2px;">Due: <?php echo date('d M, Y', strtotime($row['balance_due_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Motivational Quotes -->
        <div style="text-align: center; margin: 25px 0 15px 0; border-top: 1px dashed rgba(226, 232, 240, 0.9); padding-top: 20px;">
            <h4 style="margin: 0; color: var(--accent); font-weight: 700; font-style: italic; font-size: 14px; letter-spacing: 0.5px;">Be strong : be fit : be you</h4>
            <p style="margin: 4px 0 0 0; color: var(--text-main); font-size: 12px; font-weight: 600;">Lets build a strong tomorrow, together</p>
        </div>

        <!-- Received By / Signature -->
        <div class="received-badge">
            Payment Received By: <strong><?php echo htmlspecialchars($row['received_by'] ? $row['received_by'] : 'Super Admin'); ?></strong>
        </div>

        <div class="footer-receipt">
            <div style="font-size: 11.5px; color: var(--text-light); line-height: 1.5; max-width: 480px;">
                * Thank you for being a valued member of the Sudarshan Fitness Family!<br>
                Access your member portal anytime at: <strong>https://sudarshanfitness.de</strong><br>
                System Engineered by DRDO, Ministry of Defence (Anurag Bawaskar)<br>Please keep this receipt for future reference or plan renewal requests.
            </div>
            <div class="signature-block">
                <div class="digital-seal">[ SUDARSHAN FITNESS ]</div>
                <div class="signature-line">Authorized Sign</div>
            </div>
        </div>

        <!-- Terms & Conditions waiver block (Only appears in PDF/Print view) -->
        <div class="terms-section" style="margin-top: 35px; font-size: 10px; color: #94a3b8; line-height: 1.5; border-top: 1px solid rgba(226, 232, 240, 0.8); padding-top: 15px;">
            <strong style="color: #64748b; font-size: 10.5px; display: block; margin-bottom: 5px; text-transform: uppercase;">Terms &amp; Conditions:</strong>
            1. **Equipment Damage Recovery**: If any member causes damage to the gym machinery, weights, or equipment, 100% of the replacement or repair cost will be recovered directly from the member.<br>
            2. **Health &amp; Liability Waiver**: In the event of any physical injury, health issues, or medical emergency during training within the gym premises, the gym management will not be held responsible.<br>
            3. **Membership Fees**: Gym registration, membership, and personal training fees are strictly non-refundable and non-transferable under any circumstances.<br>
            4. **Safety &amp; Decorum**: Members must wear clean athletic attire, indoor-only training shoes, and wipe down machines after use. Always return weights to their proper racks.
        </div>
    </div>

    <script>
    function downloadPDF() {
        const element = document.querySelector('.invoice-container');
        const opt = {
            margin:       [8, 8, 8, 8],
            filename:     'Sudarshan_Fitness_Receipt_#' + '<?php echo 100 + intval($row['et_id']); ?>' + '.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        // Print/Save PDF using html2pdf.js
        html2pdf().set(opt).from(element).save();
    }

    function sendWhatsAppReceipt() {
        const btn = document.getElementById('btn-wa');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span style="font-size: 14px;">⌛</span> SENDING...';
        
        const url = '../../api/resend_whatsapp_receipt.php?uid=<?php echo urlencode($uid); ?>&etid=<?php echo urlencode($etid); ?>&type=membership';
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = origText;
                if (data.success) {
                    alert('✓ ' + data.message);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error sending receipt:', error);
                btn.disabled = false;
                btn.innerHTML = origText;
                alert('❌ Connection failed. Check network or server status.');
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
