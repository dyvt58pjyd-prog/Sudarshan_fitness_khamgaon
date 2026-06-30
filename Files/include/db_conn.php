<?php
// Suppress PHP deprecation warnings, notices, and warnings to prevent breaking JSON/AJAX responses
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1); // Log them silently instead of breaking UI
ini_set('error_log', __DIR__ . '/php_error_log.txt');

// Dynamic Environment Detection (Local vs InfinityFree Production)
$is_local = false;
$server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
$server_addr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';

if (
    empty($server_name) || // CLI / cron mode
    $server_name === 'localhost' || 
    $server_name === '127.0.0.1' || 
    $server_addr === '127.0.0.1' || 
    $server_addr === '::1' ||
    $server_name === '0.0.0.0' ||
    $server_addr === '0.0.0.0' ||
    strpos($server_name, '192.168.') === 0 ||
    strpos($server_addr, '192.168.') === 0 ||
    strpos($server_name, '10.') === 0 ||
    strpos($server_addr, '10.') === 0 ||
    strpos($server_name, '172.') === 0 ||
    strpos($server_addr, '172.') === 0 ||
    strpos($server_name, '100.') === 0 || // Tailscale IP subnet
    strpos($server_addr, '100.') === 0 || 
    strpos($server_name, '.local') !== false ||
    strpos($server_name, 'localtunnel.me') !== false || // Localtunnel
    strpos($server_name, 'ngrok-free.app') !== false || // Ngrok
    strpos($server_name, 'ngrok.io') !== false ||
    strpos($server_name, 'trycloudflare.com') !== false // Cloudflare
) {
    $is_local = true;
}

if ($is_local) {
    $con = false;
    $db_name = "titangym";

    // 1. Check if a local config file exists (useful for custom settings / Clever Cloud)
    if (file_exists(__DIR__ . '/db_config.php')) {
        include __DIR__ . '/db_config.php';
        if (isset($db_host, $db_user, $db_pass, $db_name)) {
            $db_port = isset($db_port) ? intval($db_port) : 3306;
            $con = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
        }
    }

    // 2. Check for Clever Cloud environment variables
    if (!$con) {
        $cc_host = getenv('MYSQL_ADDON_HOST');
        $cc_user = getenv('MYSQL_ADDON_USER');
        $cc_pass = getenv('MYSQL_ADDON_PASSWORD');
        $cc_db   = getenv('MYSQL_ADDON_DB');
        $cc_port = getenv('MYSQL_ADDON_PORT') ? intval(getenv('MYSQL_ADDON_PORT')) : 3306;

        if ($cc_host && $cc_user && $cc_db) {
            $con = @mysqli_connect($cc_host, $cc_user, $cc_pass, $cc_db, $cc_port);
        }
    }

    // 3. Fallback: try standard localhost port/user combinations
    if (!$con) {
        $configs = [
            ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'root', 'port' => 8889],
            ['host' => 'localhost', 'user' => 'anurag.bawaskar', 'pass' => '', 'port' => 3306],
            ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'port' => 3306],
            ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'port' => 3306],
            ['host' => '127.0.0.1', 'user' => 'anurag.bawaskar', 'pass' => '', 'port' => 3306]
        ];
        
        foreach ($configs as $config) {
            try {
                $con = @mysqli_connect($config['host'], $config['user'], $config['pass'], $db_name, $config['port']);
                if ($con) {
                    break;
                }
            } catch (Exception $e) {
                // Keep trying other configs
            }
        }
    }
} else {
    // Production/InfinityFree settings
    $host     = "localhost"; // MySQL Hostname
    $username = "u252324937_titan";            // MySQL Username
    $password = "Nikita@268724"; // Replace this with your account password (found in Client Area)
    $db_name  = "u252324937_titan";  // Database Name
    $port     = 3306;
    $con = mysqli_connect($host, $username, $password, $db_name, $port);
}

