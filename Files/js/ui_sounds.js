/**
 * Tactile UI Sound Effects for Sudarshan Fitness
 * Lightweight sound system optimized for low-spec Windows hardware.
 */

let audioCtx = null;

function getAudioContext() {
    if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) {}
    }
    return audioCtx;
}

function playTone(freq, type, duration, vol) {
    const ctx = getAudioContext();
    if (!ctx || ctx.state === 'suspended') return;
    try {
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        oscillator.type = type;
        oscillator.frequency.setValueAtTime(freq, ctx.currentTime);
        gainNode.gain.setValueAtTime(vol, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        oscillator.start();
        oscillator.stop(ctx.currentTime + duration);
    } catch (e) {}
}

const sounds = {
    click: () => {
        playTone(1200, 'sine', 0.08, 0.03);
    },
    success: () => {
        playTone(523.25, 'sine', 0.1, 0.06);
        setTimeout(() => playTone(659.25, 'sine', 0.1, 0.06), 80);
    }
};

document.addEventListener('click', function initAudio(e) {
    const ctx = getAudioContext();
    if (ctx && ctx.state === 'suspended') {
        ctx.resume();
    }
    const target = e.target.closest('button, .btn, input[type="submit"]');
    if (target) {
        sounds.click();
    }
}, { passive: true });
