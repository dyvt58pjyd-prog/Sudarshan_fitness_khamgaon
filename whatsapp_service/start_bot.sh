#!/bin/bash
# Start WhatsApp Bot locally in the background
cd "$(dirname "$0")"

echo "Installing Node.js dependencies..."
npm install

echo "Starting WhatsApp Bot using PM2 (or node if PM2 is not installed)..."
if command -v pm2 &> /dev/null
then
    pm2 start server.js --name "sudarshan-whatsapp-bot"
    echo "Bot started with PM2."
else
    # Fallback to nohup
    nohup node server.js > out.log 2> err.log &
    echo "Bot started with nohup in background. PID: $!"
fi

echo "Done! Please check out.log or PM2 logs for the QR Code to scan."
