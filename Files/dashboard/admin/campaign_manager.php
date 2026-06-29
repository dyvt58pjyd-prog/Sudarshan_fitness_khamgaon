<?php
require '../../include/db_conn.php';
page_protect();
$gym = get_gym_details($con);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_campaign') {
    $campaign_name = mysqli_real_escape_string($con, $_POST['campaign_name']);
    $scheduled_date = mysqli_real_escape_string($con, $_POST['scheduled_date']);
    $message_text = mysqli_real_escape_string($con, $_POST['message_text']);
    
    $sql = "INSERT INTO festival_campaigns (campaign_name, scheduled_date, message_text) VALUES ('$campaign_name', '$scheduled_date', '$message_text')";
    if (mysqli_query($con, $sql)) {
        echo "<script>alert('Campaign Scheduled Successfully!'); window.location.href='campaign_manager.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($con) . "');</script>";
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM festival_campaigns WHERE id=$id");
    echo "<script>alert('Campaign Deleted!'); window.location.href='campaign_manager.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sudarshan Fitness | Campaign Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .campaign-form {
            background: var(--bg-darker);
            padding: 25px;
            border-radius: 12px;
            border: 1px solid rgba(255,107,0,0.1);
        }
        .preview-box {
            text-align: center;
            padding: 20px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--glass-border);
        }
        .preview-box img {
            max-width: 100%;
            border-radius: 8px;
            max-height: 400px;
            object-fit: contain;
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
            <h2 style="color: var(--accent-primary); margin-bottom: 20px;">
                <i class="entypo-calendar"></i> Automated Festival Campaigns
            </h2>
            <p style="color: #a3a3a3; margin-bottom: 30px;">
                Schedule automated WhatsApp & Email broadcasts for upcoming festivals. The system will dynamically generate a beautiful custom poster with your gym's logo and address and send it to all active members on the morning of the scheduled date!
            </p>

            <div class="row">
                <!-- Add Campaign Form -->
                <div class="col-md-6">
                    <div class="campaign-form">
                        <h4 style="color: var(--text-main); margin-bottom: 20px;">Schedule New Campaign</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_campaign">
                            
                            <div class="form-group">
                                <label style="color: var(--text-muted);">Festival / Campaign Name (e.g., Happy Diwali)</label>
                                <input type="text" name="campaign_name" id="campaign_name" class="form-control" required style="background: var(--bg-darker); color: var(--text-main); border: 1px solid var(--glass-border);" onkeyup="updatePreview()">
                            </div>
                            
                            <div class="form-group" style="margin-top: 15px;">
                                <label style="color: var(--text-muted);">Date to Send</label>
                                <input type="date" name="scheduled_date" class="form-control" required style="background: var(--bg-darker); color: var(--text-main); border: 1px solid var(--glass-border);">
                            </div>
                            
                            <div class="form-group" style="margin-top: 15px;">
                                <label style="color: var(--text-muted);">Message Text (Included in WhatsApp/Email)</label>
                                <textarea name="message_text" id="message_text" class="form-control" rows="4" required style="background: var(--bg-darker); color: var(--text-main); border: 1px solid var(--glass-border);">Wishing you a very happy and prosperous festival from everyone here at Sudarshan Fitness Khamgaon! 💪 Let's stay fit and healthy!</textarea>
                            </div>
                            
                            <!-- Gemini AI Generator -->
                            <div style="margin-top: 15px; padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px;">
                                <label style="color: #10b981; font-weight: bold;"><i class="entypo-light-bulb"></i> AI Message Generator (Gemini)</label>
                                <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 10px;">Enter your free Google Gemini API Key to automatically generate engaging, gym-themed festival messages.</p>
                                <div style="display: flex; gap: 10px;">
                                    <input type="password" id="gemini_api_key" class="form-control" placeholder="Paste Gemini API Key here" style="background: var(--bg-darker); color: var(--text-main); border: 1px solid var(--glass-border);">
                                    <button type="button" onclick="generateWithGemini()" id="btn-gemini" class="btn btn-success" style="white-space: nowrap;">✨ Generate</button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="margin-top: 20px; width: 100%; background: var(--accent-primary); color: white; border: none; padding: 12px; font-weight: bold;">
                                <i class="entypo-paper-plane"></i> Schedule Campaign
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="col-md-6">
                    <div class="preview-box">
                        <h4 style="color: var(--text-main); margin-bottom: 20px;"><i class="entypo-picture"></i> Live Poster Preview</h4>
                        <img id="poster-preview" src="../../api/generate_poster.php?preview_title=Happy+Festival" alt="Poster Preview">
                        <p style="color: var(--text-muted); font-size: 12px; margin-top: 15px;">*This image will be generated on-the-fly and attached to the messages.*</p>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-top: 40px;">
                <div class="col-md-12">
                    <h3 style="color: var(--text-main); margin-bottom: 20px;">Scheduled Campaigns</h3>
                    <table class="table table-bordered table-hover premium-table">
                        <thead>
                            <tr>
                                <th>Campaign Name</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($con, "SELECT * FROM festival_campaigns ORDER BY scheduled_date ASC");
                            if (mysqli_num_rows($res) > 0) {
                                while ($row = mysqli_fetch_assoc($res)) {
                                    $status_color = $row['status'] == 'Sent' ? '#10b981' : '#f59e0b';
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['campaign_name']) . "</td>";
                                    echo "<td>" . date('d M Y', strtotime($row['scheduled_date'])) . "</td>";
                                    echo "<td><span style='background: {$status_color}; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;'>" . $row['status'] . "</span></td>";
                                    echo "<td>";
                                    if ($row['status'] == 'Pending') {
                                        echo "<a href='?delete={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this scheduled campaign?\")'><i class='entypo-trash'></i> Cancel</a>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>No campaigns scheduled.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Load saved API key
        document.addEventListener("DOMContentLoaded", function() {
            const savedKey = localStorage.getItem('gemini_api_key');
            if(savedKey) document.getElementById('gemini_api_key').value = savedKey;
        });

        let timeout = null;
        function updatePreview() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                const title = document.getElementById('campaign_name').value || 'Happy Festival';
                document.getElementById('poster-preview').src = '../../api/generate_poster.php?preview_title=' + encodeURIComponent(title);
            }, 500);
        }

        async function generateWithGemini() {
            const apiKey = document.getElementById('gemini_api_key').value.trim();
            const festival = document.getElementById('campaign_name').value.trim();
            
            if(!apiKey) return alert("Please enter your Gemini API Key first.");
            if(!festival) return alert("Please enter a Festival/Campaign Name first.");
            
            // Save key
            localStorage.setItem('gemini_api_key', apiKey);
            
            const btn = document.getElementById('btn-gemini');
            btn.innerHTML = "⏳ Generating...";
            btn.disabled = true;

            const prompt = `Write a short, engaging 2-3 sentence promotional WhatsApp/Email message for a gym called 'Sudarshan Fitness Khamgaon' for the occasion of '${festival}'. Include emojis. Do not use hashtags. Keep it motivational. Start with a warm greeting.`;

            try {
                const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${apiKey}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        contents: [{ parts: [{ text: prompt }] }]
                    })
                });
                
                const data = await response.json();
                if(data.error) {
                    alert("API Error: " + data.error.message);
                } else if(data.candidates && data.candidates[0].content) {
                    document.getElementById('message_text').value = data.candidates[0].content.parts[0].text.trim();
                }
            } catch(e) {
                alert("Failed to connect to Gemini API. Check your internet or API key.");
            }
            
            btn.innerHTML = "✨ Generate";
            btn.disabled = false;
        }
    </script>
</body>
</html>
