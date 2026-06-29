<?php
require '../../include/db_conn.php';
page_protect();

date_default_timezone_set("Asia/Calcutta");
$today_date = date('Y-m-d');
$q_logs = "SELECT a.*, u.username, u.photo 
           FROM attendance a 
           INNER JOIN users u ON a.uid = u.userid 
           WHERE a.date = '$today_date' 
           ORDER BY a.entry_time DESC";
$res_logs = mysqli_query($con, $q_logs);

if ($res_logs && mysqli_num_rows($res_logs) > 0) {
    while ($row_log = mysqli_fetch_assoc($res_logs)) {
        $avatar = $row_log['photo'] ? $row_log['photo'] : '../../images/logo.png';
        $entry = date('h:i A', strtotime($row_log['entry_time']));
        $exit = $row_log['exit_time'] ? date('h:i A', strtotime($row_log['exit_time'])) : '--:--';
        $status = $row_log['exit_time'] ? 'Checked Out' : 'Active In Gym';
        $status_badge_color = $row_log['exit_time'] ? 'var(--info)' : 'var(--success)';
        $status_badge_bg = $row_log['exit_time'] ? 'rgba(59, 130, 246, 0.15)' : 'rgba(16, 185, 129, 0.15)';
        ?>
        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;">
            <td style="padding: 10px 15px;">
                <img src="<?php echo htmlspecialchars($avatar); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
            </td>
            <td style="padding: 12px 15px; font-family: monospace;"><?php echo htmlspecialchars($row_log['uid']); ?></td>
            <td style="padding: 12px 15px; font-weight: 600; color: #fff;"><?php echo htmlspecialchars($row_log['username']); ?></td>
            <td style="padding: 12px 15px;"><?php echo date('d-M-Y', strtotime($row_log['date'])) . ' (' . date('l', strtotime($row_log['date'])) . ')'; ?></td>
            <td style="padding: 12px 15px; color: var(--success); font-weight: 600;"><?php echo $entry; ?></td>
            <td style="padding: 12px 15px; color: var(--warning); font-weight: 600;"><?php echo $exit; ?></td>
            <td style="padding: 12px 15px; text-align: right;">
                <span style="display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; border: 1px solid <?php echo $status_badge_color; ?>; background: <?php echo $status_badge_bg; ?>; color: <?php echo $status_badge_color; ?>;">
                    <?php echo $status; ?>
                </span>
            </td>
        </tr>
        <?php
    }
} else {
    ?>
    <tr>
        <td colspan="7" style="padding: 25px; text-align: center; color: var(--text-muted);">
            No attendance records logged for today yet.
        </td>
    </tr>
    <?php
}
?>
