<?php
require '../../include/db_conn.php';
page_protect();

$gym = get_gym_details($con);
$user_id = $_SESSION['user_data'] ?? 'guest';
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'भक्त';
$user_role = $_SESSION['role'] ?? 'member';

// Hanuman Mantras & Daily Strength Blessings
$hanuman_blessings = [
    [
        "title" => "बल संवर्धन (Unlimited Power)",
        "mantra" => "मनोजवं मारुततुल्यवेगं जितेन्द्रियं बुद्धिमतां वरिष्ठम्। वातात्मजं वानरयूथमुख्यं श्रीरामदूतं शरणं प्रपद्ये॥",
        "meaning" => "Swift as the mind, fast as the wind, master of senses, supreme among the wise. I bow to Lord Hanuman, the messenger of Shri Ram.",
        "fitness_tip" => "आज जिम में नया मील का पत्थर छुएं। जब शरीर थकने लगे, राम नाम और हनुमान जी की शक्ति का स्मरण करें!"
    ],
    [
        "title" => "संकट मोचन (Overcomer of Obstacles)",
        "mantra" => "अतुलितबलधामं हेमशैलाभदेहं दनुजवनकृशानुं ज्ञानिनामग्रगण्यम्। सकलगुणनिधानं वानराणामधीशं रघुपतिप्रियभक्तं वातजातं नमामि॥",
        "meaning" => "Abode of immeasurable strength, body glowing like a mountain of gold, destroyer of negativity, supreme among knowledge.",
        "fitness_tip" => "आलस और संकोच को दूर भगाएं। अनुशासित दिनचर्या ही हनुमान जी की सच्ची भक्ति है।"
    ],
    [
        "title" => "अष्ट सिद्धि नव निधि दाता",
        "mantra" => "अष्ट सिद्धि नौ निधि के दाता। अस बर दीन्ह जानकी माता॥",
        "meaning" => "Mother Sita blessed Lord Hanuman as the giver of eight divine powers (Siddhis) and nine wealths (Nidhis).",
        "fitness_tip" => "शरीर सौष्ठव और एकाग्रता दोनों हनुमान जी की कृपा से प्राप्त होते हैं। निरंतर अभ्यास ही सिद्धि है।"
    ],
    [
        "title" => "अतुल्य पराक्रम (Unstoppable Courage)",
        "mantra" => "महाबीर बिक्रम बजरंगी। कुमति निवार सुमति के संगी॥",
        "meaning" => "O Great Hero Vajrangi, remover of evil thoughts and companion of noble intellect.",
        "fitness_tip" => "भारी वजन उठाने से पहले मन को शांत और दृढ़ बनाएं। 'जय बजरंगबली' बोलकर सेट शुरू करें!"
    ]
];

