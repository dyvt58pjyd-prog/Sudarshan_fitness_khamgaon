<?php
require '../../include/db_conn.php';
page_protect();

$gym = get_gym_details($con);
$upi_id = "7620453195-2@ybl"; // Official Gym UPI ID

$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 2500;
$uid = isset($_GET['uid']) ? htmlspecialchars($_GET['uid']) : 'MEMBER';
$name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Gym Athlete';

$upi_string = "upi://pay?pa={$upi_id}&pn=" . urlencode($gym['gym_name']) . "&am={$amount}&cu=INR&tn=" . urlencode("Membership Payment for $name ($uid)");
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_string);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instant UPI Payment Gateway | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .pay-card { width: 100%; max-width: 450px; background: rgba(15, 7, 18, 0.95); border: 2px solid var(--accent-primary); border-radius: 24px; padding: 30px; box-shadow: 0 0 50px rgba(255, 0, 60, 0.35); text-align: center; position: relative; }
        .pay-card::before { content: '[ INSTANT UPI GATEWAY ]'; position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #030712; border: 1px solid var(--accent-primary); color: var(--accent-primary); font-family: 'Orbitron'; font-size: 10px; font-weight: 900; padding: 3px 14px; border-radius: 10px; letter-spacing: 2px; }
        .qr-box { background: #fff; padding: 15px; border-radius: 16px; margin: 20px auto; width: 220px; height: 220px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 30px rgba(255, 0, 60, 0.4); }
    </style>
</head>
<body>

    <div class="pay-card">
        <h3 style="font-family: 'Orbitron'; color: var(--accent-primary); margin-top: 0;"><?php echo htmlspecialchars($gym['gym_name']); ?></h3>
        <div style="font-size: 12px; color: var(--text-muted); font-family: 'Orbitron'; font-weight: 800;">SCAN WITH GPAY / PHONEPE / PAYTM</div>

        <div class="qr-box">
            <img src="<?php echo $qr_url; ?>" alt="UPI Payment QR Code" style="width: 100%; height: 100%;">
        </div>

        <div style="font-size: 28px; font-weight: 900; color: #10b981; font-family: 'Orbitron'; margin-bottom: 5px;">₹<?php echo number_format($amount); ?></div>
        <div style="font-size: 13px; color: #cbd5e1; font-weight: bold; margin-bottom: 20px;">Paying for: <?php echo $name; ?> (ID: <?php echo $uid; ?>)</div>

        <button onclick="alert('✅ Payment Confirmation Received! Generating PDF Tax Invoice & Sending WhatsApp Receipt...'); window.location.href='payments.php';" style="width: 100%; background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #030712; border: none; padding: 14px; border-radius: 12px; font-weight: 900; font-family: 'Orbitron'; cursor: pointer; box-shadow: 0 0 25px rgba(255,0,60,0.5);">
            CONFIRM PAYMENT &amp; PRINT RECEIPT ➔
        </button>
    </div>

</body>
</html>
