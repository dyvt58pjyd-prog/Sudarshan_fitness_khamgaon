<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../include/google_wallet_helper.php';

if ($current_staff_role !== 'super_admin' && $current_staff_role !== 'owner') {
    http_response_code(403);
    die('Access Denied: Only Gym Owner or Super Admin can manage Google Wallet credentials.');
}

$gym = function_exists('get_gym_details') ? get_gym_details($con) : ['gym_name' => 'Sudarshan Fitness Khamgaon', 'gym_logo' => ''];
$gym_logo = resolve_gym_logo_url($gym, '../', false);
$flash = null;
$test_save_url = '';
$test_member_label = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'save'));
    if ($action === 'save') {
        $issuer_id = trim((string)($_POST['issuer_id'] ?? ''));
        $class_suffix = trim((string)($_POST['class_suffix'] ?? 'sudarshan_fitness_member_v1'));
        $base_url = trim((string)($_POST['base_url'] ?? 'https://sudarshanfitness.de'));
        $card_color = trim((string)($_POST['card_color'] ?? '#0f172a'));

        $sa_json_raw = '';
        if (isset($_FILES['sa_json_file']) && $_FILES['sa_json_file']['error'] === UPLOAD_ERR_OK) {
            $sa_json_raw = (string)@file_get_contents($_FILES['sa_json_file']['tmp_name']);
        } elseif (!empty($_POST['sa_json_text'])) {
            $sa_json_raw = trim((string)$_POST['sa_json_text']);
        }

        $res = save_google_wallet_config($issuer_id, $sa_json_raw, $class_suffix, $base_url, $card_color);
        $flash = ['ok' => !empty($res['ok']), 'msg' => $res['message']];
    } elseif ($action === 'test_pass') {
        $test_uid = trim((string)($_POST['test_uid'] ?? ''));
        $sync_api = !empty($_POST['sync_api']);
        if ($test_uid !== '' && is_google_wallet_configured()) {
            $url = build_google_wallet_save_url($con, $test_uid, [], $sync_api);
            if ($url) {
                $test_save_url = $url;
                $test_member_label = $test_uid;
                $flash = ['ok' => true, 'msg' => "Signed Google Wallet Save URL generated for member #$test_uid!" . ($sync_api ? ' (REST sync attempted)' : '')];
            } else {
                $flash = ['ok' => false, 'msg' => 'Failed to sign pass for selected member.'];
            }
        } else {
            $flash = ['ok' => false, 'msg' => 'Save valid Google Wallet credentials first and pick a member.'];
        }
    }
}

$cfg = get_google_wallet_config(true);

