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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js" integrity="sha512-r6rDA7W6ZeQhvl8S7yRVQUKVHdexq+Gv7Z73y5v0Y632xNkVx55Qdb2W8Yt4mU41X2n/L+E1uC3t/V4eL6U/aA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <style>
        .scanner-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 107, 0, 0.3);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            max-width: 600px;
            margin: 30px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        #reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            background: #000;
        }
        #reader video {
            object-fit: cover;
        }
        .result-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        .success { background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #2ecc71; }
        .error { background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #e74c3c; }
        .member-info { display: flex; align-items: center; justify-content: center; gap: 15px; margin-top: 10px;}
        .member-img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #ff6b00;}
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
                <h4 style="color: #fff; margin-bottom: 20px;">Scan Member QR Code</h4>
                <div id="reader"></div>
                <div id="result" class="result-box">
                    <h4 id="result-title"></h4>
                    <div class="member-info" id="member-info-container" style="display:none;">
                        <img id="member-photo" class="member-img" src="" alt="Member">
                        <div style="text-align: left;">
                            <h3 id="member-name" style="margin: 0; color: #fff;"></h3>
                            <div id="member-time" style="color: #bbb; font-size: 12px; margin-top: 5px;"></div>
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
                    let html5QrcodeScanner = new Html5QrcodeScanner(
                        "reader",
                        { fps: 10, qrbox: {width: 250, height: 250} },
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
