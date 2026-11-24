<?php
require '../../include/db_conn.php';
require '../../include/gemini_config.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);
$today = date('Y-m-d');
$next_7 = date('Y-m-d', strtotime('+7 days'));

// Fetch live gym performance stats
$m_total_res = mysqli_query($con, "SELECT COUNT(*) as c FROM users WHERE userid != 'PENDING'");
$m_total = ($m_total_res && $r = mysqli_fetch_assoc($m_total_res)) ? intval($r['c']) : 0;

$m_active_res = mysqli_query($con, "SELECT COUNT(DISTINCT uid) as c FROM enrolls_to WHERE expire >= '$today'");
$m_active = ($m_active_res && $r = mysqli_fetch_assoc($m_active_res)) ? intval($r['c']) : 0;

$rev_res = mysqli_query($con, "SELECT COALESCE(SUM(paid_amount), 0) as total FROM enrolls_to");
$total_rev = ($rev_res && $r = mysqli_fetch_assoc($rev_res)) ? intval($r['total']) : 0;

$exp_res = mysqli_query($con, "SELECT COALESCE(SUM(amount), 0) as total FROM expenses");
$total_exp = ($exp_res && $r = mysqli_fetch_assoc($exp_res)) ? intval($r['total']) : 0;

$expiring_res = mysqli_query($con, "SELECT COUNT(*) as c FROM enrolls_to WHERE expire BETWEEN '$today' AND '$next_7' AND renewal = 'yes'");
$expiring_count = ($expiring_res && $r = mysqli_fetch_assoc($expiring_res)) ? intval($r['c']) : 0;

$dues_res = mysqli_query($con, "SELECT COUNT(*) as c, COALESCE(SUM(balance), 0) as amt FROM enrolls_to WHERE balance > 0");
$dues_row = $dues_res ? mysqli_fetch_assoc($dues_res) : ['c' => 0, 'amt' => 0];
$dues_count = intval($dues_row['c'] ?? 0);
$dues_amount = intval($dues_row['amt'] ?? 0);

$net_profit = $total_rev - $total_exp;

// Handle AI Owner Prompt Engine
$raw_query = isset($_POST['query']) ? trim($_POST['query']) : '';
$query = strtolower($raw_query);
$ai_answer = "";

