<?php
require '../../include/db_conn.php';
page_protect();

// Get role directly from session (nav.php is loaded later in HTML, can't rely on it here)
$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'super_admin';

$msg = '';
$msg_type = '';

// ── Handle Transfer Submission ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_submit'])) {
    $pt_id       = intval($_POST['pt_id']);
    $new_trainer = mysqli_real_escape_string($con, $_POST['new_trainer_id']);
    $reason      = mysqli_real_escape_string($con, trim($_POST['reason'] ?? ''));
    $done_by     = mysqli_real_escape_string($con, $_SESSION['full_name']);

    // Get current PT enrollment
    $q = mysqli_query($con, "SELECT * FROM pt_enrollments WHERE pt_id = '$pt_id'");
    if ($q && mysqli_num_rows($q) > 0) {
        $pt = mysqli_fetch_assoc($q);
        $uid         = $pt['uid'];
        $old_trainer = $pt['trainer_id'];

        if ($old_trainer === $new_trainer) {
            $msg = '⚠️ New trainer is same as current trainer. No change made.';
            $msg_type = 'warning';
        } else {
            // 1. Update pt_enrollments trainer_id
            $q1 = mysqli_query($con, "UPDATE pt_enrollments SET trainer_id = '$new_trainer' WHERE pt_id = '$pt_id'");

            // 2. Update users.trainer_id for active enrollments
            date_default_timezone_set("Asia/Calcutta");
            $today = date('Y-m-d');
            $q_active = mysqli_query($con, "SELECT trainer_id FROM pt_enrollments WHERE uid = '$uid' AND expire_date >= '$today' ORDER BY expire_date DESC LIMIT 1");
            if ($q_active && $ar = mysqli_fetch_assoc($q_active)) {
                mysqli_query($con, "UPDATE users SET trainer_id = '{$ar['trainer_id']}' WHERE userid = '$uid'");
            }

            // 3. Log the transfer
            $q_log = "INSERT INTO pt_transfer_log (pt_id, uid, old_trainer, new_trainer, reason, transferred_by, transfer_date)
                      VALUES ('$pt_id', '$uid', '$old_trainer', '$new_trainer', '$reason', '$done_by', NOW())";
            @mysqli_query($con, $q_log); // May fail if table doesn't exist yet — handled below

            // 4. Notify new trainer via WhatsApp
            $q_mem = mysqli_query($con, "SELECT username FROM users WHERE userid = '$uid'");
            $mem_name = ($q_mem && $mr = mysqli_fetch_assoc($q_mem)) ? $mr['username'] : $uid;
            @send_whatsapp_trainer_pt_notification($con, $new_trainer, $mem_name, $uid);

            if ($q1) {
                $msg = "✅ PT successfully transferred! <strong>{$mem_name}</strong>'s training has been moved to the new trainer.";
                $msg_type = 'success';
            } else {
                $msg = '❌ Database error: ' . mysqli_error($con);
                $msg_type = 'error';
            }
        }
    } else {
        $msg = '❌ PT Enrollment record not found.';
        $msg_type = 'error';
    }
}

