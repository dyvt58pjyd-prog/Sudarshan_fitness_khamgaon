#!/bin/bash
# Move to the project root directory
cd "$(dirname "$0")"

# If run from Desktop or elsewhere, resolve the actual project path
PROJECT_DIR="/Users/anurag.bawaskar/Downloads/Titan-Gym-master"
if [ -d "$PROJECT_DIR" ]; then
    cd "$PROJECT_DIR"
fi

echo "=========================================================="
echo "          Titan Gym - Unified Startup Script"
echo "=========================================================="
echo ""

# Load NVM (Node Version Manager) to get access to node/npx
export NVM_DIR="$HOME/.nvm"
if [ -s "$NVM_DIR/nvm.sh" ]; then
    echo "   Loading Node environment (NVM)..."
    \. "$NVM_DIR/nvm.sh"
else
    # Fallback to homebrew/nvm standard system paths
    export PATH="/usr/local/bin:/opt/homebrew/bin:$PATH"
fi

# Verify node/npx is available
if ! command -v npx &> /dev/null; then
    echo "   Error: Node.js and npx are required but not found in PATH."
    echo "   Please make sure Node.js is installed."
    echo "   Press any key to exit..."
    read -n 1
    exit 1
fi

# 1. Start MAMP
echo "1. Checking/Launching MAMP..."
if pgrep -x "MAMP" >/dev/null; then
    echo "   MAMP is already running."
else
    echo "   Launching MAMP application..."
    open -a MAMP
fi

# Wait for MAMP MySQL (port 8889) to be active
echo "   Waiting for MAMP MySQL (port 8889) to start..."
for i in {1..15}; do
    if lsof -i :8889 >/dev/null 2>&1; then
        echo "   MAMP MySQL is ready."
        break
    fi
    sleep 1
done

# 2. Stop any existing PHP server on port 8000
echo "2. Checking for any existing server running on port 8000..."
PID_8000=$(lsof -t -i :8000 2>/dev/null)
if [ ! -z "$PID_8000" ]; then
    echo "   Stopping process on port 8000 (PID: $PID_8000)..."
    kill -9 $PID_8000 2>/dev/null
    sleep 1
fi

# 3. Start the PHP built-in server in the background
echo "3. Starting PHP Built-in Server on port 8000..."
nohup php -S 0.0.0.0:8000 -t Files/ > /tmp/titangym_php.log 2>&1 &
PHP_PID=$!
sleep 1.5

if ps -p $PHP_PID > /dev/null; then
    echo "   PHP Server successfully started (PID: $PHP_PID)."
else
    echo "   Error: Failed to start PHP server. Check log at /tmp/titangym_php.log"
    echo "   Press any key to exit..."
    read -n 1
    exit 1
fi

# 4. Start the secure tunnel using localtunnel
echo "4. Starting secure direct hosting tunnel (sudarshanfitness.localtunnel.me)..."
echo "   Loading HTTPS URL for camera and remote device access..."
echo "----------------------------------------------------------"
echo "   App Link:    https://sudarshanfitness.localtunnel.me"
echo "   Local Link:  http://localhost:8000/"
echo "----------------------------------------------------------"
echo "Press Ctrl+C to stop the tunnel and background PHP server."
echo ""

# Ensure background PHP server is killed on script exit
trap "echo ''; echo 'Stopping PHP server (PID: $PHP_PID)...'; kill -9 $PHP_PID 2>/dev/null; exit" INT TERM EXIT

# Run localtunnel
npx localtunnel --port 8000 --subdomain sudarshanfitness
