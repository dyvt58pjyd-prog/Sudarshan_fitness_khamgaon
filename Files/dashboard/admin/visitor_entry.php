<?php
require '../../include/db_conn.php';
page_protect();

if (!in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}
$gym = get_gym_details($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Visitor Entry</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .page-container .sidebar-menu #main-menu li#visitor_entry > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .portal-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 10px !important;
            width: 100%;
            margin-bottom: 15px;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }
        .btn-submit {
            background: var(--accent-primary);
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 60px; max-width: 180px;" />
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
                        <li>Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>New Visitor Entry</h2>
            <hr />

            <div class="portal-card" style="max-width: 800px; margin: 0 auto;">
                <form action="submit_visitor.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <label style="color: var(--text-main); font-weight: 600;">Visitor Name *</label>
                            <input type="text" name="v_name" class="form-control-premium" required placeholder="Enter full name">
                            
                            <label style="color: var(--text-main); font-weight: 600;">Mobile Number *</label>
                            <input type="number" name="mobile" class="form-control-premium" required placeholder="10-digit mobile number">
                            
                            <label style="color: var(--text-main); font-weight: 600;">Address</label>
                            <textarea name="address" class="form-control-premium" rows="3" placeholder="Enter complete address"></textarea>
                            
                            <label style="color: var(--text-main); font-weight: 600;">Notes (Optional)</label>
                            <textarea name="notes" class="form-control-premium" rows="2" placeholder="Any inquiry notes or follow-up details"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label style="color: var(--text-main); font-weight: 600;"><i class="entypo-camera"></i> Photo Capture / Upload *</label>
                            <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border); text-align: center;">
                                <video id="webcam-video" autoplay playsinline style="width: 100%; max-width: 250px; border-radius: 12px; display: none; margin: 0 auto; border: 2px solid var(--accent-primary);"></video>
                                <img id="photo-preview" style="width: 100%; max-width: 250px; border-radius: 12px; display: none; margin: 0 auto; border: 2px solid var(--success);" />
                                <canvas id="photo-canvas" style="display: none;"></canvas>
                                
                                <input type="hidden" name="captured_photo" id="captured_photo" value="">
                                
                                <div style="margin-top: 15px;">
                                    <button type="button" id="start-camera-btn" class="btn btn-primary" style="width: 100%; font-weight: bold; margin-bottom: 10px;">
                                        <i class="entypo-camera"></i> Start Camera
                                    </button>
                                    <button type="button" id="capture-btn" class="btn btn-success" style="width: 100%; font-weight: bold; display: none;">
                                        <i class="entypo-record"></i> Capture Photo
                                    </button>
                                    <button type="button" id="switch-camera-btn" class="btn btn-info" style="width: 100%; font-weight: bold; display: none; margin-top: 10px;">
                                        <i class="entypo-arrows-ccw"></i> Switch Camera
                                    </button>
                                    <button type="button" id="retake-btn" class="btn btn-warning" style="width: 100%; font-weight: bold; display: none; margin-top: 10px;">
                                        <i class="entypo-ccw"></i> Retake Photo
                                    </button>
                                </div>
                                
                                <div style="margin-top: 15px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 15px; text-align: left;">
                                    <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #fff;">Or Upload Photo File</label>
                                    <input type="file" name="upload_photo" id="upload_photo" accept="image/*" class="form-control-premium" style="padding: 6px !important; margin: 0;" onchange="previewUploadedPhoto(this)">
                                </div>
                                
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 15px; text-align: center;">
                                    * Capturing or uploading a face photo of the visitor is mandatory for security and identification.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" name="submit_visitor" class="btn-submit">Save Visitor Record</button>
                    </div>
                </form>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script>
        // Photo Capture Logic
        const video = document.getElementById('webcam-video');
        const canvas = document.getElementById('photo-canvas');
        const photoPreview = document.getElementById('photo-preview');
        const capturedInput = document.getElementById('captured_photo');
        
        const startBtn = document.getElementById('start-camera-btn');
        const captureBtn = document.getElementById('capture-btn');
        const switchCamBtn = document.getElementById('switch-camera-btn');
        const retakeBtn = document.getElementById('retake-btn');
        const uploadInput = document.getElementById('upload_photo');

        let stream = null;
        let currentFacingMode = "user";

        async function startCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: currentFacingMode }, 
                    audio: false 
                });
                video.srcObject = stream;
                video.style.display = 'block';
                photoPreview.style.display = 'none';
                
                startBtn.style.display = 'none';
                captureBtn.style.display = 'inline-block';
                switchCamBtn.style.display = 'inline-block';
                retakeBtn.style.display = 'none';
                uploadInput.value = ''; // Clear file selection if using camera
            } catch (err) {
                alert("Camera access denied or unavailable. Please upload a file instead.");
                console.error(err);
            }
        }

        startBtn.addEventListener('click', startCamera);

        switchCamBtn.addEventListener('click', () => {
            currentFacingMode = (currentFacingMode === "user") ? "environment" : "user";
            startCamera();
        });

        captureBtn.addEventListener('click', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
            photoPreview.src = dataUrl;
            capturedInput.value = dataUrl; // Store base64 in hidden input
            
            // Stop camera stream
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            video.style.display = 'none';
            photoPreview.style.display = 'block';
            captureBtn.style.display = 'none';
            switchCamBtn.style.display = 'none';
            retakeBtn.style.display = 'inline-block';
        });

        retakeBtn.addEventListener('click', () => {
            capturedInput.value = '';
            startCamera(); // Restart camera
        });

        function previewUploadedPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    photoPreview.style.display = 'block';
                    video.style.display = 'none';
                    capturedInput.value = ''; // clear camera capture if file uploaded
                    
                    // Stop camera if running
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                    startBtn.style.display = 'inline-block';
                    captureBtn.style.display = 'none';
                    switchCamBtn.style.display = 'none';
                    retakeBtn.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Prevent form submission without a photo
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!capturedInput.value && !uploadInput.value) {
                e.preventDefault();
                alert("Please capture a live photo or upload a photo file before saving the visitor record.");
            }
        });
    </script>
</body>
</html>
