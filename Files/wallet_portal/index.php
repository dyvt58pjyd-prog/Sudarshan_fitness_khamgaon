<?php
require_once __DIR__ . '/auth_check.php';

ensure_wallet_pass_logs_table($con);
$gym = get_gym_details($con);
$gym_logo = resolve_gym_logo_url($gym, '../', false);

date_default_timezone_set("Asia/Calcutta");
$today_str = date('Y-m-d');

// Load trainers map
$trainers_map = [];
$q_tr = mysqli_query($con, "SELECT username, Full_name FROM admin");
if ($q_tr) {
    while ($tr = mysqli_fetch_assoc($q_tr)) {
        $trainers_map[$tr['username']] = $tr['Full_name'] ?: $tr['username'];
    }
}

// Load batches map
$batches_map = [];
$q_bt = mysqli_query($con, "SELECT id, batch_name, start_time, end_time FROM biometric_batches");
if ($q_bt) {
    while ($bt = mysqli_fetch_assoc($q_bt)) {
        $batches_map[intval($bt['id'])] = $bt['batch_name'] . ' (' . date('h:i A', strtotime($bt['start_time'])) . ' - ' . date('h:i A', strtotime($bt['end_time'])) . ')';
    }
}

// Issuance aggregate per uid
$issued_summary = [];
$q_iss = mysqli_query($con, "SELECT uid, COUNT(*) as issue_cnt, MAX(created_at) as last_issued, SUBSTRING_INDEX(GROUP_CONCAT(platform ORDER BY id DESC), ',', 1) as last_platform FROM wallet_pass_logs GROUP BY uid");
if ($q_iss) {
    while ($ir = mysqli_fetch_assoc($q_iss)) {
        $issued_summary[$ir['uid']] = $ir;
    }
}

// Fetch latest enrollment per member cleanly (ordered by expiry DESC, et_id DESC)
$db_sync_error = '';
$latest_plans = [];
if (!$con) {
    $db_sync_error = 'Database connection failed: ' . mysqli_connect_error();
} else {
    $q_enr = mysqli_query($con, "SELECT e.uid, e.expire, p.planName FROM enrolls_to e LEFT JOIN plan p ON e.pid = p.pid ORDER BY e.expire DESC, e.et_id DESC");
    if ($q_enr) {
        while ($er = mysqli_fetch_assoc($q_enr)) {
            if (!isset($latest_plans[$er['uid']])) {
                $latest_plans[$er['uid']] = $er;
            }
        }
    }
}

// Fetch lightweight member columns (never load photo_base64 LONGTEXT blobs in bulk)
$members = [];
$stats = ['total' => 0, 'active' => 0, 'issued_today' => 0, 'apple_count' => 0, 'android_count' => 0];
$files_root_dir = realpath(__DIR__ . '/..');

$q_m = false;
if ($con) {
    $q_m = @mysqli_query($con, "SELECT u.userid, u.username, u.mobile, u.email, u.joining_date, u.trainer_id, u.biometric_batch, u.partner_uid, u.photo, u.member_photo FROM users u WHERE u.userid != 'PENDING' ORDER BY CAST(u.userid AS UNSIGNED) DESC, u.username ASC");
    if (!$q_m) {
        $q_m = mysqli_query($con, "SELECT u.userid, u.username, u.mobile, u.email, u.joining_date, u.trainer_id, u.biometric_batch, u.partner_uid FROM users u WHERE u.userid != 'PENDING' ORDER BY CAST(u.userid AS UNSIGNED) DESC, u.username ASC");
    }
}
if (!$q_m && $con && $db_sync_error === '') {
    $db_sync_error = 'Member sync query failed: ' . mysqli_error($con);
}

$resolve_light_avatar = function($row) use ($files_root_dir) {
    $safe_uid = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($row['userid'] ?? ''));
    if ($files_root_dir && $safe_uid !== '' && file_exists($files_root_dir . '/uploads/member_photos/mem_' . $safe_uid . '.jpg')) {
        return '../uploads/member_photos/mem_' . $safe_uid . '.jpg';
    }
    foreach (['member_photo', 'photo'] as $col) {
        if (!empty($row[$col]) && $files_root_dir) {
            $rel = ltrim(str_replace(['../', './'], '', $row[$col]), '/');
            if (file_exists($files_root_dir . '/' . $rel)) {
                return '../' . str_replace(' ', '%20', $rel);
            }
        }
    }
    return 'api/member_avatar.php?uid=' . urlencode((string)($row['userid'] ?? ''));
};

$pass_base_url = preg_replace('/\?.*$/', '', build_signed_wallet_pass_url($con, 'UID_PLACEHOLDER', 0, false, 'universal'));

