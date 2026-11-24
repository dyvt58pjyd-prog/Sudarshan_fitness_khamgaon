<?php
define('WALLET_PORTAL_API', true);
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = trim((string)($input['action'] ?? 'issue_single'));

function compute_pass_link_expiry($validity, $expire_iso = '') {
    $v = strtolower(trim((string)$validity));
    if ($v === '24h') return time() + 86400;
    if ($v === '7d') return time() + (7 * 86400);
    if ($v === '30d') return time() + (30 * 86400);
    if ($v === 'plan_expire' && !empty($expire_iso)) {
        $ts = strtotime($expire_iso . ' 23:59:59');
        return ($ts && $ts > time()) ? $ts : 0;
    }
    return 0; // permanent signed link
}

if ($action === 'recent_logs') {
    ensure_wallet_pass_logs_table($con);
    $logs = [];
    $q_logs = mysqli_query($con, "SELECT l.*, u.username FROM wallet_pass_logs l LEFT JOIN users u ON l.uid = u.userid ORDER BY l.id DESC LIMIT 25");
    if ($q_logs) {
        while ($r = mysqli_fetch_assoc($q_logs)) {
            $logs[] = $r;
        }
    }
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit();
}

$platform = strtolower(trim((string)($input['platform'] ?? 'universal')));
if (!in_array($platform, ['apple', 'android', 'universal'], true)) {
    $platform = 'universal';
}
$validity = strtolower(trim((string)($input['validity'] ?? 'permanent')));
$note = trim((string)($input['note'] ?? ''));

if ($action === 'bulk_issue') {
    $uids = $input['uids'] ?? [];
    $channel = strtolower(trim((string)($input['channel'] ?? 'whatsapp')));
    if (!in_array($channel, ['whatsapp', 'email'], true) || !is_array($uids) || count($uids) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Provide valid members and WhatsApp or Email channel.']);
        exit();
    }

    $uids = array_slice(array_values(array_unique(array_filter(array_map('trim', $uids)))), 0, 50);
    $sent_count = 0;
    $skipped = [];

    foreach ($uids as $uid_item) {
        $ctx = fetch_wallet_pass_member_context($con, $uid_item, '../../');
        if (!$ctx) {
            $skipped[] = "$uid_item: Member not found";
            continue;
        }
        $exp_ts = compute_pass_link_expiry($validity, $ctx['expire_iso']);
        $signed_url = build_signed_wallet_pass_url($con, $ctx['user']['userid'], $exp_ts, false, $platform);

        if ($channel === 'whatsapp') {
            $mobile = preg_replace('/[^0-9]/', '', (string)($ctx['user']['mobile'] ?? ''));
            if (strlen($mobile) < 10) {
                $skipped[] = "{$ctx['user']['username']} (#{$uid_item}): Missing mobile number";
                continue;
            }
            $wa_msg = build_wallet_pass_whatsapp_text($ctx, $signed_url, $platform);
            if (function_exists('enqueue_whatsapp_message') && enqueue_whatsapp_message($con, $mobile, $wa_msg)) {
                log_wallet_pass_issuance($con, $ctx['user']['userid'], $platform, 'whatsapp_bulk', $current_staff_user, $current_staff_role, $exp_ts, $note);
                $sent_count++;
            } else {
                $skipped[] = "{$ctx['user']['username']} (#{$uid_item}): WhatsApp outbox failed";
            }
        } else {
            $mail_res = send_wallet_pass_branded_email($con, $ctx, $signed_url, $platform);
            if (!empty($mail_res['sent'])) {
                log_wallet_pass_issuance($con, $ctx['user']['userid'], $platform, 'email_bulk', $current_staff_user, $current_staff_role, $exp_ts, $note);
                $sent_count++;
            } else {
                $skipped[] = "{$ctx['user']['username']} (#{$uid_item}): " . ($mail_res['reason'] ?: 'Email failed');
            }
        }
    }

    $skip_cnt = count($skipped);
    echo json_encode([
        'success'       => true,
        'sent_count'    => $sent_count,
        'skipped_count' => $skip_cnt,
        'skipped'       => array_slice($skipped, 0, 10),
        'message'       => "Issued {$sent_count} pass(es) via " . strtoupper($channel) . ($skip_cnt > 0 ? " ({$skip_cnt} skipped)." : '.')
    ]);
    exit();
}

// Single pass issue
$uid = trim((string)($input['uid'] ?? ''));
$channel = strtolower(trim((string)($input['channel'] ?? 'copy_link')));
if (!in_array($channel, ['whatsapp', 'email', 'counter_qr', 'copy_link'], true)) {
    $channel = 'copy_link';
}
if ($uid === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select a member first.']);
    exit();
}

$ctx = fetch_wallet_pass_member_context($con, $uid, '../../');
if (!$ctx) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Member not found.']);
    exit();
}

$exp_ts = compute_pass_link_expiry($validity, $ctx['expire_iso']);
$signed_url = build_signed_wallet_pass_url($con, $ctx['user']['userid'], $exp_ts, false, $platform);
$rotating_qr = generate_rotating_qr_payload($con, $ctx['user']['userid']);
$wa_text = build_wallet_pass_whatsapp_text($ctx, $signed_url, $platform);
$wa_direct_url = 'https://wa.me/' . (strlen(preg_replace('/[^0-9]/', '', (string)$ctx['user']['mobile'])) === 10 ? '91' . preg_replace('/[^0-9]/', '', (string)$ctx['user']['mobile']) : preg_replace('/[^0-9]/', '', (string)$ctx['user']['mobile'])) . '?text=' . urlencode($wa_text);

$channel_ok = true;
$channel_msg = 'Signed pass ready.';

if ($channel === 'whatsapp') {
    $mobile = preg_replace('/[^0-9]/', '', (string)($ctx['user']['mobile'] ?? ''));
    if (strlen($mobile) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Member does not have a valid 10-digit WhatsApp/mobile number.']);
        exit();
    }
    $queued = function_exists('enqueue_whatsapp_message') && enqueue_whatsapp_message($con, $mobile, $wa_text);
    if (!$queued) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to queue WhatsApp pass message.', 'wa_direct_url' => $wa_direct_url]);
        exit();
    }
    $channel_msg = "Pass queued for WhatsApp ({$mobile})!";
} elseif ($channel === 'email') {
    $mail_res = send_wallet_pass_branded_email($con, $ctx, $signed_url, $platform);
    if (empty($mail_res['sent'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $mail_res['reason'] ?: 'Unable to send email.']);
        exit();
    }
    $channel_msg = "Branded Wallet Pass emailed to {$ctx['user']['email']}!";
} elseif ($channel === 'counter_qr') {
    $channel_msg = "Counter QR handoff issued for {$ctx['user']['username']}.";
} else {
    $channel_msg = "Signed pass link generated for {$ctx['user']['username']}.";
}

log_wallet_pass_issuance($con, $ctx['user']['userid'], $platform, $channel, $current_staff_user, $current_staff_role, $exp_ts, $note);

echo json_encode([
    'success'       => true,
    'uid'           => $ctx['user']['userid'],
    'platform'      => $platform,
    'channel'       => $channel,
    'signed_url'    => $signed_url,
    'rotating_qr'   => $rotating_qr,
    'wa_direct_url' => $wa_direct_url,
    'expires_label' => ($exp_ts > 0 ? date('d M Y h:i A', $exp_ts) : 'Never (Linked to Active Status)'),
    'message'       => $channel_msg
]);
