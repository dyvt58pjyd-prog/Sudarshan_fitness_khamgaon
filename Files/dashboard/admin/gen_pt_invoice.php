<?php
require '../../include/db_conn.php';
page_protect();

$pt_id = isset($_GET['ptid']) ? mysqli_real_escape_string($con, $_GET['ptid']) : '';

$sql = "SELECT p.*, u.username, u.mobile, u.email, t.Full_name AS trainer_name 
        FROM pt_enrollments p 
        INNER JOIN users u ON p.uid = u.userid 
        INNER JOIN admin t ON p.trainer_id = t.username 
        WHERE p.pt_id = '$pt_id'";
$res = mysqli_query($con, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
} else {
    echo "<h3>Personal Training Enrollment Record Not Found.</h3>";
    exit();
}

$gym = get_gym_details($con);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | PT Payment Receipt</title>
    <!-- Load modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            color: #1e293b;
            margin: 40px;
            background: #f8fafc;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .actions-wrapper {
            text-align: center;
            margin-bottom: 25px;
        }
        .btn-print {
            background: linear-gradient(135deg, #ff6b00, #ea580c);
            border: none;
            color: white !important;
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 0, 0.4);
        }
        .btn-whatsapp {
            background: linear-gradient(135deg, #25d366, #128c7e);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
            margin-left: 10px;
        }
        .btn-whatsapp:hover {
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        }
        .invoice-container {
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 40px;
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
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
            background: linear-gradient(90deg, #0c0c0c, #ff6b00, #ea580c);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        .gym-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .gym-logo {
            max-height: 75px;
            max-width: 180px;
            object-fit: contain;
        }
        .gym-details h3 {
            margin: 0 0 6px 0;
            color: #0c0c0c;
            font-size: 24px;
            font-weight: 700;
        }
        .gym-details p {
            margin: 2px 0;
            font-size: 13px;
            color: #64748b;
            line-height: 1.4;
        }
        .receipt-badge {
            background: rgba(255, 107, 0, 0.08);
            border: 1px solid rgba(255, 107, 0, 0.3);
            border-radius: 12px;
            padding: 15px 25px;
            text-align: right;
        }
        .receipt-badge h2 {
            margin: 0 0 5px 0;
            color: #ff6b00;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .receipt-badge p {
            margin: 3px 0;
            font-size: 13px;
            color: #475569;
        }
        .client-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .client-section h4 {
            margin: 0 0 12px 0;
            color: #475569;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .client-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .client-col strong {
            color: #0f172a;
        }
        .client-col span {
            color: #475569;
        }
        .table-invoice {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
        }
        .table-invoice th, .table-invoice td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-invoice th {
            background: #f1f5f9;
            font-weight: 600;
            color: #334155;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-invoice th:first-child {
            border-radius: 8px 0 0 8px;
        }
        .table-invoice th:last-child {
            border-radius: 0 8px 8px 0;
        }
        .plan-name {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
        }
        .plan-desc {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
            display: block;
        }
        .mode-badge {
            background: #e2e8f0;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            display: inline-block;
        }
        .total-row td {
            font-size: 16px;
            border-bottom: none;
            padding-top: 20px;
        }
        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #ff6b00;
        }
        /* PAID watermark stamp */
        .watermark-stamp {
            position: absolute;
            top: 250px;
            right: 80px;
            border: 4px double #10b981;
            border-radius: 10px;
            color: #10b981;
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 18px;
            transform: rotate(-12deg);
            opacity: 0.85;
            background: rgba(16, 185, 129, 0.03);
            pointer-events: none;
            letter-spacing: 2px;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.05);
        }
        .watermark-stamp::after {
            content: "✓ VERIFIED";
            font-size: 9px;
            display: block;
            text-align: center;
            margin-top: 2px;
            letter-spacing: 1px;
        }
        .received-badge {
            background: rgba(15, 23, 42, 0.03);
            border-left: 3px solid #ff6b00;
            padding: 10px 15px;
            border-radius: 0 8px 8px 0;
            margin-top: 50px;
            font-size: 13px;
        }
        .received-badge strong {
            color: #ff6b00;
        }
        .footer-receipt {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 25px;
        }
        .signature-block {
            text-align: center;
            width: 180px;
        }
        .signature-line {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 13px;
            color: #475569;
            font-weight: 600;
        }
        .digital-seal {
            margin-bottom: 5px;
            font-size: 11px;
            color: #94a3b8;
            letter-spacing: 0.5px;
            opacity: 0.5;
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
            <span style="font-size: 16px;">⎙</span> PRINT RECEIPT
        </button>
        <button class="btn-print btn-whatsapp" onclick="sendWhatsAppReceipt()" id="btn-wa">
            <span style="font-size: 16px;">💬</span> SEND TO WHATSAPP
        </button>
    </div>

    <div class="invoice-container">
        <!-- Verification Stamp -->
        <div class="watermark-stamp">PAID</div>

        <!-- Header -->
        <div class="invoice-header">
            <div class="gym-brand">
                <img class="gym-logo" src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="Gym Logo" />
                <div class="gym-details">
                    <h3><?php echo htmlspecialchars($gym['gym_name']); ?></h3>
                    <p><?php echo htmlspecialchars($gym['gym_address']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($gym['gym_contact']); ?> | Email: <?php echo htmlspecialchars($gym['gym_email']); ?></p>
                </div>
            </div>
            <div class="receipt-badge">
                <h2>PT Receipt</h2>
                <p><strong>Receipt ID:</strong> #PT-<?php echo 100 + intval($row['pt_id']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($row['enroll_date']); ?></p>
            </div>
        </div>

        <!-- Client info -->
        <div class="client-section">
            <h4>Billing Information</h4>
            <div class="client-grid">
                <div class="client-col">
                    <strong>Member Name:</strong> <span><?php echo htmlspecialchars($row['username']); ?></span><br>
                    <strong>Membership ID:</strong> <span><?php echo htmlspecialchars($row['uid']); ?></span>
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
                    <th style="width: 45%;">Personal Training Details</th>
                    <th style="width: 25%;">Validity Period</th>
                    <th style="width: 15%;">Payment Mode</th>
                    <th style="text-align: right; width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span class="plan-name">Personal Training (PT) Package</span>
                        <span class="plan-desc">Assigned Personal Trainer: <strong><?php echo htmlspecialchars($row['trainer_name']); ?></strong></span>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['enroll_date']); ?> to <?php echo htmlspecialchars($row['expire_date']); ?></strong>
                    </td>
                    <td>
                        <span class="mode-badge"><?php echo htmlspecialchars($row['payment_mode']); ?></span>
                    </td>
                    <td style="text-align: right; font-weight: 600; font-size: 15px;">₹<?php echo htmlspecialchars($row['amount']); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" style="border: none; padding-top: 15px; vertical-align: top; text-align: left;">
                        <?php 
                        $upi_id = isset($gym['upi_id']) ? trim($gym['upi_id']) : '';
                        if (!empty($upi_id)): 
                            $total_paid = intval($row['amount']);
                        ?>
                        <div class="upi-pay-card" style="display: inline-flex; align-items: center; gap: 15px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); padding: 12px 20px; border-radius: 12px; max-width: 320px; text-align: left; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;">
                            <div style="background: #22c55e; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                ✓
                            </div>
                            <div>
                                <span style="display: block; font-size: 13px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Payment Verified</span>
                                <span style="display: block; font-size: 11px; color: #15803d; margin-top: 2px;">Thank you for your payment.</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #475569; padding-top: 15px; vertical-align: top;">Total Paid:</td>
                    <td style="text-align: right; padding-top: 15px; vertical-align: top;"><span class="total-amount">₹<?php echo htmlspecialchars($row['amount']); ?></span></td>
                </tr>
            </tbody>
        </table>

        <!-- Received By / Signature -->
        <div class="received-badge">
            Payment Received By = <strong><?php echo htmlspecialchars($row['received_by'] ? $row['received_by'] : 'Super Admin'); ?></strong>
        </div>

        <div class="footer-receipt">
            <div style="font-size: 11px; color: #64748b; line-height: 1.4; max-width: 450px;">
                * This is a computer-generated transaction receipt from <?php echo htmlspecialchars($gym['gym_name']); ?>.<br>
                Please keep this receipt for future reference or PT renewal requests.
            </div>
            <div class="signature-block">
                <div class="digital-seal">[ SUDARSHAN FITNESS ]</div>
                <div class="signature-line">Authorized Sign</div>
            </div>
        </div>
    </div>
    <script>
    function sendWhatsAppReceipt() {
        const btn = document.getElementById('btn-wa');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span style="font-size: 16px;">⌛</span> SENDING...';
        
        const url = '../../api/resend_whatsapp_receipt.php?uid=<?php echo urlencode($row['uid']); ?>&ptid=<?php echo urlencode($row['pt_id']); ?>&type=pt';
        
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
    </script>
</body>
</html>
