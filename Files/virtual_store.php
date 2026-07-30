<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$gym = get_gym_details($con);
$member_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Member';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Virtual Gym Store &amp; Supplement Bar | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #070a12;
            --card-bg: rgba(15, 23, 42, 0.85);
            --accent: #ff6b00;
            --accent-green: #10b981;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: rgba(11, 15, 25, 0.95);
            border-bottom: 1px solid var(--glass-border);
            padding: 15px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(15px);
        }

        .gym-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .gym-logo {
            max-height: 45px;
        }

        .page-title {
            font-size: 18px;
            font-weight: 900;
            background: linear-gradient(135deg, #10b981, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid var(--glass-border);
            padding: 8px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 25px 20px;
            width: 100%;
        }

        .grid-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .prod-card {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        .prod-card:hover {
            border-color: #10b981;
            transform: translateY(-4px);
        }

        .prod-img {
            font-size: 55px;
            text-align: center;
            margin-bottom: 15px;
        }

        .prod-title {
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .prod-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
            margin-bottom: 15px;
        }

        .prod-price {
            font-size: 20px;
            font-weight: 900;
            color: #10b981;
        }

        .btn-buy {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 5px 15px rgba(16,185,129,0.4);
        }
    </style>
</head>
<body>

    <header>
        <div class="gym-brand">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" class="gym-logo" alt="Gym Logo">
            <div>
                <div class="page-title">🥤 VIRTUAL GYM SUPPLEMENT SHOP</div>
                <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($gym['gym_name']); ?> Products &amp; Gear Bar</div>
            </div>
        </div>

        <a href="javascript:history.back()" class="btn-back">← Back to Dashboard</a>
    </header>

    <div class="container">
        <div style="background: rgba(16,185,129,0.1); border: 1px solid #10b981; border-radius: 18px; padding: 20px; margin-bottom: 25px;">
            <h3 style="color:#10b981; margin-bottom: 5px;">🛒 Official Gym Supplements &amp; Training Gear</h3>
            <p style="color:#cbd5e1; font-size:13px;">Simulate product orders, order personal training packages, and consult with front desk reception for instant order pickup.</p>
        </div>

        <div class="grid-products">
            <div class="prod-card">
                <div>
                    <div class="prod-img">🥛</div>
                    <div class="prod-title">Whey Protein Isolate (1kg)</div>
                    <div class="prod-desc">Ultra-pure fast-absorbing protein with 26g Protein &amp; 5.5g BCAAs per scoop.</div>
                </div>
                <div>
                    <div class="prod-price">₹2,499</div>
                    <button class="btn-buy" onclick="buyProduct('Whey Protein Isolate', 2499)">Simulate Order 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">⚡</div>
                    <div class="prod-title">Creatine Monohydrate (250g)</div>
                    <div class="prod-desc">Micronized 100% pure creatine for explosive strength and muscle volume.</div>
                </div>
                <div>
                    <div class="prod-price">₹899</div>
                    <button class="btn-buy" onclick="buyProduct('Creatine Monohydrate', 899)">Simulate Order 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">🔥</div>
                    <div class="prod-title">Pre-Workout Energy Matrix</div>
                    <div class="prod-desc">High-stimulant pre-workout formula with 200mg Caffeine &amp; L-Citrulline.</div>
                </div>
                <div>
                    <div class="prod-price">₹1,299</div>
                    <button class="btn-buy" onclick="buyProduct('Pre-Workout Energy', 1299)">Simulate Order 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">🏋️</div>
                    <div class="prod-title">Heavy Duty Lifting Straps</div>
                    <div class="prod-desc">Padded cotton wrist straps for heavy deadlifts, rows and shrugs.</div>
                </div>
                <div>
                    <div class="prod-price">₹399</div>
                    <button class="btn-buy" onclick="buyProduct('Lifting Straps', 399)">Simulate Order 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">🥤</div>
                    <div class="prod-title">Sudarshan Fitness Shaker (700ml)</div>
                    <div class="prod-desc">Leak-proof shaker bottle with stainless steel whisk ball.</div>
                </div>
                <div>
                    <div class="prod-price">₹299</div>
                    <button class="btn-buy" onclick="buyProduct('Sudarshan Shaker Bottle', 299)">Simulate Order 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">🏆</div>
                    <div class="prod-title">Personal Trainer 1-on-1 Pass</div>
                    <div class="prod-desc">1 Month dedicated 1-on-1 Personal Training Session with Certified Coach.</div>
                </div>
                <div>
                    <div class="prod-price">₹3,500</div>
                    <button class="btn-buy" onclick="buyProduct('Personal Trainer Pass', 3500)">Simulate Order 🛒</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function buyProduct(name, price) {
            alert(`🎉 Order Simulated Successfully!\n\nProduct: ${name}\nPrice: ₹${price.toLocaleString('en-IN')}\n\nPlease show this confirmation at Sudarshan Fitness reception counter for pickup!`);
        }
    </script>
</body>
</html>