// ── Create pt_transfer_log table if not exists ───────────────────────────────
mysqli_query($con, "CREATE TABLE IF NOT EXISTS pt_transfer_log (
    log_id       INT AUTO_INCREMENT PRIMARY KEY,
    pt_id        INT NOT NULL,
    uid          VARCHAR(50) NOT NULL,
    old_trainer  VARCHAR(100) NOT NULL,
    new_trainer  VARCHAR(100) NOT NULL,
    reason       TEXT,
    transferred_by VARCHAR(100),
    transfer_date DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// ── Load all active PT enrollments ──────────────────────────────────────────
date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');
$q_enrollments = mysqli_query($con, "
    SELECT pe.pt_id, pe.uid, pe.trainer_id, pe.enroll_date, pe.expire_date, pe.amount,
           u.username AS member_name, u.mobile AS member_mobile,
           a.Full_name AS trainer_fullname
    FROM pt_enrollments pe
    JOIN users u ON u.userid = pe.uid
    LEFT JOIN admin a ON a.username = pe.trainer_id
    WHERE pe.expire_date >= '$today'
    ORDER BY pe.expire_date DESC
");

// ── Load all trainers ────────────────────────────────────────────────────────
$q_trainers = mysqli_query($con, "SELECT username, Full_name, mobile FROM admin WHERE role = 'trainer' ORDER BY Full_name");
$trainers = [];
while ($tr = mysqli_fetch_assoc($q_trainers)) {
    $trainers[] = $tr;
}

// ── Load recent transfer logs ─────────────────────────────────────────────────
$q_logs = mysqli_query($con, "
    SELECT tl.*, u.username AS member_name,
           a1.Full_name AS old_trainer_name,
           a2.Full_name AS new_trainer_name
    FROM pt_transfer_log tl
    JOIN users u ON u.userid = tl.uid
    LEFT JOIN admin a1 ON a1.username = tl.old_trainer
    LEFT JOIN admin a2 ON a2.username = tl.new_trainer
    ORDER BY tl.transfer_date DESC LIMIT 30
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Transfer PT Assignment</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#pthassubopen > a { background-color:#2b303a;color:#fff; }
        .transfer-card {
            background: rgba(15,7,18,0.92);
            border: 1px solid rgba(255,107,0,0.3);
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 28px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .transfer-card h3 {
            color: #ff6b00;
            font-weight: 800;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,107,0,0.2);
            padding-bottom: 12px;
        }
        .pt-row {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            transition: border-color 0.2s;
        }
        .pt-row:hover { border-color: rgba(255,107,0,0.35); }
        .pt-row .member-info { min-width: 180px; }
        .pt-row .member-info strong { display: block; color: #fff; font-size: 14px; font-weight: 700; }
        .pt-row .member-info span { color: #94a3b8; font-size: 12px; }
        .pt-row .trainer-info { min-width: 160px; }
        .pt-row .trainer-info label { display: block; color: #ff6b00; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .pt-row .trainer-info span { color: #e2e8f0; font-size: 13px; font-weight: 600; }
        .pt-row .expiry-info { min-width: 120px; text-align: center; }
        .pt-row .expiry-info label { display: block; color: #94a3b8; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .pt-row .expiry-info span { color: #10b981; font-size: 13px; font-weight: 700; }
        .pt-row .expiry-info span.expiring { color: #f59e0b; }
        .btn-transfer {
            background: linear-gradient(135deg, #ff6b00, #e55c00);
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-transfer:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(255,107,0,0.5); }
        /* Modal */
        .transfer-modal-backdrop {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
        }
        .transfer-modal-backdrop.open { display: flex; }
        .transfer-modal {
            background: #0d1117;
            border: 1px solid rgba(255,107,0,0.4);
            border-radius: 20px;
            padding: 30px;
            width: 95%;
            max-width: 520px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.7);
        }
        .transfer-modal h3 { color: #ff6b00; margin-top: 0; font-size: 18px; font-weight: 800; }
        .transfer-modal .info-block { background: rgba(255,107,0,0.08); border: 1px solid rgba(255,107,0,0.2); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; }
        .transfer-modal .info-block p { margin: 4px 0; font-size: 13px; color: #e2e8f0; }
        .transfer-modal .info-block strong { color: #ff6b00; }
        .transfer-modal label { display: block; color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .transfer-modal select, .transfer-modal textarea {
            width: 100%; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05); color: #fff;
            padding: 10px 14px; font-size: 13px; margin-bottom: 16px;
            box-sizing: border-box;
        }
        .transfer-modal select option { background: #1a1a2e; }
        .transfer-modal .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
        .btn-cancel-modal { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; padding: 9px 18px; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-cancel-modal:hover { background: rgba(255,255,255,0.12); }
        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th { background: rgba(255,107,0,0.1); color: #ff6b00; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; padding: 10px 12px; text-align: left; border-bottom: 1px solid rgba(255,107,0,0.2); }
        .log-table td { padding: 10px 12px; font-size: 13px; color: #e2e8f0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .log-table tr:hover td { background: rgba(255,107,0,0.04); }
        .arrow-icon { font-size: 18px; color: #ff6b00; }
        .msg-box { border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
        .msg-success { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.4); color: #6ee7b7; }
        .msg-error   { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5; }
        .msg-warning { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.4); color: #fcd34d; }
        .empty-state { text-align: center; padding: 40px; color: #64748b; }
        .empty-state div { font-size: 40px; margin-bottom: 12px; }
        .search-bar { width: 100%; padding: 10px 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; font-size: 13px; margin-bottom: 18px; box-sizing: border-box; }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
<div class="page-container sidebar-collapsed" id="navbarcollapse">
    <div class="sidebar-menu">
        <header class="logo-env">
            <div class="logo">
                <a href="index.php">
                    <?php $sl = $gym_settings_data["gym_logo"] ?? "../../images/logo.png"; ?>
                    <img src="<?php echo htmlspecialchars($sl); ?>" alt="Gym Logo" style="max-height:80px;max-width:192px;" />
                </a>
            </div>
            <div class="sidebar-collapse" onclick="collapseSidebar()">
                <a href="#" class="sidebar-collapse-icon with-animation"><i class="entypo-menu"></i></a>
            </div>
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

        <h2>🔄 Transfer Personal Training</h2>
        <hr />

        <?php if ($msg): ?>
        <div class="msg-box msg-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- ── Active PT Enrollments ─────────────────────────────────────── -->
        <div class="transfer-card">
            <h3>🏋️ Active PT Enrollments — Select to Transfer</h3>
            <input type="text" class="search-bar" id="pt-search" placeholder="🔍 Search by member name or trainer..." oninput="filterPT(this.value)">

            <div id="pt-list">
            <?php
            $has_rows = false;
            if ($q_enrollments && mysqli_num_rows($q_enrollments) > 0):
                while ($row = mysqli_fetch_assoc($q_enrollments)):
                    $has_rows = true;
                    $days_left = (strtotime($row['expire_date']) - strtotime($today)) / 86400;
                    $expiry_class = $days_left <= 10 ? 'expiring' : '';
                    $trainer_display = htmlspecialchars($row['trainer_fullname'] ?: $row['trainer_id']);
                    $trainer_id_safe = htmlspecialchars($row['trainer_id']);
                    $trainers_json = json_encode($trainers);
            ?>
            <div class="pt-row" data-search="<?php echo strtolower($row['member_name'] . ' ' . $row['trainer_fullname']); ?>">
                <div class="member-info">
                    <strong>👤 <?php echo htmlspecialchars($row['member_name']); ?></strong>
                    <span><?php echo htmlspecialchars($row['member_mobile']); ?></span>
                    <span style="display:block;color:#64748b;font-size:11px;margin-top:2px;">ID: <?php echo htmlspecialchars($row['uid']); ?></span>
                </div>
                <div class="trainer-info">
                    <label>Current Trainer</label>
                    <span><?php echo $trainer_display; ?></span>
                </div>
                <div class="expiry-info">
                    <label>Expires</label>
                    <span class="<?php echo $expiry_class; ?>"><?php echo date('d M Y', strtotime($row['expire_date'])); ?></span>
                    <span style="display:block;color:#64748b;font-size:11px;"><?php echo ceil($days_left); ?> days left</span>
                </div>
                <div>
                    <button class="btn-transfer" onclick='openTransferModal(
                        <?php echo $row['pt_id']; ?>,
                        <?php echo json_encode($row['member_name']); ?>,
                        <?php echo json_encode($row['uid']); ?>,
                        <?php echo json_encode($trainer_display); ?>,
                        <?php echo json_encode($row['trainer_id']); ?>,
                        <?php echo $trainers_json; ?>
                    )'>🔄 Transfer</button>
                </div>
            </div>
            <?php endwhile; endif; ?>
            <?php if (!$has_rows): ?>
            <div class="empty-state">
                <div>🏋️</div>
                <p>No active PT enrollments found.</p>
            </div>
            <?php endif; ?>
            </div>
        </div>

        <!-- ── Transfer Log ──────────────────────────────────────────────── -->
        <div class="transfer-card">
            <h3>📋 Recent Transfer History</h3>
            <?php
            $has_logs = false;
            if ($q_logs && mysqli_num_rows($q_logs) > 0):
                $has_logs = true;
            ?>
            <div style="overflow-x:auto;">
            <table class="log-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>From Trainer</th>
                        <th></th>
                        <th>To Trainer</th>
                        <th>Reason</th>
                        <th>Done By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; while ($log = mysqli_fetch_assoc($q_logs)): ?>
                <tr>
                    <td style="color:#64748b;"><?php echo $i++; ?></td>
                    <td><strong style="color:#fff;"><?php echo htmlspecialchars($log['member_name']); ?></strong></td>
                    <td style="color:#fca5a5;"><?php echo htmlspecialchars($log['old_trainer_name'] ?: $log['old_trainer']); ?></td>
                    <td class="arrow-icon">→</td>
                    <td style="color:#6ee7b7;"><?php echo htmlspecialchars($log['new_trainer_name'] ?: $log['new_trainer']); ?></td>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo htmlspecialchars($log['reason'] ?: '—'); ?></td>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo htmlspecialchars($log['transferred_by']); ?></td>
                    <td style="color:#64748b;font-size:12px;"><?php echo date('d M Y H:i', strtotime($log['transfer_date'])); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div>📋</div>
                <p>No transfer history yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Transfer Modal ──────────────────────────────────────────────────────── -->
<div class="transfer-modal-backdrop" id="transfer-modal-backdrop">
    <div class="transfer-modal">
        <h3>🔄 Transfer PT Assignment</h3>

        <div class="info-block">
            <p>👤 Member: <strong id="modal-member-name">—</strong></p>
            <p>🏋️ Current Trainer: <strong id="modal-current-trainer">—</strong></p>
        </div>

        <form method="POST" onsubmit="return confirmTransfer()">
            <input type="hidden" name="pt_id" id="modal-pt-id">
            <input type="hidden" name="transfer_submit" value="1">

            <label>Select New Trainer *</label>
            <select name="new_trainer_id" id="modal-new-trainer" required>
                <option value="">— Choose Trainer —</option>
            </select>

            <label>Reason for Transfer (optional)</label>
            <textarea name="reason" id="modal-reason" rows="3" placeholder="e.g. Trainer schedule conflict, member request, trainer resigned..."></textarea>

            <div class="modal-footer">
                <button type="button" class="btn-cancel-modal" onclick="closeTransferModal()">Cancel</button>
                <button type="submit" class="btn-transfer">✅ Confirm Transfer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTransferModal(ptId, memberName, uid, currentTrainer, currentTrainerId, trainers) {
    document.getElementById('modal-pt-id').value = ptId;
    document.getElementById('modal-member-name').innerText = memberName;
    document.getElementById('modal-current-trainer').innerText = currentTrainer;

    // Populate trainer dropdown — exclude current trainer
    var sel = document.getElementById('modal-new-trainer');
    sel.innerHTML = '<option value="">— Choose New Trainer —</option>';
    trainers.forEach(function(t) {
        if (t.username !== currentTrainerId) {
            var opt = document.createElement('option');
            opt.value = t.username;
            opt.text  = t.Full_name + (t.mobile ? ' (' + t.mobile + ')' : '');
            sel.appendChild(opt);
        }
    });

    document.getElementById('transfer-modal-backdrop').classList.add('open');
}

function closeTransferModal() {
    document.getElementById('transfer-modal-backdrop').classList.remove('open');
    document.getElementById('modal-reason').value = '';
}

function confirmTransfer() {
    var newT = document.getElementById('modal-new-trainer').value;
    var mem  = document.getElementById('modal-member-name').innerText;
    if (!newT) { alert('Please select a new trainer.'); return false; }
    return confirm('Are you sure you want to transfer ' + mem + "'s PT to the selected trainer?\n\nThis will update all active PT records and notify the new trainer.");
}

// Click backdrop to close
document.getElementById('transfer-modal-backdrop').addEventListener('click', function(e) {
    if (e.target === this) closeTransferModal();
});

// Search filter
function filterPT(val) {
    val = val.toLowerCase();
    document.querySelectorAll('#pt-list .pt-row').forEach(function(row) {
        row.style.display = (row.dataset.search.includes(val)) ? '' : 'none';
    });
}
</script>

<?php include '../../include/dev_credit.php'; ?>
</body>
</html>