// Check connection
if (!$con) {
    echo "Failed to connect to MySQL: Connection refused. Check database port and server status.";
} else {
    // Disable fatal exceptions for MySQLi (fixes PHP 8.1+ compatibility with legacy code)
    mysqli_report(MYSQLI_REPORT_OFF);

    // Auto-checkout members who forgot to check out (after 11:59 PM)
    try {
        mysqli_query($con, "UPDATE attendance SET exit_time = '23:59:00' WHERE (date < CURRENT_DATE() OR (date = CURRENT_DATE() AND CURRENT_TIME() > '23:59:00')) AND (exit_time IS NULL OR exit_time = '' OR exit_time = '00:00:00')");
    } catch (Exception $e) {}

    // Self-healing database check: ensure payment_qr column exists in gym_details
    $chk_qr = mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE 'payment_qr'");
    if ($chk_qr && mysqli_num_rows($chk_qr) === 0) {
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN payment_qr VARCHAR(255) DEFAULT NULL");
    }

    // Self-healing database check: ensure upi_id column exists in gym_details
    $chk_upi = mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE 'upi_id'");
    if ($chk_upi && mysqli_num_rows($chk_upi) === 0) {
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN upi_id VARCHAR(100) DEFAULT NULL");
    }

    // Self-healing database check: ensure discount_lock column exists in plan
    $chk_lock = mysqli_query($con, "SHOW COLUMNS FROM plan LIKE 'discount_lock'");
    if ($chk_lock && mysqli_num_rows($chk_lock) === 0) {
        mysqli_query($con, "ALTER TABLE plan ADD COLUMN discount_lock INT DEFAULT 0");
    }

    // Self-healing database check: ensure mobile column exists in admin
    $chk_admin_mobile = mysqli_query($con, "SHOW COLUMNS FROM admin LIKE 'mobile'");
    if ($chk_admin_mobile && mysqli_num_rows($chk_admin_mobile) === 0) {
        mysqli_query($con, "ALTER TABLE admin ADD COLUMN mobile VARCHAR(20) DEFAULT NULL");
    }

    // Self-healing database check: ensure role column exists in admin
    $chk_admin_role = mysqli_query($con, "SHOW COLUMNS FROM admin LIKE 'role'");
    if ($chk_admin_role && mysqli_num_rows($chk_admin_role) === 0) {
        mysqli_query($con, "ALTER TABLE admin ADD COLUMN role VARCHAR(50) DEFAULT 'member'");
        // Update existing admin accounts (length < 10) to owner, leaving auto-generated member IDs (which are typically numeric IDs) as members.
        // As a safe fallback for the primary gym owner, we can set username 'admin' or 'owner' to 'owner'.
        mysqli_query($con, "UPDATE admin SET role = 'owner' WHERE username = 'admin' OR username = 'admin1' OR username = 'sudarshan'");
    }

    
    // Self-healing: Ensure App Developer account exists
    $chk_dev = mysqli_query($con, "SELECT username FROM admin WHERE username='admin'");
    if ($chk_dev && mysqli_num_rows($chk_dev) === 0) {
        mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('admin', 'Anurag@268724', 'dev', 'Anurag Bawaskar', 'super_admin')");
    } else {
        // If it exists but wrong role/name, update it (optional, but requested by user)
        mysqli_query($con, "UPDATE admin SET pass_key='Anurag@268724', Full_name='Anurag Bawaskar', role='super_admin' WHERE username='admin'");
    }

    // Self-healing database check: ensure photo column exists in users
    $chk_col = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'photo'");
    if ($chk_col && mysqli_num_rows($chk_col) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL");
    }

    // Self-healing database check: ensure fitness_goal column exists in users
    $chk_fg = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'fitness_goal'");
    if ($chk_fg && mysqli_num_rows($chk_fg) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN fitness_goal VARCHAR(100) DEFAULT 'General Fitness'");
    }

    // Self-healing database check: ensure dob column in users is VARCHAR(50) to allow different date formats from CSV
    $chk_dob = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'dob'");
    
    // Self-healing: Ensure all users have a login account in the admin table
    $users_q = mysqli_query($con, "SELECT userid, username FROM users");
    if ($users_q) {
        while ($u_row = mysqli_fetch_assoc($users_q)) {
            $uid = $u_row['userid'];
            $uname = $u_row['username'];
            $chk_admin_user = mysqli_query($con, "SELECT username FROM admin WHERE username='$uid'");
            if ($chk_admin_user && mysqli_num_rows($chk_admin_user) == 0) {
                mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$uid', '1234', 'member', '$uname', 'member')");
            }
        }
    }
    if ($chk_dob && $row_dob = mysqli_fetch_assoc($chk_dob)) {
        if (strpos($row_dob['Type'], 'varchar(10)') !== false) {
            mysqli_query($con, "ALTER TABLE users MODIFY COLUMN dob VARCHAR(50) NOT NULL");
        }
    }

    // Self-healing database check: ensure joining_date column in users is VARCHAR(50) to allow different date formats from CSV
    $chk_jd = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'joining_date'");
    if ($chk_jd && $row_jd = mysqli_fetch_assoc($chk_jd)) {
        if (strpos($row_jd['Type'], 'varchar(10)') !== false) {
            mysqli_query($con, "ALTER TABLE users MODIFY COLUMN joining_date VARCHAR(50) NOT NULL");
        }
    }

    // Self-healing database check: ensure email column in users is VARCHAR(100) to avoid truncation issues
    $chk_email = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'email'");
    if ($chk_email && $row_email = mysqli_fetch_assoc($chk_email)) {
        if (strpos($row_email['Type'], 'varchar(20)') !== false) {
            mysqli_query($con, "ALTER TABLE users MODIFY COLUMN email VARCHAR(100) NOT NULL");
        }
    }

    // Self-healing database check: ensure gender column in users is VARCHAR(20) to hold "Transgender"
    $chk_gen = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'gender'");
    if ($chk_gen && $row_gen = mysqli_fetch_assoc($chk_gen)) {
        if (strpos($row_gen['Type'], 'varchar(8)') !== false) {
            mysqli_query($con, "ALTER TABLE users MODIFY COLUMN gender VARCHAR(20) NOT NULL");
        }
    }

    // Self-healing database check: ensure attendance table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uid VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        date DATE NOT NULL,
        entry_time TIME DEFAULT NULL,
        exit_time TIME DEFAULT NULL,
        FOREIGN KEY (uid) REFERENCES users(userid) ON DELETE CASCADE
    )");

    // Self-healing database check: ensure entry_code column exists in users
    $chk_code = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'entry_code'");
    if ($chk_code && mysqli_num_rows($chk_code) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN entry_code VARCHAR(20) DEFAULT NULL");
    }

    // Self-healing database check: ensure whatsapp_config table exists for Meta API
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS whatsapp_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number_id VARCHAR(100) NOT NULL,
        business_account_id VARCHAR(100) NOT NULL,
        access_token TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Self-healing database check: ensure biometric_id column exists in users
    $chk_bio_id = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'biometric_id'");
    if ($chk_bio_id && mysqli_num_rows($chk_bio_id) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN biometric_id INT DEFAULT NULL");
    }

    // Self-healing database check: ensure biometric_enabled column exists in users
    $chk_bio_en = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'biometric_enabled'");
    if ($chk_bio_en && mysqli_num_rows($chk_bio_en) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN biometric_enabled TINYINT DEFAULT 1");
    }

    // Self-healing database check: ensure discount_amount and paid_amount columns exist in enrolls_to
    $chk_disc = mysqli_query($con, "SHOW COLUMNS FROM enrolls_to LIKE 'discount_amount'");
    if ($chk_disc && mysqli_num_rows($chk_disc) === 0) {
        mysqli_query($con, "ALTER TABLE enrolls_to ADD COLUMN discount_amount INT DEFAULT 0");
    }
    $chk_paid = mysqli_query($con, "SHOW COLUMNS FROM enrolls_to LIKE 'paid_amount'");
    if ($chk_paid && mysqli_num_rows($chk_paid) === 0) {
        mysqli_query($con, "ALTER TABLE enrolls_to ADD COLUMN paid_amount INT DEFAULT NULL");
    }

    // Self-healing database check: ensure personal_training table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS personal_training (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uid VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        trainer_id VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        date DATE NOT NULL,
        workout_details TEXT DEFAULT NULL,
        nutrition_notes TEXT DEFAULT NULL,
        trainer_remarks TEXT DEFAULT NULL,
        achievements TEXT DEFAULT NULL,
        FOREIGN KEY (uid) REFERENCES users(userid) ON DELETE CASCADE,
        FOREIGN KEY (trainer_id) REFERENCES admin(username) ON DELETE CASCADE
    )");

    // Self-healing database check: ensure trainer_id column exists in users
    $chk_tr = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'trainer_id'");
    if ($chk_tr && mysqli_num_rows($chk_tr) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN trainer_id VARCHAR(20) DEFAULT NULL");
    }

    // Self-healing database check: ensure pt_enrollments table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS pt_enrollments (
        pt_id INT AUTO_INCREMENT PRIMARY KEY,
        uid VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        trainer_id VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        enroll_date DATE NOT NULL,
        expire_date DATE NOT NULL,
        amount INT NOT NULL,
        payment_mode VARCHAR(20) NOT NULL,
        received_by VARCHAR(50) NOT NULL,
        FOREIGN KEY (uid) REFERENCES users(userid) ON DELETE CASCADE,
        FOREIGN KEY (trainer_id) REFERENCES admin(username) ON DELETE CASCADE
    )");

    // Self-healing database check: ensure payment_requests table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS payment_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uid VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        pid VARCHAR(8) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        amount INT NOT NULL,
        screenshot VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uid) REFERENCES users(userid) ON DELETE CASCADE,
        FOREIGN KEY (pid) REFERENCES plan(pid) ON DELETE CASCADE
    )");
    
    // Self-healing database check: ensure utr column exists in payment_requests
    $chk_utr = mysqli_query($con, "SHOW COLUMNS FROM payment_requests LIKE 'utr'");
    if ($chk_utr && mysqli_num_rows($chk_utr) === 0) {
        mysqli_query($con, "ALTER TABLE payment_requests ADD COLUMN utr VARCHAR(50) DEFAULT NULL");
    }

    mysqli_query($con, "INSERT IGNORE INTO plan (pid, planName, amount, validity, active) VALUES ('PTPLAN', 'Personal Training', 0, 1, 'no')");

    // Self-healing database check: ensure broadcast_campaigns table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS broadcast_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(200) NOT NULL,
        target_group VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        attachment_path VARCHAR(255) DEFAULT NULL,
        sent_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Self-healing database check: ensure gym_tips table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS gym_tips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tip_text TEXT NOT NULL,
        category VARCHAR(50) NOT NULL
    )");

    // Pre-seed gym tips if empty
    $chk_tips = mysqli_query($con, "SELECT COUNT(*) as cnt FROM gym_tips");
    if ($chk_tips) {
        $row_tips = mysqli_fetch_assoc($chk_tips);
        if ($row_tips['cnt'] == 0) {
            $seeds = [
                ["Consistency is key! Every drop of sweat is a step closer to your goals. 🏋️", "Motivation"],
                ["Drink at least 3-4 liters of water daily to maintain performance and recovery. 💧", "Hydration"],
                ["Progress over perfection. Your only competition is who you were yesterday. 🌟", "Mindset"],
                ["Rest is just as important as work. Ensure you get 7-8 hours of sound sleep! 😴", "Recovery"],
                ["High-protein intake supports muscle repair. Include eggs, paneer, chicken, or lentils in your meals. 🍳", "Nutrition"],
                ["Don't skip dynamic warm-ups! They prime your nervous system and prevent joint injuries. 🏃", "Safety"],
                ["The last 2 reps of your set are where the real change happens. Push through! 🔥", "Motivation"],
                ["Stretching after your workout increases flexibility and speeds up lactic acid clearance. 🧘", "Recovery"],
                ["Focus on compound lifts like squats, deadlifts, and overhead presses for full-body strength. 💪", "Training"],
                ["Avoid refined sugars and processed food. Fuel your body with whole foods instead. 🍏", "Nutrition"],
                ["Track your weights and reps in your fitness log to ensure progressive overload. 📊", "Training"],
                ["A 10-minute incline walk after weights helps with cardiovascular health and active recovery. 🚶", "Cardio"],
                ["Believe you can, and you're halfway there. Keep pushing! 🚀", "Mindset"],
                ["Complex carbs like oats, brown rice, and sweet potatoes give you sustained workout energy. 🍠", "Nutrition"],
                ["Listen to your body. If a joint hurts, drop the weight and adjust your form. Safety first! ⚠️", "Safety"]
            ];
            foreach ($seeds as $seed) {
                $tip = mysqli_real_escape_string($con, $seed[0]);
                $cat = mysqli_real_escape_string($con, $seed[1]);
                mysqli_query($con, "INSERT INTO gym_tips (tip_text, category) VALUES ('$tip', '$cat')");
            }
        }
    }

    // Self-healing database check: ensure daily_tip_enabled column exists in gym_details
    $chk_dte = mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE 'daily_tip_enabled'");
    if ($chk_dte && mysqli_num_rows($chk_dte) === 0) {
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN daily_tip_enabled TINYINT DEFAULT 1");
    }
    
    // Self-healing database check: ensure last_tip_sent column exists in gym_details
    $chk_lts = mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE 'last_tip_sent'");
    if ($chk_lts && mysqli_num_rows($chk_lts) === 0) {
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN last_tip_sent DATE DEFAULT NULL");
    }

    // Self-healing database check: ensure health_history table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS health_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uid VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        calorie VARCHAR(8) DEFAULT NULL,
        height VARCHAR(8) DEFAULT NULL,
        weight VARCHAR(8) DEFAULT NULL,
        fat VARCHAR(8) DEFAULT NULL,
        remarks VARCHAR(200) DEFAULT NULL,
        logged_date DATE NOT NULL,
        FOREIGN KEY (uid) REFERENCES users(userid) ON DELETE CASCADE
    )");

    // Self-healing database check: ensure pt_bookings table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS pt_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uid VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        trainer_id VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        booking_date DATE NOT NULL,
        booking_time TIME NOT NULL,
        status VARCHAR(20) DEFAULT 'confirmed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uid) REFERENCES users(userid) ON DELETE CASCADE,
        FOREIGN KEY (trainer_id) REFERENCES admin(username) ON DELETE CASCADE
    )");

    // Self-healing database check: ensure expenses table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_name VARCHAR(100) NOT NULL,
        amount INT NOT NULL,
        category VARCHAR(50) NOT NULL,
        expense_date DATE NOT NULL,
        remarks TEXT DEFAULT NULL
    )");

    // Self-healing database check: ensure whatsapp_outbox table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS whatsapp_outbox (
        id INT AUTO_INCREMENT PRIMARY KEY,
        number VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        file_path VARCHAR(255) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        attempts INT DEFAULT 0,
        last_attempt TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}
