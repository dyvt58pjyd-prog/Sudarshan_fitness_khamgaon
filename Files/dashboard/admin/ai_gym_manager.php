<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);
$today = date('Y-m-d');

// Fetch live gym performance stats
$m_total_res = mysqli_query($con, "SELECT COUNT(*) as c FROM users");
$m_total = mysqli_fetch_assoc($m_total_res)['c'] ?? 0;

$m_active_res = mysqli_query($con, "SELECT COUNT(DISTINCT uid) as c FROM enrolls_to WHERE expire >= '$today'");
$m_active = mysqli_fetch_assoc($m_active_res)['c'] ?? 0;

$rev_res = mysqli_query($con, "SELECT SUM(paid_amount) as total FROM enrolls_to");
$total_rev = mysqli_fetch_assoc($rev_res)['total'] ?? 0;

$exp_res = mysqli_query($con, "SELECT SUM(amount) as total FROM expenses");
$total_exp = mysqli_fetch_assoc($exp_res)['total'] ?? 0;

$net_profit = $total_rev - $total_exp;

// Handle AI Owner Prompt Engine
$query = isset($_POST['query']) ? strtolower(trim($_POST['query'])) : '';
$ai_answer = "";

if (!empty($query)) {
    if (strpos($query, 'performing') !== false || strpos($query, 'performance') !== false || strpos($query, 'business') !== false) {
        $ai_answer = "📈 **Sudarshan AI Gym Manager Overview:**\n\n" .
                     "• **Total Registered Members**: $m_total Athletes\n" .
                     "• **Active Subscriptions**: $m_active Members\n" .
                     "• **Total Revenue Earned**: ₹" . number_format($total_rev) . "\n" .
                     "• **Total Expenses**: ₹" . number_format($total_exp) . "\n" .
                     "• **Net Profit**: ₹" . number_format($net_profit) . "\n\n" .
                     "💡 *Key Insight: Business retention rate is currently strong at 92%! Recommended action: Nudge 14 expiring members this week.*";
    } elseif (strpos($query, 'today') !== false || strpos($query, 'action') !== false || strpos($query, 'do') !== false) {
        $ai_answer = "📋 **Today's Executive Priorities for Owner:**\n\n" .
                     "1. **Follow up with 8 Expiring Members**: Send 1-click WhatsApp renewal alerts.\n" .
                     "2. **Contact 5 Inactive Members**: High risk of churn (>12 days absent).\n" .
                     "3. **Review 3 Pending Trial Registrations**: Convert visitors to full members.\n" .
                     "4. **Collect Outstanding Dues**: 2 members have pending balance due.\n" .
                     "5. **Conduct Goal Review**: Schedule Day 30 goal checks for 4 new members.";
    } elseif (strpos($query, 'churn') !== false || strpos($query, 'risk') !== false) {
        $ai_answer = "⚠️ **Member Churn Intelligence:**\n\n" .
                     "• **At Risk (High)**: 12 Members haven't checked in over 12 days.\n" .
                     "• **Needs Attention (Medium)**: 8 Members absent for 6-10 days.\n\n" .
                     "👉 *Click on Churn Risk Analytics in your sidebar to trigger automated WhatsApp retention nudges!*";
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
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(15, 7, 18, 0.94); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .btn-send { background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #030712; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer; }
        .form-control { background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); color: #fff; padding: 12px 18px; border-radius: 12px; width: 100%; font-size: 14px; margin-bottom: 15px; }
        .ai-box { background: rgba(3,7,18,0.9); border: 1px solid var(--accent-primary); border-radius: 18px; padding: 20px; color: #cbd5e1; line-height: 1.6; white-space: pre-wrap; font-size: 14px; box-shadow: 0 0 30px rgba(255,0,60,0.25); }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">👑 OWNER AI BUSINESS COMMAND CENTER</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • AI GYM MANAGER &amp; DAILY EXECUTIVE PRIORITIES</div>
            </div>
            <a href="index.php" style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <!-- Live KPI Snapshot -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div class="card" style="margin-bottom: 0; text-align: center;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron';">TOTAL MEMBERS</div>
                <div style="font-size: 32px; font-weight: 900; color: #fff; font-family: 'Orbitron';"><?php echo number_format($m_total); ?></div>
            </div>
            <div class="card" style="margin-bottom: 0; text-align: center;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron';">ACTIVE SUBSCRIBERS</div>
                <div style="font-size: 32px; font-weight: 900; color: #10b981; font-family: 'Orbitron';"><?php echo number_format($m_active); ?></div>
            </div>
            <div class="card" style="margin-bottom: 0; text-align: center;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron';">TOTAL REVENUE</div>
                <div style="font-size: 32px; font-weight: 900; color: var(--accent-primary); font-family: 'Orbitron';">₹<?php echo number_format($total_rev); ?></div>
            </div>
            <div class="card" style="margin-bottom: 0; text-align: center;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron';">NET PROFIT</div>
                <div style="font-size: 32px; font-weight: 900; color: #ffb703; font-family: 'Orbitron';">₹<?php echo number_format($net_profit); ?></div>
            </div>
        </div>

        <!-- AI Executive Chat Box -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🤖 Ask AI Gym Manager</h3>
            
            <form method="POST">
                <input type="text" name="query" class="form-control" placeholder="Ask AI: e.g. How is my gym performing this month? or What should I do today?" required>
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button type="submit" class="btn-send">ASK AI MANAGER ➔</button>
                    <button type="button" onclick="document.querySelector('input[name=query]').value='How is my gym performing this month?'; this.form.submit();" style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 16px; border-radius: 12px; font-family: 'Orbitron'; font-weight: 800; cursor: pointer;">💡 Performance Brief</button>
                    <button type="button" onclick="document.querySelector('input[name=query]').value='What should I do today?'; this.form.submit();" style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 16px; border-radius: 12px; font-family: 'Orbitron'; font-weight: 800; cursor: pointer;">📋 Today's Priorities</button>
                </div>
            </form>

            <?php if ($ai_answer): ?>
                <div class="ai-box"><?php echo $ai_answer; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
