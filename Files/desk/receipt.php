<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../include/db_conn.php';

$etid = isset($_GET['etid']) ? mysqli_real_escape_string($con, trim($_GET['etid'])) : '';
$uid  = isset($_GET['uid']) ? mysqli_real_escape_string($con, trim($_GET['uid'])) : '';

if (empty($etid) && empty($uid)) {
    die("Invalid Receipt Parameters.");
}

$where_clause = "";
if (!empty($etid)) {
    $where_clause = "e.et_id = '$etid'";
} else {
    $where_clause = "u.userid = '$uid' ORDER BY e.et_id DESC";
}

$sql = "SELECT e.*, u.username, u.mobile, u.email, u.gender, u.entry_code, u.joining_date,
               p.planName, p.amount as plan_amount, p.validity as plan_validity
        FROM enrolls_to e
        INNER JOIN users u ON e.uid = u.userid
        LEFT JOIN plan p ON e.pid = p.pid
        WHERE $where_clause
        LIMIT 1";

$res = mysqli_query($con, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    die("Receipt record not found in system.");
}

$receipt = mysqli_fetch_assoc($res);
$gym = get_gym_details($con);

$is_new = ($receipt['joining_date'] === $receipt['paid_date']);
$receipt_type = $is_new ? 'NEW MEMBERSHIP ADMISSION' : 'MEMBERSHIP RENEWAL';

$plan_price = intval($receipt['plan_amount'] ?? 0);
$discount   = intval($receipt['discount_amount'] ?? 0);
$paid_now   = floatval($receipt['paid_amount'] ?? $plan_price);
$balance    = floatval($receipt['balance'] ?? 0);
$total_payable = max(0, $plan_price - $discount);

