<?php
session_start();
if (!isset($_SESSION['member_uid'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI Nutrition Scanner</title>
    <meta name="theme-color" content="#0f172a">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; padding-bottom: 80px; background-image: radial-gradient(circle at 0% 0%, rgba(16, 185, 129, 0.15) 0%, transparent 60%); }
        .header { padding: 25px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .header-title { font-size: 20px; font-weight: 800; color: #fff; }
        .header-title span { color: #10b981; }
        
        .content { padding: 20px; text-align: center; }
        .title-box h1 { font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 5px; }
        .title-box p { color: #94a3b8; font-size: 13px; margin-bottom: 25px; }

        .scanner-container { width: 100%; max-width: 300px; height: 300px; margin: 0 auto 30px; border-radius: 20px; border: 2px dashed rgba(16, 185, 129, 0.5); position: relative; overflow: hidden; display: flex; justify-content: center; align-items: center; background: rgba(0,0,0,0.2); }
        .upload-btn { background: #10b981; color: #000; padding: 12px 24px; border-radius: 30px; font-weight: 800; font-size: 14px; text-transform: uppercase; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); transition: transform 0.2s; }
        .upload-btn:active { transform: scale(0.95); }
        input[type="file"] { display: none; }
        
        #preview-img { width: 100%; height: 100%; object-fit: cover; display: none; border-radius: 18px; }
        
        .laser { position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #10b981; box-shadow: 0 0 15px 5px rgba(16, 185, 129, 0.6); display: none; z-index: 10; animation: scan 2s infinite linear; }
        @keyframes scan {
            0% { top: 0; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        
        .scanning-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(16, 185, 129, 0.1); display: none; z-index: 5; }
        
        .results-box { background: linear-gradient(145deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.8) 100%); backdrop-filter: blur(20px); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 16px; padding: 20px; margin-top: 20px; display: none; animation: popIn 0.5s ease; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .macro-row { display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .macro-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .macro-label { color: #94a3b8; font-size: 14px; font-weight: 600; }
        .macro-val { color: #fff; font-size: 16px; font-weight: 800; }
        .macro-val.cal { color: #f59e0b; font-size: 20px; }
        .macro-val.pro { color: #3b82f6; }
        .macro-val.carb { color: #10b981; }
        .macro-val.fat { color: #ef4444; }

        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 15px 10px; border-top: 1px solid rgba(255,255,255,0.05); padding-bottom: calc(15px + env(safe-area-inset-bottom)); }
        .nav-item { color: #64748b; text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase; display: flex; flex-direction: column; align-items: center; gap: 6px; }
        .nav-item.active { color: #10b981; }
        .nav-icon { font-size: 22px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">Nutrition <span>Vision</span></div>
    </div>

    <div class="content">
        <div class="title-box">
            <h1>📸 Scan Your Meal</h1>
            <p>Our AI will instantly calculate your macros.</p>
        </div>

        <div class="scanner-container" id="scanner">
            <img id="preview-img" src="" alt="Meal Preview">
            <div class="laser" id="laser"></div>
            <div class="scanning-overlay" id="overlay"></div>
            
            <label for="cameraInput" class="upload-btn" id="uploadBtn">
                📷 Open Camera
            </label>
            <input type="file" id="cameraInput" accept="image/*" capture="environment">
        </div>

        <div id="statusText" style="color: #10b981; font-weight: 700; margin-bottom: 20px; display: none;">Analyzing ingredients with AI...</div>

        <div class="results-box" id="results">
            <h3 style="color: #fff; margin-bottom: 15px; text-transform: uppercase; font-size: 14px; letter-spacing: 1px;">AI Analysis Complete</h3>
            
            <div class="macro-row">
                <span class="macro-label">Estimated Calories</span>
                <span class="macro-val cal" id="res-cal">0 kcal</span>
            </div>
            <div class="macro-row">
                <span class="macro-label">Protein</span>
                <span class="macro-val pro" id="res-pro">0g</span>
            </div>
            <div class="macro-row">
                <span class="macro-label">Carbs</span>
                <span class="macro-val carb" id="res-carb">0g</span>
            </div>
            <div class="macro-row">
                <span class="macro-label">Fats</span>
                <span class="macro-val fat" id="res-fat">0g</span>
            </div>
            
            <button class="upload-btn" style="margin-top: 15px; width: 100%; background: #3b82f6; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);" onclick="alert('Macros logged to your daily tracker!')">✅ LOG MEAL</button>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="dashboard.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="leaderboard.php" class="nav-item">
            <span class="nav-icon">🏆</span>
            <span>Rank</span>
        </a>
        <a href="ai_scanner.php" class="nav-item active">
            <span class="nav-icon">📸</span>
            <span>Food AI</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            <span>Me</span>
        </a>
    </div>

    <script>
        document.getElementById('cameraInput').addEventListener('change', function(e) {
            if(e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('preview-img');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    
                    document.getElementById('uploadBtn').style.display = 'none';
                    document.getElementById('laser').style.display = 'block';
                    document.getElementById('overlay').style.display = 'block';
                    
                    const statusText = document.getElementById('statusText');
                    statusText.style.display = 'block';
                    statusText.innerText = "Analyzing ingredients with AI...";
                    
                    document.getElementById('results').style.display = 'none';

                    // Simulate AI Processing Delay
                    setTimeout(() => {
                        document.getElementById('laser').style.display = 'none';
                        document.getElementById('overlay').style.display = 'none';
                        statusText.innerText = "Analysis Successful!";
                        
                        // Mock random generation based on typical meals
                        const cals = Math.floor(Math.random() * (800 - 300 + 1) + 300);
                        const pro = Math.floor(Math.random() * (60 - 15 + 1) + 15);
                        const carb = Math.floor(Math.random() * (80 - 20 + 1) + 20);
                        const fat = Math.floor(Math.random() * (30 - 5 + 1) + 5);
                        
                        document.getElementById('res-cal').innerText = cals + ' kcal';
                        document.getElementById('res-pro').innerText = pro + 'g';
                        document.getElementById('res-carb').innerText = carb + 'g';
                        document.getElementById('res-fat').innerText = fat + 'g';
                        
                        document.getElementById('results').style.display = 'block';
                        
                    }, 3000);
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>
