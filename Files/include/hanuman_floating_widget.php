<!-- 🚩 GLOBAL VIRTUAL LIVE LORD HANUMAN FLOATING WIDGET -->
<style>
#hanuman-float-btn {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 99999;
    background: linear-gradient(135deg, #ff6b00 0%, #ffb703 100%);
    border: 2px solid #ffffff;
    color: #000000;
    width: 62px;
    height: 62px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 0 25px rgba(255, 107, 0, 0.85), 0 10px 25px rgba(0,0,0,0.5);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    animation: hanuman-float-pulse 3s infinite alternate;
}
#hanuman-float-btn:hover {
    transform: scale(1.12) rotate(5deg);
    box-shadow: 0 0 40px rgba(255, 107, 0, 1), 0 0 60px rgba(255, 183, 3, 0.9);
}
@keyframes hanuman-float-pulse {
    0% { transform: translateY(0) scale(1); filter: drop-shadow(0 0 10px #ff6b00); }
    100% { transform: translateY(-8px) scale(1.05); filter: drop-shadow(0 0 22px #ffb703); }
}

/* Modal Popup Overlay */
#hanuman-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(6, 8, 20, 0.85);
    backdrop-filter: blur(14px);
    z-index: 999999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
#hanuman-modal-card {
    background: linear-gradient(145deg, rgba(18, 12, 34, 0.95) 0%, rgba(6, 8, 20, 0.98) 100%);
    border: 2px solid rgba(255, 183, 3, 0.4);
    border-radius: 28px;
    max-width: 520px;
    width: 100%;
    padding: 30px 24px;
    text-align: center;
    position: relative;
    box-shadow: 0 25px 60px rgba(0,0,0,0.8), 0 0 50px rgba(255, 107, 0, 0.3);
    animation: hanuman-pop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes hanuman-pop {
    from { opacity: 0; transform: scale(0.85) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.hanuman-close-btn {
    position: absolute;
    top: 15px;
    right: 18px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hanuman-close-btn:hover { background: #ef4444; color: #fff; }
</style>

<!-- Floating Action Trigger Button -->
<div id="hanuman-float-btn" onclick="openHanumanModal()" title="🚩 सजीव श्री हनुमान दर्शन (Virtual Live Hanuman Companion)">
    <span style="font-size: 28px; filter: drop-shadow(0 0 4px #000);">🚩</span>
</div>

<!-- Modal Dialog -->
<div id="hanuman-modal-overlay" onclick="if(event.target===this) closeHanumanModal()">
    <div id="hanuman-modal-card">
        <button class="hanuman-close-btn" onclick="closeHanumanModal()">✕</button>

        <div style="margin-bottom: 8px;">
            <span style="background: rgba(255, 107, 0, 0.2); border: 1px solid #ff6b00; color: #ffb703; padding: 4px 14px; border-radius: 20px; font-weight: 800; font-size: 11.5px; letter-spacing: 1px;">
                🚩 जय श्री राम | ॐ हं हनुमते नमः
            </span>
        </div>

        <h3 style="color: #ffffff; font-size: 20px; font-weight: 900; margin-bottom: 4px;">सजीव श्री हनुमान दर्शन</h3>
        <p style="color: #ffe6aa; font-size: 12.5px; font-weight: 700; margin-bottom: 16px;">
            "बल बुधि बिद्या देहु मोहिं हरहु कलेस बिकार"
        </p>

        <!-- Hanuman SVG Avatar -->
        <div style="width: 140px; height: 160px; margin: 0 auto 14px auto; cursor: pointer;" onclick="playHanumanChime()">
            <svg viewBox="0 0 200 240" style="width:100%; height:100%; filter: drop-shadow(0 0 20px rgba(255,183,3,0.8));">
                <radialGradient id="h_aura" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#ffb703" stop-opacity="0.9"/>
                    <stop offset="100%" stop-color="#ff6b00" stop-opacity="0"/>
                </radialGradient>
                <circle cx="100" cy="100" r="90" fill="url(#h_aura)"/>
                <path d="M 75 40 L 100 10 L 125 40 L 115 50 L 85 50 Z" fill="#ffb703" stroke="#ff6b00" stroke-width="2"/>
                <circle cx="100" cy="28" r="5" fill="#ef4444"/>
                <ellipse cx="100" cy="80" rx="35" ry="38" fill="#d97706"/>
                <path d="M 94 52 L 106 52 L 104 70 L 100 75 L 96 70 Z" fill="#ef4444"/>
                <ellipse cx="85" cy="72" rx="6" ry="4" fill="#ffffff"/>
                <circle cx="85" cy="72" r="2.5" fill="#000"/>
                <ellipse cx="115" cy="72" rx="6" ry="4" fill="#ffffff"/>
                <circle cx="115" cy="72" r="2.5" fill="#000"/>
                <path d="M 88 95 Q 100 106 112 95" fill="none" stroke="#7c2d12" stroke-width="3"/>
                <path d="M 60 115 Q 100 125 140 115 L 148 180 Q 100 200 52 180 Z" fill="#b45309"/>
                <rect x="145" y="60" width="8" height="130" rx="4" fill="#ffb703"/>
                <circle cx="149" cy="55" r="20" fill="#ffb703" stroke="#92400e" stroke-width="3"/>
            </svg>
        </div>

        <div id="hanuman-widget-quote" style="background: rgba(255,107,0,0.12); border: 1px dashed #ff6b00; border-radius: 14px; padding: 12px 16px; color: #fff; font-size: 13.5px; font-weight: 700; line-height: 1.5; margin-bottom: 16px;">
            "आज का वर्कआउट हनुमान जी को समर्पित करें। 'जय बजरंगबली' बोलकर शुरुआत करें!"
        </div>

        <!-- Quick Interactive Action Buttons -->
        <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
            <button onclick="widgetPushpanjali()" style="background: rgba(255,107,0,0.2); border: 1px solid #ff6b00; color: #ffea9f; padding: 8px 14px; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer;">🌸 पुष्पांजलि</button>
            <button onclick="playHanumanBell()" style="background: rgba(255,183,3,0.2); border: 1px solid #ffb703; color: #ffea9f; padding: 8px 14px; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer;">🔔 मंदिर घंटी</button>
            <button onclick="speakMantraWidget()" style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 8px 14px; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer;">🔊 मंत्र</button>
        </div>

        <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard/admin/') !== false) ? '../member/virtual_hanuman.php' : ((strpos($_SERVER['REQUEST_URI'], '/dashboard/member/') !== false) ? 'virtual_hanuman.php' : './dashboard/member/virtual_hanuman.php'); ?>" style="background: linear-gradient(135deg, #ff6b00, #ff8800); color: #ffffff; padding: 10px 20px; border-radius: 12px; font-weight: 900; font-size: 13px; text-decoration: none; display: inline-block; box-shadow: 0 4px 15px rgba(255,107,0,0.5);">
            🚩 संपूर्ण हनुमान मंदिर एवं चालीसा दर्शन →
        </a>
    </div>
</div>

<script>
function openHanumanModal() {
    document.getElementById('hanuman-modal-overlay').style.display = 'flex';
    playHanumanBell();
}
function closeHanumanModal() {
    document.getElementById('hanuman-modal-overlay').style.display = 'none';
}

// Built-in Web Audio API Bell & Synthesizer
let hAudioCtx = null;
function getHAudioContext() {
    if (!hAudioCtx) {
        hAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (hAudioCtx.state === 'suspended') {
        hAudioCtx.resume();
    }
    return hAudioCtx;
}

function playHanumanBell() {
    try {
        const ctx = getHAudioContext();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 1.2);
        gain.gain.setValueAtTime(0.7, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 2.0);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 2.0);
    } catch(e) {}
}

function playHanumanChime() {
    playHanumanBell();
    const quotes = [
        "जय श्री राम! आज का वर्कआउट हनुमान जी को समर्पित करें।",
        "शरीर को गदा सा और मन को वज्र सा बनाएं!",
        "आलस ही सबसे बड़ा शत्रु है। उठो और लक्ष्य की ओर बढ़ो!",
        "संकट कटै मिटै सब पीरा। जो सुमिरै हनुमत बलबीरा॥",
        "जय बजरंगबली! हर सेट में पूरी शक्ति झोंक दें!"
    ];
    const q = quotes[Math.floor(Math.random() * quotes.length)];
    document.getElementById('hanuman-widget-quote').innerHTML = `🚩 <strong>हनुमान जी का संदेश:</strong><br>"${q}"`;
}

function widgetPushpanjali() {
    playHanumanBell();
    document.getElementById('hanuman-widget-quote').innerHTML = "🌸 <strong>पुष्पांजलि स्वीकृत!</strong> हनुमान जी की कृपा सदा आप पर बनी रहे! 'जय श्री राम!'";
}

function speakMantraWidget() {
    playHanumanBell();
    if ('speechSynthesis' in window) {
        const u = new SpeechSynthesisUtterance("ॐ हं हनुमते नमः। जय श्री राम।");
        u.lang = 'hi-IN';
        u.rate = 0.9;
        window.speechSynthesis.speak(u);
    }
    document.getElementById('hanuman-widget-quote').innerHTML = "🔊 <strong>ॐ हं हनुमते नमः!</strong> निरंतर नाम स्मरण से असीम शक्ति प्राप्त होती है!";
}
</script>
