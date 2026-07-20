<?php
require '../../include/db_conn.php';
page_protect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | QR Attendance Scanner</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <script src="../../js/html5-qrcode.min.js" type="text/javascript"></script>
    
    <style>
        body {
            background: #0f172a;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }
        .scanner-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 107, 0, 0.3);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            max-width: 650px;
            margin: 40px auto;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .scanner-title {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #ff6b00 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        #reader {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 25px;
            background: #000;
            border: 4px solid rgba(255, 107, 0, 0.2);
            box-shadow: 0 10px 30px rgba(255, 107, 0, 0.1);
            position: relative;
        }
        #reader video {
            object-fit: cover;
            width: 100%;
        }
        .result-box {
            margin-top: 25px;
            padding: 20px;
            border-radius: 12px;
            display: none;
            animation: fadeIn 0.4s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success { background: rgba(16, 185, 129, 0.15); border: 2px solid #10b981; color: #10b981; box-shadow: 0 0 20px rgba(16, 185, 129, 0.2); }
        .error { background: rgba(239, 68, 68, 0.15); border: 2px solid #ef4444; color: #ef4444; box-shadow: 0 0 20px rgba(239, 68, 68, 0.2); }
        .member-info { display: flex; align-items: center; justify-content: center; gap: 20px; margin-top: 15px; }
        .member-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ff6b00; box-shadow: 0 5px 15px rgba(255, 107, 0, 0.3); }
        #member-name { font-size: 22px; font-weight: 700; margin: 0; color: #fff; }
        #member-time { color: #94a3b8; font-size: 14px; margin-top: 5px; font-weight: 500; }
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
                        <li>Welcome <?php echo $_SESSION['full_name']; ?></li>                            
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>QR Code Scanner - Attendance</h3>
            </div>
            <hr/>

            <div class="scanner-card">
                <div class="scanner-title">Sudarshan Scanner</div>
                <div id="reader"></div>
                <div id="result" class="result-box">
                    <h4 id="result-title" style="font-weight: 800; font-size: 20px; margin-bottom: 5px;"></h4>
                    <div class="member-info" id="member-info-container" style="display:none;">
                        <img id="member-photo" class="member-img" src="" alt="Member">
                        <div style="text-align: left;">
                            <h3 id="member-name"></h3>
                            <div id="member-time"></div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                let isProcessing = false;
                
                function onScanSuccess(decodedText, decodedResult) {
                    if (isProcessing) return;
                    isProcessing = true;
                    
                    const resultBox = document.getElementById('result');
                    const memberInfoContainer = document.getElementById('member-info-container');
                    
                    // Call API to log attendance
                    fetch('log_attendance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'uid=' + encodeURIComponent(decodedText)
                    })
                    .then(response => response.json())
                    .then(data => {
                        resultBox.style.display = 'block';
                        resultBox.className = 'result-box';
                        
                        if (data.status === 'success') {
                            resultBox.classList.add('success');
                            document.getElementById('result-title').innerText = data.type === 'entry' ? '✅ Check-In Successful' : '✅ Check-Out Successful';
                            
                            document.getElementById('member-name').innerText = data.username;
                            document.getElementById('member-photo').src = data.photo || '../../images/logo.png';
                            document.getElementById('member-time').innerText = data.time + " | " + data.date;
                            memberInfoContainer.style.display = 'flex';
                            
                            // Speak message (wrap in try-catch for iOS restrictions)
                            try {
                                let msg = new SpeechSynthesisUtterance(data.username + (data.type === 'entry' ? ' checked in' : ' checked out'));
                                window.speechSynthesis.speak(msg);
                            } catch(e) { console.log('Speech synthesis failed', e); }
                            
                        } else if (data.status === 'expired') {
                            resultBox.classList.add('error');
                            document.getElementById('result-title').innerText = '❌ Membership Expired';
                            
                            document.getElementById('member-name').innerText = data.username;
                            document.getElementById('member-photo').src = data.photo || '../../images/logo.png';
                            document.getElementById('member-time').innerText = "Expired on: " + data.expire_date;
                            memberInfoContainer.style.display = 'flex';
                            
                            try {
                                let msg = new SpeechSynthesisUtterance('Membership expired for ' + data.username);
                                window.speechSynthesis.speak(msg);
                            } catch(e) { console.log('Speech synthesis failed', e); }
                            
                        } else {
                            resultBox.classList.add('error');
                            document.getElementById('result-title').innerText = '❌ Error: ' + data.message;
                            memberInfoContainer.style.display = 'none';
                        }
                        
                        setTimeout(() => {
                            resultBox.style.display = 'none';
                            isProcessing = false;
                        }, 4000);
                    })
                    .catch(err => {
                        console.error(err);
                        isProcessing = false;
                    });
                }

                function onScanFailure(error) {
                    // handle scan failure quietly
                }

                try {
                    // Removed qrbox to allow full frame scanning. This significantly improves reading angles!
                    let html5QrcodeScanner = new Html5QrcodeScanner(
                        "reader",
                        { fps: 15 },
                        false);
                    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                } catch(err) {
                    document.getElementById('reader').innerHTML = '<div style="color:red; padding:20px;">Camera initialization failed. Please ensure you are using HTTPS and have granted camera permissions. Error: ' + err + '</div>';
                }
            </script>
        </div>
    </div>
</body>
</html>
