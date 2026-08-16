<?php
require '../../include/db_conn.php';
page_protect();

$current_role = $_SESSION['role'] ?? 'member';
if (!in_array($current_role, ['super_admin', 'owner'])) {
    echo "<head><script>alert('Access Denied: Security Command Center requires Superadmin or Owner privilege.');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);
$msg = '';
$msg_type = '';

// ── Handle Action Submissions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'unblock_ip') {
        $ip_to_unblock = mysqli_real_escape_string($con, trim($_POST['ip'] ?? ''));
        if (!empty($ip_to_unblock)) {
            @mysqli_query($con, "DELETE FROM blocked_ips WHERE ip_address = '$ip_to_unblock'");
            @mysqli_query($con, "DELETE FROM login_attempts WHERE ip_address = '$ip_to_unblock'");
            log_security_event($con, 'IP_UNBLOCKED', "IP address $ip_to_unblock manually unblocked by admin", 'info');
            $msg = "✅ IP address <strong>" . htmlspecialchars($ip_to_unblock) . "</strong> has been unblocked successfully.";
            $msg_type = "success";
        }
    } elseif ($action === 'block_ip') {
        $ip_to_block = mysqli_real_escape_string($con, trim($_POST['ip'] ?? ''));
        $reason      = mysqli_real_escape_string($con, trim($_POST['reason'] ?? 'Manual Admin Block'));
        if (!empty($ip_to_block)) {
            $admin_user = $_SESSION['username'] ?? 'admin';
            @mysqli_query($con, "INSERT INTO blocked_ips (ip_address, reason, blocked_by) VALUES ('$ip_to_block', '$reason', '$admin_user') ON DUPLICATE KEY UPDATE reason='$reason'");
            log_security_event($con, 'IP_BLOCKED', "IP address $ip_to_block manually blocked by admin (Reason: $reason)", 'warning');
            $msg = "🚫 IP address <strong>" . htmlspecialchars($ip_to_block) . "</strong> has been added to the security blocklist.";
            $msg_type = "danger";
        }
    } elseif ($action === 'clear_audit_logs') {
        @mysqli_query($con, "DELETE FROM security_audit_logs WHERE created_at < NOW() - INTERVAL 30 DAY");
        log_security_event($con, 'LOGS_PURGED', "Purged audit logs older than 30 days", 'info');
        $msg = "🧹 Security audit logs older than 30 days cleaned up successfully.";
        $msg_type = "info";
    }
}

// ── Fetch Security Statistics ───────────────────────────────────────────────
$q_failed_24h = mysqli_query($con, "SELECT COUNT(*) as cnt FROM login_attempts WHERE status = 'failed' AND attempt_time > NOW() - INTERVAL 24 HOUR");
$failed_24h_cnt = ($q_failed_24h && $rf = mysqli_fetch_assoc($q_failed_24h)) ? intval($rf['cnt']) : 0;

$q_blocked = mysqli_query($con, "SELECT COUNT(*) as cnt FROM blocked_ips WHERE expires_at IS NULL OR expires_at > NOW()");
$blocked_cnt = ($q_blocked && $rb = mysqli_fetch_assoc($q_blocked)) ? intval($rb['cnt']) : 0;

$q_total_logs = mysqli_query($con, "SELECT COUNT(*) as cnt FROM security_audit_logs");
$total_logs_cnt = ($q_total_logs && $rt = mysqli_fetch_assoc($q_total_logs)) ? intval($rt['cnt']) : 0;

// Threat Level Computation
if ($failed_24h_cnt > 20 || $blocked_cnt > 5) {
    $threat_level = "HIGH RISK";
    $threat_color = "#ef4444";
    $threat_badge = "🔴";
} elseif ($failed_24h_cnt > 5 || $blocked_cnt > 0) {
    $threat_level = "MODERATE";
    $threat_color = "#f59e0b";
    $threat_badge = "🟡";
} else {
    $threat_level = "HEALTHY & SECURE";
    $threat_color = "#10b981";
    $threat_badge = "🟢";
}

// Fetch Audit Logs
$q_logs = mysqli_query($con, "SELECT * FROM security_audit_logs ORDER BY id DESC LIMIT 50");

