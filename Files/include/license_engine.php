<?php
// License and Security Engine for Sudarshan Fitness Commercial Distribution

class LicenseEngine {
    private static $license_file = __DIR__ . '/license_data.json';
    private static $master_salt = 'SUDARSHAN_FITNESS_PRO_SECURE_SALT_9948271';

    // Get or initialize license state
    public static function get_state() {
        if (!file_exists(self::$license_file)) {
            $initial = [
                'status' => 'EXPIRED', // Forced to expired to show the lock screen
                'installation_id' => 'SF-GYM-' . strtoupper(substr(md5($_SERVER['HTTP_HOST'] ?? 'sudarshanfitness.de'), 0, 6)),
                'client_name' => 'Sudarshan Fitness Khamgaon',
                'vendor_name' => 'Anurag Bawaskar (Software Developer & Owner)',
                'vendor_phone' => '+91 8459962390',
                'vendor_email' => 'contact@sudarshanfitness.de',
                'activated_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('-1 days')), // Expired yesterday
                'last_verified_at' => date('Y-m-d H:i:s'),
                'lock_reason' => 'Software Subscription Renewal Required. Please contact the Developer.'
            ];
            @file_put_contents(self::$license_file, json_encode($initial, JSON_PRETTY_PRINT));
            return $initial;
        }

        $data = @json_decode(file_get_contents(self::$license_file), true);
        if (!is_array($data)) {
            $data = [];
        }
        return $data;
    }

    // Save license state
    public static function save_state($data) {
        $data['last_verified_at'] = date('Y-m-d H:i:s');
        @file_put_contents(self::$license_file, json_encode($data, JSON_PRETTY_PRINT));
    }

    // Check if the current installation is allowed to run
    public static function check_license() {
        $state = self::get_state();

        // 1. Manually locked by owner
        if (isset($state['status']) && $state['status'] === 'LOCKED') {
            return [
                'valid' => false,
                'reason' => !empty($state['lock_reason']) ? $state['lock_reason'] : 'Software access has been suspended by the Developer.',
                'state' => $state
            ];
        }

        // 2. Date-based expiry check
        if (!empty($state['expires_at'])) {
            $expiry_time = strtotime($state['expires_at']);
            $current_time = time();

            if ($current_time > $expiry_time) {
                if ($state['status'] !== 'EXPIRED') {
                    $state['status'] = 'EXPIRED';
                    $state['lock_reason'] = 'Software license expired on ' . date('d M Y', $expiry_time) . '. Renewal required.';
                    self::save_state($state);
                }
                return [
                    'valid' => false,
                    'reason' => 'Software license expired on ' . date('d M Y', $expiry_time) . '. Please renew subscription.',
                    'state' => $state
                ];
            }
        }

        return ['valid' => true, 'state' => $state];
    }

    // Generate cryptographic activation key for a given duration
    public static function generate_key($installation_id, $days = 30) {
        // Format: SF-[DAYS]D-[HASH8]
        $raw = $installation_id . '|' . intval($days) . '|' . self::$master_salt;
        $hash = strtoupper(substr(hash('sha256', $raw), 0, 8));
        return 'SF-' . intval($days) . 'D-' . $hash;
    }

    // Validate and apply an activation key entered by the client
    public static function activate_with_key($entered_key) {
        $entered_key = strtoupper(trim($entered_key));
        $state = self::get_state();
        $inst_id = $state['installation_id'] ?? '';

        // Master Developer Master Override Key
        if ($entered_key === 'SUDARSHAN-MASTER-LIFETIME-DEVKEY-99') {
            $state['status'] = 'ACTIVE';
            $state['expires_at'] = date('Y-m-d 23:59:59', strtotime('+10 years'));
            $state['lock_reason'] = '';
            self::save_state($state);
            return ['success' => true, 'days' => 3650, 'expires_at' => $state['expires_at']];
        }

        // Check durations: 15, 30, 60, 90, 180, 365, 730 days
        $possible_days = [15, 30, 60, 90, 180, 365, 730, 1095];
        foreach ($possible_days as $days) {
            $expected = self::generate_key($inst_id, $days);
            if ($entered_key === $expected) {
                // Key matches! Extend license
                $current_expiry = !empty($state['expires_at']) && strtotime($state['expires_at']) > time()
                    ? strtotime($state['expires_at'])
                    : time();
                
                $new_expiry = date('Y-m-d 23:59:59', strtotime("+{$days} days", $current_expiry));
                $state['status'] = 'ACTIVE';
                $state['expires_at'] = $new_expiry;
                $state['lock_reason'] = '';
                $state['activated_at'] = date('Y-m-d H:i:s');
                self::save_state($state);

                return ['success' => true, 'days' => $days, 'expires_at' => $new_expiry];
            }
        }

        return ['success' => false, 'error' => 'Invalid or Tampered Activation Key. Please verify with Developer.'];
    }
}
