<?php
/**
 * Native Google Wallet Generic Pass Helper (RS256 JWT + Optional REST API Sync).
 * Produces https://pay.google.com/gp/v/save/<JWT> links without Composer dependencies.
 */

if (!function_exists('get_pass_hmac_secret')) {
    require_once __DIR__ . '/pass_helper.php';
}

if (!function_exists('gw_base64url_encode')) {
    function gw_base64url_encode($data) {
        return rtrim(strtr(base64_encode((string)$data), '+/', '-_'), '=');
    }
}

if (!function_exists('gw_normalize_iso_date')) {
    function gw_normalize_iso_date($raw_date, $fallback_iso = '') {
        $raw = trim((string)$raw_date);
        if ($raw === '' || $raw === '0000-00-00' || $raw === 'N/A') {
            return $fallback_iso !== '' ? $fallback_iso : date('Y-m-d');
        }
        $ts = strtotime($raw);
        if (!$ts) {
            return $fallback_iso !== '' ? $fallback_iso : date('Y-m-d');
        }
        return date('Y-m-d', $ts);
    }
}

if (!function_exists('get_google_wallet_config')) {
    function get_google_wallet_config($force_reload = false) {
        static $cached = null;
        if ($cached !== null && !$force_reload) {
            return $cached;
        }

        $cfg = [
            'issuer_id'    => trim((string)(getenv('GOOGLE_WALLET_ISSUER_ID') ?: '')),
            'class_suffix' => 'sudarshan_fitness_member_v1',
            'base_url'     => 'https://sudarshanfitness.de',
            'card_color'   => '#0f172a',
            'client_email' => '',
            'private_key'  => '',
            'configured'   => false,
            'error'        => 'Not configured yet.',
            'updated_at'   => '',
        ];

        $php_cfg_file = __DIR__ . '/google_wallet_config.php';
        $sa_json_path = __DIR__ . '/google_wallet_key.json';

        if (is_file($php_cfg_file)) {
            $php_arr = @include $php_cfg_file;
            if (is_array($php_arr)) {
                if (!empty($php_arr['issuer_id'])) $cfg['issuer_id'] = trim((string)$php_arr['issuer_id']);
                if (!empty($php_arr['class_suffix'])) $cfg['class_suffix'] = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)$php_arr['class_suffix']);
                if (!empty($php_arr['base_url'])) $cfg['base_url'] = rtrim(trim((string)$php_arr['base_url']), '/');
                if (!empty($php_arr['card_color'])) $cfg['card_color'] = trim((string)$php_arr['card_color']);
                if (!empty($php_arr['service_account_json']) && is_file((string)$php_arr['service_account_json'])) {
                    $sa_json_path = (string)$php_arr['service_account_json'];
                }
            }
        }

        $json_cfg_file = __DIR__ . '/google_wallet_config.json';
        if (is_file($json_cfg_file)) {
            $raw_cfg = @file_get_contents($json_cfg_file);
            $decoded = $raw_cfg ? json_decode($raw_cfg, true) : null;
            if (is_array($decoded)) {
                if (!empty($decoded['issuer_id'])) $cfg['issuer_id'] = trim((string)$decoded['issuer_id']);
                if (!empty($decoded['class_suffix'])) $cfg['class_suffix'] = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)$decoded['class_suffix']);
                if (!empty($decoded['base_url'])) $cfg['base_url'] = rtrim(trim((string)$decoded['base_url']), '/');
                if (!empty($decoded['card_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$decoded['card_color'])) {
                    $cfg['card_color'] = (string)$decoded['card_color'];
                }
                if (!empty($decoded['updated_at'])) $cfg['updated_at'] = (string)$decoded['updated_at'];
                if (!empty($decoded['sa_key_file']) && is_file((string)$decoded['sa_key_file'])) {
                    $sa_json_path = (string)$decoded['sa_key_file'];
                }
            }
        }

        $sa_data = null;
        $env_sa = getenv('GOOGLE_WALLET_SA_JSON');
        if (is_string($env_sa) && trim($env_sa) !== '') {
            $sa_data = json_decode(trim($env_sa), true);
        } elseif (is_file($sa_json_path)) {
            $sa_raw = @file_get_contents($sa_json_path);
            if ($sa_raw) {
                $sa_data = json_decode($sa_raw, true);
            }
        }

        if (is_array($sa_data)) {
            $cfg['client_email'] = trim((string)($sa_data['client_email'] ?? ''));
            $cfg['private_key']  = str_replace('\n', "\n", (string)($sa_data['private_key'] ?? ''));
        }

        if ($cfg['issuer_id'] === '' || !preg_match('/^\d{6,30}$/', $cfg['issuer_id'])) {
            $cfg['error'] = 'Valid numeric Google Wallet Issuer ID is required.';
        } elseif ($cfg['client_email'] === '' || !filter_var($cfg['client_email'], FILTER_VALIDATE_EMAIL)) {
            $cfg['error'] = 'Service Account JSON is missing a valid client_email.';
        } elseif ($cfg['private_key'] === '' || !function_exists('openssl_pkey_get_private')) {
            $cfg['error'] = 'Service Account private_key or PHP OpenSSL extension is unavailable.';
        } else {
            $pkey = @openssl_pkey_get_private($cfg['private_key']);
            if ($pkey === false) {
                $cfg['error'] = 'Service Account private_key could not be parsed by OpenSSL.';
            } else {
                if (PHP_VERSION_ID < 80000 && is_resource($pkey)) {
                    @openssl_free_key($pkey);
                }
                $cfg['configured'] = true;
                $cfg['error'] = '';
            }
        }

        $cached = $cfg;
        return $cached;
    }
}

