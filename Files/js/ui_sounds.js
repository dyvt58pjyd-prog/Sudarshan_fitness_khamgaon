/**
 * Tactile UI Sound Effects for Sudarshan Fitness
 * Synthesizes high-tech UI sounds using the Web Audio API.
 * No external MP3 files required!
 */

const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

function playTone(freq, type, duration, vol) {
    if (audioCtx.state === 'suspended') return; // Browser autoplay policy
    
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    
    oscillator.type = type;
    oscillator.frequency.setValueAtTime(freq, audioCtx.currentTime);
    
    // Quick fade out to prevent clicking
    gainNode.gain.setValueAtTime(vol, audioCtx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
    
    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    
    oscillator.start();
    oscillator.stop(audioCtx.currentTime + duration);
}

// Sound Profiles
const sounds = {
    hover: () => playTone(800, 'sine', 0.05, 0.02),
    click: () => {
        playTone(1200, 'sine', 0.1, 0.05);
        setTimeout(() => playTone(1600, 'sine', 0.1, 0.05), 50);
    },
    success: () => {
        playTone(523.25, 'sine', 0.1, 0.1); // C5
        setTimeout(() => playTone(659.25, 'sine', 0.1, 0.1), 100); // E5
        setTimeout(() => playTone(783.99, 'sine', 0.4, 0.1), 200); // G5
    }
};

// Initialize after user gesture
document.addEventListener('click', function initAudio() {
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    // document.removeEventListener('click', initAudio); // Keep it running just in case
}, { once: false });

// Attach to UI elements
document.addEventListener('DOMContentLoaded', () => {
    const interactables = document.querySelectorAll('a, button, .btn, input[type="submit"]');
    
    interactables.forEach(el => {
        el.addEventListener('mouseenter', () => sounds.hover());
        el.addEventListener('click', () => sounds.click());
    });
});
