const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const fs = require('fs');
const path = require('path');
const axios = require('axios'); // We need axios for HTTP polling
const { execSync } = require('child_process');

// Configuration
const HOSTINGER_URL = process.env.HOSTINGER_URL || 'https://sudarshanfitness.de';
const API_SECRET = 'TITAN_GYM_SECRET_KEY_123';
const POLL_INTERVAL_MS = 10000; // 10 seconds

// Push Architecture Setup for QR Code Exposure
const qrcodeImage = require('qrcode'); // To generate base64/image instead of terminal
let clientState = 'DISCONNECTED'; 
let linkedUser = null;
let currentQR = null; // Store the raw QR string
let client;

// Choose Chrome executable path dynamically based on OS/platform
let chromePath = '';
if (process.platform === 'darwin') {
    if (fs.existsSync('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome')) chromePath = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
} else if (process.platform === 'win32') {
    if (fs.existsSync('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe')) chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    else if (fs.existsSync('C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe')) chromePath = 'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe';
} else if (process.platform === 'linux') {
    if (fs.existsSync('/usr/bin/google-chrome')) chromePath = '/usr/bin/google-chrome';
    else if (fs.existsSync('/usr/bin/chromium-browser')) chromePath = '/usr/bin/chromium-browser';
}

const puppeteerOptions = {
    headless: 'new',
    args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--disable-gpu',
        '--disable-background-timer-throttling',
        '--disable-backgrounding-occluded-windows',
        '--disable-renderer-backgrounding',
        '--disable-features=IsolateOrigins,site-per-process',
        '--disable-site-isolation-trials',
        '--disable-blink-features=AutomationControlled'
    ]
};

if (chromePath) {
    console.log(`[WhatsApp] Auto-detected Chrome at: ${chromePath}`);
    puppeteerOptions.executablePath = chromePath;
}

function initWhatsAppClient() {
    client = new Client({
        authStrategy: new LocalAuth({ dataPath: path.join(__dirname, 'sessions') }),
        puppeteer: puppeteerOptions,
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        webVersionCache: { type: 'remote', remotePath: 'https://raw.githubusercontent.com/wwebjs-dev/extension/main/release-build/wdudef.json' },
        authTimeoutMs: 90000,
        qrMaxRetries: 0,
        takeoverOnConflict: true,
        takeoverTimeoutMs: 15000
    });

    client.on('qr', (qr) => {
        clientState = 'QR_READY';
        currentQR = qr;
        console.log('[WhatsApp] Scan this QR Code to link your device:');
        qrcode.generate(qr, {small: true});
        pushStatusToHostinger();
    });

    client.on('ready', () => {
        clientState = 'CONNECTED';
        currentQR = null;
        linkedUser = client.info.wid.user;
        console.log(`[WhatsApp] Client is ready! Connected as: ${linkedUser}`);
        pushStatusToHostinger();
        startPolling();
    });

    client.on('authenticated', () => {
        console.log('[WhatsApp] Authenticated successfully.');
    });

    client.on('auth_failure', (msg) => {
        clientState = 'DISCONNECTED';
        console.error('[WhatsApp] Authentication failure:', msg);
        pushStatusToHostinger();
    });

    client.on('disconnected', (reason) => {
        clientState = 'DISCONNECTED';
        linkedUser = null;
        console.log('[WhatsApp] Client disconnected:', reason);
        pushStatusToHostinger();
        setTimeout(() => initWhatsAppClient(), 5000);
    });

    console.log('[WhatsApp] Initializing client...');
    client.initialize().catch(err => console.error('[WhatsApp] Initialization failed:', err));
}

let isPolling = false;

async function startPolling() {
    console.log(`[WhatsApp Poller] Started polling ${HOSTINGER_URL} every ${POLL_INTERVAL_MS/1000}s`);
    
    setInterval(async () => {
        if (clientState !== 'CONNECTED' || isPolling) return;
        isPolling = true;

        try {
            const pollUrl = `${HOSTINGER_URL}/Files/api/poll_whatsapp_messages.php?key=${API_SECRET}`;
            const response = await axios.get(pollUrl);
            const data = response.data;

            if (data && data.success && data.messages && data.messages.length > 0) {
                console.log(`[WhatsApp Poller] Found ${data.messages.length} pending messages.`);
                
                for (const msg of data.messages) {
                    await sendMessage(msg);
                    const randomDelayMs = Math.floor(Math.random() * (10000 - 5000 + 1)) + 5000;
                    console.log(`[WhatsApp Poller] Sleeping for ${randomDelayMs / 1000}s to mimic human behavior...`);
                    await new Promise(res => setTimeout(res, randomDelayMs));
                }
            }
        } catch (error) {
            console.error(`[WhatsApp Poller] HTTP Error checking Hostinger:`, error.message);
        }

        isPolling = false;
    }, POLL_INTERVAL_MS);
}

async function sendMessage(msgData) {
    const { id, number, message, filePath } = msgData;
    let status = 'failed';
    
    try {
        let cleanedNumber = number.toString().replace(/\D/g, '');
        if (cleanedNumber.length === 10) cleanedNumber = '91' + cleanedNumber;
        const chatId = cleanedNumber + '@c.us';

        console.log(`[WhatsApp] Sending msg ID ${id} to ${chatId}...`);
        await client.sendMessage(chatId, message);
        status = 'sent';
    } catch (err) {
        console.error(`[WhatsApp] Failed to send msg ID ${id}:`, err.message);
    }

    // Report back to Hostinger
    try {
        await axios.post(`${HOSTINGER_URL}/Files/api/update_message_status.php?key=${API_SECRET}`, {
            id: id,
            status: status
        });
        console.log(`[WhatsApp] Updated msg ID ${id} status to ${status} on Hostinger.`);
    } catch (err) {
        console.error(`[WhatsApp Poller] Failed to report status to Hostinger:`, err.message);
    }
}
// ==========================================
// Push Architecture (Bypassing AWS Firewall)
// ==========================================

async function pushStatusToHostinger() {
    try {
        let qrData = null;
        if (clientState === 'QR_READY' && currentQR) {
            qrData = await qrcodeImage.toDataURL(currentQR);
        }
        
        await axios.post(`${HOSTINGER_URL}/Files/api/update_whatsapp_status.php?key=${API_SECRET}`, {
            status: clientState,
            user: linkedUser,
            qrImage: qrData
        });
    } catch (err) {
        console.error(`[WhatsApp Push] Failed to push status to Hostinger:`, err.message);
    }
}

// Push status every 60 seconds to keep the dashboard alive
setInterval(pushStatusToHostinger, 60000);

// Start everything
initWhatsAppClient();
