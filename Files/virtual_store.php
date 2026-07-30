<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$gym = get_gym_details($con);
$member_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Hunter';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>[SYSTEM ITEM SHOP] Solo Leveling Supplements | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #030712;
            --card-bg: rgba(9, 14, 28, 0.9);
            --system-cyan: #00f0ff;
            --monarch-purple: #7000ff;
            --quest-gold: #ffb703;
            --text-main: #f8fafc;
            --text-muted: #64748b;
            --system-border: rgba(0, 240, 255, 0.35);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: 
                radial-gradient(circle at 50% 20%, rgba(112, 0, 255, 0.15) 0%, transparent 60%),
                radial-gradient(circle at 80% 80%, rgba(0, 240, 255, 0.1) 0%, transparent 50%);
        }

        header {
            background: rgba(5, 9, 20, 0.95);
            border-bottom: 2px solid var(--system-border);
            padding: 15px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 30px rgba(0, 240, 255, 0.2);
        }

        .gym-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .gym-logo {
            max-height: 45px;
            filter: drop-shadow(0 0 10px rgba(0,240,255,0.5));
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 900;
            background: linear-gradient(135deg, #00f0ff, #0077ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 20px rgba(0, 240, 255, 0.5);
        }

        .btn-back {
            background: rgba(0, 240, 255, 0.1);
            color: var(--system-cyan);
            border: 1px solid var(--system-border);
            padding: 8px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: var(--system-cyan);
            color: #030712;
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.6);
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
            border: 1px solid var(--system-border);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
            box-shadow: 0 0 25px rgba(0,240,255,0.15);
            position: relative;
        }

        .prod-card:hover {
            border-color: var(--system-cyan);
            box-shadow: 0 0 35px rgba(0,240,255,0.4);
            transform: translateY(-4px);
        }

        .prod-img {
            font-size: 55px;
            text-align: center;
            margin-bottom: 15px;
        }

        .prod-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 15px;
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
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            font-weight: 900;
            color: var(--system-cyan);
            text-shadow: 0 0 10px var(--system-cyan);
        }

        .btn-buy {
            background: linear-gradient(135deg, var(--system-cyan), #0077ff);
            color: #030712;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 900;
            font-size: 12px;
            font-family: 'Orbitron', sans-serif;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 0 20px rgba(0,240,255,0.5);
            transition: all 0.2s ease;
        }

        .btn-buy:hover {
            box-shadow: 0 0 30px rgba(0,240,255,0.8);
        }
    </style>
</head>
<body>

    <header>
        <div class="gym-brand">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" class="gym-logo" alt="Gym Logo">
            <div>
                <div class="page-title">[ SYSTEM ITEM SHOP ]</div>
                <div style="font-size: 11px; color: var(--system-cyan); font-family: 'Orbitron'; font-weight: 700;">STAT ELIXIRS &amp; MONARCH RECOVERY GEAR</div>
            </div>
        </div>

        <a href="javascript:history.back()" class="btn-back">← RETURN TO BASE</a>
    </header>

    <div class="container">
        <div style="background: rgba(0,240,255,0.08); border: 1px solid var(--system-cyan); border-radius: 18px; padding: 20px; margin-bottom: 25px; box-shadow: 0 0 25px rgba(0,240,255,0.2);">
            <h3 style="color:var(--system-cyan); font-family:'Orbitron'; margin-bottom: 5px;">🛒 SYSTEM ITEM SHOP &amp; RECOVERY VAULT</h3>
            <p style="color:#cbd5e1; font-size:13px;">Purchase stat-boosting elixirs, protein recovery formulas, and gear. Present confirmation code at Sudarshan Fitness reception counter for instant pickup.</p>
        </div>

        <div class="grid-products">
            <div class="prod-card">
                <div>
                    <div class="prod-img">🥛</div>
                    <div class="prod-title">Whey Protein Elixir (1kg)</div>
                    <div class="prod-desc">Ultra-pure fast-absorbing protein elixir with 26g Protein &amp; 5.5g BCAAs per scoop.</div>
                </div>
                <div>
                    <div class="prod-price">₹2,499</div>
                    <button class="btn-buy" onclick="buyProduct('Whey Protein Elixir', 2499)">PURCHASE ITEM 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">⚡</div>
                    <div class="prod-title">Monarch Creatine (250g)</div>
                    <div class="prod-desc">Micronized 100% pure creatine for explosive strength and muscle volume stats.</div>
                </div>
                <div>
                    <div class="prod-price">₹899</div>
                    <button class="btn-buy" onclick="buyProduct('Monarch Creatine', 899)">PURCHASE ITEM 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">🔥</div>
                    <div class="prod-title">Awakening Pre-Workout Matrix</div>
                    <div class="prod-desc">High-stimulant pre-workout formula with 200mg Caffeine &amp; L-Citrulline.</div>
                </div>
                <div>
                    <div class="prod-price">₹1,299</div>
                    <button class="btn-buy" onclick="buyProduct('Awakening Pre-Workout', 1299)">PURCHASE ITEM 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">🏋️</div>
                    <div class="prod-title">Monarch Heavy Lifting Straps</div>
                    <div class="prod-desc">Padded cotton wrist straps for heavy deadlifts, rows and shrugs.</div>
                </div>
                <div>
                    <div class="prod-price">₹399</div>
                    <button class="btn-buy" onclick="buyProduct('Monarch Lifting Straps', 399)">PURCHASE ITEM 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">🥤</div>
                    <div class="prod-title">System Holographic Shaker (700ml)</div>
                    <div class="prod-desc">Leak-proof shaker bottle with stainless steel mixing ball.</div>
                </div>
                <div>
                    <div class="prod-price">₹299</div>
                    <button class="btn-buy" onclick="buyProduct('System Holographic Shaker', 299)">PURCHASE ITEM 🛒</button>
                </div>
            </div>

            <div class="prod-card">
                <div>
                    <div class="prod-img">🏆</div>
                    <div class="prod-title">S-Rank Personal Trainer Pass</div>
                    <div class="prod-desc">1 Month dedicated 1-on-1 Personal Training Session with Master Coach.</div>
                </div>
                <div>
                    <div class="prod-price">₹3,500</div>
                    <button class="btn-buy" onclick="buyProduct('S-Rank Personal Trainer Pass', 3500)">PURCHASE ITEM 🛒</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function buyProduct(name, price) {
            alert(`👑 [SYSTEM PURCHASE CONFIRMED!]\n\nItem: ${name}\nPrice: ₹${price.toLocaleString('en-IN')}\n\nPlease show this confirmation code at Sudarshan Fitness reception counter for instant item pickup!`);
        }
    </script>
</body>
</html>
