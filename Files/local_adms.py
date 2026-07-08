import http.server
import socketserver
import urllib.parse
import requests
import json
import socket
from datetime import datetime

PORT = 8080
CLOUD_API_LOG = "https://sudarshanfitness.de/api/log_attendance.php"

# Get local IP
hostname = socket.gethostname()
try:
    local_ip = socket.gethostbyname(hostname)
except:
    local_ip = "127.0.0.1"
    
s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
try:
    # doesn't even have to be reachable
    s.connect(('10.255.255.255', 1))
    local_ip = s.getsockname()[0]
except Exception:
    pass
finally:
    s.close()


class ADMSHandler(http.server.BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        # Suppress default logging
        pass

    def _send_response(self, text="OK"):
        self.send_response(200)
        self.send_header('Content-type', 'text/plain')
        self.end_headers()
        self.wfile.write(text.encode('utf-8'))

    def do_GET(self):
        if "/pub/chat" not in self.path:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] GET request received: {self.path}")
        self._send_response()

    def do_POST(self):
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length).decode('utf-8')
        
        print(f"[{datetime.now().strftime('%H:%M:%S')}] PUNCH RECEIVED from Machine!")
        
        # Parse ADMS attendance format (usually plain text table)
        # Format example: 1\t2026-07-01 15:30:00\t1...
        lines = post_data.split("\n")
        punches = []
        for line in lines:
            parts = line.split("\t")
            if len(parts) >= 2:
                bio_id = parts[0].strip()
                timestamp = parts[1].strip()
                if bio_id and timestamp:
                    punches.append({"bio_id": bio_id, "timestamp": timestamp})
        
        if not punches:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] No valid punches found in payload.")
            self._send_response()
            return
            
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Found {len(punches)} punches. Forwarding to Cloud...")
        
        for p in punches:
            payload = {
                "biometric_id": p["bio_id"],
                "timestamp": p["timestamp"]
            }
            try:
                headers = {
                    'Content-Type': 'application/json',
                    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                }
                res = requests.post(CLOUD_API_LOG, json=payload, headers=headers, timeout=5)
                if res.status_code == 200:
                    data = res.json()
                    name = data.get("name", f"ID {p['bio_id']}")
                    print(f"  -> SUCCESS! Synced {name} ({p['timestamp']}) to live website!")
                else:
                    print(f"  -> FAILED to sync to Cloud. Code: {res.status_code}")
            except Exception as e:
                print(f"  -> NETWORK ERROR pushing to Cloud: {e}")
                
        self._send_response()

print("================================================================")
print("           TITAN GYM LOCAL ADMS PROXY SERVER                    ")
print("================================================================")
print(f"Your Mac's Local IP Address: {local_ip}")
print("----------------------------------------------------------------")
print("INSTRUCTIONS FOR BIOMETRIC MACHINE:")
print("1. Go to Menu -> Comm. -> Cloud Server Setting")
print(f"2. Set 'Enable Domain Name' to OFF / NO")
print(f"3. Set 'Server Address' to exactly: {local_ip}")
print(f"4. Set 'Server Port' to: {PORT}")
print("5. Restart the machine and punch your finger!")
print("================================================================\n")
print(f"Listening for machine punches on port {PORT}...")

with socketserver.TCPServer(("", PORT), ADMSHandler) as httpd:
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nShutting down server.")