?>
<?php
if (!function_exists('page_protect')) {
    function page_protect()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        /* Secure against Session Hijacking by checking user agent */
        if (isset($_SESSION['HTTP_USER_AGENT'])) {
            if ($_SESSION['HTTP_USER_AGENT'] != md5($_SERVER['HTTP_USER_AGENT'])) {
                session_destroy();
                echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
                exit();
            }
        }
        
        /* If session not set, redirect to main login page */
        if (!isset($_SESSION['user_data']) || !isset($_SESSION['logged'])) {
            session_destroy();
            echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
            exit();
        }
    }
}

if (!function_exists('get_gym_details')) {
    function get_gym_details($con)
    {
        $sql = "SELECT * FROM gym_details WHERE id = 1";
        $result = mysqli_query($con, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $row['gym_logo'] = '../../images/logo.jpg'; // Fixed logo path as per user request
            return $row;
        }
        return [
            'gym_name' => 'SUDARSHAN FITNESS',
            'gym_address' => '123 Premium Way, Gym City',
            'gym_contact' => '1234567890',
            'gym_email' => 'sudarshan.fitness.khm@gmail.com',
            'gym_logo' => '../../images/logo.png'
        ];
    }
}

if (!function_exists('get_member_rank')) {
    function get_member_rank($xp) {
        if ($xp < 200) return 'Beginner';
        if ($xp < 500) return 'Bronze';
        if ($xp < 1000) return 'Silver';
        if ($xp < 2500) return 'Gold';
        if ($xp < 5000) return 'Platinum';
        if ($xp < 10000) return 'Diamond';
        return 'Titan';
    }
}