$today_idx = date('N') % count($hanuman_blessings);
$daily_blessing = $hanuman_blessings[$today_idx];
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>🚩 सजीव श्री हनुमान दर्शन | Virtual Hanuman Companion - <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Noto+Sans+Devanagari:wght@400;600;700;800;900&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/entypo.css">
    <style>
        :root {
            --saffron: #ff6b00;
            --deep-gold: #ffb703;
            --divine-glow: rgba(255, 107, 0, 0.4);
            --bg: #060814;
            --card-bg: rgba(18, 12, 34, 0.85);
            --border: rgba(255, 183, 3, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg);
            color: #ffffff;
            font-family: 'Noto Sans Devanagari', 'Outfit', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 50% 15%, rgba(255, 107, 0, 0.22) 0%, transparent 60%),
                radial-gradient(circle at 50% 85%, rgba(139, 0, 0, 0.25) 0%, transparent 70%);
            padding: 20px;
        }

        /* Top Header Navigation */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(18, 12, 34, 0.9);
            border: 1px solid var(--border);
            padding: 16px 24px;
            border-radius: 20px;
            margin-bottom: 25px;
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .top-nav h1 {
            font-size: 20px;
            font-weight: 900;
            color: var(--deep-gold);
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }
        .btn-back {
            background: rgba(255, 107, 0, 0.15);
            border: 1px solid var(--saffron);
            color: #ffb703;
            padding: 8px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: var(--saffron);
            color: #fff;
            box-shadow: 0 0 20px var(--saffron);
        }

        /* Grid Layout */
        .hanuman-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 25px;
            max-width: 1300px;
            margin: 0 auto;
        }
        @media (max-width: 992px) {
            .hanuman-grid { grid-template-columns: 1fr; }
        }

        /* Virtual Live Hanuman Altar Card */
        .altar-card {
            background: var(--card-bg);
            border: 2px solid var(--border);
            border-radius: 28px;
            padding: 35px 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6), inset 0 0 40px rgba(255, 107, 0, 0.15);
            backdrop-filter: blur(16px);
        }
        
        /* Dynamic Divine Aura Halo */
        .divine-halo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%);
            width: 380px;
            height: 380px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 183, 3, 0.4) 0%, rgba(255, 107, 0, 0.2) 50%, transparent 75%);
            animation: pulse-halo 4s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: 1;
        }
        @keyframes pulse-halo {
            0% { transform: translate(-50%, -60%) scale(0.85); opacity: 0.6; }
            100% { transform: translate(-50%, -60%) scale(1.15); opacity: 1; filter: drop-shadow(0 0 40px #ff6b00); }
        }

        /* Virtual Live Hanuman Avatar Container */
        .avatar-container {
            position: relative;
            z-index: 2;
            margin: 0 auto 20px auto;
            width: 280px;
            height: 320px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 25px rgba(255, 183, 3, 0.7));
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: divine-float 3.5s ease-in-out infinite alternate;
        }
        .avatar-container:hover .avatar-img {
            transform: scale(1.06);
            filter: drop-shadow(0 0 40px rgba(255, 107, 0, 0.95));
        }

        @keyframes divine-float {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-12px); }
        }

        /* Interactive Action Buttons Bar (Pushpanjali, Aarti, Bell, Chanting) */
        .puja-controls {
            position: relative;
            z-index: 3;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .puja-btn {
            background: linear-gradient(135deg, rgba(255, 107, 0, 0.25) 0%, rgba(255, 183, 3, 0.15) 100%);
            border: 1px solid var(--saffron);
            color: #ffea9f;
            padding: 12px 20px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 13.5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.25s ease;
        }
        .puja-btn:hover {
            background: linear-gradient(135deg, var(--saffron) 0%, #ff8800 100%);
            color: #ffffff;
            box-shadow: 0 0 25px rgba(255, 107, 0, 0.8);
            transform: translateY(-2px);
        }
        .puja-btn:active {
            transform: translateY(1px);
        }

        /* Flower Shower Particle Overlay */
        #flower-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }

        /* Divine Message Speech Bubble */
        .hanuman-speech {
            background: rgba(30, 20, 55, 0.9);
            border: 1px dashed var(--saffron);
            border-radius: 18px;
            padding: 16px 20px;
            margin-top: 18px;
            position: relative;
            z-index: 3;
            font-size: 15px;
            line-height: 1.6;
            color: #fff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }
        .hanuman-speech::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 0 10px 10px 10px;
            border-style: solid;
            border-color: transparent transparent var(--saffron) transparent;
        }

        /* Right Column Cards */
        .info-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
        }
        .card-header-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--deep-gold);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,183,3,0.15);
            padding-bottom: 10px;
        }

        /* Hanuman Chalisa Reader & Player */
        .chalisa-box {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 16px;
            padding: 20px;
            max-height: 280px;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 14.5px;
            line-height: 1.8;
            color: #ffe6aa;
            text-align: center;
        }
        .chalisa-verse {
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px dashed rgba(255,183,3,0.2);
        }
        .chalisa-verse:last-child { border-bottom: none; }

        /* AI Interactive Ask Hanuman Box */
        .ask-box {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }
        .ask-input {
            flex: 1;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
        }
        .ask-btn {
            background: linear-gradient(135deg, var(--saffron), #ff8800);
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        /* Glowing Diya Flame Animation */
        .diya-container {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #ffb703;
            font-weight: bold;
        }
        .flame {
            width: 14px;
            height: 22px;
            background: linear-gradient(to top, #ff6b00, #ffb703, #ffffff);
            border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
            box-shadow: 0 0 15px #ff6b00, 0 0 30px #ffb703;
            animation: flicker 0.15s ease-in-out infinite alternate;
        }
        @keyframes flicker {
            0% { transform: scale(1) rotate(-1deg); opacity: 0.9; }
            100% { transform: scale(1.15) rotate(1deg); opacity: 1; filter: drop-shadow(0 0 10px #ffb703); }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <div class="top-nav">
        <h1>🚩 सजीव श्री हनुमान | Live Virtual Hanuman Companion</h1>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>
    </div>

    <!-- Main Hanuman Altar Grid -->
    <div class="hanuman-grid">

        <!-- Left Altar Column -->
        <div class="altar-card">
            <canvas id="flower-canvas"></canvas>
            
            <div class="divine-halo"></div>

            <div style="position: relative; z-index: 3; margin-bottom: 10px;">
                <span style="background: rgba(255, 107, 0, 0.2); border: 1px solid var(--saffron); color: #ffb703; padding: 4px 14px; border-radius: 20px; font-weight: 800; font-size: 12px; letter-spacing: 1px;">
                    🚩 जय श्री राम | ॐ हं हनुमते नमः
                </span>
            </div>

            <!-- SVG Vector / Live Hanuman Render -->
            <div class="avatar-container" onclick="triggerHanumanBlessing()">
                <svg class="avatar-img" viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <radialGradient id="aura" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#ffb703" stop-opacity="0.9"/>
                            <stop offset="60%" stop-color="#ff6b00" stop-opacity="0.5"/>
                            <stop offset="100%" stop-color="#ff6b00" stop-opacity="0"/>
                        </radialGradient>
                        <linearGradient id="gold" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#ffe6aa"/>
                            <stop offset="50%" stop-color="#ffb703"/>
                            <stop offset="100%" stop-color="#d97706"/>
                        </linearGradient>
                        <linearGradient id="gadaGold" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#fff"/>
                            <stop offset="50%" stop-color="#ffb703"/>
                            <stop offset="100%" stop-color="#92400e"/>
                        </linearGradient>
                    </defs>
                    
                    <!-- Outer Halo Rays -->
                    <circle cx="100" cy="100" r="90" fill="url(#aura)"/>
                    <circle cx="100" cy="100" r="82" fill="none" stroke="#ffea9f" stroke-width="2" stroke-dasharray="6,4"/>

                    <!-- Crown (Mukut) -->
                    <path d="M 75 40 L 100 10 L 125 40 L 115 50 L 85 50 Z" fill="url(#gold)" stroke="#ff6b00" stroke-width="2"/>
                    <circle cx="100" cy="28" r="5" fill="#ef4444"/>

                    <!-- Face & Ears -->
                    <ellipse cx="100" cy="80" rx="35" ry="38" fill="#d97706"/>
                    <ellipse cx="62" cy="80" rx="8" ry="12" fill="#b45309"/>
                    <ellipse cx="138" cy="80" rx="8" ry="12" fill="#b45309"/>

                    <!-- Tilak (Tilak on Forehead) -->
                    <path d="M 94 52 L 106 52 L 104 70 L 100 75 L 96 70 Z" fill="#ef4444"/>
                    <circle cx="100" cy="62" r="2.5" fill="#ffb703"/>

                    <!-- Eyes & Divine Smile -->
                    <ellipse cx="85" cy="72" rx="6" ry="4" fill="#ffffff"/>
                    <circle cx="85" cy="72" r="2.5" fill="#000"/>
                    <ellipse cx="115" cy="72" rx="6" ry="4" fill="#ffffff"/>
                    <circle cx="115" cy="72" r="2.5" fill="#000"/>
                    
                    <path d="M 88 95 Q 100 106 112 95" fill="none" stroke="#7c2d12" stroke-width="3" stroke-linecap="round"/>

                    <!-- Muscular Torso & Kundal -->
                    <path d="M 60 115 Q 100 125 140 115 L 148 180 Q 100 200 52 180 Z" fill="#b45309"/>
                    <!-- Sacred Garland (Vanamala) -->
                    <path d="M 65 118 Q 100 170 135 118" fill="none" stroke="#ffea9f" stroke-width="6" stroke-dasharray="4,2"/>

                    <!-- Gada (Divine Mace) -->
                    <rect x="145" y="60" width="8" height="130" rx="4" fill="url(#gadaGold)"/>
                    <circle cx="149" cy="55" r="20" fill="url(#gadaGold)" stroke="#b45309" stroke-width="3"/>
                    <path d="M 132 55 L 166 55" stroke="#ef4444" stroke-width="3"/>
                </svg>
            </div>

            <!-- Interactive Speech Message Bubble -->
            <div class="hanuman-speech" id="speech-bubble">
                "जय श्री राम, <strong><?php echo htmlspecialchars($user_name); ?></strong>! 🚩<br>
                मैं सदैव आपके साथ हूँ। अपने शरीर और मन को वज्र सा मजबूत बनाएं!"
            </div>

            <!-- Diya Flame Status Indicator -->
            <div style="margin-top: 15px; display: flex; justify-content: center; align-items: center; gap: 20px;">
                <div class="diya-container">
                    <div class="flame"></div>
                    <span>अखंड दीप प्रज्वलित</span>
                </div>
            </div>

            <!-- Interactive Puja Controls -->
            <div class="puja-controls">
                <button class="puja-btn" onclick="triggerPushpanjali()">🌸 पुष्पवर्षा (Offer Flowers)</button>
                <button class="puja-btn" onclick="playTempleBell()">🔔 मंदिर घंटी (Temple Bell)</button>
                <button class="puja-btn" onclick="triggerAarti()">🪔 महा आरती (Perform Aarti)</button>
                <button class="puja-btn" onclick="speakMantra()">🔊 मंत्र उच्चारण (Chant Mantra)</button>
            </div>
        </div>

        <!-- Right Informational & Interactive Column -->
        <div>
            
            <!-- Daily Strength & Power Blessing -->
            <div class="info-card" style="border-left: 4px solid var(--saffron);">
                <div class="card-header-title">
                    <span>💪 आज का बल संवर्धन (Today's Power Blessing)</span>
                </div>
                <h3 style="color: var(--saffron); font-size: 16px; margin-bottom: 8px;"><?php echo htmlspecialchars($daily_blessing['title']); ?></h3>
                <div style="background: rgba(255,107,0,0.08); border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid rgba(255,107,0,0.2);">
                    <div style="font-weight: 700; color: #ffea9f; font-size: 15px; line-height: 1.6; text-align: center; margin-bottom: 8px;">
                        "<?php echo htmlspecialchars($daily_blessing['mantra']); ?>"
                    </div>
                    <div style="font-size: 12.5px; color: #cbd5e1; text-align: center;">
                        <?php echo htmlspecialchars($daily_blessing['meaning']); ?>
                    </div>
                </div>
                <div style="font-size: 13px; color: #10b981; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                    <span>🏋️ वर्कआउट प्रेरणा:</span> <?php echo htmlspecialchars($daily_blessing['fitness_tip']); ?>
                </div>
            </div>

            <!-- Hanuman Chalisa Reader & Player -->
            <div class="info-card">
                <div class="card-header-title">
                    <span>📜 श्री हनुमान चालीसा (Shri Hanuman Chalisa)</span>
                    <button onclick="toggleAudioChalisa()" id="btn-chalisa-audio" style="background: rgba(255,183,3,0.2); border: 1px solid var(--deep-gold); color: #ffea9f; padding: 4px 12px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">▶️ सुनें (Listen Chant)</button>
                </div>

                <div class="chalisa-box" id="chalisa-reader">
                    <div class="chalisa-verse">
                        <strong>दोहा</strong><br>
                        श्रीगुरु चरन सरोज रज निज मनु मुकुरु सुधारि।<br>
                        बरनऊँ रघुबर बिमल जसु जो दायकु फल चारि॥<br>
                        बुद्धिहीन तनु जानिके सुमिरौ पवन-कुमार।<br>
                        बल बुधि बिद्या देहु मोहिं हरहु कलेस बिकार॥
                    </div>
                    <div class="chalisa-verse">
                        जय हनुमान ज्ञान गुन सागर। जय कपीस तिहुँ लोक उजागर॥१॥<br>
                        रामदूत अतुलित बल धामा। अंजनि-पुत्र पवनसुत नामा॥२॥
                    </div>
                    <div class="chalisa-verse">
                        महाबीर बिक्रम बजरंगी। कुमति निवार सुमति के संगी॥३॥<br>
                        कंचन बरन बिराज सुबेसा। कानन कुंडल कुंचित केसा॥४॥
                    </div>
                    <div class="chalisa-verse">
                        हाथ बज्र औ ध्वजा बिराजै। काँधे मूँज जनेऊ साजै॥५॥<br>
                        संकर सुवन केसरीनंदन। तेज प्रताप महा जग बन्दन॥६॥
                    </div>
                    <div class="chalisa-verse">
                        विद्यावान गुनी अति चातुर। राम काज करिबे को आतुर॥७॥<br>
                        प्रभु चरित्र सुनिबे को रसिया। राम लखन सीता मन बसिया॥८॥
                    </div>
                    <div class="chalisa-verse">
                        सब पर राम तपस्वी राजा। तिन के काज सकल तुम साजा॥२७॥<br>
                        और मनोरथ जो कोई लावै। सोइ अमित जीवन फल पावै॥२८॥
                    </div>
                    <div class="chalisa-verse">
                        जो यह पढे हनुमान चालीसा। होय सिध्दि साखी गौरीसा॥३८॥<br>
                        पवनतनय संकट हरन मंगल मूरति रूप।<br>
                        राम लखन सीता सहित हृदय बसहु सुर भूप॥४०॥
                    </div>
                </div>
            </div>

            <!-- Interactive Ask Hanuman AI Guidance Box -->
            <div class="info-card">
                <div class="card-header-title">
                    <span>🤖 हनुमान जी से मार्गदर्शन (Interactive Wisdom Guidance)</span>
                </div>
                <div style="font-size: 13px; color: #94a3b8; margin-bottom: 10px;">
                    हनुमान जी से अनुशासन, वर्कआउट, भय निवारण या शक्ति संवर्धन पर सवाल पूछें:
                </div>
                <div class="ask-box">
                    <input type="text" id="user-question" class="ask-input" placeholder="जैसे: 'हनुमान जी, वर्कआउट में आलस कैसे दूर करें?'" onkeypress="if(event.key==='Enter') askHanumanAI()">
                    <button class="ask-btn" onclick="askHanumanAI()">पूछें 🚩</button>
                </div>
            </div>

        </div>

    </div>

    <!-- Interactive Scripts for Animation, Flowers, Web Audio Synthesizer -->
    <script>
        // 1. Flower Canvas Shower Animation
        const canvas = document.getElementById('flower-canvas');
        const ctx = canvas.getContext('2d');
        let flowers = [];

        function resizeCanvas() {
            canvas.width = canvas.parentElement.clientWidth;
            canvas.height = canvas.parentElement.clientHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        class Flower {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = -20;
                this.size = Math.random() * 8 + 6;
                this.speedY = Math.random() * 2 + 1.5;
                this.speedX = Math.random() * 1 - 0.5;
                this.rotation = Math.random() * 360;
                this.rotSpeed = Math.random() * 2 - 1;
                this.color = ['#ff6b00', '#ffb703', '#ef4444', '#f43f5e', '#ffffff'][Math.floor(Math.random() * 5)];
            }
            update() {
                this.y += this.speedY;
                this.x += this.speedX;
                this.rotation += this.rotSpeed;
            }
            draw() {
                ctx.save();
                ctx.translate(this.x, this.y);
                ctx.rotate((this.rotation * Math.PI) / 180);
                ctx.fillStyle = this.color;
                // Draw 5 petals
                for (let i = 0; i < 5; i++) {
                    ctx.beginPath();
                    ctx.ellipse(0, this.size / 2, this.size / 3, this.size / 1.5, 0, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.rotate((72 * Math.PI) / 180);
                }
                ctx.fillStyle = '#ffea9f';
                ctx.beginPath();
                ctx.arc(0, 0, this.size / 4, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }
        }

        function animateFlowers() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = flowers.length - 1; i >= 0; i--) {
                flowers[i].update();
                flowers[i].draw();
                if (flowers[i].y > canvas.height + 20) {
                    flowers.splice(i, 1);
                }
            }
            if (flowers.length > 0) {
                requestAnimationFrame(animateFlowers);
            }
        }

        function triggerPushpanjali() {
            for (let i = 0; i < 45; i++) {
                setTimeout(() => {
                    flowers.push(new Flower());
                    if (flowers.length === 1) animateFlowers();
                }, i * 30);
            }
            document.getElementById('speech-bubble').innerHTML = "🌸 <strong>पुष्पांजलि स्वीकृत!</strong> हनुमान जी का आशीर्वाद सदा आप पर बना रहे! 'जय श्री राम!'";
            playChimeSound();
        }

        // 2. Web Audio API Synthesizer (Temple Bell & Chimes)
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        let audioCtx = null;

        function getAudioContext() {
            if (!audioCtx) {
                audioCtx = new AudioContext();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }

        function playTempleBell() {
            try {
                const ctx = getAudioContext();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime); // High metallic chime A5
                osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 1.5);

                gain.gain.setValueAtTime(0.8, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 2.5);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start();
                osc.stop(ctx.currentTime + 2.5);

                document.getElementById('speech-bubble').innerHTML = "🔔 <strong>जय बजरंगबली!</strong> मंदिर की घंटी की पवित्र ध्वनि से वातावरण शुद्ध हुआ!";
            } catch (e) {
                console.log(e);
            }
        }

        function playChimeSound() {
            try {
                const ctx = getAudioContext();
                const freqs = [523.25, 659.25, 783.99, 1046.50]; // C Major Chord
                freqs.forEach((f, i) => {
                    setTimeout(() => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(f, ctx.currentTime);
                        gain.gain.setValueAtTime(0.4, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 1.2);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + 1.2);
                    }, i * 120);
                });
            } catch (e) {}
        }

        function triggerAarti() {
            triggerPushpanjali();
            playTempleBell();
            document.getElementById('speech-bubble').innerHTML = "🪔 <strong>जय श्री राम! आरती सम्पन्न!</strong> हनुमान जी की दिव्य ज्योत आपके जीवन और जिम साधना को प्रकाशित करे!";
        }

        function speakMantra() {
            playTempleBell();
            const text = "ॐ हं हनुमते नमः। जय श्री राम।";
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'hi-IN';
                utterance.rate = 0.9;
                utterance.pitch = 1.0;
                window.speechSynthesis.speak(utterance);
            }
            document.getElementById('speech-bubble').innerHTML = "🔊 <strong>ॐ हं हनुमते नमः!</strong> निरंतर नाम स्मरण से असीम शक्ति प्राप्त होती है!";
        }

        function triggerHanumanBlessing() {
            triggerPushpanjali();
            playTempleBell();
            const quotes = [
                "जय श्री राम! आज का वर्कआउट हनुमान जी को समर्पित करें।",
                "शरीर को गदा सा और मन को वज्र सा बनाएं!",
                "आलस ही सबसे बड़ा शत्रु है। उठो और लक्ष्य की ओर बढ़ो!",
                "संकट कटै मिटै सब पीरा। जो सुमिरै हनुमत बलबीरा॥",
                "जय बजरंगबली! हर सेट में पूरी शक्ति झोंक दें!"
            ];
            const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];
            document.getElementById('speech-bubble').innerHTML = `🚩 <strong>हनुमान जी का संदेश:</strong><br>"${randomQuote}"`;
        }

        // 3. Interactive AI Wisdom Generator
        function askHanumanAI() {
            const input = document.getElementById('user-question');
            const q = input.value.trim();
            if (!q) return;

            playTempleBell();
            const bubble = document.getElementById('speech-bubble');
            bubble.innerHTML = "🌀 <em>हनुमान जी ध्यान मुद्रा में सोच रहे हैं...</em>";

            setTimeout(() => {
                let ans = "";
                const lower = q.toLowerCase();

                if (lower.includes('आलस') || lower.includes('थकान') || lower.includes('lazy')) {
                    ans = "आलस तमस का प्रतीक है। हनुमान जी कहते हैं — सुबह सूर्योदय से पहले उठें, ठंडे पानी से मुंह धोएं और 10 दंड (Push-ups) लगाकर 'जय श्री राम' का उद्घोष करें! आलस तुरंत भाग जाएगा।";
                } else if (lower.includes('ताकत') || lower.includes('strength') || lower.includes('वजन')) {
                    ans = "ताकत केवल भोजन से नहीं, अनुशासन और पवित्र विचारों से आती है। शुद्ध सात्विक आहार लें, पर्याप्त नींद लें और निरंतर अभ्यास करें।";
                } else if (lower.includes('डर') || lower.includes('भय') || lower.includes('fear')) {
                    ans = "सब सुख लहै तुम्हारी सरना। तुम रक्षक काहू को डर ना॥ हनुमान जी का ध्यान करने वाले साधक को कभी भय स्पर्श नहीं कर सकता!";
                } else {
                    ans = `प्रिय भक्त, "${q}" के उत्तर में हनुमान जी का संदेश है: 'सत्य, अनुशासन और अनवरत मेहनत ही सफलता की कुंजी है। ईश्वर पर अटूट विश्वास रखें!'`;
                }

                bubble.innerHTML = `🚩 <strong>हनुमान जी का मार्गदर्शन:</strong><br>"${ans}"`;
                input.value = '';
            }, 800);
        }

        // Audio Chalisa Player Toggle
        let isPlayingChalisa = false;
        function toggleAudioChalisa() {
            const btn = document.getElementById('btn-chalisa-audio');
            if (!isPlayingChalisa) {
                isPlayingChalisa = true;
                btn.innerHTML = "⏸️ रोकें (Pause)";
                speakFullChalisa();
            } else {
                isPlayingChalisa = false;
                btn.innerHTML = "▶️ सुनें (Listen Chant)";
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }
            }
        }

        function speakFullChalisa() {
            if ('speechSynthesis' in window) {
                const chalisaText = "श्रीगुरु चरन सरोज रज निज मनु मुकुरु सुधारि। बरनऊँ रघुबर बिमल जसु जो दायकु फल चारि। बुद्धिहीन तनु जानिके सुमिरौ पवन कुमार। बल बुधि बिद्या देहु मोहिं हरहु कलेस बिकार। जय हनुमान ज्ञान गुन सागर। जय कपीस तिहुँ लोक उजागर। रामदूत अतुलित बल धामा। अंजनि पुत्र पवनसुत नामा।";
                const ut = new SpeechSynthesisUtterance(chalisaText);
                ut.lang = 'hi-IN';
                ut.rate = 0.85;
                ut.onend = function() {
                    isPlayingChalisa = false;
                    document.getElementById('btn-chalisa-audio').innerHTML = "▶️ सुनें (Listen Chant)";
                };
                window.speechSynthesis.speak(ut);
            }
        }
    </script>
</body>
</html>
