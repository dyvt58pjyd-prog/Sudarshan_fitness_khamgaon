<?php
require '../../include/db_conn.php';
page_protect();

$gym = get_gym_details($con);
$enquiry_url = "https://sudarshanfitness.de/guest_enquiry.php";
$qr_img = "https://chart.googleapis.com/chart?chs=350x350&cht=qr&chl=" . urlencode($enquiry_url);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Visitor QR Poster</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: #0b0f19; color: #fff; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px; }
        
        .poster {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border: 4px solid #ff6b00;
            border-radius: 32px;
            padding: 50px 40px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
            position: relative;
        }

        .logo { max-height: 80px; margin-bottom: 15px; }
        .gym-name { font-size: 28px; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: -0.5px; }
        .tagline { color: #ff6b00; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 30px; }

        .qr-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 24px;
            display: inline-block;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            margin-bottom: 25px;
        }

        .qr-img { width: 260px; height: 260px; display: block; }
        
        .instruction-heading { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .instruction-desc { font-size: 14px; color: #94a3b8; line-height: 1.6; margin-bottom: 25px; }
        
        .print-btn {
            background: linear-gradient(135deg, #ff6b00, #ff8800);
            color: #fff;
            border: none;
            padding: 14px 30px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(255,107,0,0.4);
        }

        @media print {
            body { background: #fff; color: #000; padding: 0; }
            .poster { border-color: #000; box-shadow: none; background: #fff; color: #000; }
            .gym-name, .instruction-heading { color: #000; }
            .instruction-desc { color: #333; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

    <div class="poster">
        <img src="../../<?php echo htmlspecialchars($gym['gym_logo']); ?>" class="logo" alt="Gym Logo"><br>
        <div class="gym-name"><?php echo htmlspecialchars($gym['gym_name']); ?></div>
        <div class="tagline">🔥 Visitor Gym Tour &amp; Self-Registration Pass</div>

        <div class="qr-box">
            <img src="<?php echo $qr_img; ?>" class="qr-img" alt="Visitor Registration QR Code">
            <div style="font-size: 12px; font-weight: 900; color: #0f172a; margin-top: 10px; text-transform: uppercase;">SCAN WITH PHONE CAMERA</div>
        </div>

        <div class="instruction-heading">📱 Welcome to Sudarshan Fitness!</div>
        <div class="instruction-desc">
            Scan this QR code with your mobile camera to fill out your visitor information form before your guided gym tour.
        </div>

        <button onclick="window.print()" class="print-btn">🖨️ Print Reception Poster / Standee</button>
    </div>

</body>
</html>