if (!function_exists('check_and_upgrade_db')) {
    function check_and_upgrade_db($con) {
        // Phase 3: Gamification and Heatmap Schema
        $cols = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'xp_points'");
        if(mysqli_num_rows($cols) == 0) mysqli_query($con, "ALTER TABLE users ADD COLUMN xp_points INT DEFAULT 0");
        
        $cols = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'gym_rank'");
        if(mysqli_num_rows($cols) == 0) mysqli_query($con, "ALTER TABLE users ADD COLUMN gym_rank VARCHAR(50) DEFAULT 'Beginner'");
        
        $workout_logs_sql = "CREATE TABLE IF NOT EXISTS workout_logs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            uid VARCHAR(20) NOT NULL,
            muscle_group VARCHAR(50) NOT NULL,
            intensity INT DEFAULT 5,
            log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        mysqli_query($con, $workout_logs_sql);
    }
}

if (!function_exists('add_member_xp')) {
    function add_member_xp($con, $userid, $xp_to_add) {
        $uid_esc = mysqli_real_escape_string($con, $userid);
        // Get current XP
        $res = mysqli_query($con, "SELECT xp_points FROM users WHERE userid = '$uid_esc'");
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $current_xp = intval($row['xp_points']);
            $new_xp = $current_xp + $xp_to_add;
            $new_rank = get_member_rank($new_xp);
            
            // Update
            mysqli_query($con, "UPDATE users SET xp_points = $new_xp, gym_rank = '$new_rank' WHERE userid = '$uid_esc'");
            
            return [
                'old_xp' => $current_xp,
                'new_xp' => $new_xp,
                'old_rank' => get_member_rank($current_xp),
                'new_rank' => $new_rank,
                'leveled_up' => (get_member_rank($current_xp) !== $new_rank)
            ];
        }
        return false;
    }
}

