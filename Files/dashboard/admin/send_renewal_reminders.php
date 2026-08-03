<?php
require '../../include/db_conn.php';
require '../../include/smtp_mailer.php';
page_protect();

$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'super_admin';
date_default_timezone_set("Asia/Calcutta");
$today = new DateTime();

// ── Handle Send Action ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminders'])) {
    @set_time_limit(300); // 5 minutes execution time for bulk sending
    ob_start();

    $days_filter = intval($_POST['days_filter'] ?? 5); // 3, 5, or 7
    $sent = 0; $failed = 0; $no_email = 0;

    $q = mysqli_query($con, "
        SELECT u.userid, u.username, u.email, u.mobile,
               e.expire, p.planName, e.amount
        FROM users u
        INNER JOIN enrolls_to e ON u.userid = e.uid
        INNER JOIN plan p ON e.pid = p.pid
        WHERE e.expire >= CURDATE()
          AND e.expire <= DATE_ADD(CURDATE(), INTERVAL $days_filter DAY)
          AND e.renewal = 'yes'
        ORDER BY e.expire ASC
    ");

    $gym = get_gym_details($con);
    $gym_name = htmlspecialchars($gym['gym_name'] ?? 'Sudarshan Fitness');

    while ($row = mysqli_fetch_assoc($q)) {
        if (empty($row['email'])) { $no_email++; continue; }

        $expire_dt  = new DateTime($row['expire']);
        $diff       = $today->diff($expire_dt);
        $days_left  = intval($diff->days);
        $name       = htmlspecialchars($row['username']);
        $plan       = htmlspecialchars($row['planName']);
        $expire_fmt = date('d M Y', strtotime($row['expire']));

        $urgency_color = $days_left <= 2 ? '#dc2626' : ($days_left <= 4 ? '#f59e0b' : '#3b82f6');
        $urgency_label = $days_left === 0 ? 'TODAY' : ($days_left === 1 ? 'TOMORROW' : "IN $days_left DAYS");

        $html = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#0a0a0f;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#0a0a0f;padding:30px 0;'>
<tr><td align='center'>
<table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;'>

<!-- Header -->
<tr><td style='background:linear-gradient(135deg,#7c2d12,#991b1b);border-radius:16px 16px 0 0;padding:30px;text-align:center;'>
  <div style='font-size:40px;margin-bottom:8px;'>⚠️</div>
  <h1 style='color:#fff;font-size:22px;margin:0;font-weight:900;letter-spacing:1px;'>MEMBERSHIP EXPIRY NOTICE</h1>
  <div style='background:$urgency_color;color:#fff;display:inline-block;padding:4px 16px;border-radius:20px;font-size:13px;font-weight:800;letter-spacing:2px;margin-top:10px;'>EXPIRES $urgency_label</div>
</td></tr>

<!-- Body -->
<tr><td style='background:#111827;padding:30px;'>
  <p style='color:#d1d5db;font-size:16px;margin:0 0 20px 0;'>Dear <strong style='color:#fff;'>$name</strong>,</p>
  <p style='color:#9ca3af;font-size:14px;line-height:1.7;margin:0 0 24px 0;'>
    This is an important reminder from <strong style='color:#fff;'>$gym_name</strong>. 
    Your gym membership is about to expire and we don't want you to miss a single workout!
  </p>

  <!-- Expiry Card -->
  <div style='background:#1f2937;border:2px solid $urgency_color;border-radius:12px;padding:20px;margin-bottom:24px;text-align:center;'>
    <div style='color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;'>Your Membership Expires On</div>
    <div style='color:$urgency_color;font-size:28px;font-weight:900;margin-bottom:6px;'>$expire_fmt</div>
    <div style='color:#6b7280;font-size:13px;'>Plan: <strong style='color:#d1d5db;'>$plan</strong></div>
  </div>

  <p style='color:#9ca3af;font-size:14px;line-height:1.7;margin:0 0 24px 0;'>
    🏋️ <strong style='color:#fff;'>Don't let your fitness journey stop!</strong> Visit the reception desk or contact us to renew your membership and continue enjoying all the benefits.
  </p>

  <!-- CTA -->
  <div style='text-align:center;margin-bottom:24px;'>
    <div style='background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;display:inline-block;padding:14px 32px;border-radius:12px;font-size:15px;font-weight:800;letter-spacing:0.5px;'>
      🔥 RENEW NOW — Visit Reception
    </div>
  </div>

  <div style='background:#1f2937;border-radius:10px;padding:14px 18px;font-size:13px;color:#6b7280;text-align:center;'>
    $gym_name &nbsp;|&nbsp; Khamgaon &nbsp;|&nbsp; Your fitness, our mission 💪
  </div>
</td></tr>

<!-- Footer -->
<tr><td style='background:#0d1117;border-radius:0 0 16px 16px;padding:16px;text-align:center;'>
  <p style='color:#374151;font-size:11px;margin:0;'>This is an automated reminder from $gym_name. Please do not reply to this email.</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>";

        $subject = "⚠️ Your {$gym_name} Membership Expires $urgency_label — Renew Now!";
        
        try {
            $result = send_smtp_email($row['email'], $row['username'], $subject, $html);
            if ($result) $sent++; else $failed++;
        } catch (Throwable $e) {
            $failed++;
        }

        // Log reminder sent
        @mysqli_query($con, "INSERT INTO renewal_reminder_log (uid, email, days_left, sent_at) VALUES ('" 
            . mysqli_real_escape_string($con, $row['userid']) . "','" 
            . mysqli_real_escape_string($con, $row['email']) . "',$days_left, NOW()) 
            ON DUPLICATE KEY UPDATE sent_at = NOW()");
    }

    $response = ['sent' => $sent, 'failed' => $failed, 'no_email' => $no_email];
    if (isset($_POST['ajax'])) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit();
    }
}