if (!empty($raw_query)) {
    $sys = "You are the executive AI business advisor for {$gym['gym_name']} in Khamgaon, Maharashtra. Provide sharp, practical, data-driven gym operations and retention advice based ONLY on the supplied live metrics.";
    $context_prompt = "Live Gym Metrics as of $today:\n"
                    . "- Total Members: $m_total\n"
                    . "- Active Subscriptions: $m_active\n"
                    . "- Expiring in Next 7 Days: $expiring_count\n"
                    . "- Pending Balance Dues: $dues_count members totaling ₹" . number_format($dues_amount) . "\n"
                    . "- Total Membership Revenue: ₹" . number_format($total_rev) . "\n"
                    . "- Total Expenses: ₹" . number_format($total_exp) . "\n"
                    . "- Net Profit: ₹" . number_format($net_profit) . "\n\n"
                    . "Owner Question: $raw_query";

    $live_ai = query_gemini_ai($context_prompt, $sys);
    if (!empty($live_ai)) {
        $ai_answer = $live_ai;
    } elseif (strpos($query, 'performing') !== false || strpos($query, 'performance') !== false || strpos($query, 'business') !== false) {
        $ai_answer = "📈 **Sudarshan AI Gym Manager Overview:**\n\n" .
                     "• **Total Registered Members**: $m_total Athletes\n" .
                     "• **Active Subscriptions**: $m_active Members\n" .
                     "• **Expiring Next 7 Days**: $expiring_count Members\n" .
                     "• **Total Revenue Earned**: ₹" . number_format($total_rev) . "\n" .
                     "• **Total Expenses**: ₹" . number_format($total_exp) . "\n" .
                     "• **Net Profit**: ₹" . number_format($net_profit);
    } elseif (strpos($query, 'today') !== false || strpos($query, 'action') !== false || strpos($query, 'do') !== false) {
        $ai_answer = "📋 **Today's Executive Priorities for Owner:**\n\n" .
                     "1. **Follow up with $expiring_count Expiring Members** (next 7 days).\n" .
                     "2. **Collect Outstanding Dues**: $dues_count members owe ₹" . number_format($dues_amount) . ".\n" .
                     "3. **Review Churn Radar**: Check inactive members and trigger WhatsApp retention nudges.";
    } elseif (strpos($query, 'churn') !== false || strpos($query, 'risk') !== false) {
        $ai_answer = "⚠️ **Member Churn Intelligence:**\n\n" .
                     "• **Expiring within 7 days**: $expiring_count Members\n" .
                     "• **Active vs Total**: $m_active active out of $m_total total members.\n\n" .
                     "👉 Open Churn Radar in your sidebar to send WhatsApp retention nudges!";
    } else {
        $ai_answer = "🤖 **AI Gym Manager Assistant:**\n\nHow can I help you manage **{$gym['gym_name']}** today?\n\nTry asking:\n" .
                     "• *How is my gym performing this month?*\n" .
                     "• *What should I do today?*\n" .
                     "• *Which members are at risk of churn?*";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Gym Manager Command Center | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: var(--text-main); padding: 25px; }
        .card { background: var(--card-bg); color: var(--text-main); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .btn-send { background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #ffffff; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; cursor: pointer; }
        .form-control { background: rgba(3,7,18,0.8); border: 1px solid rgba(255,107,0,0.3); color: var(--text-main); padding: 12px 18px; border-radius: 12px; width: 100%; font-size: 14px; margin-bottom: 15px; }
        .ai-box { background: var(--bg-dark); border: 1px solid var(--accent-primary); border-radius: 18px; padding: 20px; color: var(--text-main); line-height: 1.6; white-space: pre-wrap; font-size: 14px; box-shadow: 0 0 30px rgba(255,107,0,0.25); }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: var(--accent-primary); margin: 0;">👑 OWNER AI BUSINESS COMMAND CENTER</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">SUDARSHAN FITNESS v2.0 • AI GYM MANAGER &amp; DAILY EXECUTIVE PRIORITIES</div>
            </div>
            <a href="index.php" style="background: rgba(255,107,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <!-- Live KPI Snapshot -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div class="card" style="margin-bottom: 0; text-align: center;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">TOTAL MEMBERS</div>
                <div style="font-size: 32px; font-weight: 900; color: var(--text-main); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;"><?php echo number_format($m_total); ?></div>
            </div>
            <div class="card" style="margin-bottom: 0; text-align: center;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">ACTIVE SUBSCRIBERS</div>
                <div style="font-size: 32px; font-weight: 900; color: #10b981; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;"><?php echo number_format($m_active); ?></div>
            </div>
            <div class="card" style="margin-bottom: 0; text-align: center;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">TOTAL REVENUE</div>
                <div style="font-size: 32px; font-weight: 900; color: var(--accent-primary); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">₹<?php echo number_format($total_rev); ?></div>
            </div>
            <div class="card" style="margin-bottom: 0; text-align: center;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">NET PROFIT</div>
                <div style="font-size: 32px; font-weight: 900; color: #ffb703; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">₹<?php echo number_format($net_profit); ?></div>
            </div>
        </div>

        <!-- AI Executive Chat Box -->
        <div class="card">
            <h3 style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: var(--text-main); margin-top: 0;">🤖 Ask AI Gym Manager</h3>
            
            <form method="POST">
                <input type="text" name="query" class="form-control" placeholder="Ask AI: e.g. How is my gym performing this month? or What should I do today?" required>
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button type="submit" class="btn-send">ASK AI MANAGER ➔</button>
                    <button type="button" onclick="document.querySelector('input[name=query]').value='How is my gym performing this month?'; this.form.submit();" style="background: rgba(255,107,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 16px; border-radius: 12px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-weight: 800; cursor: pointer;">💡 Performance Brief</button>
                    <button type="button" onclick="document.querySelector('input[name=query]').value='What should I do today?'; this.form.submit();" style="background: rgba(255,107,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 16px; border-radius: 12px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-weight: 800; cursor: pointer;">📋 Today's Priorities</button>
                </div>
            </form>

            <?php if ($ai_answer): ?>
                <div class="ai-box"><?php echo htmlspecialchars($ai_answer); ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