$logo_path = $gym['gym_logo'] ?? '../images/logo.png';
if (substr($logo_path, 0, 6) === '../../') {
    $logo_path = '../' . substr($logo_path, 6);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo htmlspecialchars($receipt['et_id']); ?> - <?php echo htmlspecialchars($receipt['username']); ?> | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff6b00;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: var(--text-dark);
            padding: 30px 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Top Action Bar (hidden on print) */
        .action-bar {
            width: 100%;
            max-width: 620px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-print {
            background: #ff6b00;
            color: #fff;
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
        }
        .btn-print:hover { background: #e05d00; transform: translateY(-1px); }
        .btn-close {
            background: #e2e8f0;
            color: #334155;
        }
        .btn-close:hover { background: #cbd5e1; }
        .btn-pdf {
            background: #0284c7;
            color: #fff;
        }
        .btn-pdf:hover { background: #0369a1; }

        /* Receipt Sheet */
        .receipt-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            width: 100%;
            max-width: 620px;
            padding: 36px 32px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .receipt-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ff6b00, #ff9e00);
            border-radius: 20px 20px 0 0;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .gym-info h1 {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .gym-info p {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 3px;
            line-height: 1.4;
        }
        .logo-img {
            max-height: 55px;
            max-width: 140px;
            object-fit: contain;
        }

        /* Badge */
        .receipt-type-badge {
            display: inline-block;
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
            padding: 5px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        /* Meta Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .meta-item label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .meta-item span {
            font-size: 13.5px;
            font-weight: 700;
            color: #1e293b;
        }
        .member-id-pill {
            background: #ff6b00;
            color: #fff !important;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Plan Summary Box */
        .plan-summary {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .plan-header {
            background: #f3f4f6;
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .plan-title {
            font-weight: 800;
            font-size: 14px;
            color: #111827;
        }
        .plan-dates {
            font-size: 12px;
            color: #4b5563;
            font-weight: 600;
        }
        .bill-table {
            width: 100%;
            border-collapse: collapse;
        }
        .bill-table tr td {
            padding: 10px 18px;
            font-size: 13px;
        }
        .bill-table tr:not(:last-child) td {
            border-bottom: 1px solid #f3f4f6;
        }
        .bill-table tr.total-row {
            background: #fff7ed;
            font-weight: 800;
            font-size: 14px;
            color: #9a3412;
            border-top: 1.5px solid #fed7aa;
        }
        .amount-val {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
        }

        /* Gate Access Pin Callout */
        .access-callout {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }
        .access-callout .text-wrap h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .access-callout .text-wrap p {
            font-size: 11px;
            color: #cbd5e1;
        }
        .access-pin-box {
            background: rgba(255, 107, 0, 0.2);
            border: 1px solid #ff6b00;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 3px;
            color: #ff9e00;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Footer Notes */
        .footer-note {
            font-size: 10.5px;
            color: #94a3b8;
            line-height: 1.5;
            border-top: 1px dashed #e2e8f0;
            padding-top: 16px;
            text-align: center;
        }

        /* Print Media Styles */
        @media print {
            body { background: #fff; padding: 0; }
            .action-bar { display: none !important; }
            .receipt-card {
                border: none;
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            .receipt-card::before { display: none; }
        }
    </style>
</head>
<body>

    <!-- Action Bar (hidden when printing) -->
    <div class="action-bar">
        <a href="index.php" class="btn btn-close">← Back to Desk</a>
        <div style="display: flex; gap: 8px;">
            <?php 
            $pdf_candidate = "../../uploads/Sudarshan_Fitness_Receipt_" . $receipt['et_id'] . ".pdf";
            if (file_exists(__DIR__ . '/' . $pdf_candidate)): ?>
                <a href="<?php echo htmlspecialchars($pdf_candidate); ?>" download class="btn btn-pdf">📥 Download PDF</a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-print">🖨️ Print Receipt</button>
        </div>
    </div>

    <!-- Receipt Card -->
    <div class="receipt-card">
        
        <div class="header">
            <div class="gym-info">
                <h1><?php echo htmlspecialchars($gym['gym_name']); ?></h1>
                <p><?php echo htmlspecialchars($gym['gym_address'] ?? 'Khamgaon, Dist. Buldhana, Maharashtra - 444303'); ?></p>
                <p>Phone: <?php echo htmlspecialchars($gym['gym_phone'] ?? '+91 94221 00000'); ?> | Email: <?php echo htmlspecialchars($gym['gym_email'] ?? 'info@sudarshanfitness.com'); ?></p>
            </div>
            <div>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="logo-img">
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <span class="receipt-type-badge"><?php echo $receipt_type; ?></span>
            <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Receipt #SF-<?php echo str_pad($receipt['et_id'], 5, '0', STR_PAD_LEFT); ?></span>
        </div>

        <!-- Meta Grid -->
        <div class="meta-grid">
            <div class="meta-item">
                <label>Member Name</label>
                <span><?php echo htmlspecialchars($receipt['username']); ?></span>
            </div>
            <div class="meta-item">
                <label>Membership ID</label>
                <span><span class="member-id-pill">#<?php echo htmlspecialchars($receipt['uid']); ?></span></span>
            </div>
            <div class="meta-item">
                <label>Phone / Mobile</label>
                <span><?php echo htmlspecialchars($receipt['mobile']); ?></span>
            </div>
            <div class="meta-item">
                <label>Transaction Date</label>
                <span><?php echo date('d-M-Y', strtotime($receipt['paid_date'])); ?></span>
            </div>
        </div>

        <!-- Plan Summary Table -->
        <div class="plan-summary">
            <div class="plan-header">
                <span class="plan-title"><?php echo htmlspecialchars($receipt['planName'] ?? 'Gym Membership'); ?></span>
                <span class="plan-dates">
                    Valid: <?php echo date('d-M-Y', strtotime($receipt['paid_date'])); ?> → <strong><?php echo date('d-M-Y', strtotime($receipt['expire'])); ?></strong>
                </span>
            </div>
            <table class="bill-table">
                <tr>
                    <td>Standard Catalog Plan Fee</td>
                    <td class="amount-val">₹<?php echo number_format($plan_price, 2); ?></td>
                </tr>
                <?php if ($discount > 0): ?>
                <tr>
                    <td style="color: #16a34a;">Special Desk Discount Applied</td>
                    <td class="amount-val" style="color: #16a34a;">- ₹<?php echo number_format($discount, 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>Net Payable Amount</strong></td>
                    <td class="amount-val"><strong>₹<?php echo number_format($total_payable, 2); ?></strong></td>
                </tr>
                <tr class="total-row">
                    <td>Amount Paid Received (<?php echo htmlspecialchars($receipt['payment_mode'] ?? 'Cash'); ?>)</td>
                    <td class="amount-val">₹<?php echo number_format($paid_now, 2); ?></td>
                </tr>
                <?php if ($balance > 0): ?>
                <tr>
                    <td style="color: #dc2626; font-weight: 700;">Remaining Balance Due</td>
                    <td class="amount-val" style="color: #dc2626; font-weight: 700;">₹<?php echo number_format($balance, 2); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Entry PIN Callout -->
        <div class="access-callout">
            <div class="text-wrap">
                <h4>🔑 Gate Access Passcode</h4>
                <p>Use this 6-digit code or your fingerprint at the entrance turnstile.</p>
            </div>
            <div class="access-pin-box">
                <?php echo htmlspecialchars($receipt['entry_code'] ?? '123456'); ?>
            </div>
        </div>

        <!-- Staff / Desk Verification -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
            <div style="font-size: 11px; color: var(--text-muted);">
                Received By: <strong><?php echo htmlspecialchars($receipt['received_by'] ?? 'Front Desk Staff'); ?></strong><br>
                Mode: <strong><?php echo htmlspecialchars($receipt['payment_mode'] ?? 'Cash'); ?></strong>
            </div>
            <div style="text-align: right; font-size: 11px; color: var(--text-muted);">
                <div style="border-bottom: 1px solid #94a3b8; width: 140px; margin-bottom: 4px; display: inline-block;"></div><br>
                Authorized Desk Signature
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            <p><strong>Gym Terms:</strong> Membership fees are strictly non-refundable and non-transferable. Clean athletic indoor training shoes and personal towel are mandatory on the workout floor. All equipment damages are 100% recoverable.</p>
            <p style="margin-top: 6px;">Thank you for training with <strong><?php echo htmlspecialchars($gym['gym_name']); ?></strong>!</p>
        </div>

    </div>

    <?php if (isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 400);
        });
    </script>
    <?php endif; ?>

</body>
</html>
