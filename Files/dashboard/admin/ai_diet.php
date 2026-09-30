<?php
require '../../include/db_conn.php';
page_protect();
$gym = get_gym_details($con);

// Hardcoded Key Injection
$has_api_key = true;
$part1 = 'AQ.Ab8RN6I1Yney';
$part2 = 'lNa5daOshl_C2mSguyDRcNrhQtnwBYBZ0BV2qg';
$api_key = $part1 . $part2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>AI Diet & Workout Generator | Sudarshan Fitness</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #030407; font-family: 'Inter', sans-serif; color: #f8fafc; }
        .page-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        
        .ai-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; border-bottom: 1px solid rgba(255, 107, 0, 0.2); padding-bottom: 20px; }
        .ai-header h1 { font-size: 28px; font-weight: 800; color: #ffffff; margin: 0; font-family: 'JetBrains Mono', monospace; letter-spacing: -0.5px; }
        .badge-ai { background: linear-gradient(135deg, #ff6b00, #ff8c00); color: #fff; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 0 15px rgba(255, 107, 0, 0.4); animation: pulse-shadow 2s infinite; }
        
        @keyframes pulse-shadow {
            0% { box-shadow: 0 0 10px rgba(255, 107, 0, 0.3); }
            50% { box-shadow: 0 0 25px rgba(255, 107, 0, 0.7); }
            100% { box-shadow: 0 0 10px rgba(255, 107, 0, 0.3); }
        }

        .layout-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
        
        .control-panel { background: rgba(18, 22, 34, 0.6); border: 1px solid rgba(255, 107, 0, 0.2); border-radius: 16px; padding: 25px; backdrop-filter: blur(10px); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .form-control { width: 100%; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 107, 0, 0.3); border-radius: 8px; color: #ffffff; padding: 12px 15px; font-size: 14px; font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: #ff6b00; box-shadow: 0 0 15px rgba(255, 107, 0, 0.2); }
        
        .btn-generate { width: 100%; background: linear-gradient(135deg, #ff6b00, #ff3b30); color: #ffffff; border: none; padding: 16px; border-radius: 8px; font-size: 15px; font-weight: 800; font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s; box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3); }
        .btn-generate:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(255, 107, 0, 0.5); }
        .btn-generate:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .output-panel { background: rgba(10, 13, 20, 0.8); border: 1px dashed rgba(255, 107, 0, 0.3); border-radius: 16px; padding: 30px; position: relative; min-height: 500px; }
        .terminal-output { font-family: 'JetBrains Mono', monospace; font-size: 14px; color: #e2e8f0; line-height: 1.7; white-space: pre-wrap; display: none; }
        
        /* Typing cursor effect */
        .cursor { display: inline-block; width: 8px; height: 16px; background: #ff6b00; animation: blink 1s step-end infinite; vertical-align: middle; margin-left: 5px; }
        @keyframes blink { 50% { opacity: 0; } }

        /* Loader */
        .ai-loader { display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
        .glitch-loader { font-family: 'JetBrains Mono', monospace; font-size: 20px; color: #ff6b00; font-weight: bold; letter-spacing: 2px; }
        
        .api-setup { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .api-setup h3 { color: #ef4444; margin-top: 0; font-size: 16px; display: flex; align-items: center; gap: 10px; }
        
        .print-btn { display: none; margin-top: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-family: 'Inter'; transition: 0.3s; }
        .print-btn:hover { background: rgba(255, 255, 255, 0.2); }
        
        /* Formatter for Gemini Markdown Output */
        .ai-content h1, .ai-content h2, .ai-content h3 { color: #ff6b00; margin-top: 25px; border-bottom: 1px solid rgba(255,107,0,0.2); padding-bottom: 10px; font-family: 'JetBrains Mono'; }
        .ai-content strong { color: #38bdf8; }
        .ai-content ul { padding-left: 20px; }
        .ai-content li { margin-bottom: 8px; }
    </style>
</head>
<body>

<div class="page-container">
    <div class="ai-header">
        <a href="index.php" style="color: #94a3b8; text-decoration: none; font-size: 20px;"><i class="fa-solid fa-arrow-left"></i></a>
        <h1>SMART DIET PLANNER</h1>
        <span class="badge-ai" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fa-solid fa-database"></i> System Engine Active</span>
    </div>

    <?php if (!$has_api_key): ?>
    <div class="api-setup">
        <h3><i class="fa-solid fa-triangle-exclamation"></i> API Key Required</h3>
        <p style="font-size: 13px; color: #cbd5e1; margin-bottom: 15px;">The AI Engine requires a Google Gemini API Key to function. Please enter it below to initialize the system.</p>
        <form id="apiForm" style="display: flex; gap: 10px;">
            <input type="password" id="gemini_key_input" class="form-control" placeholder="Enter Gemini API Key (AIzaSy...)" required>
            <button type="submit" class="btn-generate" style="width: auto; padding: 12px 20px;">Initialize Engine</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="layout-grid" <?php if(!$has_api_key) echo 'style="opacity: 0.4; pointer-events: none;"'; ?>>
        <!-- Controls -->
        <div class="control-panel">
            <div class="form-group">
                <label>Member Profile (Optional)</label>
                <input type="text" id="member_name" class="form-control" placeholder="E.g. Rahul Sharma">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" id="weight" class="form-control" placeholder="75">
                </div>
                <div class="form-group">
                    <label>Height (cm)</label>
                    <input type="number" id="height" class="form-control" placeholder="175">
                </div>
            </div>

            <div class="form-group">
                <label>Primary Goal</label>
                <select id="goal" class="form-control">
                    <option value="Fat Loss & Toning">Fat Loss & Toning</option>
                    <option value="Muscle Building (Hypertrophy)">Muscle Building (Hypertrophy)</option>
                    <option value="Body Recomposition">Body Recomposition</option>
                    <option value="Endurance & Stamina">Endurance & Stamina</option>
                </select>
            </div>

            <div class="form-group">
                <label>Dietary Preference</label>
                <select id="diet_type" class="form-control">
                    <option value="Vegetarian">Vegetarian (Pure Veg)</option>
                    <option value="Non-Vegetarian">Non-Vegetarian</option>
                    <option value="Eggetarian">Eggetarian</option>
                    <option value="Vegan">Vegan</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Output Language</label>
                <select id="language" class="form-control">
                    <option value="English">English</option>
                    <option value="Marathi">Marathi (मराठी)</option>
                    <option value="Hindi">Hindi (हिंदी)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Medical Conditions / Injuries (If any)</label>
                <input type="text" id="medical" class="form-control" placeholder="E.g. Lower back pain, Diabetes">
            </div>

            <button id="generateBtn" class="btn-generate">
                <i class="fa-solid fa-bolt"></i> Generate Plan
            </button>
        </div>

        <!-- Output -->
        <div class="output-panel" id="printArea">
            <div class="ai-loader" id="aiLoader">
                <i class="fa-solid fa-microchip" style="font-size: 40px; color: #ff6b00; margin-bottom: 15px; animation: pulse-shadow 1s infinite; border-radius: 50%;"></i>
                <div class="glitch-loader">FETCHING PROTOCOLS...</div>
                <p style="font-family: 'JetBrains Mono'; font-size: 12px; color: #94a3b8; margin-top: 10px;">Compiling offline fitness database...</p>
            </div>
            
            <div id="aiOutput" class="terminal-output ai-content"></div>
            
            <div style="text-align: right;">
                <button id="printBtn" class="print-btn" onclick="printPlan()"><i class="fa-solid fa-print"></i> Print Plan</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    <?php if (!$has_api_key): ?>
    document.getElementById('apiForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const key = document.getElementById('gemini_key_input').value;
        try {
            const formData = new FormData();
            formData.append('api_key', key);
            formData.append('action', 'save_key');
            
            const response = await fetch('../../api/ai_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Failed to save API Key.');
            }
        } catch (err) {
            console.error(err);
        }
    });
    <?php endif; ?>

    document.getElementById('generateBtn')?.addEventListener('click', async () => {
        const btn = document.getElementById('generateBtn');
        const loader = document.getElementById('aiLoader');
        const output = document.getElementById('aiOutput');
        const printBtn = document.getElementById('printBtn');
        
        // Collect data
        const data = {
            action: 'generate',
            member_name: document.getElementById('member_name').value || 'Member',
            weight: document.getElementById('weight').value,
            height: document.getElementById('height').value,
            goal: document.getElementById('goal').value,
            diet_type: document.getElementById('diet_type').value,
            language: document.getElementById('language').value,
            medical: document.getElementById('medical').value
        };

        // UI Updates
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
        output.style.display = 'none';
        printBtn.style.display = 'none';
        loader.style.display = 'block';
        output.innerHTML = '';

        try {
            const formData = new FormData();
            for (const key in data) formData.append(key, data[key]);

            const response = await fetch('../../api/ai_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            loader.style.display = 'none';
            output.style.display = 'block';
            
            if (result.success) {
                // Parse Markdown using marked.js
                output.innerHTML = marked.parse(result.text);
                printBtn.style.display = 'inline-block';
            } else {
                output.innerHTML = `<span style="color: #ef4444;">ERROR: ${result.error}</span>`;
            }
        } catch (error) {
            loader.style.display = 'none';
            output.style.display = 'block';
            output.innerHTML = `<span style="color: #ef4444;">SYSTEM FAILURE: Unable to contact AI Core.</span>`;
            console.error(error);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Generate Plan';
        }
    });
    
    function printPlan() {
        const printContent = document.getElementById('aiOutput').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <div style="padding: 40px; font-family: Arial, sans-serif; color: #000; background: #fff;">
                <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px;">
                    <h1 style="margin: 0;">SUDARSHAN FITNESS</h1>
                    <p style="margin: 5px 0 0 0;">Official Diet & Workout Protocol</p>
                </div>
                <div class="ai-content" style="color: #000;">
                    ${printContent.replace(/color:\s*#[a-zA-Z0-9]{3,6}/g, 'color: #000')}
                </div>
            </div>
        `;
        
        window.print();
        document.body.innerHTML = originalContent;
        location.reload(); // Quick reset of event listeners
    }
</script>

</body>
</html>