if (!function_exists('reset_google_wallet_config_cache')) {
    function reset_google_wallet_config_cache() {
        // Reload config dynamically via fresh file reads when saving in setup UI.
        return true;
    }
}

if (!function_exists('is_google_wallet_configured')) {
    function is_google_wallet_configured() {
        $cfg = get_google_wallet_config();
        return !empty($cfg['configured']);
    }
}

if (!function_exists('save_google_wallet_config')) {
    function save_google_wallet_config($issuer_id, $sa_json_raw = '', $class_suffix = 'sudarshan_fitness_member_v1', $base_url = 'https://sudarshanfitness.de', $card_color = '#0f172a') {
        $issuer_id = trim((string)$issuer_id);
        if (!preg_match('/^\d{6,30}$/', $issuer_id)) {
            return ['ok' => false, 'message' => 'Issuer ID must be numeric (from pay.google.com/business/console).'];
        }

        $class_suffix = preg_replace('/[^a-zA-Z0-9._-]/', '_', trim((string)$class_suffix));
        if ($class_suffix === '') {
            $class_suffix = 'sudarshan_fitness_member_v1';
        }

        $base_url = rtrim(trim((string)$base_url), '/');
        if ($base_url === '' || !preg_match('#^https?://#i', $base_url)) {
            $base_url = 'https://sudarshanfitness.de';
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$card_color)) {
            $card_color = '#0f172a';
        }

        $sa_json_path = __DIR__ . '/google_wallet_key.json';
        $sa_raw = trim((string)$sa_json_raw);

        if ($sa_raw !== '') {
            $decoded = json_decode($sa_raw, true);
            if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                return ['ok' => false, 'message' => 'Invalid Service Account JSON file: client_email and private_key are required.'];
            }
            $pkey_str = str_replace('\n', "\n", (string)$decoded['private_key']);
            $pkey = @openssl_pkey_get_private($pkey_str);
            if ($pkey === false) {
                return ['ok' => false, 'message' => 'Service Account private_key failed OpenSSL validation.'];
            }
            if (PHP_VERSION_ID < 80000 && is_resource($pkey)) {
                @openssl_free_key($pkey);
            }
            $written = @file_put_contents($sa_json_path, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
            if ($written === false) {
                return ['ok' => false, 'message' => 'Could not write google_wallet_key.json. Check Files/include permissions.'];
            }
            @chmod($sa_json_path, 0600);
        } elseif (!is_file($sa_json_path)) {
            return ['ok' => false, 'message' => 'Please upload or paste your Google Cloud Service Account JSON key.'];
        }

        $cfg_payload = [
            'issuer_id'    => $issuer_id,
            'class_suffix' => $class_suffix,
            'base_url'     => $base_url,
            'card_color'   => $card_color,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        $cfg_path = __DIR__ . '/google_wallet_config.json';
        $written_cfg = @file_put_contents($cfg_path, json_encode($cfg_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if ($written_cfg === false) {
            return ['ok' => false, 'message' => 'Could not write google_wallet_config.json. Check Files/include permissions.'];
        }
        @chmod($cfg_path, 0600);
        get_google_wallet_config(true);

        return ['ok' => true, 'message' => 'Google Wallet credentials saved and verified with OpenSSL!'];
    }
}

if (!function_exists('generate_google_wallet_barcode_token')) {
    function generate_google_wallet_barcode_token($con, $uid) {
        $uid_str = trim((string)$uid);
        $sig = substr(hash_hmac('sha256', "GW_QR|{$uid_str}", get_pass_hmac_secret($con)), 0, 16);
        return "SFG1:{$uid_str}:{$sig}";
    }
}

if (!function_exists('gw_resolve_public_base_url')) {
    function gw_resolve_public_base_url($config = null) {
        if (!is_array($config)) {
            $config = get_google_wallet_config();
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '' && stripos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false) {
            // Google Wallet requires public HTTPS endpoints for pass images and origins.
            return 'https://' . $host;
        }
        return !empty($config['base_url']) ? rtrim((string)$config['base_url'], '/') : 'https://sudarshanfitness.de';
    }
}

if (!function_exists('gw_build_app_prefix_path')) {
    function gw_build_app_prefix_path() {
        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $pos = strpos($script, '/Files/');
        if ($pos !== false) {
            return substr($script, 0, $pos) . '/Files';
        }
        return '/Files';
    }
}

if (!function_exists('build_signed_member_photo_url')) {
    function build_signed_member_photo_url($con, $uid, $variant = 'portrait', $config = null) {
        $base = gw_resolve_public_base_url($config);
        $prefix = gw_build_app_prefix_path();
        $sig = sign_pass_url_signature($con, (string)$uid, 0);
        return $base . $prefix . '/api/pass_photo.php?uid=' . urlencode((string)$uid)
            . '&variant=' . urlencode((string)$variant)
            . '&sig=' . urlencode($sig);
    }
}

if (!function_exists('fetch_google_wallet_member_payload_data')) {
    function fetch_google_wallet_member_payload_data($con, $uid_raw, $overrides = []) {
        $uid = trim((string)$uid_raw);
        if ($uid === '') {
            return null;
        }
        $uid_esc = mysqli_real_escape_string($con, $uid);
        $q_u = mysqli_query($con, "SELECT * FROM users WHERE userid = '$uid_esc' LIMIT 1");
        if (!$q_u || mysqli_num_rows($q_u) === 0) {
            return null;
        }
        $u = mysqli_fetch_assoc($q_u);

        $plan_name = trim((string)($overrides['plan_name'] ?? ''));
        $expire_raw = trim((string)($overrides['expire_date'] ?? ''));
        $start_raw = trim((string)($overrides['start_date'] ?? ''));

        $q_en = mysqli_query(
            $con,
            "SELECT e.expire, e.paid_date, e.start_date, p.planName
             FROM enrolls_to e
             LEFT JOIN plan p ON e.pid = p.pid
             WHERE e.uid = '$uid_esc'
             ORDER BY (e.renewal = 'yes') DESC, e.et_id DESC
             LIMIT 1"
        );
        if ($q_en && $en = mysqli_fetch_assoc($q_en)) {
            if ($plan_name === '' && !empty($en['planName'])) $plan_name = trim((string)$en['planName']);
            if ($expire_raw === '' && !empty($en['expire'])) $expire_raw = trim((string)$en['expire']);
            if ($start_raw === '' && !empty($en['start_date']) && $en['start_date'] !== '0000-00-00') {
                $start_raw = trim((string)$en['start_date']);
            }
            if ($start_raw === '' && !empty($en['paid_date']) && $en['paid_date'] !== '0000-00-00') {
                $start_raw = trim((string)$en['paid_date']);
            }
        }

        if ($start_raw === '' && !empty($u['joining_date']) && $u['joining_date'] !== '0000-00-00') {
            $start_raw = trim((string)$u['joining_date']);
        }

        $today_iso = date('Y-m-d');
        $start_iso = gw_normalize_iso_date($start_raw, $today_iso);
        $expire_iso = gw_normalize_iso_date($expire_raw, $today_iso);
        if (strtotime($expire_iso) < strtotime($start_iso)) {
            $start_iso = $today_iso;
        }

        $is_active = ($expire_iso >= $today_iso);
        $gym = function_exists('get_gym_details') ? get_gym_details($con) : ['gym_name' => 'Sudarshan Fitness Khamgaon', 'gym_logo' => ''];
        $cfg = get_google_wallet_config();
        $base = gw_resolve_public_base_url($cfg);
        $prefix = gw_build_app_prefix_path();

        $gym_logo = trim((string)($gym['gym_logo'] ?? ''));
        if ($gym_logo === '') {
            $gym_logo_url = $base . $prefix . '/images/gym_logo.png';
        } elseif (preg_match('#^https?://#i', $gym_logo)) {
            $gym_logo_url = $gym_logo;
        } else {
            $gym_logo_url = $base . $prefix . '/' . ltrim($gym_logo, './');
        }

        $mobile_raw = trim((string)($u['mobile'] ?? ''));
        $mobile_display = $mobile_raw !== '' ? $mobile_raw : 'Not Provided';

        $smart_rel = build_signed_wallet_pass_url($con, $u['userid'], 0, true, 'android');
        if (strpos($smart_rel, '/Files/') === 0) {
            $smart_pass_url = $base . $smart_rel;
        } elseif (preg_match('#^https?://#i', $smart_rel)) {
            $smart_pass_url = $smart_rel;
        } else {
            $smart_pass_url = $base . $prefix . '/' . ltrim($smart_rel, './');
        }

        return [
            'uid'              => (string)$u['userid'],
            'username'         => trim((string)($u['username'] ?? 'Member')),
            'mobile'           => $mobile_display,
            'email'            => trim((string)($u['email'] ?? '')),
            'plan_name'        => $plan_name !== '' ? $plan_name : 'Gym Membership',
            'start_iso'        => $start_iso,
            'start_fmt'        => date('d M Y', strtotime($start_iso)),
            'expire_iso'       => $expire_iso,
            'expire_fmt'       => date('d M Y', strtotime($expire_iso)),
            'is_active'        => $is_active,
            'status_label'     => $is_active ? 'ACTIVE' : 'EXPIRED',
            'gym_name'         => trim((string)($gym['gym_name'] ?? 'Sudarshan Fitness Khamgaon')),
            'gym_logo_url'     => $gym_logo_url,
            'member_photo_url' => build_signed_member_photo_url($con, $u['userid'], 'portrait', $cfg),
            'member_hero_url'  => build_signed_member_photo_url($con, $u['userid'], 'hero', $cfg),
            'barcode_value'    => generate_google_wallet_barcode_token($con, $u['userid']),
            'smart_pass_url'   => $smart_pass_url,
        ];
    }
}

if (!function_exists('build_google_wallet_pass_structures')) {
    function build_google_wallet_pass_structures($con, $uid, $config = null, $overrides = []) {
        if (!is_array($config)) {
            $config = get_google_wallet_config();
        }
        if (empty($config['configured'])) {
            return null;
        }

        $m = fetch_google_wallet_member_payload_data($con, $uid, $overrides);
        if (!$m) {
            return null;
        }

        $issuer_id = $config['issuer_id'];
        $class_id = $issuer_id . '.' . $config['class_suffix'];
        $obj_suffix = 'member_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $m['uid']);
        $object_id = $issuer_id . '.' . $obj_suffix;

        $generic_class = [
            'id' => $class_id,
            'classTemplateInfo' => [
                'cardTemplateOverride' => [
                    'cardRowTemplateInfos' => [
                        [
                            'twoItems' => [
                                'startItem' => [
                                    'firstValue' => [
                                        'fields' => [['fieldPath' => "object.textModulesData['start_date']"]]
                                    ]
                                ],
                                'endItem' => [
                                    'firstValue' => [
                                        'fields' => [['fieldPath' => "object.textModulesData['expiry_date']"]]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'twoItems' => [
                                'startItem' => [
                                    'firstValue' => [
                                        'fields' => [['fieldPath' => "object.textModulesData['member_id']"]]
                                    ]
                                ],
                                'endItem' => [
                                    'firstValue' => [
                                        'fields' => [['fieldPath' => "object.textModulesData['mobile_no']"]]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'oneItem' => [
                                'item' => [
                                    'firstValue' => [
                                        'fields' => [['fieldPath' => "object.textModulesData['plan_name']"]]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $generic_object = [
            'id' => $object_id,
            'classId' => $class_id,
            'state' => $m['is_active'] ? 'ACTIVE' : 'EXPIRED',
            'hexBackgroundColor' => $m['is_active'] ? $config['card_color'] : '#7f1d1d',
            'logo' => [
                'sourceUri' => ['uri' => $m['gym_logo_url']],
                'contentDescription' => [
                    'defaultValue' => ['language' => 'en-US', 'value' => $m['gym_name']]
                ]
            ],
            'cardTitle' => [
                'defaultValue' => ['language' => 'en-US', 'value' => $m['gym_name']]
            ],
            'subheader' => [
                'defaultValue' => ['language' => 'en-US', 'value' => $m['plan_name'] . ' • #' . $m['uid']]
            ],
            'header' => [
                'defaultValue' => ['language' => 'en-US', 'value' => $m['username']]
            ],
            'heroImage' => [
                'sourceUri' => ['uri' => $m['member_hero_url']],
                'contentDescription' => [
                    'defaultValue' => ['language' => 'en-US', 'value' => $m['username'] . ' Member Photo']
                ]
            ],
            'validTimeInterval' => [
                'start' => ['date' => $m['start_iso'] . 'T00:00:00+05:30'],
                'end'   => ['date' => $m['expire_iso'] . 'T23:59:59+05:30'],
            ],
            'barcode' => [
                'type' => 'QR_CODE',
                'value' => $m['barcode_value'],
                'alternateText' => 'ID #' . $m['uid']
            ],
            'textModulesData' => [
                ['id' => 'member_id',   'header' => 'Member ID',   'body' => '#' . $m['uid']],
                ['id' => 'mobile_no',   'header' => 'Mobile',      'body' => $m['mobile']],
                ['id' => 'start_date',  'header' => 'Start Date',  'body' => $m['start_fmt']],
                ['id' => 'expiry_date', 'header' => 'Expiry Date', 'body' => $m['expire_fmt']],
                ['id' => 'plan_name',   'header' => 'Plan',        'body' => $m['plan_name'] . ' (' . $m['status_label'] . ')'],
            ],
            'imageModulesData' => [
                [
                    'id' => 'member_photo_full',
                    'mainImage' => [
                        'sourceUri' => ['uri' => $m['member_photo_url']],
                        'contentDescription' => [
                            'defaultValue' => ['language' => 'en-US', 'value' => $m['username'] . ' Photo']
                        ]
                    ]
                ]
            ],
            'linksModuleData' => [
                'uris' => [
                    [
                        'uri' => $m['smart_pass_url'],
                        'description' => 'Open Live Member Pass Card',
                        'id' => 'live_smart_pass'
                    ]
                ]
            ]
        ];

        return [
            'member'        => $m,
            'class_id'      => $class_id,
            'object_id'     => $object_id,
            'genericClass'  => $generic_class,
            'genericObject' => $generic_object,
        ];
    }
}

if (!function_exists('gw_fetch_oauth_access_token')) {
    function gw_fetch_oauth_access_token($config, $timeout_sec = 3) {
        if (!function_exists('curl_init') || empty($config['configured'])) {
            return null;
        }
        $now = time();
        $header = gw_base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = gw_base64url_encode(json_encode([
            'iss'   => $config['client_email'],
            'scope' => 'https://www.googleapis.com/auth/wallet_object.issuer',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));
        $signing_input = $header . '.' . $claims;
        $signature = '';
        $pkey = @openssl_pkey_get_private($config['private_key']);
        if (!$pkey || !@openssl_sign($signing_input, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
            return null;
        }
        if (PHP_VERSION_ID < 80000 && is_resource($pkey)) {
            @openssl_free_key($pkey);
        }

        $assertion = $signing_input . '.' . gw_base64url_encode($signature);
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout_sec,
            CURLOPT_CONNECTTIMEOUT => $timeout_sec,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $assertion,
            ]),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !$resp) {
            return null;
        }
        $json = json_decode($resp, true);
        return $json['access_token'] ?? null;
    }
}

if (!function_exists('gw_rest_json_request')) {
    function gw_rest_json_request($method, $url, $access_token, $body, $timeout_sec = 3) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout_sec,
            CURLOPT_CONNECTTIMEOUT => $timeout_sec,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => (int)$code, 'body' => $resp];
    }
}

if (!function_exists('sync_google_wallet_pass_on_server')) {
    function sync_google_wallet_pass_on_server($con, $uid, $overrides = [], $timeout_sec = 3) {
        $cfg = get_google_wallet_config();
        $structs = build_google_wallet_pass_structures($con, $uid, $cfg, $overrides);
        if (!$structs) {
            return false;
        }
        $token = gw_fetch_oauth_access_token($cfg, $timeout_sec);
        if (!$token) {
            return false;
        }

        $cls_id = rawurlencode($structs['class_id']);
        $obj_id = rawurlencode($structs['object_id']);

        // Best-effort upsert class & patch existing object (when already saved by member, updates in-place on phone)
        $cls_res = gw_rest_json_request('PUT', "https://walletobjects.googleapis.com/walletobjects/v1/genericClass/{$cls_id}", $token, $structs['genericClass'], $timeout_sec);
        if ($cls_res['code'] === 404) {
            gw_rest_json_request('POST', "https://walletobjects.googleapis.com/walletobjects/v1/genericClass", $token, $structs['genericClass'], $timeout_sec);
        }

        $patch_res = gw_rest_json_request('PATCH', "https://walletobjects.googleapis.com/walletobjects/v1/genericObject/{$obj_id}", $token, $structs['genericObject'], $timeout_sec);
        return ($patch_res['code'] >= 200 && $patch_res['code'] < 300);
    }
}

if (!function_exists('build_google_wallet_save_url')) {
    function build_google_wallet_save_url($con, $uid, $overrides = [], $sync_existing = false) {
        $cfg = get_google_wallet_config();
        $structs = build_google_wallet_pass_structures($con, $uid, $cfg, $overrides);
        if (!$structs) {
            return null;
        }

        if ($sync_existing) {
            @sync_google_wallet_pass_on_server($con, $uid, $overrides, 3);
        }

        $base = gw_resolve_public_base_url($cfg);
        $jwt_header = gw_base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $jwt_payload = gw_base64url_encode(json_encode([
            'iss'     => $cfg['client_email'],
            'aud'     => 'google',
            'typ'     => 'savetowallet',
            'iat'     => time(),
            'origins' => [$base],
            'payload' => [
                'genericClasses'  => [$structs['genericClass']],
                'genericObjects'  => [$structs['genericObject']],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $signing_input = $jwt_header . '.' . $jwt_payload;
        $signature = '';
        $pkey = @openssl_pkey_get_private($cfg['private_key']);
        if (!$pkey || !@openssl_sign($signing_input, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
            return null;
        }
        if (PHP_VERSION_ID < 80000 && is_resource($pkey)) {
            @openssl_free_key($pkey);
        }

        return 'https://pay.google.com/gp/v/save/' . $signing_input . '.' . gw_base64url_encode($signature);
    }
}

if (!function_exists('build_google_wallet_email_block')) {
    function build_google_wallet_email_block($con, $uid, $overrides = [], $sync_existing = true) {
        if (!is_google_wallet_configured()) {
            return '';
        }
        $structs = build_google_wallet_pass_structures($con, $uid, null, $overrides);
        if (!$structs) {
            return '';
        }
        $save_url = build_google_wallet_save_url($con, $uid, $overrides, $sync_existing);
        if (!$save_url) {
            return '';
        }

        $m = $structs['member'];
        $save_href = htmlspecialchars($save_url, ENT_QUOTES, 'UTF-8');
        $name_esc = htmlspecialchars($m['username'], ENT_QUOTES, 'UTF-8');
        $uid_esc = htmlspecialchars($m['uid'], ENT_QUOTES, 'UTF-8');
        $mob_esc = htmlspecialchars($m['mobile'], ENT_QUOTES, 'UTF-8');
        $start_esc = htmlspecialchars($m['start_fmt'], ENT_QUOTES, 'UTF-8');
        $exp_esc = htmlspecialchars($m['expire_fmt'], ENT_QUOTES, 'UTF-8');
        $plan_esc = htmlspecialchars($m['plan_name'], ENT_QUOTES, 'UTF-8');
        $photo_src = htmlspecialchars($m['member_photo_url'], ENT_QUOTES, 'UTF-8');

        return "
        <div style='background: linear-gradient(135deg, #0b1220 0%, #162032 100%); border: 2px solid #4285F4; border-radius: 16px; padding: 22px; margin: 24px 0; text-align: center; box-shadow: 0 10px 28px rgba(66, 133, 244, 0.18);'>
            <div style='display: inline-block; background: rgba(66,133,244,0.14); color: #93c5fd; font-size: 11px; font-weight: 800; padding: 5px 14px; border-radius: 999px; letter-spacing: 1px; text-transform: uppercase; border: 1px solid rgba(66,133,244,0.4); margin-bottom: 12px;'>
                GOOGLE WALLET DIGITAL MEMBERSHIP CARD
            </div>
            <div style='margin: 6px 0 12px 0;'>
                <img src='{$photo_src}' alt='{$name_esc}' style='width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #34A853; background: #0f172a;'>
            </div>
            <div style='color: #ffffff; font-size: 17px; font-weight: 800; margin-bottom: 4px;'>{$name_esc} (#{$uid_esc})</div>
            <div style='color: #cbd5e1; font-size: 12.5px; margin-bottom: 14px;'>
                Plan: <strong style='color:#ffffff;'>{$plan_esc}</strong> &bull; Mobile: <strong style='color:#ffffff;'>{$mob_esc}</strong><br>
                Start Date: <strong style='color:#38bdf8;'>{$start_esc}</strong> &bull; Expiry Date: <strong style='color:#34d399;'>{$exp_esc}</strong>
            </div>
            <a href='{$save_href}' target='_blank' style='display: inline-block; background: #020617; color: #ffffff; text-decoration: none; padding: 13px 26px; border-radius: 999px; font-weight: 800; font-size: 14px; border: 1.5px solid #e2e8f0; letter-spacing: 0.3px; box-shadow: 0 6px 18px rgba(0,0,0,0.45);'>
                <span style='color:#4285F4;'>G</span><span style='color:#EA4335;'>o</span><span style='color:#FBBC05;'>o</span><span style='color:#4285F4;'>g</span><span style='color:#34A853;'>l</span><span style='color:#EA4335;'>e</span>&nbsp; Wallet &mdash; Add Pass
            </a>
            <p style='color: #94a3b8; font-size: 11px; margin: 12px 0 0 0; line-height: 1.5;'>
                Make sure Google Wallet is installed on your phone, then tap above to save your official gym card (photo, ID, mobile, start &amp; expiry dates + gate QR).
            </p>
        </div>";
    }
}