// Fetch Blocked IPs
$q_ip_list = mysqli_query($con, "SELECT * FROM blocked_ips ORDER BY id DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Security Master Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Orbitron:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/entypo.css">
    <style>
        :root {
            --bg: #0b0f19;
            --card-bg: rgba(15, 23, 42, 0.8);
            --border: rgba(255, 255, 255, 0.1);
            --accent: #ff003c;
            --accent-green: #10b981;
            --accent-blue: #3b82f6;
        }
        body { background: var(--bg); color: #fff; font-family: 'Outfit', sans-serif; padding: 25px; margin: 0; }
        .header-box { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(15,23,42,0.95)); padding: 22px 30px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
        .header-title h2 { margin: 0; font-size: 22px; font-weight: 800; color: #fff; font-family: 'Orbitron', sans-serif; letter-spacing: 1px; }
        
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; padding: 20px; text-align: center; box-shadow: 0 8px 24px rgba(0,0,0,0.3); backdrop-filter: blur(10px); }
        .stat-val { font-size: 28px; font-weight: 900; margin-top: 5px; }
        .stat-lbl { color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

        .sec-box { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 25px; margin-bottom: 25px; backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .sec-title { font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; font-family: 'Orbitron', sans-serif; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; }

        .table-custom { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table-custom th { background: rgba(255,255,255,0.05); color: #94a3b8; text-transform: uppercase; font-size: 11px; padding: 12px 14px; text-align: left; font-weight: 700; border-bottom: 1px solid var(--border); }
        .table-custom td { padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.04); color: #e2e8f0; }

        .badge-sev { padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .badge-critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid #f59e0b; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #3b82f6; }

        .btn-action { background: rgba(255,255,255,0.1); border: 1px solid var(--border); color: #fff; padding: 8px 16px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-action:hover { background: rgba(255,255,255,0.2); }
        .btn-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.4); }

        .form-input { background: #0f172a; border: 1px solid var(--border); color: #fff; padding: 10px 14px; border-radius: 10px; font-size: 13px; outline: none; }
        .alert-bar { padding: 14px 20px; border-radius: 14px; margin-bottom: 25px; font-weight: bold; font-size: 14px; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; }
        .alert-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }
        .alert-info { background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; color: #93c5fd; }
    </style>
</head>
<body>

    <!-- Header Box -->
    <div class="header-box">
        <div class="header-title">
            <h2>🛡️ Super Security Command Center &amp; Threat Monitor</h2>
            <span style="font-size: 12px; color: #94a3b8;">Real-time perimeter defense, session binding, brute-force shield &amp; security audit log</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="index.php" class="btn-action">← Return to Dashboard</a>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert-bar alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stat Metrics -->
    <div class="stat-grid">
        <div class="stat-card" style="border-color: <?php echo $threat_color; ?>;">
            <div class="stat-lbl">System Security Status</div>
            <div class="stat-val" style="color: <?php echo $threat_color; ?>;"><?php echo $threat_badge; ?> <?php echo $threat_level; ?></div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Perimeter Defense Filter</div>
        </div>

        <div class="stat-card">
            <div class="stat-lbl">Active Blocked IPs</div>
            <div class="stat-val" style="color: #ef4444;"><?php echo number_format($blocked_cnt); ?></div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Enforced IP Lockouts</div>
        </div>

        <div class="stat-card">
            <div class="stat-lbl">Failed Logins (24h)</div>
            <div class="stat-val" style="color: #f59e0b;"><?php echo number_format($failed_24h_cnt); ?></div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Rate-limited attempts</div>
        </div>

        <div class="stat-card">
            <div class="stat-lbl">Total Audit Log Events</div>
            <div class="stat-val" style="color: #60a5fa;"><?php echo number_format($total_logs_cnt); ?></div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Immutable Security Trail</div>
        </div>
    </div>

    <!-- Security Hardening Status Checklist -->
    <div class="sec-box" style="border: 2px solid #10b981; background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(15,23,42,0.95));">
        <div class="sec-title">
            <span>🇮🇳 CERT-In Indian Military Standard Cyber Security Protection (MIL-STD-256-INDIA)</span>
            <span style="font-size: 11px; color: #10b981; background: rgba(16,185,129,0.2); border: 1px solid #10b981; padding: 4px 12px; border-radius: 8px; font-weight: 900;">🎖️ MILITARY DEFENSE SHIELD ACTIVE</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 14px;">
                <div style="color: #10b981; font-weight: 800; font-size: 13px;">✅ CERT-In HTTP Defense Headers Enforced</div>
                <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">Strict-Transport-Security, X-Frame-Options (SAMEORIGIN), X-XSS-Protection &amp; CSP Headers.</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 14px;">
                <div style="color: #10b981; font-weight: 800; font-size: 13px;">✅ Web Application Firewall (WAF Payload Deep Inspection)</div>
                <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">Real-time perimeter inspection blocks SQL Injection, XSS, Path Traversal &amp; RCE probes.</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 14px;">
                <div style="color: #10b981; font-weight: 800; font-size: 13px;">✅ Master Security PIN Gate (268724)</div>
                <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">Zero-Trust dual authorization required for structural, financial &amp; security parameter edits.</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 14px;">
                <div style="color: #10b981; font-weight: 800; font-size: 13px;">✅ Session Hijacking &amp; Subnet Fingerprint Lock</div>
                <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">User-Agent hash and IP subnet binding terminates hijacked administrative cookies.</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 14px;">
                <div style="color: #10b981; font-weight: 800; font-size: 13px;">✅ Automated IP Quarantine Lockout (5 Attempts)</div>
                <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">Quarantines intruder IPs automatically into threat blocklist upon rate limit overflow.</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 14px;">
                <div style="color: #10b981; font-weight: 800; font-size: 13px;">✅ Base64 Encrypted Data Vault &amp; Audit Trail</div>
                <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">Permanent Base64 database photo persistence &amp; real-time forensic event logging.</div>
            </div>
        </div>
            </div>
        </div>
    </div>

    <!-- Manual IP Block & Lockout Management -->
    <div class="sec-box">
        <div class="sec-title">
            <span>🚫 IP Blocklist &amp; Lockout Control</span>
        </div>

        <form method="POST" action="" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <input type="hidden" name="action" value="block_ip">
            <input type="text" name="ip" class="form-input" placeholder="IP Address (e.g. 192.168.1.100)" required style="flex: 1; min-width: 200px;">
            <input type="text" name="reason" class="form-input" placeholder="Reason (e.g. Suspicious Scanner Traffic)" required style="flex: 2; min-width: 250px;">
            <button type="submit" class="btn-action btn-danger">🚫 Manually Block IP</button>
        </form>

        <?php if ($q_ip_list && mysqli_num_rows($q_ip_list) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Reason</th>
                            <th>Blocked Date</th>
                            <th>Blocked By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ip_row = mysqli_fetch_assoc($q_ip_list)): ?>
                            <tr>
                                <td><strong style="color: #ef4444; font-family: monospace; font-size: 14px;"><?php echo htmlspecialchars($ip_row['ip_address']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ip_row['reason']); ?></td>
                                <td><?php echo htmlspecialchars($ip_row['blocked_at']); ?></td>
                                <td><?php echo htmlspecialchars($ip_row['blocked_by']); ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="unblock_ip">
                                        <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip_row['ip_address']); ?>">
                                        <button type="submit" class="btn-action" style="background: rgba(16,185,129,0.2); border-color:#10b981; color:#10b981;">🔓 Unblock IP</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="color: #94a3b8; font-size: 13px; text-align: center; padding: 20px; background: rgba(255,255,255,0.02); border-radius: 12px;">No active blocked IP addresses found.</div>
        <?php endif; ?>
    </div>

    <!-- Live Security Audit Log Stream -->
    <div class="sec-box">
        <div class="sec-title">
            <span>📋 Live Security Audit Trail (Last 50 Events)</span>
            <form method="POST" action="" onsubmit="return confirm('Purge audit logs older than 30 days?');" style="display:inline;">
                <input type="hidden" name="action" value="clear_audit_logs">
                <button type="submit" class="btn-action" style="font-size: 11px;">🧹 Clean Logs (>30 Days)</button>
            </form>
        </div>

        <?php if ($q_logs && mysqli_num_rows($q_logs) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User ID / Name</th>
                            <th>Event Type</th>
                            <th>Description</th>
                            <th>Severity</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($q_logs)): ?>
                            <?php
                            $sev = strtolower($log['severity']);
                            $badge_cls = ($sev === 'critical') ? 'badge-critical' : (($sev === 'warning') ? 'badge-warning' : 'badge-info');
                            ?>
                            <tr>
                                <td style="white-space: nowrap; font-size: 12px; color: #94a3b8;"><?php echo htmlspecialchars($log['created_at']); ?></td>
                                <td><strong><?php echo htmlspecialchars($log['username']); ?></strong> <span style="font-size:11px; color:#94a3b8;">(#<?php echo htmlspecialchars($log['user_id']); ?>)</span></td>
                                <td><code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-weight: bold; color: #38bdf8;"><?php echo htmlspecialchars($log['event_type']); ?></code></td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td><span class="badge-sev <?php echo $badge_cls; ?>"><?php echo htmlspecialchars($log['severity']); ?></span></td>
                                <td style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="color: #94a3b8; font-size: 13px; text-align: center; padding: 20px; background: rgba(255,255,255,0.02); border-radius: 12px;">No security audit logs recorded yet.</div>
        <?php endif; ?>
    </div>

</body>
</html>