$members_sample = [];
$q_mem = @mysqli_query($con, "SELECT userid, username, mobile FROM users ORDER BY CAST(userid AS UNSIGNED) DESC, userid DESC LIMIT 50");
if ($q_mem) {
    while ($mr = mysqli_fetch_assoc($q_mem)) {
        $members_sample[] = $mr;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Wallet Pass Setup | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Inter', -apple-system, sans-serif; }
        body { margin: 0; background: #090d16; color: #f8fafc; padding: 20px; min-height: 100vh; }
        .wrap { max-width: 960px; margin: 0 auto; }
        .top {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
            background: #111827; border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 14px 20px; margin-bottom: 18px;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #4285F4; background: #fff; }
        .btn-nav {
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #f8fafc;
            padding: 8px 14px; border-radius: 10px; text-decoration: none; font-size: 12.5px; font-weight: 700;
        }
        .grid { display: grid; grid-template-columns: 1.25fr 0.9fr; gap: 18px; }
        @media (max-width: 820px) { .grid { grid-template-columns: 1fr; } }
        .card { background: #111827; border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 20px; }
        h2 { margin: 0 0 12px; font-size: 16px; font-weight: 800; }
        label { display: block; font-size: 11px; color: #94a3b8; font-weight: 800; text-transform: uppercase; margin: 12px 0 6px; }
        input[type="text"], input[type="url"], select, textarea {
            width: 100%; background: #0b1220; border: 1px solid #334155; color: #f8fafc; border-radius: 10px;
            padding: 10px 12px; font-size: 13px;
        }
        textarea { min-height: 110px; font-family: monospace; font-size: 11.5px; }
        .btn-primary {
            width: 100%; margin-top: 16px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none;
            padding: 12px; border-radius: 12px; font-weight: 800; font-size: 13.5px; cursor: pointer;
        }
        .status-pill { display: inline-block; padding: 5px 12px; border-radius: 999px; font-size: 11.5px; font-weight: 800; margin-bottom: 12px; }
        .st-ok { background: rgba(16,185,129,0.16); color: #34d399; border: 1px solid rgba(16,185,129,0.4); }
        .st-warn { background: rgba(245,158,11,0.16); color: #fbbf24; border: 1px solid rgba(245,158,11,0.4); }
        .flash { padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 700; }
        .steps li { margin-bottom: 10px; font-size: 12.5px; color: #cbd5e1; line-height: 1.55; }
        .wallet-cta {
            display: inline-block; margin-top: 12px; background: #020617; color: #fff; text-decoration: none;
            padding: 12px 24px; border-radius: 999px; font-weight: 800; font-size: 13.5px; border: 1.5px solid #e2e8f0;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div class="brand">
            <img src="<?php echo htmlspecialchars($gym_logo); ?>" alt="Logo">
            <div>
                <div style="font-size:17px;font-weight:900;">Native Google Wallet Pass Setup</div>
                <div style="font-size:12px;color:#94a3b8;">Embed official Add to Google Wallet passes inside Registration &amp; Renewal emails</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="index.php" class="btn-nav">&larr; Back to Wallet Counter</a>
            <a href="../dashboard/admin/index.php" class="btn-nav">⚙️ Admin</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="flash" style="background:<?php echo $flash['ok'] ? 'rgba(16,185,129,0.16)' : 'rgba(239,68,68,0.16)'; ?>;border:1px solid <?php echo $flash['ok'] ? '#10b981' : '#ef4444'; ?>;color:<?php echo $flash['ok'] ? '#6ee7b7' : '#fca5a5'; ?>;">
            <?php echo htmlspecialchars($flash['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2>1. Google Wallet Credentials</h2>
            <?php if ($cfg['configured']): ?>
                <div class="status-pill st-ok">✅ Connected &amp; Ready (Emails automatically include Google Wallet Pass)</div>
            <?php else: ?>
                <div class="status-pill st-warn">⚠️ Setup Required: <?php echo htmlspecialchars($cfg['error']); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <label>Google Wallet Issuer ID (Numeric)</label>
                <input type="text" name="issuer_id" required placeholder="e.g. 3388000000012345678" value="<?php echo htmlspecialchars($cfg['issuer_id']); ?>">

                <label>Service Account JSON File (.json from Google Cloud)</label>
                <input type="file" name="sa_json_file" accept=".json,application/json" style="font-size:12.5px;color:#cbd5e1;">
                <?php if ($cfg['client_email'] !== ''): ?>
                    <div style="font-size:11.5px;color:#34d399;margin-top:4px;">Current Service Account: <strong><?php echo htmlspecialchars($cfg['client_email']); ?></strong> (leave blank to keep existing private key)</div>
                <?php endif; ?>

                <label>Or Paste Service Account JSON Content</label>
                <textarea name="sa_json_text" placeholder='{"type": "service_account", "project_id": "...", "private_key": "...", "client_email": "..."}'></textarea>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label>Pass Class Suffix</label>
                        <input type="text" name="class_suffix" value="<?php echo htmlspecialchars($cfg['class_suffix']); ?>">
                    </div>
                    <div>
                        <label>Card Background Hex</label>
                        <input type="text" name="card_color" value="<?php echo htmlspecialchars($cfg['card_color']); ?>">
                    </div>
                </div>

                <label>Public HTTPS Base URL (for Pass Logo &amp; Member Photo)</label>
                <input type="url" name="base_url" value="<?php echo htmlspecialchars($cfg['base_url']); ?>">

                <button type="submit" class="btn-primary">💾 Save &amp; Verify Google Wallet Keys</button>
            </form>
        </div>

        <div style="display:grid;gap:18px;align-content:start;">
            <div class="card">
                <h2>2. Quick Setup Checklist</h2>
                <ol class="steps" style="padding-left:18px;margin:0;">
                    <li>Open <strong>Google Cloud Console</strong> &rarr; Enable <strong>Google Wallet API</strong> &rarr; Create a <strong>Service Account</strong> &rarr; Create &amp; download a <strong>JSON Key</strong>.</li>
                    <li>Open <a href="https://pay.google.com/business/console/" target="_blank" style="color:#60a5fa;">Google Pay &amp; Wallet Console</a> &rarr; Copy your numeric <strong>Issuer ID</strong>.</li>
                    <li>In Wallet Console &rarr; <strong>Users</strong> &rarr; Invite your Service Account's <code>client_email</code> with Admin/Developer permission.</li>
                    <li>While in Demo Mode, add test emails in Console or click <strong>Request Publishing Access</strong> (~1–2 days approval) so all members save passes without <code>[TEST ONLY]</code>.</li>
                </ol>
            </div>

            <div class="card">
                <h2>3. Test Native Google Wallet Link</h2>
                <p style="font-size:12px;color:#94a3b8;margin:0 0 10px;">
                    Includes Gym Logo, Name, Member ID, Mobile, Start Date, Expiry Date, Member Photo &amp; Gate QR (<code>SFG1</code>).
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_pass">
                    <label>Select Member</label>
                    <select name="test_uid">
                        <?php foreach ($members_sample as $ms): ?>
                            <option value="<?php echo htmlspecialchars($ms['userid']); ?>">#<?php echo htmlspecialchars($ms['userid']); ?> &mdash; <?php echo htmlspecialchars($ms['username']); ?> (<?php echo htmlspecialchars($ms['mobile'] ?? ''); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <label style="display:flex;align-items:center;gap:8px;text-transform:none;font-size:12px;color:#cbd5e1;margin-top:10px;">
                        <input type="checkbox" name="sync_api" value="1"> Also test REST Class/Object PATCH sync
                    </label>
                    <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#059669,#047857);">🧪 Generate Test Save Link</button>
                </form>

                <?php if ($test_save_url !== ''): ?>
                    <div style="margin-top:14px;padding:12px;border-radius:12px;background:#0b1220;border:1px solid #334155;text-align:center;">
                        <div style="font-size:11.5px;color:#34d399;font-weight:700;">Ready for Member #<?php echo htmlspecialchars($test_member_label); ?></div>
                        <a href="<?php echo htmlspecialchars($test_save_url); ?>" target="_blank" class="wallet-cta">
                            Add to Google Wallet &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
