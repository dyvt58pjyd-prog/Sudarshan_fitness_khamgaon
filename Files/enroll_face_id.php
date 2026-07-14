<?php
require 'include/db_conn.php';

$user = isset($_GET['u']) ? mysqli_real_escape_string($con, $_GET['u']) : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($user) || empty($token)) {
    die("Invalid enrollment link.");
}

// Verify the token
$query = "SELECT securekey, Full_name FROM admin WHERE username='$user' AND role IN ('owner', 'super_admin', 'reception', 'trainer')";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) !== 1) {
    die("Owner not found.");
}

$row = mysqli_fetch_assoc($result);
$expected_token = hash('sha256', $user . $row['securekey']);

if (!hash_equals($expected_token, $token)) {
    die("Invalid or expired token.");
}

// Generate a random challenge
$challenge = bin2hex(random_bytes(32));

// Save challenge to session to verify later in the API
session_start();
$_SESSION['webauthn_enroll_user'] = $user;
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
        <p>Welcome, <strong><?php echo htmlspecialchars($row['Full_name']); ?></strong>.</p>
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
            statusMsg.innerText = "Loading AI Models...";
            try {
                await Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri('js/face-api/models_v2'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('js/face-api/models_v2'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('js/face-api/models_v2')
                ]);
                modelsLoaded = true;
                statusMsg.innerText = "AI Models Loaded. Ready to scan.";
            } catch (err) {
                console.error(err);
                statusMsg.innerText = "Error loading AI models: " + err.message;
            }
        }

        loadModels();

        async function startRegistration() {
            if (!modelsLoaded) {
                alert("Please wait for AI models to finish loading.");
                return;
            }

            try {
                registerBtn.style.display = 'none';
                videoContainer.style.display = 'block';
                statusMsg.innerText = "Starting camera...";
                
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" }, audio: false });
                video.srcObject = stream;
                
                video.onplay = async () => {
                    const canvas = faceapi.createCanvasFromMedia(video);
                    videoContainer.append(canvas);
                    const displaySize = { width: video.clientWidth, height: video.clientHeight };
                    faceapi.matchDimensions(canvas, displaySize);

                    statusMsg.innerText = "Scanning face... Please hold still and look directly at the camera.";
                    statusIcon.innerText = "👀";

                    let scanCount = 0;
                    let bestDetection = null;

                    const scanInterval = setInterval(async () => {
                        const detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
                        
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
                const res = await fetch('api/webauthn_register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ descriptor: descriptorArray })
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