if ($q_m) {
    while ($row = mysqli_fetch_assoc($q_m)) {
        $stats['total']++;
        $uid_val = $row['userid'];
        $plan_row = $latest_plans[$uid_val] ?? null;
        $expire_raw = $plan_row['expire'] ?? '';
        $plan_name_val = !empty($plan_row['planName']) ? $plan_row['planName'] : 'No Plan';
        $is_active = false;
        $days_left = 0;
        $status_key = 'expired';
        $expire_fmt = 'No Plan';

        if (!empty($expire_raw)) {
            $expire_fmt = date('d M Y', strtotime($expire_raw));
            $diff = strtotime($expire_raw) - strtotime($today_str);
            $days_left = intval(ceil($diff / 86400));
            if ($diff >= 0) {
                $is_active = true;
                $status_key = ($days_left <= 7) ? 'expiring' : 'active';
            }
        }

        // Check couple partner plan if member's own plan row is absent/expired
        if (!$is_active && !empty($row['partner_uid']) && isset($latest_plans[$row['partner_uid']])) {
            $p_row = $latest_plans[$row['partner_uid']];
            $p_exp = $p_row['expire'] ?? '';
            if (!empty($p_exp)) {
                $p_diff = strtotime($p_exp) - strtotime($today_str);
                if ($p_diff >= 0) {
                    $is_active = true;
                    $expire_raw = $p_exp;
                    $expire_fmt = date('d M Y', strtotime($p_exp));
                    $days_left = intval(ceil($p_diff / 86400));
                    $status_key = ($days_left <= 7) ? 'expiring' : 'active';
                    if (!empty($p_row['planName'])) {
                        $plan_name_val = $p_row['planName'] . ' (Couple)';
                    }
                }
            }
        }

        if ($is_active) {
            $stats['active']++;
        }

        $iss = $issued_summary[$uid_val] ?? null;
        $batch_id = isset($row['biometric_batch']) && $row['biometric_batch'] !== '' ? intval($row['biometric_batch']) : null;
        $batch_label = ($batch_id !== null && isset($batches_map[$batch_id])) ? $batches_map[$batch_id] : 'General Batch (06:00 AM - 10:00 PM)';
        $trainer_label = (!empty($row['trainer_id']) && isset($trainers_map[$row['trainer_id']])) ? $trainers_map[$row['trainer_id']] : 'General Floor Coach';

        $members[] = [
            'userid'        => $uid_val,
            'username'      => $row['username'] ?: 'Member',
            'mobile'        => $row['mobile'] ?? '',
            'email'         => $row['email'] ?? '',
            'joining_date'  => !empty($row['joining_date']) ? date('d M Y', strtotime($row['joining_date'])) : 'N/A',
            'plan_name'     => $plan_name_val,
            'expire_fmt'    => $expire_fmt,
            'expire_iso'    => $expire_raw,
            'days_left'     => $days_left,
            'is_active'     => $is_active,
            'status_key'    => $status_key,
            'trainer_name'  => $trainer_label,
            'batch_name'    => $batch_label,
            'photo_url'     => $resolve_light_avatar($row),
            'rotating_qr'   => generate_rotating_qr_payload($con, $uid_val),
            'sig'           => sign_pass_url_signature($con, $uid_val, 0),
            'issue_cnt'     => $iss ? intval($iss['issue_cnt']) : 0,
            'last_platform' => $iss ? $iss['last_platform'] : ''
        ];
    }
}

$q_agg = $con ? @mysqli_query($con, "SELECT SUM(DATE(created_at) = '$today_str') AS issued_today, SUM(platform = 'apple') AS apple_count, SUM(platform = 'android') AS android_count FROM wallet_pass_logs") : false;
if ($q_agg && $ag = mysqli_fetch_assoc($q_agg)) {
    $stats['issued_today']  = intval($ag['issued_today'] ?? 0);
    $stats['apple_count']   = intval($ag['apple_count'] ?? 0);
    $stats['android_count'] = intval($ag['android_count'] ?? 0);
}