if (!function_exists('send_member_email')) {
    function send_member_email($con, $email, $name, $memID, $password, $planName, $amount, $expiredate, $entry_code = '', $discount = 0, $paid_amount = NULL, $gender = '') {
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        $gym_email = $gym['gym_email'];
        
        $subject = "Welcome to the Sudarshan Fitness Family - Registration Confirmed";
        
        if ($paid_amount === NULL) {
            $paid_amount = intval($amount) - intval($discount);
            if ($paid_amount < 0) {
                $paid_amount = 0;
            }
        }

        // Do not combine PT enrollment into the standard plan welcome email receipt
        $pt_section = "";
        $total_paid_with_pt = $paid_amount;

        // Determine WhatsApp group link based on gender
        $whatsapp_section = "";
        $gender_str = strtolower(trim($gender));
        if (empty($gender_str)) {
            $gender_q = mysqli_query($con, "SELECT gender FROM users WHERE userid = '$memID_esc'");
            if ($gender_q && mysqli_num_rows($gender_q) > 0) {
                $gender_row = mysqli_fetch_assoc($gender_q);
                $gender_str = strtolower(trim($gender_row['gender']));
            }
        }
        
        $whatsapp_link = "";
        $is_transgender = false;
        if ($gender_str === 'male' || $gender_str === 'm') {
            $whatsapp_link = "https://chat.whatsapp.com/LMkWJql6kT91P5X59caDI0?s=sw&p=i&ilr=0";
        } elseif ($gender_str === 'female' || $gender_str === 'f') {
            $whatsapp_link = "https://chat.whatsapp.com/ISk4F5HqcJhBK477gJ55Ee?s=sw&p=i&ilr=0";
        } elseif ($gender_str === 'transgender' || $gender_str === 't') {
            $is_transgender = true;
        }

        if ($is_transgender) {
            $whatsapp_section = "
                <div style='background-color: rgba(37, 211, 102, 0.05); border: 1px solid rgba(37, 211, 102, 0.3); padding: 20px; margin: 25px 0; border-radius: 12px; text-align: center;'>
                    <strong style='color: #25D366; font-size: 16px; display: block; margin-bottom: 10px;'>Join Our Members WhatsApp Groups!</strong>
                    <p style='font-size: 13px; color: #475569; margin: 0 0 15px 0;'>Get real-time updates, fitness tips, and connect with fellow gym members in our community groups.</p>
                    <div style='display: flex; gap: 10px; justify-content: center;'>
                        <a href='https://chat.whatsapp.com/LMkWJql6kT91P5X59caDI0?s=sw&p=i&ilr=0' target='_blank' style='display: inline-block; background-color: #25D366; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 10px rgba(37, 211, 102, 0.25);'>Join Male Group &rarr;</a>
                        <a href='https://chat.whatsapp.com/ISk4F5HqcJhBK477gJ55Ee?s=sw&p=i&ilr=0' target='_blank' style='display: inline-block; background-color: #25D366; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 10px rgba(37, 211, 102, 0.25);'>Join Female Group &rarr;</a>
                    </div>
                </div>
            ";
        } elseif (!empty($whatsapp_link)) {
            $whatsapp_section = "
                <div style='background-color: rgba(37, 211, 102, 0.05); border: 1px solid rgba(37, 211, 102, 0.3); padding: 20px; margin: 25px 0; border-radius: 12px; text-align: center;'>
                    <strong style='color: #25D366; font-size: 16px; display: block; margin-bottom: 10px;'>Join Our Members WhatsApp Group!</strong>
                    <p style='font-size: 13px; color: #475569; margin: 0 0 15px 0;'>Get real-time updates, fitness tips, and connect with fellow gym members in our exclusive community group.</p>
                    <a href='$whatsapp_link' target='_blank' style='display: inline-block; background-color: #25D366; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 10px rgba(37, 211, 102, 0.25);'>Join WhatsApp Group &rarr;</a>
                </div>
            ";
        }

        $is_prebook = (date('Y-m-d') < '2026-07-08');
        $welcome_heading = $is_prebook ? "Welcome to the Sudarshan Fitness Family! (Pre-Booking Confirmed)" : "Welcome to the Sudarshan Fitness Family!";
        $welcome_text = $is_prebook ? 
            "Congratulations on your Grand Opening Pre-Booking! Your spot is officially secured and your membership will begin on July 8th, 2026. Below are your membership details and portal credentials. Your official payment receipt PDF has been attached." : 
            "Congratulations! Your registration with the <strong>Sudarshan Fitness Family</strong> is confirmed. Below are your membership details and credentials. Your official payment receipt PDF has been attached to this email.";

        // Construct HTML Email Body
        $mail_body = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 30px; margin: 0; }
                .container { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
                .top-line { position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #0c0c0c, #ff6b00); }
                h2 { color: #ff6b00; font-size: 22px; font-weight: 700; margin-top: 10px; margin-bottom: 20px; }
                p { font-size: 14px; line-height: 1.6; color: #475569; }
                .details-table { width: 100%; border-collapse: collapse; margin: 25px 0; }
                .details-table th, .details-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; text-align: left; font-size: 14px; }
                .details-table th { color: #475569; font-weight: 600; width: 40%; background-color: #f8fafc; }
                .details-table td { color: #0f172a; font-weight: 600; }
                .login-box { background-color: rgba(255, 107, 0, 0.05); border: 1px dashed rgba(255, 107, 0, 0.3); padding: 20px; margin: 25px 0; border-radius: 10px; font-size: 14px; line-height: 1.6; }
                .login-box strong { color: #ff6b00; }
                .login-box code { background-color: rgba(255, 107, 0, 0.1); color: #ff6b00; padding: 2px 6px; border-radius: 4px; font-size: 13px; font-weight: bold; }
                .footer { margin-top: 35px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; line-height: 1.5; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='top-line'></div>
                <h2>$welcome_heading</h2>
                <p>Dear <strong>$name</strong>,</p>
                <p>$welcome_text</p>
                

 
                 " . $whatsapp_section . "
 
                 <div class='login-box'>
                    <strong style='font-size: 15px; display: block; margin-bottom: 8px;'>Portal Access Credentials:</strong>
                    Portal Link: <a href='https://sudarshanfitness.loca.lt' style='color: #ff6b00; text-decoration: none; font-weight: bold;'>Go to Portal &rarr;</a><br>
                    Username ID: <code>$memID</code><br>
                    Password: <code>$password</code>
                </div>

                <table class='details-table'>
                    <tr>
                        <th>Membership ID</th>
                        <td>$memID</td>
                    </tr>
                    <tr>
                        <th>Biometric Access PIN</th>
                        <td><code style='background-color: rgba(255, 107, 0, 0.1); color: #ff6b00; padding: 2px 6px; border-radius: 4px; font-size: 15px; font-weight: bold;'>$entry_code</code></td>
                    </tr>
                    <tr>
                        <th>Subscribed Plan</th>
                        <td>$planName</td>
                    </tr>
                    <tr>
                        <th>Plan Price</th>
                        <td>₹$amount</td>
                    </tr>
                    " . $pt_section . "
                    " . (intval($discount) > 0 ? "
                    <tr>
                        <th>Discount Applied</th>
                        <td style='color: #ef4444;'>- ₹$discount</td>
                    </tr>
                    " : "") . "
                    <tr>
                        <th>Amount Paid</th>
                        <td style='color: #10b981;'>₹$total_paid_with_pt</td>
                    </tr>
                    <tr>
                        <th>Expires On</th>
                        <td>$expiredate</td>
                    </tr>
                </table>

                <p>Log in to your dashboard to track your health status, daily routines, and renewals.</p>
                
                <div class='footer'>
                    This is an automated message from $gym_name.<br>
                    Need help? Contact support: <a href='mailto:$gym_email' style='color: #ff6b00; text-decoration: none;'>$gym_email</a><br>
                    <br>
                    System Engineered by <strong>Anurag Bawaskar</strong> | <a href='tel:8459962390' style='color: #ff6b00; text-decoration: none;'>📞 8459962390</a>
                </div>
            </div>
        </body>
        </html>";

        // 1. Send SMTP if configured, else fall back to native PHP email
        require_once __DIR__ . '/pdf_generator.php';
        $pdf_path = generate_receipt_pdf_file($con, $memID);

        require_once __DIR__ . '/smtp_mailer.php';
        $sent = send_smtp_email($email, $name, $subject, $mail_body, $pdf_path, basename($pdf_path), 'payments');

        // 2. Fetch mobile and send WhatsApp Welcome with PDF receipt attached
        $q_mob = mysqli_query($con, "SELECT mobile FROM users WHERE userid = '" . mysqli_real_escape_string($con, $memID) . "'");
        if ($q_mob && $mob_row = mysqli_fetch_assoc($q_mob)) {
            $payment_mode = 'Cash';
            $q_pay = mysqli_query($con, "SELECT payment_mode FROM enrolls_to WHERE uid = '" . mysqli_real_escape_string($con, $memID) . "' ORDER BY et_id DESC LIMIT 1");
            if ($q_pay && $p_row = mysqli_fetch_assoc($q_pay)) {
                $payment_mode = $p_row['payment_mode'];
            }
            send_whatsapp_welcome_confirmation($con, $mob_row['mobile'], $name, $memID, $password, $planName, $paid_amount, $expiredate, $payment_mode, $entry_code, $gender, $pdf_path);
        }

        if ($pdf_path && file_exists($pdf_path)) {
            @unlink($pdf_path);
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $gym_email\r\n";
        $headers .= "Reply-To: $gym_email\r\n";

        if (!$sent) {
            @mail($email, $subject, $mail_body, $headers);
        }

        // 2. Write locally to log file for visual verification
        $log_path = __DIR__ . "/email_log.txt";
        $log_entry = "========================================================\n";
        $log_entry .= "DATE: " . date('Y-m-d H:i:s') . "\n";
        $log_entry .= "TO: $email\n";
        $log_entry .= "SUBJECT: $subject\n";
        $log_entry .= "BODY:\n" . strip_tags($mail_body) . "\n";
        $log_entry .= "========================================================\n\n";
        @file_put_contents($log_path, $log_entry, FILE_APPEND);
    }
}

if (!function_exists('send_payment_email')) {
    function send_payment_email($con, $email, $name, $memID, $planName, $amount, $expiredate, $payment_mode, $received_by, $entry_code = '', $discount = 0, $paid_amount = NULL) {
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        $gym_email = $gym['gym_email'];
        
        $subject = "Payment Receipt - $gym_name";
        
        if ($paid_amount === NULL) {
            $paid_amount = intval($amount) - intval($discount);
            if ($paid_amount < 0) {
                $paid_amount = 0;
            }
        }

        $memID_esc = mysqli_real_escape_string($con, $memID);

        // Construct HTML Receipt Body
        $mail_body = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 30px; margin: 0; }
                .container { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
                .top-line { position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #0c0c0c, #ff6b00); }
                h2 { color: #ff6b00; font-size: 22px; font-weight: 700; margin-top: 10px; margin-bottom: 20px; }
                p { font-size: 14px; line-height: 1.6; color: #475569; }
                .details-table { width: 100%; border-collapse: collapse; margin: 25px 0; }
                .details-table th, .details-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; text-align: left; font-size: 14px; }
                .details-table th { color: #475569; font-weight: 600; width: 40%; background-color: #f8fafc; }
                .details-table td { color: #0f172a; font-weight: 600; }
                .footer { margin-top: 35px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; line-height: 1.5; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='top-line'></div>
                <h2>Payment Received - Confirmation Receipt</h2>
                <p>Dear <strong>$name</strong>,</p>
                <p>Thank you for your payment. Below are the details of your subscription renewal transaction at <strong>$gym_name</strong>. Your official payment receipt PDF has been attached to this email.</p>
                


                <table class='details-table'>
                    <tr>
                        <th>Membership ID</th>
                        <td>$memID</td>
                    </tr>
                    <tr>
                        <th>Biometric Access PIN</th>
                        <td><code style='background-color: rgba(255, 107, 0, 0.1); color: #ff6b00; padding: 2px 6px; border-radius: 4px; font-size: 15px; font-weight: bold;'>$entry_code</code></td>
                    </tr>
                    <tr>
                        <th>Subscribed Plan</th>
                        <td>$planName</td>
                    </tr>
                    <tr>
                        <th>Plan Price</th>
                        <td>₹$amount</td>
                    </tr>
                    " . (intval($discount) > 0 ? "
                    <tr>
                        <th>Discount Applied</th>
                        <td style='color: #ef4444;'>- ₹$discount</td>
                    </tr>
                    " : "") . "
                    <tr>
                        <th>Amount Paid</th>
                        <td style='color: #10b981;'>₹$paid_amount</td>
                    </tr>
                    <tr>
                        <th>Payment Mode</th>
                        <td style='text-transform: uppercase;'>$payment_mode</td>
                    </tr>
                    <tr>
                        <th>Expires On</th>
                        <td>$expiredate</td>
                    </tr>
                    <tr>
                        <th>Processed By</th>
                        <td>$received_by</td>
                    </tr>
                </table>
                
                <div class='footer'>
                    This is an automated transaction confirmation from $gym_name.<br>
                    Need help? Contact support: <a href='mailto:$gym_email' style='color: #ff6b00; text-decoration: none;'>$gym_email</a><br>
                    <br>
                    System Engineered by <strong>Anurag Bawaskar</strong> | <a href='tel:8459962390' style='color: #ff6b00; text-decoration: none;'>📞 8459962390</a>
                </div>
            </div>
        </body>
        </html>";

        // Send via SMTP
        require_once __DIR__ . '/pdf_generator.php';
        $pdf_path = generate_receipt_pdf_file($con, $memID);

        require_once __DIR__ . '/smtp_mailer.php';
        $sent = send_smtp_email($email, $name, $subject, $mail_body, $pdf_path, basename($pdf_path), 'payments');

        // Send WhatsApp Payment Confirmation with PDF receipt attached
        $q_mob = mysqli_query($con, "SELECT mobile FROM users WHERE userid = '" . mysqli_real_escape_string($con, $memID) . "'");
        if ($q_mob && $mob_row = mysqli_fetch_assoc($q_mob)) {
            send_whatsapp_payment_confirmation($con, $mob_row['mobile'], $name, $planName, $paid_amount, $expiredate, $payment_mode, $pdf_path);
        }

        if ($pdf_path && file_exists($pdf_path)) {
            @unlink($pdf_path);
        }
        
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $gym_email\r\n";
        $headers .= "Reply-To: $gym_email\r\n";
        
        if (!$sent) {
            @mail($email, $subject, $mail_body, $headers);
        }

        // Write locally to log file for visual verification
        $log_path = __DIR__ . "/email_log.txt";
        $log_entry = "========================================================\n";
        $log_entry .= "DATE: " . date('Y-m-d H:i:s') . "\n";
        $log_entry .= "TO: $email\n";
        $log_entry .= "SUBJECT: $subject\n";
        $log_entry .= "BODY:\n" . strip_tags($mail_body) . "\n";
        $log_entry .= "========================================================\n\n";
        @file_put_contents($log_path, $log_entry, FILE_APPEND);
    }
}

if (!function_exists('send_pt_email')) {
    function send_pt_email($con, $email, $name, $memID, $trainer_name, $amount, $expire_date, $payment_mode, $received_by) {
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        $gym_email = $gym['gym_email'];
        
        $subject = "Personal Training Enrollment Confirmed - $gym_name";
        
        // Construct HTML Receipt Body
        $mail_body = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 30px; margin: 0; }
                .container { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
                .top-line { position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #ff6b00, #ff8c00); }
                h2 { color: #ff6b00; font-size: 22px; font-weight: 700; margin-top: 10px; margin-bottom: 20px; }
                p { font-size: 14px; line-height: 1.6; color: #475569; }
                .details-table { width: 100%; border-collapse: collapse; margin: 25px 0; }
                .details-table th, .details-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; text-align: left; font-size: 14px; }
                .details-table th { color: #475569; font-weight: 600; width: 40%; background-color: #f8fafc; }
                .details-table td { color: #0f172a; font-weight: 600; }
                .footer { margin-top: 35px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; line-height: 1.5; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='top-line'></div>
                <h2>Personal Training Receipt</h2>
                <p>Dear <strong>$name</strong>,</p>
                <p>Thank you for enrolling in our Personal Training program at <strong>$gym_name</strong>. Your personal training session plan has been activated. Below are your transaction details and assigned personal trainer. Your official receipt PDF has been attached directly to this email:</p>
                
                <table class='details-table'>
                    <tr>
                        <th>Membership ID</th>
                        <td>$memID</td>
                    </tr>
                    <tr>
                        <th>Personal Trainer</th>
                        <td style='color: #ff6b00;'>$trainer_name</td>
                    </tr>
                    <tr>
                        <th>PT Enrollment Date</th>
                        <td>" . date('Y-m-d') . "</td>
                    </tr>
                    <tr>
                        <th>PT Validity Until</th>
                        <td style='color: #ff6b00;'>$expire_date</td>
                    </tr>
                    <tr>
                        <th>Amount Paid</th>
                        <td style='color: #10b981;'>₹$amount</td>
                    </tr>
                    <tr>
                        <th>Payment Mode</th>
                        <td>$payment_mode</td>
                    </tr>
                    <tr>
                        <th>Processed By</th>
                        <td>$received_by</td>
                    </tr>
                </table>

                <p>Your personal trainer will work directly with you to outline your customized workout routines, diet logs, and monitor your physical achievements. You can view your training history anytime in the member portal.</p>
                
                <div class='footer'>
                    This is an automated transaction confirmation from $gym_name.<br>
                    Need help? Contact support: <a href='mailto:$gym_email' style='color: #ff6b00; text-decoration: none;'>$gym_email</a>
                </div>
            </div>
        </body>
        </html>";

        // Send via SMTP
        require_once __DIR__ . '/pdf_generator.php';
        $pdf_path = generate_pt_receipt_pdf_file($con, $memID);

        require_once __DIR__ . '/smtp_mailer.php';
        $sent = send_smtp_email($email, $name, $subject, $mail_body, $pdf_path, basename($pdf_path), 'payments');

        // Send WhatsApp PT Payment Confirmation with PDF receipt attached
        $q_mob = mysqli_query($con, "SELECT mobile FROM users WHERE userid = '" . mysqli_real_escape_string($con, $memID) . "'");
        if ($q_mob && $mob_row = mysqli_fetch_assoc($q_mob)) {
            send_whatsapp_payment_confirmation($con, $mob_row['mobile'], $name, "Personal Training ($trainer_name)", $amount, $expire_date, $payment_mode, $pdf_path);
        }

        if ($pdf_path && file_exists($pdf_path)) {
            @unlink($pdf_path);
        }
        
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $gym_email\r\n";
        $headers .= "Reply-To: $gym_email\r\n";
        
        if (!$sent) {
            @mail($email, $subject, $mail_body, $headers);
        }

        // Write locally to log file for visual verification
        $log_path = __DIR__ . "/email_log.txt";
        $log_entry = "========================================================\n";
        $log_entry .= "DATE: " . date('Y-m-d H:i:s') . "\n";
        $log_entry .= "TO: $email\n";
        $log_entry .= "SUBJECT: $subject\n";
        $log_entry .= "BODY:\n" . strip_tags($mail_body) . "\n";
        $log_entry .= "========================================================\n\n";
        @file_put_contents($log_path, $log_entry, FILE_APPEND);
    }
}

if (!function_exists('send_whatsapp_payment_confirmation')) {
    function send_whatsapp_payment_confirmation($con, $mobile, $name, $planName, $amount, $expiredate, $payment_mode, $pdf_path = null) {
        if (empty($mobile)) {
            return false;
        }
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        
        $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($wa_mobile) === 10) {
            $wa_mobile = '91' . $wa_mobile;
        }
        
        $message = "🏋️ *{$gym_name}* Payment Confirmation 🏋️\n\n" .
                   "Dear *{$name}*,\n\n" .
                   "Thank you for your payment of *₹" . number_format($amount) . "* for the plan *{$planName}* via *{$payment_mode}*.\n\n" .
                   "Your subscription is now *ACTIVE* and will expire on *{$expiredate}*.\n\n" .
                   "Log in to your member portal to view receipt and workout routines:\n" .
                   "👉 https://sudarshanfitness.localtunnel.me\n\n" .
                   "Thank you,\n" .
                   "*{$gym_name}*";
                   
        return enqueue_whatsapp_message($con, $wa_mobile, $message, $pdf_path);
    }
}

if (!function_exists('send_whatsapp_welcome_confirmation')) {
    function send_whatsapp_welcome_confirmation($con, $mobile, $name, $memID, $password, $planName, $amount, $expiredate, $payment_mode, $entry_code, $gender, $pdf_path = null) {
        if (empty($mobile)) {
            return false;
        }
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        
        $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($wa_mobile) === 10) {
            $wa_mobile = '91' . $wa_mobile;
        }
        
        // Determine WhatsApp group link based on gender
        $whatsapp_link = "";
        $is_transgender = false;
        $gender_str = strtolower(trim($gender));
        if (empty($gender_str)) {
            $memID_esc = mysqli_real_escape_string($con, $memID);
            $gender_q = mysqli_query($con, "SELECT gender FROM users WHERE userid = '$memID_esc'");
            if ($gender_q && mysqli_num_rows($gender_q) > 0) {
                $gender_row = mysqli_fetch_assoc($gender_q);
                $gender_str = strtolower(trim($gender_row['gender']));
            }
        }
        if ($gender_str === 'male' || $gender_str === 'm') {
            $whatsapp_link = "https://chat.whatsapp.com/LMkWJql6kT91P5X59caDI0?s=sw&p=i&ilr=0";
        } elseif ($gender_str === 'female' || $gender_str === 'f') {
            $whatsapp_link = "https://chat.whatsapp.com/ISk4F5HqcJhBK477gJ55Ee?s=sw&p=i&ilr=0";
        } elseif ($gender_str === 'transgender' || $gender_str === 't') {
            $is_transgender = true;
        }
        
        $group_msg = "";
        if ($is_transgender) {
            $group_msg = "Please join our exclusive members WhatsApp groups to stay connected:\nMale Group: https://chat.whatsapp.com/LMkWJql6kT91P5X59caDI0?s=sw&p=i&ilr=0\nFemale Group: https://chat.whatsapp.com/ISk4F5HqcJhBK477gJ55Ee?s=sw&p=i&ilr=0\n\n";
        } elseif (!empty($whatsapp_link)) {
            $group_msg = "Please join our exclusive members WhatsApp group to stay connected:\n👉 {$whatsapp_link}\n\n";
        }

        // Fetch height, weight, and calculate BMI for the welcome message
        $memID_esc = mysqli_real_escape_string($con, $memID);
        $health_q = mysqli_query($con, "SELECT height, weight FROM health_status WHERE uid = '$memID_esc'");
        $health_msg = "";
        if ($health_q && mysqli_num_rows($health_q) > 0) {
            $health_row = mysqli_fetch_assoc($health_q);
            $w = floatval($health_row['weight']);
            $h = floatval($health_row['height']);
            if ($w > 0 && $h > 0) {
                $bmi = round($w / (($h / 100) * ($h / 100)), 1);
                $category = 'Normal 🟢';
                if ($bmi < 18.5) {
                    $category = 'Underweight 🟡';
                } elseif ($bmi >= 25 && $bmi < 29.9) {
                    $category = 'Overweight 🟠';
                } elseif ($bmi >= 29.9) {
                    $category = 'Obese 🔴';
                }
                
                $health_msg = "📊 *Your Registered Health Metrics:*\n" .
                              "• Height: *{$h} cm* | Weight: *{$w} kg*\n" .
                              "• Calculated BMI: *{$bmi}* ({$category})\n\n" .
                              "💬 *Interactive AI Coach Tips:*\n" .
                              "Reply to this number with any of these commands to get customized health guidance instantly:\n" .
                              "👉 */bmi* - Calculate your BMI & weight status\n" .
                              "👉 */workout* - Get your weekly workout routine split\n" .
                              "👉 */diet* - Get your targeted meal & caloric chart\n\n";
            }
        }

        $message = "🏋️ *Welcome to the {$gym_name} Family!* 🏋️\n\n" .
                   "Dear *{$name}*,\n\n" .
                   "Your registration is confirmed. Welcome aboard!\n\n" .
                   "🔑 *Portal Access Credentials:*\n" .
                   "Link: https://sudarshanfitness.localtunnel.me\n" .
                   "Username ID: *{$memID}*\n" .
                   "Password: *{$password}*\n\n" .
                   "🚪 *Gate Access Code:* *{$entry_code}*\n" .
                   "(Use this code at the entrance screen if Face ID fails)\n\n" .
                   "💳 *Subscription Details:*\n" .
                   "Plan: *{$planName}*\n" .
                   "Amount Paid: *₹" . number_format($amount) . "* via *{$payment_mode}*\n" .
                   "Expires On: *{$expiredate}*\n\n" .
                   $health_msg .
                   $group_msg .
                   "Thank you,\n" .
                   "*{$gym_name}*";
                   
        return enqueue_whatsapp_message($con, $wa_mobile, $message, $pdf_path);
    }
}

if (!function_exists('send_whatsapp_trainer_pt_notification')) {
    function send_whatsapp_trainer_pt_notification($con, $trainer_id, $client_name, $client_id) {
        if (empty($trainer_id)) {
            return false;
        }
        
        $trainer_id_esc = mysqli_real_escape_string($con, $trainer_id);
        $q_tr = mysqli_query($con, "SELECT Full_name, mobile FROM admin WHERE username = '$trainer_id_esc'");
        if (!$q_tr || mysqli_num_rows($q_tr) === 0) {
            return false;
        }
        
        $trainer = mysqli_fetch_assoc($q_tr);
        $mobile = $trainer['mobile'];
        $trainer_name = $trainer['Full_name'];
        
        if (empty($mobile)) {
            return false;
        }
        
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        
        $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($wa_mobile) === 10) {
            $wa_mobile = '91' . $wa_mobile;
        }
        
        $message = "🏋️ *{$gym_name} Personal Training Alert* 🏋️\n\n" .
                   "Hello *{$trainer_name}*,\n\n" .
                   "You have been assigned a new Personal Training client!\n\n" .
                   "👤 *Client Details:*\n" .
                   "Name: *{$client_name}*\n" .
                   "Member ID: *{$client_id}*\n\n" .
                   "Please connect with the client to customize their workout routines, diet charts, and track their fitness journey.\n\n" .
                   "Thank you,\n" .
                   "*{$gym_name}*";
                   
        return enqueue_whatsapp_message($con, $wa_mobile, $message);
    }
}

if (!function_exists('get_member_streak')) {
    function get_member_streak($con, $uid) {
        $uid_esc = mysqli_real_escape_string($con, $uid);
        $res = mysqli_query($con, "SELECT DISTINCT date FROM attendance WHERE uid = '$uid_esc' ORDER BY date DESC");
        if (!$res || mysqli_num_rows($res) === 0) {
            return 0;
        }
        
        $dates = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $dates[] = $row['date'];
        }
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $latest = $dates[0];
        if ($latest !== $today && $latest !== $yesterday) {
            return 0;
        }
        
        $streak = 1;
        $current_date = strtotime($latest);
        for ($i = 1; $i < count($dates); $i++) {
            $next_date = strtotime($dates[$i]);
            $diff = ($current_date - $next_date) / (60 * 60 * 24);
            if ($diff == 1) {
                $streak++;
                $current_date = $next_date;
            } elseif ($diff > 1) {
                break;
            }
        }
        return $streak;
    }
}

if (!function_exists('send_whatsapp_pt_booking_notification')) {
    function send_whatsapp_pt_booking_notification($con, $uid, $trainer_id, $booking_date, $booking_time) {
        $uid_esc = mysqli_real_escape_string($con, $uid);
        $trainer_id_esc = mysqli_real_escape_string($con, $trainer_id);
        
        // Fetch member info
        $q_mem = mysqli_query($con, "SELECT username, mobile FROM users WHERE userid = '$uid_esc'");
        if (!$q_mem || mysqli_num_rows($q_mem) === 0) return false;
        $mem_data = mysqli_fetch_assoc($q_mem);
        $mem_name = $mem_data['username'];
        $mem_mobile = $mem_data['mobile'];
        
        // Fetch trainer info
        $q_tr = mysqli_query($con, "SELECT Full_name, mobile FROM admin WHERE username = '$trainer_id_esc'");
        if (!$q_tr || mysqli_num_rows($q_tr) === 0) return false;
        $tr_data = mysqli_fetch_assoc($q_tr);
        $tr_name = $tr_data['Full_name'];
        $tr_mobile = $tr_data['mobile'];
        
        // Gym details
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        
        // Format date and time
        $formatted_date = date('d-M-Y', strtotime($booking_date));
        $formatted_time = date('h:i A', strtotime($booking_time));
        
        $url = 'http://localhost:5001/send';
        
        // 1. Send to Member
        if (!empty($mem_mobile)) {
            $mem_wa = preg_replace('/[^0-9]/', '', $mem_mobile);
            if (strlen($mem_wa) === 10) $mem_wa = '91' . $mem_wa;
            
            $mem_msg = "🏋️ *PT Session Booked! - {$gym_name}* 🏋️\n\n" .
                       "Dear *{$mem_name}*,\n\n" .
                       "Your personal training session has been scheduled successfully!\n\n" .
                       "📅 *Booking Details:*\n" .
                       "• Trainer: *{$tr_name}*\n" .
                       "• Date: *{$formatted_date}*\n" .
                       "• Time: *{$formatted_time}*\n\n" .
                       "Please arrive on time. Keep grinding! 💪";
                       
            enqueue_whatsapp_message($con, $mem_wa, $mem_msg);
        }
        
        // 2. Send to Trainer
        if (!empty($tr_mobile)) {
            $tr_wa = preg_replace('/[^0-9]/', '', $tr_mobile);
            if (strlen($tr_wa) === 10) $tr_wa = '91' . $tr_wa;
            
            $tr_msg = "🏋️ *New PT Session Booking - {$gym_name}* 🏋️\n\n" .
                      "Hello *{$tr_name}*,\n\n" .
                      "A personal training session has been scheduled with you.\n\n" .
                      "📅 *Booking Details:*\n" .
                      "• Member: *{$mem_name}* (ID: *{$uid}*)\n" .
                      "• Date: *{$formatted_date}*\n" .
                      "• Time: *{$formatted_time}*\n\n" .
                      "Get ready to coach! 🏋️";
                      
            enqueue_whatsapp_message($con, $tr_wa, $tr_msg);
        }
        
        return true;
    }
}

if (!function_exists('enqueue_whatsapp_message')) {
    function enqueue_whatsapp_message($con, $mobile, $message, $pdf_path = null) {
        if (empty($mobile)) {
            return false;
        }

        // Clean mobile number
        $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($wa_mobile) === 10) {
            $wa_mobile = '91' . $wa_mobile;
        }

        if (strlen($wa_mobile) < 10) {
            return false;
        }

        // Prepare values for SQL
        $mobile_esc = mysqli_real_escape_string($con, $wa_mobile);
        $msg_esc = mysqli_real_escape_string($con, $message);
        $file_esc = $pdf_path ? mysqli_real_escape_string($con, realpath($pdf_path)) : 'NULL';
        $file_val = $pdf_path ? "'$file_esc'" : "NULL";

        // Insert into outbox as pending
        $insert_q = "INSERT INTO whatsapp_outbox (number, message, file_path, status, attempts, last_attempt) 
                     VALUES ('$mobile_esc', '$msg_esc', $file_val, 'pending', 0, NULL)";
        if (!mysqli_query($con, $insert_q)) {
            error_log("[WhatsApp Outbox] Failed to insert message: " . mysqli_error($con));
            return false;
        }
        $msg_id = mysqli_insert_id($con);

        return true;
    }
}
check_and_upgrade_db($con);