// ── Create log table if not exists ──────────────────────────────────────────
mysqli_query($con, "CREATE TABLE IF NOT EXISTS renewal_reminder_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(50), email VARCHAR(200), days_left INT, sent_at DATETIME,
    UNIQUE KEY uniq_uid_date (uid, sent_at)
) ENGINE=InnoDB");

// ── Load expiring members preview ─────────────────────────────────────────────
$expiring = [];
for ($d = 1; $d <= 7; $d++) {
    $date = date('Y-m-d', strtotime("+$d days"));
    $q = mysqli_query($con, "
        SELECT u.userid, u.username, u.email, u.mobile, e.expire, p.planName
        FROM users u
        INNER JOIN enrolls_to e ON u.userid = e.uid
        INNER JOIN plan p ON e.pid = p.pid
        WHERE e.expire = '$date' AND e.renewal = 'yes'
    ");
    while ($r = mysqli_fetch_assoc($q)) {
        $r['days_left'] = $d;
        $expiring[] = $r;
    }
}

// Count by range
$count_3 = count(array_filter($expiring, function($r) { return $r['days_left'] <= 3; }));
$count_5 = count(array_filter($expiring, function($r) { return $r['days_left'] <= 5; }));
$count_7 = count($expiring);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>SUDARSHAN FITNESS | Send Renewal Reminders</title>
<link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
<script type="text/javascript" src="../../js/Script.js"></script>
<link rel="stylesheet" href="../../css/dashMain.css">
<link rel="stylesheet" type="text/css" href="../../css/entypo.css">
<link rel="stylesheet" href="../../css/premium.css">
<link href="a1style.css" rel="stylesheet" type="text/css">
<style>
.page-container .sidebar-menu #main-menu li#renewal_remind > a { background-color:#2b303a;color:#fff; }
.remind-card { background:rgba(15,7,18,0.92);border:1px solid rgba(255,107,0,0.3);border-radius:18px;padding:28px;margin-bottom:24px;box-shadow:0 8px 30px rgba(0,0,0,0.4); }
.remind-card h3 { color:#ff6b00;font-weight:800;font-size:16px;text-transform:uppercase;letter-spacing:1px;margin:0 0 20px 0;border-bottom:1px solid rgba(255,107,0,0.2);padding-bottom:12px; }
.stat-box { background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:18px;text-align:center; }
.stat-box .num { font-size:36px;font-weight:900;line-height:1; }
.stat-box .lbl { font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;margin-top:6px; }
.member-row { display:flex;align-items:center;gap:14px;padding:12px 16px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;margin-bottom:8px;flex-wrap:wrap; }
.member-row:hover { border-color:rgba(255,107,0,0.3); }
.days-badge { padding:3px 10px;border-radius:20px;font-size:11px;font-weight:800;letter-spacing:0.5px;white-space:nowrap; }
.days-1-2 { background:rgba(220,38,38,0.2);color:#f87171;border:1px solid rgba(220,38,38,0.4); }
.days-3-4 { background:rgba(245,158,11,0.2);color:#fcd34d;border:1px solid rgba(245,158,11,0.4); }
.days-5-7 { background:rgba(59,130,246,0.2);color:#93c5fd;border:1px solid rgba(59,130,246,0.4); }
.send-btn { background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;border:none;padding:14px 28px;border-radius:12px;font-size:14px;font-weight:800;cursor:pointer;transition:all 0.2s;letter-spacing:0.5px;display:inline-flex;align-items:center;gap:8px; }
.send-btn:hover { transform:translateY(-2px);box-shadow:0 6px 20px rgba(220,38,38,0.5); }
.send-btn:disabled { opacity:0.5;cursor:not-allowed;transform:none; }
.range-select { display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px; }
.range-btn { flex:1;min-width:140px;padding:14px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:#fff;cursor:pointer;text-align:center;transition:all 0.2s; }
.range-btn:hover, .range-btn.active { background:rgba(220,38,38,0.15);border-color:#dc2626; }
.range-btn .big { font-size:28px;font-weight:900;color:#dc2626;display:block;line-height:1;margin-bottom:4px; }
.range-btn .sm { font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px; }
.result-toast { display:none;background:#111827;border:1px solid rgba(255,255,255,0.1);border-radius:14px;padding:20px 24px;margin-bottom:20px; }
.result-toast.show { display:flex;align-items:center;gap:14px; }
</style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
<div class="page-container sidebar-collapsed" id="navbarcollapse">
    <div class="sidebar-menu">
        <header class="logo-env">
            <div class="logo"><a href="index.php">
                <?php $sl = $gym_settings_data["gym_logo"] ?? "../../images/logo.png"; ?>
                <img src="<?php echo htmlspecialchars($sl); ?>" alt="Gym Logo" style="max-height:80px;max-width:192px;" />
            </a></div>
            <div class="sidebar-collapse" onclick="collapseSidebar()"><a href="#" class="sidebar-collapse-icon with-animation"><i class="entypo-menu"></i></a></div>
        </header>
        <?php include('nav.php'); ?>
    </div>

    <div class="main-content">
        <div class="row">
            <div class="col-md-6 col-sm-8 clearfix"></div>
            <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                <ul class="list-inline links-list pull-right">
                    <li>Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?></li>
                    <li><a href="logout.php">Log Out <i class="entypo-logout right"></i></a></li>
                </ul>
            </div>
        </div>

        <h2>📧 Send Renewal Reminder Emails</h2>
        <hr />

        <!-- Result Toast -->
        <div class="result-toast" id="result-toast">
            <div style="font-size:36px;" id="toast-icon">✅</div>
            <div>
                <div id="toast-title" style="color:#fff;font-size:16px;font-weight:800;margin-bottom:4px;"></div>
                <div id="toast-detail" style="color:#94a3b8;font-size:13px;"></div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="remind-card">
            <h3>📊 Expiring Members — Overview</h3>
            <div class="row" style="margin:0;">
                <div class="col-md-4" style="padding:0 8px 16px 0;">
                    <div class="stat-box" style="border-color:rgba(220,38,38,0.4);">
                        <div class="num" style="color:#f87171;"><?php echo $count_3; ?></div>
                        <div class="lbl">Expiring in 1–3 Days</div>
                        <div style="font-size:11px;color:#dc2626;margin-top:6px;font-weight:700;">🔴 URGENT</div>
                    </div>
                </div>
                <div class="col-md-4" style="padding:0 8px 16px 0;">
                    <div class="stat-box" style="border-color:rgba(245,158,11,0.4);">
                        <div class="num" style="color:#fcd34d;"><?php echo $count_5; ?></div>
                        <div class="lbl">Expiring in 1–5 Days</div>
                        <div style="font-size:11px;color:#f59e0b;margin-top:6px;font-weight:700;">🟡 SOON</div>
                    </div>
                </div>
                <div class="col-md-4" style="padding:0 8px 16px 0;">
                    <div class="stat-box" style="border-color:rgba(59,130,246,0.4);">
                        <div class="num" style="color:#93c5fd;"><?php echo $count_7; ?></div>
                        <div class="lbl">Expiring in 1–7 Days</div>
                        <div style="font-size:11px;color:#3b82f6;margin-top:6px;font-weight:700;">🔵 THIS WEEK</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- One-Click Send -->
        <div class="remind-card">
            <h3>📬 One-Click Bulk Email Sender</h3>
            <p style="color:#94a3b8;font-size:13px;margin:0 0 20px 0;">Choose how many days ahead to notify, then click Send. Each member gets a personalized warning email.</p>

            <!-- Range selector -->
            <div class="range-select" id="range-select">
                <div class="range-btn active" onclick="selectRange(3, this)">
                    <span class="big"><?php echo $count_3; ?></span>
                    <span class="sm">Send to 1–3 Day Expiring</span>
                </div>
                <div class="range-btn" onclick="selectRange(5, this)">
                    <span class="big"><?php echo $count_5; ?></span>
                    <span class="sm">Send to 1–5 Day Expiring</span>
                </div>
                <div class="range-btn" onclick="selectRange(7, this)">
                    <span class="big"><?php echo $count_7; ?></span>
                    <span class="sm">Send to 1–7 Day Expiring</span>
                </div>
            </div>

            <button class="send-btn" id="send-btn" onclick="sendReminders()">
                <span id="send-btn-icon">📧</span>
                <span id="send-btn-text">Send Renewal Reminders</span>
            </button>
            <span style="color:#64748b;font-size:12px;margin-left:14px;">Members without email will be skipped.</span>
        </div>

        <!-- Members List -->
        <div class="remind-card">
            <h3>🗒️ Expiring Members Detail</h3>
            <?php if (empty($expiring)): ?>
            <div style="text-align:center;padding:40px;color:#64748b;">
                <div style="font-size:40px;margin-bottom:12px;">🎉</div>
                <p>No members expiring in the next 7 days!</p>
            </div>
            <?php else: ?>
            <?php foreach ($expiring as $m):
                $dl = $m['days_left'];
                $badge_class = $dl <= 2 ? 'days-1-2' : ($dl <= 4 ? 'days-3-4' : 'days-5-7');
                $has_email = !empty($m['email']);
            ?>
            <div class="member-row">
                <div style="flex:1;min-width:160px;">
                    <strong style="color:#fff;display:block;font-size:14px;"><?php echo htmlspecialchars($m['username']); ?></strong>
                    <span style="color:#64748b;font-size:12px;"><?php echo htmlspecialchars($m['userid']); ?></span>
                </div>
                <div style="min-width:120px;">
                    <span style="color:#94a3b8;font-size:12px;display:block;">Plan</span>
                    <span style="color:#e2e8f0;font-size:13px;font-weight:600;"><?php echo htmlspecialchars($m['planName']); ?></span>
                </div>
                <div style="min-width:110px;">
                    <span style="color:#94a3b8;font-size:12px;display:block;">Expires</span>
                    <span style="color:#e2e8f0;font-size:13px;font-weight:600;"><?php echo date('d M Y', strtotime($m['expire'])); ?></span>
                </div>
                <span class="days-badge <?php echo $badge_class; ?>">⏱ <?php echo $dl; ?> day<?php echo $dl > 1 ? 's' : ''; ?> left</span>
                <?php if ($has_email): ?>
                    <span style="color:#10b981;font-size:12px;">✉️ <?php echo htmlspecialchars($m['email']); ?></span>
                <?php else: ?>
                    <span style="color:#ef4444;font-size:12px;">⚠️ No email</span>
                <?php endif; ?>
                <div>
                    <a href="read_member.php?name=<?php echo urlencode($m['userid']); ?>" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;padding:5px 12px;border-radius:8px;font-size:11px;text-decoration:none;font-weight:600;">View</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
var selectedDays = 3;

function selectRange(days, el) {
    selectedDays = days;
    document.querySelectorAll('.range-btn').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
}

function sendReminders() {
    var btn = document.getElementById('send-btn');
    var icon = document.getElementById('send-btn-icon');
    var txt  = document.getElementById('send-btn-text');

    if (!confirm('Send renewal reminder emails to all members expiring within ' + selectedDays + ' days?\n\nThis will send individual emails to each member who has an email address registered.')) return;

    btn.disabled = true;
    icon.innerText = '⏳';
    txt.innerText  = 'Sending emails...';

    var fd = new FormData();
    fd.append('send_reminders', '1');
    fd.append('ajax', '1');
    fd.append('days_filter', selectedDays);

    fetch('send_renewal_reminders.php', { method: 'POST', body: fd })
        .then(function(r) {
            if (!r.ok) { throw new Error('HTTP error ' + r.status); }
            return r.text();
        })
        .then(function(text) {
            btn.disabled = false;
            icon.innerText = '📧';
            txt.innerText  = 'Send Renewal Reminders';

            var data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Response text:', text);
                alert('Server returned invalid response. Details logged in browser console.');
                return;
            }

            var toast = document.getElementById('result-toast');
            document.getElementById('toast-icon').innerText = data.sent > 0 ? '✅' : '⚠️';
            document.getElementById('toast-title').innerText = data.sent + ' reminder email' + (data.sent !== 1 ? 's' : '') + ' sent successfully!';
            document.getElementById('toast-detail').innerText = 
                (data.failed > 0 ? data.failed + ' failed. ' : '') +
                (data.no_email > 0 ? data.no_email + ' members had no email on record.' : '');
            toast.classList.add('show');
            toast.style.borderColor = data.sent > 0 ? 'rgba(16,185,129,0.5)' : 'rgba(245,158,11,0.5)';
            toast.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function(err) {
            btn.disabled = false;
            icon.innerText = '📧';
            txt.innerText  = 'Send Renewal Reminders';
            alert('Request failed: ' + err.message);
        });
}
</script>

<?php include '../../include/dev_credit.php'; ?>
</body>
</html>