$recent_logs = [];
$q_logs = mysqli_query($con, "SELECT l.*, u.username FROM wallet_pass_logs l LEFT JOIN users u ON l.uid = u.userid ORDER BY l.id DESC LIMIT 20");
if ($q_logs) {
    while ($lr = mysqli_fetch_assoc($q_logs)) {
        $recent_logs[] = $lr;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Pass Issuance Portal | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($gym_logo); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js" defer></script>
    <style>
        :root {
            --bg: #070b14;
            --card: rgba(17, 24, 39, 0.92);
            --border: rgba(255, 255, 255, 0.08);
            --accent: #ff6b00;
            --green: #10b981;
            --blue: #38bdf8;
            --red: #ef4444;
            --text: #f8fafc;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            margin: 0; background: radial-gradient(circle at top left, rgba(255,107,0,0.12), transparent 35%), var(--bg);
            color: var(--text); min-height: 100vh; padding: 18px;
        }
        .shell { max-width: 1420px; margin: 0 auto; }

        .topbar {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;
            background: var(--card); border: 1px solid var(--border); border-radius: 18px; padding: 14px 20px; margin-bottom: 18px;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); background: #fff; }
        .brand h1 { margin: 0; font-size: 18px; font-weight: 900; }
        .brand .sub { font-size: 11.5px; color: var(--muted); font-weight: 600; margin-top: 2px; }

        .nav-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .pill { background: rgba(56,189,248,0.12); border: 1px solid rgba(56,189,248,0.3); color: var(--blue); padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .top-btn {
            background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text); text-decoration: none;
            padding: 8px 14px; border-radius: 10px; font-size: 12.5px; font-weight: 700; cursor: pointer;
        }
        .top-btn:hover { border-color: var(--accent); }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 15px 18px; }
        .stat-lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.7px; color: var(--muted); font-weight: 700; }
        .stat-val { font-size: 24px; font-weight: 900; margin-top: 4px; }

        .main-grid { display: grid; grid-template-columns: 1.05fr 1.15fr; gap: 18px; align-items: start; }
        @media (max-width: 1020px) { .main-grid { grid-template-columns: 1fr; } }

        .panel { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 20px; }
        .panel-title { font-size: 15px; font-weight: 800; margin: 0 0 12px; display: flex; justify-content: space-between; align-items: center; }

        .search-bar {
            width: 100%; padding: 11px 14px; border-radius: 12px; border: 1px solid #334155; background: #0b1220;
            color: #fff; font-size: 13.5px; margin-bottom: 10px; outline: none;
        }
        .search-bar:focus { border-color: var(--accent); }

        .filter-row { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
        .f-btn {
            background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--muted);
            padding: 6px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; cursor: pointer;
        }
        .f-btn.active { background: rgba(255,107,0,0.18); border-color: var(--accent); color: #fff; }

        .bulk-bar {
            display: none; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;
            background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.35); border-radius: 12px;
            padding: 9px 12px; margin-bottom: 10px; font-size: 12px; font-weight: 700;
        }

        .member-list { max-height: 580px; overflow-y: auto; display: grid; gap: 8px; padding-right: 4px; }
        .m-row {
            display: flex; align-items: center; gap: 11px; padding: 11px 12px; border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.06); background: rgba(15, 23, 42, 0.7); cursor: pointer; transition: border-color .15s;
        }
        .m-row:hover { border-color: rgba(255,107,0,0.5); }
        .m-row.selected { border-color: var(--accent); background: rgba(255,107,0,0.12); }
        .m-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 1.5px solid #475569; background: #0f172a; flex-shrink: 0; }
        .m-meta { flex: 1; min-width: 0; }
        .m-name { font-size: 13.5px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .m-sub { font-size: 11px; color: var(--muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge { padding: 3px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; white-space: nowrap; }
        .badge-active { background: rgba(16,185,129,0.15); color: #34d399; }
        .badge-expiring { background: rgba(245,158,11,0.18); color: #fbbf24; }
        .badge-expired { background: rgba(239,68,68,0.15); color: #f87171; }

        .plat-tabs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
        .plat-tab {
            padding: 10px 8px; border-radius: 12px; border: 1px solid var(--border); background: rgba(255,255,255,0.04);
            color: var(--muted); font-size: 12px; font-weight: 800; text-align: center; cursor: pointer;
        }
        .plat-tab.active-apple { background: linear-gradient(135deg, #1e293b, #020617); border-color: #e2e8f0; color: #fff; }
        .plat-tab.active-android { background: rgba(16,185,129,0.18); border-color: #10b981; color: #6ee7b7; }
        .plat-tab.active-universal { background: rgba(56,189,248,0.18); border-color: #38bdf8; color: #7dd3fc; }

        .note-banner {
            background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.25); color: #bae6fd;
            font-size: 11.5px; line-height: 1.5; border-radius: 12px; padding: 10px 12px; margin-bottom: 14px;
        }

        .studio-split { display: grid; grid-template-columns: 300px 1fr; gap: 18px; align-items: start; }
        @media (max-width: 760px) { .studio-split { grid-template-columns: 1fr; } }

        /* Live Preview Wallet Card */
        .preview-card {
            border-radius: 22px; overflow: hidden; border: 2px solid #10b981;
            background: linear-gradient(160deg, #1e293b 0%, #0f172a 70%, #020617 100%);
            box-shadow: 0 18px 40px rgba(0,0,0,0.55);
        }
        .preview-card.theme-apple { border-color: #e2e8f0; }
        .preview-card.theme-expired { border-color: #ef4444; }
        .pv-head {
            background: linear-gradient(135deg, #065f46, #047857); padding: 12px 14px;
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
        }
        .preview-card.theme-apple .pv-head { background: linear-gradient(135deg, #111827, #334155); }
        .preview-card.theme-expired .pv-head { background: linear-gradient(135deg, #991b1b, #dc2626); }
        .pv-head-left { display: flex; align-items: center; gap: 9px; min-width: 0; }
        .pv-head-left img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,215,0,0.5); background: #fff; }
        .pv-gym-name { font-size: 11px; font-weight: 900; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pv-plat-tag { font-size: 9px; color: rgba(255,255,255,0.85); font-weight: 700; }
        .pv-body { padding: 14px; text-align: center; }
        .pv-avatar { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #10b981; background: #0f172a; }
        .pv-name { font-size: 16px; font-weight: 900; margin-top: 6px; }
        .pv-id { font-size: 11px; color: var(--blue); font-weight: 800; margin-top: 2px; }
        .pv-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px 0; text-align: left;
            background: rgba(0,0,0,0.35); border-radius: 12px; padding: 10px; font-size: 11px;
        }
        .pv-lbl { font-size: 8.5px; color: var(--muted); text-transform: uppercase; font-weight: 800; }
        .pv-val { font-weight: 800; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pv-qr-box { background: #fff; padding: 8px; border-radius: 12px; display: inline-block; }
        .pv-foot { padding: 9px 12px; background: rgba(0,0,0,0.4); font-size: 9.5px; color: var(--muted); text-align: left; }

        .controls-stack { display: grid; gap: 11px; }
        .ctrl-label { font-size: 11px; color: var(--muted); font-weight: 800; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .ctrl-input { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #334155; background: #0b1220; color: #fff; font-size: 13px; }
        .issue-btn {
            width: 100%; padding: 12px 14px; border-radius: 12px; border: none; color: #fff; font-size: 13px; font-weight: 800;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: transform .15s;
        }
        .issue-btn:hover { transform: translateY(-1px); }
        .issue-btn:disabled { opacity: 0.6; cursor: wait; transform: none; }
        .toast { display: none; padding: 11px 13px; border-radius: 12px; font-size: 12.5px; font-weight: 700; }

        .logs-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12.5px; }
        .logs-table th, .logs-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .logs-table th { color: var(--muted); font-size: 11px; text-transform: uppercase; }

        .modal-bg {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);
            z-index: 9999; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-card {
            background: #1e293b; border: 2px solid var(--accent); border-radius: 24px; padding: 26px;
            max-width: 410px; width: 100%; text-align: center;
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="topbar">
        <div class="brand">
            <img src="<?php echo htmlspecialchars($gym_logo); ?>" alt="Gym Logo">
            <div>
                <h1><?php echo htmlspecialchars($gym['gym_name']); ?> — Wallet Pass Counter</h1>
                <div class="sub">Issue Official Signed Apple iPhone &amp; Android Gym Wallet Passes</div>
            </div>
        </div>
        <div class="nav-actions">
            <span class="pill">👤 <?php echo htmlspecialchars($current_staff_name); ?> (<?php echo htmlspecialchars(strtoupper($current_staff_role)); ?>)</span>
            <button type="button" class="top-btn" onclick="location.reload()">↻ Sync Members</button>
            <a href="../desk/" class="top-btn">⚡ Desk Portal</a>
            <?php if ($current_staff_role === 'super_admin' || $current_staff_role === 'owner'): ?>
                <a href="google_setup.php" class="top-btn" style="border-color:#4285F4;color:#93c5fd;">💳 Google Wallet Setup</a>
                <a href="../dashboard/admin/index.php" class="top-btn">⚙️ Admin Hub</a>
            <?php endif; ?>
            <a href="logout.php" class="top-btn" style="color:#f87171;">Logout</a>
        </div>
    </div>

    <?php if ($db_sync_error !== ''): ?>
        <div style="background:rgba(239,68,68,0.16);border:1px solid rgba(239,68,68,0.45);color:#fca5a5;padding:12px 16px;border-radius:14px;margin-bottom:16px;font-size:13px;font-weight:700;">
            ⚠️ Member Sync Warning: <?php echo htmlspecialchars($db_sync_error); ?>
        </div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-card"><div class="stat-lbl">Active / Total Members</div><div class="stat-val" style="color:#34d399;"><?php echo $stats['active']; ?> <span style="font-size:14px;color:var(--muted);font-weight:700;">/ <?php echo $stats['total']; ?></span></div></div>
        <div class="stat-card"><div class="stat-lbl">Passes Issued Today</div><div class="stat-val" style="color:#fbbf24;" id="statToday"><?php echo $stats['issued_today']; ?></div></div>
        <div class="stat-card"><div class="stat-lbl">Apple iPhone Passes</div><div class="stat-val" style="color:#e2e8f0;" id="statApple"><?php echo $stats['apple_count']; ?></div></div>
        <div class="stat-card"><div class="stat-lbl">Android Passes</div><div class="stat-val" style="color:#38bdf8;" id="statAndroid"><?php echo $stats['android_count']; ?></div></div>
    </div>

    <div class="main-grid">
        <!-- LEFT: MEMBER DIRECTORY -->
        <div class="panel">
            <div class="panel-title">
                <span>👥 Select Member to Issue Pass</span>
                <span style="font-size:12px;color:var(--muted);" id="visibleCountLabel"><?php echo count($members); ?> members</span>
            </div>

            <input type="text" id="memberSearchInput" class="search-bar" placeholder="Search by Name, Member ID, Mobile, or Email...">

            <div class="filter-row">
                <button type="button" class="f-btn active" onclick="setStatusFilter('all', this)">All</button>
                <button type="button" class="f-btn" onclick="setStatusFilter('active', this)">🟢 Active</button>
                <button type="button" class="f-btn" onclick="setStatusFilter('expiring', this)">⚠️ Expiring Soon</button>
                <button type="button" class="f-btn" onclick="setStatusFilter('unissued', this)">🆕 Not Issued Yet</button>
                <button type="button" class="f-btn" onclick="setStatusFilter('expired', this)">🔴 Expired</button>
            </div>

            <div class="bulk-bar" id="bulkBar">
                <span id="bulkCountLabel">0 selected</span>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" class="f-btn active" onclick="bulkIssue('whatsapp')">🟢 Bulk WhatsApp</button>
                    <button type="button" class="f-btn active" onclick="bulkIssue('email')">📧 Bulk Email</button>
                    <button type="button" class="f-btn" onclick="clearSelection()">Clear</button>
                </div>
            </div>

            <div class="member-list" id="memberListContainer"></div>
        </div>

        <!-- RIGHT: APPLE / ANDROID WALLET PASS STUDIO -->
        <div class="panel">
            <div class="panel-title">
                <span>🎟️ Apple &amp; Android Pass Studio</span>
                <span id="selectedMemberBadge" style="font-size:12px;color:var(--blue);font-weight:700;">Select a member</span>
            </div>

            <div class="plat-tabs">
                <button type="button" class="plat-tab active-apple" id="tabApple" onclick="setPlatform('apple')">🍎 Apple iPhone</button>
                <button type="button" class="plat-tab" id="tabAndroid" onclick="setPlatform('android')">🤖 Android</button>
                <button type="button" class="plat-tab" id="tabUniversal" onclick="setPlatform('universal')">🌐 Universal</button>
            </div>

            <div class="note-banner" id="platformNotice">
                <strong>🍎 Apple iPhone Smart Wallet Pass:</strong> Uses an HMAC-signed Web-Clip &amp; HD Pass Card + 60s rotating gate QR. Members open it in Safari -> <em>Share -> Add to Home Screen</em> (works on every iPhone without needing a paid Apple Developer Pass Certificate).
            </div>

            <div class="studio-split">
                <!-- Live Card Preview -->
                <div class="preview-card theme-apple" id="previewCard">
                    <div class="pv-head">
                        <div class="pv-head-left">
                            <img src="<?php echo htmlspecialchars($gym_logo); ?>" alt="Logo">
                            <div style="min-width:0;">
                                <div class="pv-gym-name"><?php echo htmlspecialchars($gym['gym_name']); ?></div>
                                <div class="pv-plat-tag" id="pvPlatTag">🍎 Apple iPhone Smart Pass</div>
                            </div>
                        </div>
                        <span class="badge badge-active" id="pvStatusPill">ACTIVE</span>
                    </div>
                    <div class="pv-body">
                        <img src="../images/default_avatar.png" alt="Avatar" class="pv-avatar" id="pvAvatar">
                        <div class="pv-name" id="pvName">Select Member</div>
                        <div class="pv-id" id="pvUid">#---</div>
                        <div class="pv-grid">
                            <div><div class="pv-lbl">Plan</div><div class="pv-val" id="pvPlan" style="color:#38bdf8;">-</div></div>
                            <div><div class="pv-lbl">Valid Till</div><div class="pv-val" id="pvExpire">-</div></div>
                            <div><div class="pv-lbl">Batch</div><div class="pv-val" id="pvBatch">-</div></div>
                            <div><div class="pv-lbl">Coach</div><div class="pv-val" id="pvCoach">-</div></div>
                        </div>
                        <div class="pv-qr-box">
                            <canvas id="previewQrCanvas"></canvas>
                        </div>
                        <div style="font-size:10px;color:#34d399;font-weight:700;margin-top:6px;">🔒 Rotating 60s Gate QR + 1-Tap Check-In</div>
                    </div>
                    <div class="pv-foot">
                        <div>📍 <?php echo htmlspecialchars($gym['gym_address']); ?></div>
                        <div>📞 <?php echo htmlspecialchars($gym['gym_contact']); ?></div>
                    </div>
                </div>

                <!-- Issuance Controls -->
                <div class="controls-stack">
                    <div>
                        <label class="ctrl-label">Signed Link Validity</label>
                        <select id="validitySelect" class="ctrl-input">
                            <option value="permanent">Permanent (Tied to Active Status)</option>
                            <option value="plan_expire">Expire on Plan End Date</option>
                            <option value="30d">30 Days</option>
                            <option value="7d">7 Days</option>
                            <option value="24h">24 Hours (Trial / Guest)</option>
                        </select>
                    </div>
                    <div>
                        <label class="ctrl-label">Reception / Staff Note (Optional)</label>
                        <input type="text" id="issueNoteInput" class="ctrl-input" placeholder="e.g. Issued at evening desk / New phone">
                    </div>

                    <button type="button" class="issue-btn" id="btnIssueWa" onclick="issuePass('whatsapp')" style="background:#16a34a;">
                        🟢 Issue &amp; Send via WhatsApp
                    </button>
                    <button type="button" class="issue-btn" id="btnIssueEmail" onclick="issuePass('email')" style="background:#2563eb;">
                        📧 Issue &amp; Send Branded Email
                    </button>
                    <button type="button" class="issue-btn" id="btnCounterQr" onclick="issuePass('counter_qr')" style="background:linear-gradient(135deg,#ff6b00,#ea580c);">
                        📱 Show Counter Scan QR on Screen
                    </button>
                    <button type="button" class="issue-btn" id="btnGoogleWallet" onclick="issuePass('google_wallet')" style="background:#020617;border:1.5px solid #4285F4;">
                        💳 Generate Google Wallet Save Link
                    </button>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <button type="button" class="issue-btn" onclick="issuePass('copy_link')" style="background:#334155;font-size:12px;">
                            📋 Copy Pass URL
                        </button>
                        <button type="button" class="issue-btn" onclick="openLivePassTab()" style="background:#0f766e;font-size:12px;">
                            ↗️ Open Pass Card
                        </button>
                    </div>

                    <div id="issueToast" class="toast"></div>
                    <div id="directWaFallback" style="display:none;font-size:12px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT ISSUANCE LOGS -->
    <div class="panel" style="margin-top:18px;overflow-x:auto;">
        <div class="panel-title">
            <span>📜 Recent Pass Issuance Log</span>
            <button type="button" class="top-btn" onclick="reloadLogs()">↻ Refresh</button>
        </div>
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Member</th>
                    <th>Platform</th>
                    <th>Channel</th>
                    <th>Issued By</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody id="logsTableBody"></tbody>
        </table>
    </div>
</div>

<!-- COUNTER SCAN HANDOFF MODAL -->
<div class="modal-bg" id="counterQrModal" onclick="closeCounterModal()">
    <div class="modal-card" onclick="event.stopPropagation()">
        <img src="<?php echo htmlspecialchars($gym_logo); ?>" alt="Logo" style="width:58px;height:58px;border-radius:50%;object-fit:cover;border:2px solid #ff6b00;background:#fff;">
        <h3 style="margin:10px 0 4px;font-size:18px;font-weight:900;" id="cmMemberName">Scan to Open Wallet Pass</h3>
        <div style="font-size:12px;color:#38bdf8;font-weight:700;margin-bottom:14px;" id="cmPlatformBadge">🍎 Apple iPhone Smart Pass</div>
        <div style="background:#fff;padding:14px;border-radius:16px;display:inline-block;">
            <canvas id="counterHandoffQr"></canvas>
        </div>
        <p style="font-size:12px;color:#cbd5e1;line-height:1.5;margin:14px 0;">
            Ask the member to point their <strong>iPhone Camera / Android Camera</strong> at this QR code, open Safari/Chrome, and tap <strong>Add to Home Screen</strong>.
        </p>
        <button type="button" class="issue-btn" onclick="closeCounterModal()" style="background:#334155;">Done</button>
    </div>
</div>

<script>
const MEMBERS_DATA = <?php echo json_encode($members, JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]'; ?>;
const INITIAL_LOGS = <?php echo json_encode($recent_logs, JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]'; ?>;
const PASS_URL_BASE = <?php echo json_encode($pass_base_url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

let activeStatusFilter = 'all';
let activePlatform = 'apple';
let selectedUid = MEMBERS_DATA.length ? String(MEMBERS_DATA[0].userid) : '';
let renderLimit = 60;
const bulkSelected = new Set();

let previewQr = null;
let counterQr = null;
function initQrInstances() {
    if (typeof QRious === 'undefined') return;
    if (!previewQr) {
        previewQr = new QRious({
            element: document.getElementById('previewQrCanvas'),
            value: 'SFQ1:PREVIEW',
            size: 118,
            background: 'white',
            foreground: 'black',
            level: 'M'
        });
    }
    if (!counterQr) {
        counterQr = new QRious({
            element: document.getElementById('counterHandoffQr'),
            value: 'https://sudarshanfitness.de',
            size: 230,
            background: 'white',
            foreground: 'black',
            level: 'M'
        });
    }
}

function getMemberPassUrl(m, plat) {
    let u = `${PASS_URL_BASE}?uid=${encodeURIComponent(m.userid)}&sig=${encodeURIComponent(m.sig || '')}`;
    if (plat === 'apple' || plat === 'android') {
        u += `&platform=${encodeURIComponent(plat)}`;
    }
    return u;
}

function setQrDisplay(canvasId, qrInstance, value, size) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.parentElement) return;
    if (qrInstance) {
        qrInstance.value = value;
        canvas.style.display = 'block';
        const oldFallback = canvas.parentElement.querySelector('img.qr-fallback-img');
        if (oldFallback) oldFallback.remove();
        return;
    }
    canvas.style.display = 'none';
    let img = canvas.parentElement.querySelector('img.qr-fallback-img');
    if (!img) {
        img = document.createElement('img');
        img.className = 'qr-fallback-img';
        img.alt = 'QR Code';
        img.width = size;
        img.height = size;
        img.style.display = 'block';
        canvas.parentElement.appendChild(img);
    }
    img.src = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(value)}`;
}

function esc(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getFilteredMembers() {
    const q = document.getElementById('memberSearchInput').value.trim().toLowerCase();
    return MEMBERS_DATA.filter(m => {
        if (activeStatusFilter === 'active' && !m.is_active) return false;
        if (activeStatusFilter === 'expiring' && m.status_key !== 'expiring') return false;
        if (activeStatusFilter === 'expired' && m.is_active) return false;
        if (activeStatusFilter === 'unissued' && m.issue_cnt > 0) return false;
        if (q !== '') {
            const hay = `${m.userid} ${m.username} ${m.mobile} ${m.email} ${m.plan_name}`.toLowerCase();
            if (!hay.includes(q)) return false;
        }
        return true;
    });
}

function renderMemberList() {
    const list = getFilteredMembers();
    const container = document.getElementById('memberListContainer');
    document.getElementById('visibleCountLabel').textContent = `${list.length} members`;

    if (list.length === 0) {
        container.innerHTML = '<div style="padding:24px;text-align:center;color:#94a3b8;font-size:13px;">No matching members found.</div>';
        return;
    }

    const slice = list.slice(0, renderLimit);
    let html = slice.map(m => {
        const uidStr = String(m.userid);
        const isSel = uidStr === String(selectedUid);
        const badgeCls = m.status_key === 'expiring' ? 'badge-expiring' : (m.is_active ? 'badge-active' : 'badge-expired');
        const badgeTxt = m.status_key === 'expiring' ? `${m.days_left}d left` : (m.is_active ? 'ACTIVE' : 'EXPIRED');
        const issueTxt = m.issue_cnt > 0 ? `🎟️ ${m.issue_cnt}x (${m.last_platform})` : 'Not issued';
        const checked = bulkSelected.has(uidStr) ? 'checked' : '';
        const safeUid = esc(uidStr);
        return `
            <div class="m-row ${isSel ? 'selected' : ''}" data-uid="${safeUid}">
                <input type="checkbox" class="bulk-chk" data-uid="${safeUid}" ${checked} style="width:16px;height:16px;accent-color:#ff6b00;cursor:pointer;">
                <img src="${esc(m.photo_url)}" alt="" class="m-avatar" loading="lazy">
                <div class="m-meta">
                    <div class="m-name">#${safeUid} &bull; ${esc(m.username)}</div>
                    <div class="m-sub">${esc(m.plan_name)} &bull; ${esc(m.mobile || 'No mobile')} &bull; ${esc(issueTxt)}</div>
                </div>
                <span class="badge ${badgeCls}">${esc(badgeTxt)}</span>
            </div>
        `;
    }).join('');

    if (list.length > slice.length) {
        html += `<button type="button" class="top-btn show-more-btn" style="width:100%;text-align:center;margin-top:6px;">Show More (${list.length - slice.length} remaining)</button>`;
    }
    container.innerHTML = html;
}

function selectMember(uid) {
    selectedUid = String(uid);
    renderMemberList();
    renderPreview();
}

function toggleBulk(uid, checked) {
    const uidStr = String(uid);
    if (checked) bulkSelected.add(uidStr);
    else bulkSelected.delete(uidStr);
    const bar = document.getElementById('bulkBar');
    bar.style.display = bulkSelected.size > 0 ? 'flex' : 'none';
    document.getElementById('bulkCountLabel').textContent = `${bulkSelected.size} selected`;
}

function clearSelection() {
    bulkSelected.clear();
    document.getElementById('bulkBar').style.display = 'none';
    renderMemberList();
}

function bumpStatCounters(count = 1) {
    const t = document.getElementById('statToday');
    if (t) t.textContent = String((parseInt(t.textContent, 10) || 0) + count);
    if (activePlatform === 'apple') {
        const a = document.getElementById('statApple');
        if (a) a.textContent = String((parseInt(a.textContent, 10) || 0) + count);
    } else if (activePlatform === 'android') {
        const n = document.getElementById('statAndroid');
        if (n) n.textContent = String((parseInt(n.textContent, 10) || 0) + count);
    }
}

function setStatusFilter(filter, btn) {
    activeStatusFilter = filter;
    renderLimit = 60;
    document.querySelectorAll('.filter-row .f-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderMemberList();
}

function setPlatform(plat) {
    activePlatform = plat;
    document.getElementById('tabApple').className = 'plat-tab' + (plat === 'apple' ? ' active-apple' : '');
    document.getElementById('tabAndroid').className = 'plat-tab' + (plat === 'android' ? ' active-android' : '');
    document.getElementById('tabUniversal').className = 'plat-tab' + (plat === 'universal' ? ' active-universal' : '');

    const notice = document.getElementById('platformNotice');
    if (plat === 'apple') {
        notice.innerHTML = '<strong>🍎 Apple iPhone Smart Wallet Pass:</strong> Uses an HMAC-signed Web-Clip &amp; HD Pass Card + 60s rotating gate QR. Members open it in Safari -> <em>Share -> Add to Home Screen</em> (works on every iPhone without needing a paid Apple Developer Pass Certificate).';
    } else if (plat === 'android') {
        notice.innerHTML = '<strong>🤖 Android Smart Wallet Pass:</strong> Issues an HMAC-signed PWA Pass Shortcut with rotating 60s gate QR &amp; 1-Tap Check-In. Works directly in Android Chrome via <em>Install App / Add to Home Screen</em>.';
    } else {
        notice.innerHTML = '<strong>🌐 Universal Smart Wallet Pass:</strong> Generates a signed link tailored for both Apple iPhone Safari and Android Chrome with full gym branding and rotating gate QR.';
    }
    renderPreview();
}

function getSelectedMember() {
    return MEMBERS_DATA.find(m => String(m.userid) === String(selectedUid)) || null;
}

function renderPreview() {
    const m = getSelectedMember();
    const card = document.getElementById('previewCard');
    const tag = document.getElementById('pvPlatTag');

    if (activePlatform === 'apple') tag.textContent = '🍎 Apple iPhone Smart Pass';
    else if (activePlatform === 'android') tag.textContent = '🤖 Android Smart Pass';
    else tag.textContent = '🔒 Official Digital Pass';

    if (!m) {
        document.getElementById('selectedMemberBadge').textContent = 'No member selected';
        return;
    }

    card.className = 'preview-card' + (!m.is_active ? ' theme-expired' : (activePlatform === 'apple' ? ' theme-apple' : ''));
    document.getElementById('selectedMemberBadge').textContent = `#${m.userid} • ${m.username}`;
    const av = document.getElementById('pvAvatar');
    av.onerror = () => { av.src = '../images/default_avatar.png'; };
    av.src = m.photo_url || '../images/default_avatar.png';
    document.getElementById('pvName').textContent = m.username;
    document.getElementById('pvUid').textContent = `MEMBER ID #${m.userid}`;
    document.getElementById('pvPlan').textContent = m.plan_name;
    document.getElementById('pvExpire').textContent = m.is_active ? `${m.expire_fmt} (${m.days_left}d)` : `${m.expire_fmt} (Expired)`;
    document.getElementById('pvBatch').textContent = m.batch_name;
    document.getElementById('pvCoach').textContent = m.trainer_name;

    const pill = document.getElementById('pvStatusPill');
    pill.textContent = m.is_active ? '🟢 ACTIVE' : '🔴 EXPIRED';
    pill.className = 'badge ' + (m.is_active ? 'badge-active' : 'badge-expired');

    setQrDisplay('previewQrCanvas', previewQr, m.rotating_qr, 118);
}

async function parseApiResponse(res) {
    const text = await res.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        return { success: false, message: `Server returned non-JSON response (HTTP ${res.status}). Please re-login if session expired.` };
    }
}

function showToast(ok, text) {
    const t = document.getElementById('issueToast');
    t.style.display = 'block';
    t.style.background = ok ? 'rgba(16,185,129,0.16)' : 'rgba(239,68,68,0.16)';
    t.style.border = ok ? '1px solid rgba(16,185,129,0.4)' : '1px solid rgba(239,68,68,0.4)';
    t.style.color = ok ? '#6ee7b7' : '#fca5a5';
    t.textContent = text;
}

async function issuePass(channel) {
    const m = getSelectedMember();
    if (!m) { showToast(false, 'Please select a member first.'); return; }

    const validity = document.getElementById('validitySelect').value;
    const note = document.getElementById('issueNoteInput').value.trim();
    const waBox = document.getElementById('directWaFallback');
    waBox.style.display = 'none';

    try {
        const res = await fetch('api/issue_pass.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'issue_single',
                uid: m.userid,
                platform: activePlatform,
                channel: channel,
                validity: validity,
                note: note
            })
        });
        const data = await parseApiResponse(res);
        if (!data.success) {
            showToast(false, data.message || 'Pass issuance failed.');
            if (data.wa_direct_url) {
                waBox.style.display = 'block';
                waBox.innerHTML = `<a href="${esc(data.wa_direct_url)}" target="_blank" style="color:#34d399;font-weight:700;">↗️ Open WhatsApp Web Direct Send Instead</a>`;
            }
            return;
        }

        m.issue_cnt = (m.issue_cnt || 0) + 1;
        m.last_platform = activePlatform;
        bumpStatCounters(1);
        renderMemberList();
        reloadLogs();

        if (channel === 'copy_link') {
            if (navigator.clipboard) await navigator.clipboard.writeText(data.signed_url).catch(() => {});
            showToast(true, `📋 Copied signed URL (${data.expires_label})`);
        } else if (channel === 'counter_qr') {
            document.getElementById('cmMemberName').textContent = `${m.username} (#${m.userid})`;
            document.getElementById('cmPlatformBadge').textContent = activePlatform === 'apple' ? '🍎 Apple iPhone Pass Link' : (activePlatform === 'android' ? '🤖 Android Pass Link' : '📲 Signed Pass Link');
            setQrDisplay('counterHandoffQr', counterQr, data.signed_url, 230);
            document.getElementById('counterQrModal').style.display = 'flex';
            showToast(true, '📱 Counter scan QR ready for member camera.');
        } else if (channel === 'google_wallet') {
            if (navigator.clipboard && data.google_wallet_url) await navigator.clipboard.writeText(data.google_wallet_url).catch(() => {});
            showToast(true, `💳 ${data.message}`);
            if (data.google_wallet_url) {
                waBox.style.display = 'block';
                waBox.innerHTML = `<a href="${esc(data.google_wallet_url)}" target="_blank" style="color:#93c5fd;font-weight:800;">↗️ Open Native "Add to Google Wallet" Link</a>`;
            }
        } else {
            showToast(true, `✅ ${data.message}`);
            if (data.wa_direct_url && channel === 'whatsapp') {
                waBox.style.display = 'block';
                waBox.innerHTML = `<a href="${esc(data.wa_direct_url)}" target="_blank" style="color:#38bdf8;font-weight:700;">↗️ Or send immediately via WhatsApp Web</a>`;
            }
        }
    } catch (e) {
        showToast(false, 'Network/server error issuing pass.');
    }
}

async function bulkIssue(channel) {
    if (bulkSelected.size === 0) return;
    const uids = Array.from(bulkSelected);
    const validity = document.getElementById('validitySelect').value;
    const note = document.getElementById('issueNoteInput').value.trim();

    showToast(true, `⏳ Issuing ${uids.length} passes via ${channel.toUpperCase()}...`);
    try {
        const res = await fetch('api/issue_pass.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'bulk_issue',
                uids: uids,
                platform: activePlatform,
                channel: channel,
                validity: validity,
                note: note
            })
        });
        const data = await parseApiResponse(res);
        if (data.success) {
            if (data.sent_count > 0) bumpStatCounters(data.sent_count);
            showToast(true, `✅ Issued ${data.sent_count} pass(es). Skipped: ${data.skipped_count}.`);
            clearSelection();
            reloadLogs();
        } else {
            showToast(false, data.message || 'Bulk issue failed.');
        }
    } catch (e) {
        showToast(false, 'Bulk issue network error.');
    }
}

async function openLivePassTab() {
    const m = getSelectedMember();
    if (!m) { showToast(false, 'Please select a member first.'); return; }
    const validity = document.getElementById('validitySelect').value;
    if (validity === 'permanent') {
        window.open(getMemberPassUrl(m, activePlatform), '_blank');
        return;
    }
    const win = window.open('about:blank', '_blank');
    try {
        const res = await fetch('api/issue_pass.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'issue_single',
                uid: m.userid,
                platform: activePlatform,
                channel: 'copy_link',
                validity: validity,
                note: 'Opened pass card preview'
            })
        });
        const data = await parseApiResponse(res);
        if (data.success && data.signed_url) {
            if (win) win.location.href = data.signed_url;
            else window.open(data.signed_url, '_blank');
            bumpStatCounters(1);
            reloadLogs();
            return;
        }
    } catch (e) {}
    const fallbackUrl = getMemberPassUrl(m, activePlatform);
    if (win) win.location.href = fallbackUrl;
}

function closeCounterModal() {
    document.getElementById('counterQrModal').style.display = 'none';
}

function renderLogs(logs) {
    const body = document.getElementById('logsTableBody');
    if (!logs || logs.length === 0) {
        body.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#94a3b8;">No wallet passes issued from portal yet.</td></tr>';
        return;
    }
    body.innerHTML = logs.map(l => {
        const platLabel = l.platform === 'apple' ? '🍎 Apple' : (l.platform === 'android' ? '🤖 Android' : '🌐 Universal');
        return `
            <tr>
                <td>${esc(l.created_at)}</td>
                <td><strong>${esc(l.username || l.uid)}</strong> <span style="color:#38bdf8;">(#${esc(l.uid)})</span></td>
                <td>${platLabel}</td>
                <td>${esc(l.channel)}</td>
                <td>${esc(l.issued_by)} (${esc(l.staff_role || 'staff')})</td>
                <td style="color:#94a3b8;">${esc(l.note || '-')}</td>
            </tr>
        `;
    }).join('');
}

async function reloadLogs() {
    try {
        const res = await fetch('api/issue_pass.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'recent_logs' })
        });
        const data = await parseApiResponse(res);
        if (data.success && data.logs) {
            renderLogs(data.logs);
        }
    } catch (e) {}
}

const memberListEl = document.getElementById('memberListContainer');
memberListEl.addEventListener('click', (e) => {
    if (e.target.closest('button.show-more-btn')) {
        renderLimit += 60;
        renderMemberList();
        return;
    }
    if (e.target.closest('input.bulk-chk')) return;
    const row = e.target.closest('.m-row[data-uid]');
    if (row) selectMember(row.dataset.uid);
});
memberListEl.addEventListener('change', (e) => {
    const chk = e.target.closest('input.bulk-chk[data-uid]');
    if (chk) toggleBulk(chk.dataset.uid, chk.checked);
});
memberListEl.addEventListener('error', (e) => {
    if (e.target && e.target.tagName === 'IMG') {
        e.target.src = '../images/default_avatar.png';
    }
}, true);

document.getElementById('memberSearchInput').addEventListener('input', () => {
    renderLimit = 60;
    renderMemberList();
});
window.addEventListener('DOMContentLoaded', () => {
    initQrInstances();
    renderPreview();
});
initQrInstances();
renderMemberList();
renderPreview();
renderLogs(INITIAL_LOGS);
</script>
</body>
</html>
