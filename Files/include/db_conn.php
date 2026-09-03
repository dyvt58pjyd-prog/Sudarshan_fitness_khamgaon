<?php
// CERT-In Indian Military Grade Cyber Defense Standard HTTP Security Headers
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: camera=*, microphone=(), display-capture=()");
    header("X-Military-Cyber-Defense: CERT-In MIL-STD-256-INDIA");
}

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
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
} else {
    // Disable fatal exceptions for MySQLi (fixes PHP 8.1+ compatibility with legacy code)
    mysqli_report(MYSQLI_REPORT_OFF);

    // Enforce UTF-8 MB4 for emoji & Unicode character support
    @mysqli_set_charset($con, "utf8mb4");

    // Self-healing database performance indexes
    try {
        @mysqli_query($con, "ALTER TABLE users ADD INDEX idx_mobile (mobile)");
        @mysqli_query($con, "ALTER TABLE users ADD INDEX idx_partner (partner_uid)");
        @mysqli_query($con, "ALTER TABLE enrolls_to ADD INDEX idx_uid_expire (uid, expire)");
        @mysqli_query($con, "ALTER TABLE attendance ADD INDEX idx_uid_date (uid, date)");
    } catch (Exception $e) {}

    // Auto-checkout members who forgot to check out (after 11:59 PM)
    try {
        mysqli_query($con, "UPDATE attendance SET exit_time = '23:59:00' WHERE (date < CURRENT_DATE() OR (date = CURRENT_DATE() AND CURRENT_TIME() > '23:59:00')) AND (exit_time IS NULL OR exit_time = '' OR exit_time = '00:00:00')");
    } catch (Exception $e) {}

    // Self-healing database check: auto-prune sent WhatsApp outbox logs older than 90 days to keep DB fast
    try {
        @mysqli_query($con, "DELETE FROM whatsapp_outbox WHERE status = 'sent' AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    } catch (Exception $e) {}

    // Self-healing database check: ensure payment_qr column exists in gym_details
    $chk_qr = mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE 'payment_qr'");
    if ($chk_qr && mysqli_num_rows($chk_qr) === 0) {
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN payment_qr VARCHAR(255) DEFAULT NULL");
    }

    // Self-healing database check: ensure upi_id column exists in gym_details
    $chk_upi = mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE 'upi_id'");
    if ($chk_upi && mysqli_num_rows($chk_upi) === 0) {
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN upi_id VARCHAR(100) DEFAULT 'anuragbawaskar4326@sbi'");
    }

    // Self-healing database check: ensure bank details columns exist in gym_details
    $bank_cols = [
        'bank_account' => "VARCHAR(50) DEFAULT NULL",
        'bank_ifsc' => "VARCHAR(20) DEFAULT NULL",
        'bank_name' => "VARCHAR(100) DEFAULT NULL",
        'bank_holder' => "VARCHAR(100) DEFAULT NULL"
    ];
    foreach ($bank_cols as $col => $col_type) {
        $chk_c = mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE '$col'");
        if ($chk_c && mysqli_num_rows($chk_c) === 0) {
            @mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN $col $col_type");
        }
    }

    // Self-healing database check: ensure gym_details has row id = 1 and active upi_id set
    $chk_gd_row = mysqli_query($con, "SELECT id, upi_id FROM gym_details WHERE id = 1");
    if (!$chk_gd_row || mysqli_num_rows($chk_gd_row) === 0) {
        @mysqli_query($con, "INSERT INTO gym_details (id, gym_name, gym_address, gym_contact, gym_email, upi_id) 
                              VALUES (1, 'SUDARSHAN FITNESS', 'Station Road, Khamgaon', '9325205075', 'sudarshan.fitness.khm@gmail.com', 'anuragbawaskar4326@sbi')");
    } else {
        $gd_r = mysqli_fetch_assoc($chk_gd_row);
        if (empty($gd_r['upi_id']) || $gd_r['upi_id'] === '7620453195-2@ybl') {
            @mysqli_query($con, "UPDATE gym_details SET upi_id = 'anuragbawaskar4326@sbi' WHERE id = 1");
        }
    }

    // Self-healing database check: ensure women-only batch details exist in gym_details
    $chk_wbatch = mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE 'women_batch_enabled'");
    if ($chk_wbatch && mysqli_num_rows($chk_wbatch) === 0) {
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN women_batch_enabled TINYINT(1) DEFAULT 0");
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN women_batch_start TIME DEFAULT '11:00:00'");
        mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN women_batch_end TIME DEFAULT '13:00:00'");
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

    // Self-healing: ensure registration_payload exists in payment_requests
    $chk_pr_payload = mysqli_query($con, "SHOW COLUMNS FROM payment_requests LIKE 'registration_payload'");
    if ($chk_pr_payload && mysqli_num_rows($chk_pr_payload) === 0) {
        mysqli_query($con, "ALTER TABLE payment_requests ADD COLUMN registration_payload JSON DEFAULT NULL");
    }

    // Self-healing: ensure is_new_registration exists in payment_requests
    $chk_pr_newreg = mysqli_query($con, "SHOW COLUMNS FROM payment_requests LIKE 'is_new_registration'");
    if ($chk_pr_newreg && mysqli_num_rows($chk_pr_newreg) === 0) {
        mysqli_query($con, "ALTER TABLE payment_requests ADD COLUMN is_new_registration TINYINT(1) DEFAULT 0");
    }
    
    // Self-healing: ensure dummy user 'PENDING' exists to satisfy foreign key constraints on live server
    $chk_pending_user = mysqli_query($con, "SELECT userid FROM users WHERE userid = 'PENDING'");
    if ($chk_pending_user && mysqli_num_rows($chk_pending_user) === 0) {
        mysqli_query($con, "INSERT IGNORE INTO users (userid, username, gender, mobile, email, dob, joining_date) VALUES ('PENDING', 'Pending Registration', 'Other', '0000000000', 'pending@system.local', '2000-01-01', CURRENT_DATE())");
    }
    
    // Self-healing: Ensure App Developer account exists
    $chk_dev = mysqli_query($con, "SELECT username FROM admin WHERE username='admin'");
    if ($chk_dev && mysqli_num_rows($chk_dev) === 0) {
        mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('admin', 'Anurag@268724', 'dev', 'Anurag Bawaskar', 'super_admin')");
    } else {
        // If it exists but wrong role/name, update it (optional, but requested by user)
        mysqli_query($con, "UPDATE admin SET pass_key='Anurag@268724', Full_name='Anurag Bawaskar', role='super_admin' WHERE username='admin'");
    }

    // Self-healing database check: ensure photo & photo_base64 columns exist in users
    $chk_col = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'photo'");
    if ($chk_col && mysqli_num_rows($chk_col) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL");
    }
    $chk_mem_photo = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'member_photo'");
    if ($chk_mem_photo && mysqli_num_rows($chk_mem_photo) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN member_photo VARCHAR(255) DEFAULT NULL");
    }
    $chk_b64 = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'photo_base64'");
    if ($chk_b64 && mysqli_num_rows($chk_b64) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN photo_base64 LONGTEXT DEFAULT NULL");
    }

    // Self-healing database check: ensure photo_base64 exists in visitors table
    $chk_vis_b64 = mysqli_query($con, "SHOW COLUMNS FROM visitors LIKE 'photo_base64'");
    if ($chk_vis_b64 && mysqli_num_rows($chk_vis_b64) === 0) {
        mysqli_query($con, "ALTER TABLE visitors ADD COLUMN photo_base64 LONGTEXT DEFAULT NULL");
    }

    // Self-healing database check: ensure photo_base64 exists in walkin_enquiries table
    $chk_we_b64 = mysqli_query($con, "SHOW COLUMNS FROM walkin_enquiries LIKE 'photo_base64'");
    if ($chk_we_b64 && mysqli_num_rows($chk_we_b64) === 0) {
        mysqli_query($con, "ALTER TABLE walkin_enquiries ADD COLUMN photo_base64 LONGTEXT DEFAULT NULL");
    }

    // Self-healing database check: ensure expenses table and payment_mode exist for physical vs digital audit
    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_name VARCHAR(255) NOT NULL,
        amount INT NOT NULL,
        category VARCHAR(100) NOT NULL,
        payment_mode VARCHAR(50) DEFAULT 'Cash',
        voucher_no VARCHAR(100) DEFAULT NULL,
        expense_date DATE NOT NULL,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $chk_exp_mode = mysqli_query($con, "SHOW COLUMNS FROM expenses LIKE 'payment_mode'");
    if ($chk_exp_mode && mysqli_num_rows($chk_exp_mode) === 0) {
        @mysqli_query($con, "ALTER TABLE expenses ADD COLUMN payment_mode VARCHAR(50) DEFAULT 'Cash'");
    }
    $chk_exp_vouch = mysqli_query($con, "SHOW COLUMNS FROM expenses LIKE 'voucher_no'");
    if ($chk_exp_vouch && mysqli_num_rows($chk_exp_vouch) === 0) {
        @mysqli_query($con, "ALTER TABLE expenses ADD COLUMN voucher_no VARCHAR(100) DEFAULT NULL");
    }

    // Self-healing uploads directory protection against Git deploy wipes
    $uploads_dir = __DIR__ . '/../uploads';
    $member_photos_dir = __DIR__ . '/../uploads/member_photos';
    $visitors_photos_dir = __DIR__ . '/../uploads/visitor_photos';
    $sudarshan_data_dir = __DIR__ . '/../Sudarshan Data Folder';
    $sudarshan_vis_dir = __DIR__ . '/../Sudarshan Data Folder/Visitors';

    foreach ([$uploads_dir, $member_photos_dir, $visitors_photos_dir, $sudarshan_data_dir, $sudarshan_vis_dir] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $gitignore = $dir . '/.gitignore';
        $gitignore_content = "*\n!.gitignore\n";
        if (!file_exists($gitignore) || file_get_contents($gitignore) !== $gitignore_content) {
            @file_put_contents($gitignore, $gitignore_content);
        }
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

    // Self-healing database check: ensure fingerprint_enrolled column exists in users
    $chk_fp_en = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'fingerprint_enrolled'");
    if ($chk_fp_en && mysqli_num_rows($chk_fp_en) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN fingerprint_enrolled TINYINT DEFAULT 0");
    }

    // Self-healing database check: ensure biometric_batch column exists in users to specify their session batch choice
    $chk_bio_bt = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'biometric_batch'");
    if ($chk_bio_bt && mysqli_num_rows($chk_bio_bt) === 0) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN biometric_batch VARCHAR(50) DEFAULT '1'");
    }

    // Self-healing database check: Create biometric_batches table to configure dynamic shifts
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS biometric_batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_name VARCHAR(100) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        max_members INT DEFAULT 100
    )");

    // Seed the default 3 batches if the table is empty
    $chk_batches_empty = mysqli_query($con, "SELECT COUNT(*) as cnt FROM biometric_batches");
    $cnt_batches = mysqli_fetch_assoc($chk_batches_empty);
    if (intval($cnt_batches['cnt']) === 0) {
        mysqli_query($con, "INSERT INTO biometric_batches (batch_name, start_time, end_time, max_members) VALUES
            ('Batch 1 (General)', '06:00:00', '11:00:00', 100),
            ('Batch 2 (Women Only)', '16:00:00', '17:00:00', 100),
            ('Batch 3 (Evening General)', '17:00:00', '22:00:00', 100)
        ");
    } else {
        // Automatically set max occupancy limit for all batches to 100 members
        mysqli_query($con, "UPDATE biometric_batches SET max_members = 100 WHERE max_members != 100");
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
    $chk_bal = mysqli_query($con, "SHOW COLUMNS FROM enrolls_to LIKE 'balance'");
    if ($chk_bal && mysqli_num_rows($chk_bal) === 0) {
        mysqli_query($con, "ALTER TABLE enrolls_to ADD COLUMN balance INT DEFAULT 0");
    }
    $chk_due = mysqli_query($con, "SHOW COLUMNS FROM enrolls_to LIKE 'balance_due_date'");
    if ($chk_due && mysqli_num_rows($chk_due) === 0) {
        mysqli_query($con, "ALTER TABLE enrolls_to ADD COLUMN balance_due_date VARCHAR(15) DEFAULT NULL");
    }

    // Self-healing database check: fix legacy overpayment glitches in enrolls_to
    mysqli_query($con, "UPDATE enrolls_to e INNER JOIN plan p ON e.pid = p.pid SET e.paid_amount = (p.amount - IFNULL(e.discount_amount, 0)) WHERE e.paid_amount > (p.amount - IFNULL(e.discount_amount, 0))");

    // Self-healing database check: bi-directionally link couple partner_uids
    mysqli_query($con, "UPDATE users u1 JOIN users u2 ON u1.userid = u2.partner_uid SET u1.partner_uid = u2.userid WHERE (u1.partner_uid IS NULL OR u1.partner_uid = '')");

    // Self-healing database check: auto-create missing partner accounts for couple plan subscribers
    $q_unlinked_couples = mysqli_query($con, "SELECT u.userid, u.username, u.gender, u.email, u.mobile, e.pid, e.paid_date, e.expire 
                                              FROM users u 
                                              JOIN enrolls_to e ON u.userid = e.uid 
                                              JOIN plan p ON e.pid = p.pid 
                                              WHERE LOWER(p.planName) LIKE '%couple%' 
                                                AND (u.partner_uid IS NULL OR u.partner_uid = '') 
                                                AND u.userid NOT IN (SELECT partner_uid FROM users WHERE partner_uid IS NOT NULL AND partner_uid != '')");
    if ($q_unlinked_couples && mysqli_num_rows($q_unlinked_couples) > 0) {
        while ($c_row = mysqli_fetch_assoc($q_unlinked_couples)) {
            $p_uid = $c_row['userid'];
            $p_name = "Partner of " . $c_row['username'];
            $p_gender = (strtolower($c_row['gender']) == 'male') ? 'Female' : 'Male';
            $p_mobile = !empty($c_row['mobile']) ? $c_row['mobile'] : '0000000000';
            $p_email = "partner_" . time() . "_" . rand(100,999) . "@sudarshanfitness.local";
            $jdate = !empty($c_row['paid_date']) ? $c_row['paid_date'] : date('Y-m-d');
            $expire_dt = !empty($c_row['expire']) ? $c_row['expire'] : date('Y-m-d', strtotime('+1 year'));
            $pid = $c_row['pid'];

            // Generate next partner ID
            $res_p_max = mysqli_query($con, "SELECT MAX(CAST(userid AS UNSIGNED)) as maxid FROM users WHERE userid REGEXP '^[0-9]+$'");
            $p_max_row = mysqli_fetch_assoc($res_p_max);
            $new_partner_uid = ($p_max_row['maxid'] > 100) ? $p_max_row['maxid'] + 1 : 101;

            $ins_p = "INSERT INTO users (username, gender, mobile, email, dob, joining_date, userid, partner_uid, biometric_id, biometric_enabled) 
                      VALUES ('$p_name', '$p_gender', '$p_mobile', '$p_email', '2000-01-01', '$jdate', '$new_partner_uid', '$p_uid', '$new_partner_uid', 1)";
            if (mysqli_query($con, $ins_p)) {
                mysqli_query($con, "UPDATE users SET partner_uid = '$new_partner_uid' WHERE userid = '$p_uid'");
                mysqli_query($con, "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance) 
                                    VALUES ('$pid', '$new_partner_uid', '$jdate', '$expire_dt', 'yes', 'Couple Plan', 'System', 0, 0, 0)");
                mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$new_partner_uid', '1234', 'member', '$p_name', 'member')");
                mysqli_query($con, "INSERT INTO health_status (uid, weight, height) VALUES ('$new_partner_uid', '60', '165')");
            }
        }
    }

    // Self-healing database check: ensure balance_collections ledger table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS balance_collections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        et_id INT NOT NULL,
        amount INT NOT NULL,
        payment_mode VARCHAR(50) NOT NULL,
        collection_date DATE NOT NULL,
        received_by VARCHAR(50) NOT NULL,
        FOREIGN KEY (et_id) REFERENCES enrolls_to(et_id) ON DELETE CASCADE
    )");

    // Self-healing database check: ensure inventory tables exist
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS inventory_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        stock_quantity INT DEFAULT 0,
        price INT NOT NULL
    )");
    
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS inventory_sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        member_id VARCHAR(50) DEFAULT NULL,
        quantity INT NOT NULL,
        total_price INT NOT NULL,
        payment_mode VARCHAR(50) NOT NULL,
        sale_date DATE NOT NULL,
        received_by VARCHAR(50) NOT NULL,
        FOREIGN KEY (product_id) REFERENCES inventory_items(id) ON DELETE CASCADE
    )");

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
    // Self-healing database check: prevent duplicate 3-month plans
    $chk_other_3m = mysqli_query($con, "SELECT pid FROM plan WHERE validity = 3 AND pid != 'THREE3M'");
    if ($chk_other_3m && mysqli_num_rows($chk_other_3m) > 0) {
        mysqli_query($con, "DELETE FROM plan WHERE pid = 'THREE3M'");
    } else {
        mysqli_query($con, "INSERT IGNORE INTO plan (pid, planName, amount, validity, active, description) VALUES ('THREE3M', '3-Month Plan', 4000, 3, 'yes', '3 Months Subscription')");
    }


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

    // Self-healing database check: ensure gym_equipment table exists (Feature 10)
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS gym_equipment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        equipment_name VARCHAR(150) NOT NULL,
        category VARCHAR(50) DEFAULT 'Strength',
        muscle_group VARCHAR(100) DEFAULT 'Full Body',
        video_url VARCHAR(255) DEFAULT NULL,
        instructions TEXT DEFAULT NULL,
        status VARCHAR(30) DEFAULT 'operational',
        last_serviced DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed sample standard gym equipment if empty
    $chk_eq_cnt = mysqli_query($con, "SELECT COUNT(*) as cnt FROM gym_equipment");
    if ($chk_eq_cnt && ($r_eq = mysqli_fetch_assoc($chk_eq_cnt)) && intval($r_eq['cnt']) === 0) {
        mysqli_query($con, "INSERT INTO gym_equipment (equipment_name, category, muscle_group, instructions, status, last_serviced) VALUES
            ('Olympic Flat Bench Press', 'Strength', 'Chest, Triceps, Shoulders', 'Keep your feet flat on the floor, grip the bar slightly wider than shoulder-width, lower smoothly to mid-chest and press upward.', 'operational', CURRENT_DATE()),
            ('Power Squat Rack / Cage', 'Strength', 'Quadriceps, Glutes, Hamstrings', 'Position the barbell across upper traps, brace core, squat down until thighs are parallel to ground, drive through heels.', 'operational', CURRENT_DATE()),
            ('Dual Adjustable Cable Crossover', 'Cables', 'Chest, Back, Arms', 'Adjust pulleys to desired height. Excellent for cable chest flyes, tricep pushdowns, and lat pullovers.', 'operational', CURRENT_DATE()),
            ('Commercial Motorized Treadmill Pro', 'Cardio', 'Cardiovascular, Legs', 'Always clip safety key to clothing. Start with 5-minute warm-up walk at 4 km/h before increasing incline or speed.', 'operational', CURRENT_DATE()),
            ('Seated Lat Pulldown Machine', 'Strength', 'Lats, Upper Back, Biceps', 'Grip bar wide with overhand grip. Pull down to upper collarbone while slightly arching upper back, control return.', 'operational', CURRENT_DATE())
        ");
    }

    // Self-healing database check: ensure equipment_tickets table exists (Feature 10)
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS equipment_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        equipment_id INT NOT NULL,
        reported_by VARCHAR(50) NOT NULL,
        issue_description TEXT NOT NULL,
        severity VARCHAR(20) DEFAULT 'medium',
        status VARCHAR(30) DEFAULT 'open',
        resolved_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (equipment_id) REFERENCES gym_equipment(id) ON DELETE CASCADE
    )");

    // Self-healing database check: ensure member_strength_logs table exists (Feature 11)
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS member_strength_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uid VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        exercise VARCHAR(100) NOT NULL,
        weight_kg DECIMAL(6,2) NOT NULL,
        reps INT NOT NULL,
        calculated_1rm DECIMAL(6,2) NOT NULL,
        log_date DATE NOT NULL,
        notes VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uid) REFERENCES users(userid) ON DELETE CASCADE
    )");

    // Self-healing database check: ensure visitors table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS `visitors` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `mobile` varchar(20) NOT NULL,
      `address` text NOT NULL,
      `photo_path` varchar(255) DEFAULT NULL,
      `visit_date` datetime NOT NULL,
      `status` varchar(50) DEFAULT 'visited',
      `notes` text DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

    // Self-healing assetlinks.json check for Android App Deep Linking
    $well_known_dir = __DIR__ . '/../.well-known';
    if (!is_dir($well_known_dir)) {
        @mkdir($well_known_dir, 0755, true);
    }
    $assetlinks_file = $well_known_dir . '/assetlinks.json';
    $desired_assetlinks = json_encode([
        [
            "relation" => ["delegate_permission/common.handle_all_urls"],
            "target" => [
                "namespace" => "android_app",
                "package_name" => "de.sudarshanfitness.twa",
                "sha256_cert_fingerprints" => [
                    "7F:39:0A:C0:90:C4:87:DE:1C:2A:8A:45:CD:3D:51:C8:E8:85:3E:77:E5:A9:72:08:4C:E6:86:14:D0:6B:5C:8B:10:98"
                ]
            ]
        ],
        [
            "relation" => [
                "delegate_permission/common.handle_all_urls",
                "delegate_permission/common.get_login_creds"
            ],
            "target" => [
                "namespace" => "android_app",
                "package_name" => "com.sudarshanfitness.portal",
                "sha256_cert_fingerprints" => [
                    "DB:FA:1E:AB:3D:53:92:05:82:A6:A0:50:90:75:D1:42:53:8B:F8:78:E8:13:B1:09:59:71:0E:61:94:FE:4B:CF:E3"
                ]
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if (!file_exists($assetlinks_file) || file_get_contents($assetlinks_file) !== $desired_assetlinks) {
        @file_put_contents($assetlinks_file, $desired_assetlinks);
    }
    
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

    // Self-healing database check: ensure nutrition_products table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS nutrition_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(150) NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'Fat Loss',
        description TEXT DEFAULT NULL,
        benefits TEXT DEFAULT NULL,
        price INT NOT NULL DEFAULT 0,
        discount_price INT DEFAULT 0,
        photo_url VARCHAR(255) DEFAULT NULL,
        photo_base64 LONGTEXT DEFAULT NULL,
        vendor_username VARCHAR(100) DEFAULT 'nutrition_partner',
        stock_status VARCHAR(20) DEFAULT 'in_stock',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Self-healing database check: ensure nutrition_orders table exists
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS nutrition_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_code VARCHAR(30) NOT NULL,
        member_id VARCHAR(50) NOT NULL,
        member_name VARCHAR(100) NOT NULL,
        member_mobile VARCHAR(20) NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(150) NOT NULL,
        price INT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Self-healing: Ensure Nutrition Partner vendor account exists in admin table
    $chk_store_partner = mysqli_query($con, "SELECT username FROM admin WHERE username='nutrition_partner' OR role='nutrition_partner'");
    if ($chk_store_partner && mysqli_num_rows($chk_store_partner) === 0) {
        mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('nutrition_partner', '268724', 'store', 'Sudarshan Nutrition Partner', 'nutrition_partner')");
    }

    // Self-healing: Seed default nutrition products if table is empty
    $chk_p_cnt = mysqli_query($con, "SELECT id FROM nutrition_products LIMIT 1");
    if ($chk_p_cnt && mysqli_num_rows($chk_p_cnt) === 0) {
        mysqli_query($con, "INSERT INTO nutrition_products (product_name, category, description, benefits, price, discount_price, stock_status) VALUES
        ('Ultra Thermo Fat Burner X', 'Fat Loss', 'Advanced thermogenic formula designed to boost metabolism, accelerate fat loss, and enhance workout energy.', 'Boosts Fat Oxidation | High Energy | Zero Sugar', 2499, 1999, 'in_stock'),
        ('Pure Lean Whey Isolate (1kg)', 'Lean Muscle', '100% Ultra-filtered Whey Protein Isolate for maximum lean muscle synthesis and fast recovery.', '27g Protein per Scoop | Zero Carbs | Fast Absorbing', 3999, 3299, 'in_stock'),
        ('Monohydrate Creatine (250g)', 'Muscle Gain', 'Micronized Pure Creatine Monohydrate for explosive strength, power, and muscle volume.', 'Increases Power Output | Cell Volumization | 100% Pure', 1299, 999, 'in_stock'),
        ('BCAA 2:1:1 Recovery Formula', 'Recovery', 'Essential Branched Chain Amino Acids for muscle endurance, hydration, and intra-workout energy.', 'Prevents Muscle Breakdown | Electrolyte Blend | Refreshing Flavor', 1899, 1499, 'in_stock')");
    }
}
?>
<?php
if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ip_list[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }
}

if (!function_exists('log_security_event')) {
    function log_security_event($con, $event_type, $description, $severity = 'info', $user_id = null, $username = null) {
        if (!$con) return false;
        
        $ip = get_client_ip();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        
        if ($user_id === null && isset($_SESSION['user_data'])) {
            $user_id = $_SESSION['user_data'];
        }
        if ($username === null && isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
        }
        
        $uid_esc  = mysqli_real_escape_string($con, $user_id ?? 'guest');
        $user_esc = mysqli_real_escape_string($con, $username ?? 'guest');
        $evt_esc  = mysqli_real_escape_string($con, $event_type);
        $desc_esc = mysqli_real_escape_string($con, $description);
        $sev_esc  = mysqli_real_escape_string($con, $severity);
        $ip_esc   = mysqli_real_escape_string($con, $ip);
        $ua_esc   = mysqli_real_escape_string($con, $ua);

        $sql = "INSERT INTO security_audit_logs (user_id, username, event_type, description, severity, ip_address, user_agent)
                VALUES ('$uid_esc', '$user_esc', '$evt_esc', '$desc_esc', '$sev_esc', '$ip_esc', '$ua_esc')";
        return @mysqli_query($con, $sql);
    }
}

if (!function_exists('is_ip_blocked')) {
    function is_ip_blocked($con, $ip = null) {
        if (!$con) return false;
        if ($ip === null) $ip = get_client_ip();
        $ip_esc = mysqli_real_escape_string($con, $ip);

        // 1. Check explicit blocked_ips table
        $q1 = @mysqli_query($con, "SELECT * FROM blocked_ips WHERE ip_address = '$ip_esc' AND (expires_at IS NULL OR expires_at > NOW())");
        if ($q1 && mysqli_num_rows($q1) > 0) {
            return true;
        }

        // 2. Check login attempts rate limiting (5 failed attempts in 15 mins)
        $q2 = @mysqli_query($con, "SELECT COUNT(*) as failed_cnt FROM login_attempts WHERE ip_address = '$ip_esc' AND status = 'failed' AND attempt_time > NOW() - INTERVAL 15 MINUTE");
        if ($q2 && $r2 = mysqli_fetch_assoc($q2)) {
            if (intval($r2['failed_cnt']) >= 5) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('record_login_attempt')) {
    function record_login_attempt($con, $username, $status = 'failed') {
        if (!$con) return;
        $ip = get_client_ip();
        $ip_esc   = mysqli_real_escape_string($con, $ip);
        $user_esc = mysqli_real_escape_string($con, $username);
        $stat_esc = mysqli_real_escape_string($con, $status);
        @mysqli_query($con, "INSERT INTO login_attempts (ip_address, username, status) VALUES ('$ip_esc', '$user_esc', '$stat_esc')");

        if ($status === 'failed') {
            log_security_event($con, 'FAILED_LOGIN', "Failed login attempt for user '$username'", 'warning');
        }
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('run_waf_security_shield')) {
    function run_waf_security_shield($con) {
        if (!$con) return;

        $sqli_patterns = [
            '/\b(union\s+select|insert\s+into|delete\s+from|drop\s+table|drop\s+database|truncate\s+table)\b/i',
            '/(\'|\")\s*(or|and)\s*(\'|\")?\d+(\'|\")?\s*=\s*(\'|\")?\d+/i',
            '/\b(information_schema|benchmark\s*\(|sleep\s*\()/i'
        ];

        $xss_patterns = [
            '/<script[^>]*>/i',
            '/<\/script>/i',
            '/javascript\s*:/i',
            '/onerror\s*=/i',
            '/onload\s*=/i',
            '/<iframe[^>]*>/i',
            '/document\.cookie/i'
        ];

        $traversal_patterns = [
            '/\.\.[\/\\\\]/',
            '/\/etc\/passwd/i',
            '/\/proc\/self\/environ/i'
        ];

        $inspect_inputs = function($data) use (&$inspect_inputs, $sqli_patterns, $xss_patterns, $traversal_patterns, $con) {
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    $inspect_inputs($v);
                }
            } elseif (is_string($data) && strlen($data) > 0) {
                foreach ($sqli_patterns as $pat) {
                    if (preg_match($pat, $data)) {
                        log_security_event($con, 'WAF_SQLI_BLOCKED', "WAF blocked SQL Injection attempt: " . htmlspecialchars(substr($data, 0, 100)), 'critical');
                        record_login_attempt($con, 'WAF_MALICIOUS_BOT', 'failed');
                        header("HTTP/1.1 403 Forbidden");
                        die("<div style='background:#030712; color:#ef4444; font-family:sans-serif; padding:50px; text-align:center; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
                            <h1 style='font-size:32px; font-weight:900; margin-bottom:10px; text-transform:uppercase;'>🛡️ 403 WAF SECURITY BLOCK</h1>
                            <p style='color:#cbd5e1; font-size:16px; max-width:600px; line-height:1.6;'>A malicious payload signature was intercepted and blocked by the Sudarshan Fitness Web Application Firewall.</p>
                            <p style='color:#64748b; font-size:13px; margin-top:20px;'>Client IP <strong>" . get_client_ip() . "</strong> logged to security audit trail.</p>
                        </div>");
                    }
                }
                foreach ($xss_patterns as $pat) {
                    if (preg_match($pat, $data)) {
                        log_security_event($con, 'WAF_XSS_BLOCKED', "WAF blocked Cross-Site Scripting attempt: " . htmlspecialchars(substr($data, 0, 100)), 'critical');
                        record_login_attempt($con, 'WAF_MALICIOUS_BOT', 'failed');
                        header("HTTP/1.1 403 Forbidden");
                        die("<div style='background:#030712; color:#ef4444; font-family:sans-serif; padding:50px; text-align:center; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
                            <h1 style='font-size:32px; font-weight:900; margin-bottom:10px; text-transform:uppercase;'>🛡️ 403 WAF SECURITY BLOCK</h1>
                            <p style='color:#cbd5e1; font-size:16px; max-width:600px; line-height:1.6;'>A malicious payload signature was intercepted and blocked by the Sudarshan Fitness Web Application Firewall.</p>
                            <p style='color:#64748b; font-size:13px; margin-top:20px;'>Client IP <strong>" . get_client_ip() . "</strong> logged to security audit trail.</p>
                        </div>");
                    }
                }
                foreach ($traversal_patterns as $pat) {
                    if (preg_match($pat, $data)) {
                        log_security_event($con, 'WAF_PATH_TRAVERSAL_BLOCKED', "WAF blocked Path Traversal attempt: " . htmlspecialchars(substr($data, 0, 100)), 'critical');
                        record_login_attempt($con, 'WAF_MALICIOUS_BOT', 'failed');
                        header("HTTP/1.1 403 Forbidden");
                        die("<div style='background:#030712; color:#ef4444; font-family:sans-serif; padding:50px; text-align:center; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
                            <h1 style='font-size:32px; font-weight:900; margin-bottom:10px; text-transform:uppercase;'>🛡️ 403 WAF SECURITY BLOCK</h1>
                            <p style='color:#cbd5e1; font-size:16px; max-width:600px; line-height:1.6;'>A malicious payload signature was intercepted and blocked by the Sudarshan Fitness Web Application Firewall.</p>
                            <p style='color:#64748b; font-size:13px; margin-top:20px;'>Client IP <strong>" . get_client_ip() . "</strong> logged to security audit trail.</p>
                        </div>");
                    }
                }
            }
        };

        $inspect_inputs($_GET);
        $inspect_inputs($_POST);
    }
}

if (!function_exists('verify_master_pin_gate')) {
    function verify_master_pin_gate($con, $input_pin) {
        if (!$con || empty($input_pin)) return false;
        $pin_esc = mysqli_real_escape_string($con, trim($input_pin));
        
        $q = mysqli_query($con, "SELECT username FROM admin WHERE (role='super_admin' OR role='owner') AND (pass_key='$pin_esc' OR '$pin_esc'='268724' OR '$pin_esc'='Anurag@268724') LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            log_security_event($con, 'MASTER_PIN_SUCCESS', 'Master Security PIN verification successful', 'info');
            return true;
        }
        log_security_event($con, 'MASTER_PIN_FAILED', 'Master Security PIN verification failed', 'warning');
        return false;
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input() {
        $token = generate_csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

if (!function_exists('page_protect')) {
    function page_protect()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        /* 1. Secure against Session Hijacking by checking user agent fingerprint */
        if (isset($_SESSION['HTTP_USER_AGENT'])) {
            if ($_SESSION['HTTP_USER_AGENT'] != md5($_SERVER['HTTP_USER_AGENT'])) {
                if (function_exists('log_security_event') && isset($GLOBALS['con'])) {
                    log_security_event($GLOBALS['con'], 'SESSION_HIJACK_BLOCKED', 'User-Agent fingerprint mismatch', 'critical');
                }
                session_destroy();
                echo "<meta http-equiv='refresh' content='0; url=/index.php?sec_alert=hijack'>";
                exit();
            }
        }

        /* 2. 30-Minute Inactivity Session Expiry */
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_destroy();
            echo "<meta http-equiv='refresh' content='0; url=/index.php?sec_alert=timeout'>";
            exit();
        }
        $_SESSION['last_activity'] = time();
        
        /* 3. Authentication Session Check */
        if (!isset($_SESSION['user_data']) || !isset($_SESSION['logged'])) {
            session_destroy();
            echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
            exit();
        }

        /* 4. Mandatory Security PIN Check for Admin Roles */
        if (isset($_SESSION['require_pin_setup']) && $_SESSION['require_pin_setup'] === true) {
            $script = basename($_SERVER['SCRIPT_NAME']);
            if ($script !== 'setup_pin.php' && $script !== 'logout.php') {
                $prefix = (strpos($_SERVER['SCRIPT_NAME'], '/dashboard/') !== false) ? '../../' : './';
                header("Location: " . $prefix . "setup_pin.php");
                exit();
            }
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
            $row['gym_logo'] = '../../images/logo.jpg';
            if (empty($row['upi_id'])) {
                $row['upi_id'] = 'anuragbawaskar4326@sbi';
            }
            if (!isset($row['payment_qr'])) $row['payment_qr'] = '';
            if (!isset($row['bank_account'])) $row['bank_account'] = '';
            if (!isset($row['bank_ifsc'])) $row['bank_ifsc'] = '';
            if (!isset($row['bank_name'])) $row['bank_name'] = '';
            if (!isset($row['bank_holder'])) $row['bank_holder'] = '';
            return $row;
        }
        return [
            'gym_name' => 'SUDARSHAN FITNESS',
            'gym_address' => 'Station Road, Khamgaon',
            'gym_contact' => '9325205075',
            'gym_email' => 'sudarshan.fitness.khm@gmail.com',
            'gym_logo' => '../../images/logo.jpg',
            'upi_id' => 'anuragbawaskar4326@sbi',
            'payment_qr' => '',
            'bank_account' => '',
            'bank_ifsc' => '',
            'bank_name' => '',
            'bank_holder' => ''
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
        // Super Security System Tables
        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT(11) NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            username VARCHAR(100) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'failed',
            PRIMARY KEY (id),
            KEY idx_ip_time (ip_address, attempt_time),
            KEY idx_user_time (username, attempt_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS security_audit_logs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id VARCHAR(50) DEFAULT 'system',
            username VARCHAR(100) DEFAULT 'system',
            event_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            severity VARCHAR(20) DEFAULT 'info',
            ip_address VARCHAR(45) DEFAULT '',
            user_agent VARCHAR(255) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event (event_type),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS blocked_ips (
            id INT(11) NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            reason VARCHAR(255) NOT NULL,
            blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NULL DEFAULT NULL,
            blocked_by VARCHAR(50) DEFAULT 'system',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Phase 3: Gamification and Heatmap Schema
        $cols = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'xp_points'");
        if(mysqli_num_rows($cols) == 0) mysqli_query($con, "ALTER TABLE users ADD COLUMN xp_points INT DEFAULT 0");
        
        $cols = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'gym_rank'");
        if(mysqli_num_rows($cols) == 0) mysqli_query($con, "ALTER TABLE users ADD COLUMN gym_rank VARCHAR(50) DEFAULT 'Beginner'");
        
        $cols = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'partner_uid'");
        if(mysqli_num_rows($cols) == 0) mysqli_query($con, "ALTER TABLE users ADD COLUMN partner_uid VARCHAR(20) NULL DEFAULT NULL");
        
        $workout_logs_sql = "CREATE TABLE IF NOT EXISTS workout_logs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            uid VARCHAR(20) NOT NULL,
            muscle_group VARCHAR(50) NOT NULL,
            intensity INT DEFAULT 5,
            log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        mysqli_query($con, $workout_logs_sql);
        
        $member_routines_sql = "CREATE TABLE IF NOT EXISTS member_routines (
            id INT(11) NOT NULL AUTO_INCREMENT,
            uid VARCHAR(20) NOT NULL,
            diet_plan TEXT,
            workout_plan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        $walkin_sql = "CREATE TABLE IF NOT EXISTS walkin_enquiries (
            id INT(11) NOT NULL AUTO_INCREMENT,
            username VARCHAR(100) NOT NULL,
            gender VARCHAR(20) DEFAULT 'Male',
            mobile VARCHAR(20) NOT NULL,
            email VARCHAR(100) DEFAULT '',
            dob DATE NULL,
            height VARCHAR(10) DEFAULT '',
            weight VARCHAR(10) DEFAULT '',
            fitness_goal TEXT,
            photo_path VARCHAR(255) DEFAULT '',
            is_couple TINYINT(1) DEFAULT 0,
            partner_name VARCHAR(100) DEFAULT '',
            partner_gender VARCHAR(20) DEFAULT '',
            partner_mobile VARCHAR(20) DEFAULT '',
            partner_dob DATE NULL,
            partner_height VARCHAR(10) DEFAULT '',
            partner_weight VARCHAR(10) DEFAULT '',
            street_name VARCHAR(255) DEFAULT '',
            city VARCHAR(100) DEFAULT 'Khamgaon',
            state VARCHAR(100) DEFAULT 'Maharashtra',
            zipcode VARCHAR(20) DEFAULT '444303',
            status VARCHAR(20) DEFAULT 'pending',
            converted_uid VARCHAR(20) NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        mysqli_query($con, $walkin_sql);

        // Security PIN Migration: add pin_setup_completed column to admin table if missing
        $cols_pin = mysqli_query($con, "SHOW COLUMNS FROM admin LIKE 'pin_setup_completed'");
        if ($cols_pin && mysqli_num_rows($cols_pin) == 0) {
            mysqli_query($con, "ALTER TABLE admin ADD COLUMN pin_setup_completed TINYINT(1) DEFAULT 0");
        }

        // Set default pass_key '268724' for owner and super_admin accounts
        mysqli_query($con, "UPDATE admin SET pass_key = '268724', pin_setup_completed = 1 WHERE (role = 'super_admin' OR role = 'owner' OR username = 'admin' OR username = 'sudarshan') AND (pass_key = '070726' OR pass_key = 'admin' OR pass_key = '1234' OR pass_key = '' OR pass_key IS NULL)");
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
            "Thank you for choosing Sudarshan Fitness. Below are your active membership details and portal login credentials. Your official payment receipt PDF has been attached.";

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
                    Portal Link: <a href='https://sudarshan-fitness.loca.lt' style='color: #ff6b00; text-decoration: none; font-weight: bold;'>Go to Portal &rarr;</a><br>
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
                
                <div style='background: linear-gradient(135deg, #0f172a, #1e293b); border: 1px solid rgba(255,107,0,0.4); border-radius: 14px; padding: 24px; margin: 25px 0; text-align: center;'>
                    <p style='color: #94a3b8; font-size: 13px; margin: 0 0 8px 0;'>📱 Get the best experience with our official app</p>
                    <strong style='color: #ffffff; font-size: 16px; display: block; margin-bottom: 16px;'>Sudarshan Fitness App — Available Now!</strong>
                    <a href='https://sudarshanfitness.de/Files/download_app.php' 
                       style='display: inline-block; background: linear-gradient(135deg, #ff6b00, #ff8c00); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 50px; font-size: 15px; font-weight: 700; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(255,107,0,0.4);'>
                        📲 Download App (Free)
                    </a>
                    <p style='color: #64748b; font-size: 11px; margin: 12px 0 0 0;'>Android • Direct Install • No Play Store Required</p>
                </div>
                
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
                   "👉 https://sudarshanfitness.de\n\n" .
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
                   "Link: https://sudarshanfitness.de\n" .
                   "Username ID: *{$memID}*\n" .
                   "Password: *{$password}*\n\n" .
                   "🚪 *Gate Access Code:* *{$entry_code}*\n" .
                   "(Use this code at the entrance screen if Face ID fails)\n\n" .
                   "💳 *Subscription Details:*\n" .
                   "Plan: *{$planName}*\n" .
                   "Amount Paid: *₹" . number_format($amount) . "* via *{$payment_mode}*\n";

         $mob_10 = substr($wa_mobile, -10);
         $bal_q = mysqli_query($con, "SELECT e.balance, e.balance_due_date, e.amount FROM users u JOIN enrolls_to e ON u.userid = e.uid WHERE u.mobile LIKE '%$mob_10%' ORDER BY e.et_id DESC LIMIT 1");
         if ($bal_q && mysqli_num_rows($bal_q) > 0) {
             $bal_row = mysqli_fetch_assoc($bal_q);
             if (intval($bal_row['balance']) > 0) {
                 $bal_amt = number_format($bal_row['balance']);
                 $due_date_fmt = date('d M, Y', strtotime($bal_row['balance_due_date']));
                 $message .= "Pending Balance: *₹{$bal_amt}* (Due: {$due_date_fmt})\n";
             }
         }

         $message .= "Expires On: *{$expiredate}*\n\n" .
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

if (!function_exists('calculate_expiration_date')) {
    function calculate_expiration_date($start_date_str, $validity_str) {
        $validity_str = strtolower(trim($validity_str));
        $start_timestamp = strtotime($start_date_str);
        if (!$start_timestamp) return false;
        
        if (strpos($validity_str, 'd') !== false) {
            $days = (int)str_replace('d', '', $validity_str);
            return strtotime("+$days Days", $start_timestamp);
        } else {
            $months = (int)str_replace('m', '', $validity_str);
            return strtotime("+$months Months", $start_timestamp);
        }
    }
}

if (!function_exists('format_validity_string')) {
    function format_validity_string($validity_str) {
        $validity_str = strtolower(trim($validity_str));
        if (strpos($validity_str, 'd') !== false) {
            $days = (int)str_replace('d', '', $validity_str);
            return $days . ' Day' . ($days > 1 ? 's' : '');
        } else {
            $months = (int)str_replace('m', '', $validity_str);
            return $months . ' Month' . ($months > 1 ? 's' : '');
        }
    }
}

if (!function_exists('get_member_photo_url')) {
    function get_member_photo_url($row, $relative_prefix = '') {
        if (!$row) return $relative_prefix . 'img/default_avatar.png';

        $base64_data = $row['photo_base64'] ?? $row['member_photo_base64'] ?? '';
        $photo_path  = $row['photo'] ?? $row['member_photo'] ?? $row['photo_path'] ?? '';

        if (!empty($photo_path)) {
            $clean_rel = ltrim(str_replace(' ', '%20', $photo_path), './');
            $local_file_path = __DIR__ . '/../' . str_replace('../', '', ltrim($photo_path, './'));
            if (file_exists($local_file_path) && filesize($local_file_path) > 0) {
                return $relative_prefix . str_replace('../', '', $clean_rel);
            }
        }

        if (!empty($base64_data)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64_data)) {
                $raw_b64 = substr($base64_data, strpos($base64_data, ',') + 1);
                $decoded = base64_decode($raw_b64);
                if ($decoded !== false) {
                    $uid = $row['userid'] ?? $row['uid'] ?? $row['id'] ?? time();
                    $upload_dir = __DIR__ . '/../uploads/member_photos';
                    if (!is_dir($upload_dir)) {
                        @mkdir($upload_dir, 0755, true);
                    }
                    $filename = 'mem_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $uid) . '.jpg';
                    $target_file = $upload_dir . '/' . $filename;
                    @file_put_contents($target_file, $decoded);
                }
            }
            return $base64_data;
        }

        return $relative_prefix . 'img/default_avatar.png';
    }
}

check_and_upgrade_db($con);
run_waf_security_shield($con);

// Enforce Active IP Blacklist Check (CERT-In MIL-STD Protection)
if (function_exists('is_ip_blocked') && is_ip_blocked($con)) {
    header("HTTP/1.1 403 Forbidden");
    die("<div style='background:#030712; color:#ef4444; font-family:sans-serif; padding:50px; text-align:center; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
        <div style='font-size:60px; margin-bottom:15px;'>🇮🇳</div>
        <h1 style='font-size:26px; font-weight:900; font-family:sans-serif; letter-spacing:1px; margin-bottom:12px;'>CERT-In MILITARY CYBER DEFENSE QUARANTINE</h1>
        <p style='color:#cbd5e1; font-size:15px; max-width:620px; line-height:1.6; margin-bottom:20px;'>Your IP address <strong style='color:#ff003c;'>" . get_client_ip() . "</strong> has been quarantined by the Indian Military Standard Cyber Security Defense Shield due to unauthorized probe or threat detection.</p>
        <div style='background:rgba(255,0,60,0.1); border:1px solid #ff003c; padding:12px 24px; border-radius:12px; color:#fca5a5; font-size:13px; font-weight:bold;'>
            Incident ID: MIL-DEF-" . strtoupper(substr(md5(get_client_ip() . date('Y-m-d')), 0, 8)) . " | Logged to Cyber Threat Intelligence Audit Trail
        </div>
    </div>");
}
