/**
 * Celebration Confetti & Sound Effects for Sudarshan Fitness
 * Triggers when ?success=1 is in the URL.
 */

// Load canvas-confetti dynamically
const confettiScript = document.createElement('script');
confettiScript.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js';
document.head.appendChild(confettiScript);

function triggerCelebration() {
    // Play Success Chime using Web Audio API
    if (window.AudioContext || window.webkitAudioContext) {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
        
        function playTone(freq, duration, delay) {
            setTimeout(() => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + duration);
            }, delay);
        }
        
        // Happy arpeggio
        playTone(523.25, 0.2, 0);    // C5
        playTone(659.25, 0.2, 100);  // E5
        playTone(783.99, 0.4, 200);  // G5
        playTone(1046.50, 0.6, 300); // C6
    }
    
    // Trigger Confetti after script loads
    if (window.confetti) {
        fireConfetti();
    } else {
        confettiScript.onload = fireConfetti;
    }
}

function fireConfetti() {
    var duration = 3000;
    var end = Date.now() + duration;

    (function frame() {
        confetti({
            particleCount: 5,
            angle: 60,
            spread: 55,
            origin: { x: 0 },
            colors: ['#ff6b00', '#ffd700', '#ffffff']
        });
        confetti({
            particleCount: 5,
            angle: 120,
            spread: 55,
            origin: { x: 1 },
            colors: ['#ff6b00', '#ffd700', '#ffffff']
        });

        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    }());
}

// Check URL for success parameter
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') && urlParams.get('success') == '1') {
        // Remove param from URL without reloading so it doesn't trigger on refresh
        window.history.replaceState({}, document.title, window.location.pathname);
        
        // Wait a tiny bit for UI to render
        setTimeout(triggerCelebration, 300);
    }
});
