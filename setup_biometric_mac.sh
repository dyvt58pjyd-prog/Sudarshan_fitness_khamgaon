#!/bin/bash
# Move to the script's directory
cd "$(dirname "$0")"

echo "=========================================================="
echo "          Titan Gym - Mac Biometric setup"
echo "=========================================================="
echo ""

# Ensure biometric_service directory is found
if [ ! -d "biometric_service" ]; then
    echo "Error: biometric_service folder not found. Make sure you are in the project root."
    exit 1
fi

# Copy plist files to ~/Library/LaunchAgents/
echo "1. Copying LaunchAgents configuration to library..."
mkdir -p ~/Library/LaunchAgents
cp biometric_service/com.titangym.biometricsync.plist ~/Library/LaunchAgents/
cp biometric_service/com.titangym.localadms.plist ~/Library/LaunchAgents/

# Unload any existing copies to avoid duplicate process conflicts
echo "2. Unloading existing services if loaded..."
launchctl unload ~/Library/LaunchAgents/com.titangym.biometricsync.plist 2>/dev/null
launchctl unload ~/Library/LaunchAgents/com.titangym.localadms.plist 2>/dev/null

# Load the LaunchAgents to run in the background persistently
echo "3. Starting services in the background..."
launchctl load ~/Library/LaunchAgents/com.titangym.biometricsync.plist
launchctl load ~/Library/LaunchAgents/com.titangym.localadms.plist

echo ""
echo "----------------------------------------------------------"
echo "🎉 Setup complete! Services are now running in the background."
echo "   - Biometric Sync Agent is running (logs/status are in biometric_service/out.log)"
echo "   - Local ADMS Push Proxy is active on port 8080 (logs are in biometric_service/adms_out.log)"
echo "----------------------------------------------------------"
echo ""
