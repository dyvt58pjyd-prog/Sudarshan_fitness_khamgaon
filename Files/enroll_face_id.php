<?php
require 'include/db_conn.php';

$user = isset($_GET['u']) ? mysqli_real_escape_string($con, $_GET['u']) : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($user) || empty($token)) {
    die("Invalid enrollment link.");
}

// Verify the token
$query = "SELECT securekey, Full_name FROM admin WHERE username='$user' AND role='owner'";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) !== 1) {
    die("Owner not found.");
}

$row = mysqli_fetch_assoc($result);
$expected_token = hash('sha256', $user . $row['securekey']);

if (!hash_equals($expected_token, $token)) {
    die("Invalid or expired token.");
}

// Generate a random challenge
$challenge = bin2hex(random_bytes(32));

// Save challenge to session to verify later in the API
session_start();
$_SESSION['webauthn_enroll_user'] = $user;
$_SESSION['webauthn_enroll_challenge'] = $challenge;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face ID Registration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0b0c10;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            padding: 20px;
        }
        .card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .btn {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 30px;
            width: 100%;
            transition: transform 0.1s;
        }
        .btn:active {
            transform: scale(0.95);
        }
        .status {
            margin-top: 20px;
            font-size: 14px;
            color: #94a3b8;
        }
    </script>
</head>
<body>
    <div class="card">
        <div class="icon">📱🔐</div>
        <h2>Setup Face ID</h2>
        <p>Welcome, <strong><?php echo htmlspecialchars($row['Full_name']); ?></strong>.</p>
        <p style="font-size: 14px; color: #cbd5e1;">Click the button below to register your device's native Face ID or Biometrics for quick login.</p>
        
        <button class="btn" id="registerBtn" onclick="registerFaceID()">Register Face ID</button>
        <div id="status" class="status"></div>
    </div>

    <script>
    // Helper to convert base64url to Uint8Array
    function base64urlToUint8Array(base64url) {
        const padding = '='.repeat((4 - base64url.length % 4) % 4);
        const base64 = (base64url + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Helper to convert ArrayBuffer to base64url
    function arrayBufferToBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    async function registerFaceID() {
        if (!window.PublicKeyCredential) {
            document.getElementById('status').innerText = "WebAuthn is not supported by your browser/device.";
            document.getElementById('status').style.color = "#ef4444";
            return;
        }

        const btn = document.getElementById('registerBtn');
        const status = document.getElementById('status');
        btn.disabled = true;
        btn.innerText = "Scanning...";

        try {
            // Configuration for WebAuthn Registration
            const challengeHex = "<?php echo $challenge; ?>";
            const challengeBuffer = new Uint8Array(challengeHex.match(/[\da-f]{2}/gi).map(h => parseInt(h, 16)));
            
            const userIdString = "<?php echo $user; ?>";
            const userIdBuffer = new TextEncoder().encode(userIdString);

            const publicKey = {
                challenge: challengeBuffer,
                rp: {
                    name: "Sudarshan Fitness App",
                },
                user: {
                    id: userIdBuffer,
                    name: userIdString,
                    displayName: "<?php echo htmlspecialchars($row['Full_name']); ?>"
                },
                pubKeyCredParams: [
                    { type: "public-key", alg: -7 },  // ES256
                    { type: "public-key", alg: -257 } // RS256
                ],
                authenticatorSelection: {
                    authenticatorAttachment: "platform", // Force native biometrics
                    userVerification: "required"         // Force biometric verification
                },
                timeout: 60000,
                attestation: "none"
            };

            // Call native Face ID / Biometrics prompt
            const credential = await navigator.credentials.create({ publicKey });

            // Prepare credential data to send to server
            const credentialData = {
                id: credential.id,
                rawId: arrayBufferToBase64Url(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: arrayBufferToBase64Url(credential.response.clientDataJSON),
                    attestationObject: arrayBufferToBase64Url(credential.response.attestationObject)
                }
            };

            // Send to server
            status.innerText = "Saving credential...";
            
            const response = await fetch('api/webauthn_register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(credentialData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                status.innerText = "Face ID Registered Successfully! You can now log in using Face ID.";
                status.style.color = "#22c55e";
                btn.innerText = "Done";
            } else {
                status.innerText = "Error saving credential: " + result.error;
                status.style.color = "#ef4444";
                btn.innerText = "Try Again";
                btn.disabled = false;
            }

        } catch (err) {
            console.error(err);
            status.innerText = "Registration failed: " + err.message;
            status.style.color = "#ef4444";
            btn.innerText = "Try Again";
            btn.disabled = false;
        }
    }
    </script>
</body>
</html>
