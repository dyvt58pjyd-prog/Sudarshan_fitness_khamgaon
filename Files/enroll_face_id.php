<?php
require 'include/db_conn.php';

$user = isset($_GET['u']) ? mysqli_real_escape_string($con, $_GET['u']) : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($user) || empty($token)) {
    die("Invalid enrollment link.");
}

// Verify the token for either Admin or Member
$display_name = '';
$is_admin = false;

$query = "SELECT securekey, Full_name FROM admin WHERE username='$user' AND role IN ('owner', 'super_admin', 'reception', 'trainer')";
$result = mysqli_query($con, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);
    $expected_token = hash('sha256', $user . $row['securekey']);
    if (!hash_equals($expected_token, $token)) {
        die("Invalid or expired enrollment token.");
    }
    $display_name = $row['Full_name'];
    $is_admin = true;
} else {
    // Check members table
    $query_m = "SELECT userid, username, password FROM users WHERE userid='$user'";
    $result_m = mysqli_query($con, $query_m);
    if ($result_m && mysqli_num_rows($result_m) === 1) {
        $row_m = mysqli_fetch_assoc($result_m);
        $expected_token = hash('sha256', $user . $row_m['password']);
        if (!hash_equals($expected_token, $token)) {
            die("Invalid or expired enrollment token.");
        }
        $display_name = $row_m['username'];
        $is_admin = false;
    } else {
        die("User profile not found or enrollment link is invalid.");
    }
}

// Generate a random challenge
$challenge = bin2hex(random_bytes(32));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($is_admin) {
    $_SESSION['webauthn_enroll_user'] = $user;
    $_SESSION['role'] = 'owner';
} else {
    $_SESSION['user_data'] = $user;
    $_SESSION['role'] = 'member';
}
$_SESSION['webauthn_enroll_challenge'] = $challenge;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face ID Registration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0b0c10;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            padding: 20px;
        }
        .card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .btn {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 30px;
            width: 100%;
            transition: transform 0.1s;
        }
        .btn:active {
            transform: scale(0.95);
        }
        .video-container {
            position: relative;
            width: 100%;
            max-width: 300px;
            height: 300px;
            margin: 20px auto;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #3b82f6;
            display: none;
        }
        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform: scaleX(-1);
        }
    </style>
    <script src="js/face-api/face-api.min.js"></script>
</head>
<body>
    <div class="card">
        <div class="icon" id="statusIcon">📱🔐</div>
        <h2>Setup Face ID</h2>
        <p>Welcome, <strong><?php echo htmlspecialchars($display_name); ?></strong>.</p>
        <p>This will use advanced computer vision to scan your face. Please ensure you are in a well-lit area.</p>

        <div class="video-container" id="videoContainer">
            <video id="video" autoplay muted playsinline></video>
        </div>

        <button class="btn" id="registerBtn" onclick="startRegistration()">
            Start Face Scan
        </button>

        <div class="status" id="statusMsg"></div>
    </div>

    <script>
        const video = document.getElementById('video');
        const videoContainer = document.getElementById('videoContainer');
        const registerBtn = document.getElementById('registerBtn');
        const statusMsg = document.getElementById('statusMsg');
        const statusIcon = document.getElementById('statusIcon');
        
        let modelsLoaded = false;

        async function loadModels() {
            if (modelsLoaded) return;
            statusMsg.innerText = "⚡ Loading Optical Sensors...";
            const localUri = 'js/face-api/models_v2';
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(localUri),
                    faceapi.nets.faceLandmark68TinyNet.loadFromUri(localUri),
                    faceapi.nets.faceRecognitionNet.loadFromUri(localUri)
                ]);
                modelsLoaded = true;
                statusMsg.innerText = "⚡ Sensors Ready! Ready to scan.";
            } catch (err) {
                console.warn("Local face model load error:", err);
                statusMsg.innerText = "Model loading error. Please refresh.";
            }
        }

        loadModels();

        async function startRegistration() {
            registerBtn.style.display = 'none';
            videoContainer.style.display = 'block';
            statusMsg.innerText = "⚡ Opening Camera & Initializing Sensors...";

            if (!modelsLoaded) {
                await loadModels();
            }

            try {
                registerBtn.style.display = 'none';
                videoContainer.style.display = 'block';
                statusMsg.innerText = "Starting camera...";
                
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user", width: { ideal: 640 }, height: { ideal: 480 } }, audio: false });
                video.srcObject = stream;
                
                video.onplay = async () => {
                    const canvas = faceapi.createCanvasFromMedia(video);
                    videoContainer.append(canvas);
                    const displaySize = { width: video.clientWidth, height: video.clientHeight };
                    faceapi.matchDimensions(canvas, displaySize);

                    statusMsg.innerText = "Scanning face... Please look at camera.";
                    statusIcon.innerText = "👀";

                    let scanCount = 0;
                    let bestDetection = null;

                    const scanInterval = setInterval(async () => {
                        let options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.2 });
                        let detections = null;
                        try {
                            detections = await faceapi.detectSingleFace(video, options).withFaceLandmarks(true).withFaceDescriptor();
                        } catch (e) {
                            detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
                        }
                        
                        if (detections) {
                            bestDetection = detections; // Store the best quality detection
                            scanCount++;
                            statusMsg.innerText = `Scanning... (${scanCount}/5)`;
                            
                            // Draw bounding box
                            const resizedDetections = faceapi.resizeResults(detections, displaySize);
                            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                            faceapi.draw.drawDetections(canvas, resizedDetections);
                        } else {
                            statusMsg.innerText = "No face detected. Look directly at the camera.";
                        }

                        if (scanCount >= 5) {
                            clearInterval(scanInterval);
                            statusMsg.innerText = "Face captured successfully! Saving...";
                            
                            // Stop camera
                            stream.getTracks().forEach(track => track.stop());
                            videoContainer.style.display = 'none';
                            
                            // Send descriptor to server
                            const descriptorArray = Array.from(bestDetection.descriptor);
                            saveToDatabase(descriptorArray);
                        }
                    }, 500); // scan every 500ms
                };

            } catch (err) {
                console.error(err);
                statusMsg.innerText = "Camera Error: " + err.message;
                registerBtn.style.display = 'block';
            }
        }

        async function saveToDatabase(descriptorArray) {
            try {
                const res = await fetch('api/enroll_face.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        descriptor: descriptorArray,
                        u: '<?php echo addslashes($user); ?>',
                        token: '<?php echo addslashes($token); ?>'
                    })
                });

                const result = await res.json();
                
                if (result.success) {
                    statusIcon.innerText = "✅";
                    statusMsg.innerText = "Face ID Registration Complete!";
                    statusMsg.style.color = "#10b981";
                    
                    setTimeout(() => {
                        window.location.href = 'index.php'; // redirect to login
                    }, 2000);
                } else {
                    throw new Error(result.error);
                }
            } catch (err) {
                statusIcon.innerText = "❌";
                statusMsg.innerText = "Registration Failed: " + err.message;
                statusMsg.style.color = "#ef4444";
                registerBtn.style.display = 'block';
                registerBtn.innerText = 'Try Again';
            }
        }
    </script>
</body>
</html>
