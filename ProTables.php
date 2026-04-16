<?php
/**
 * Plugin Name: Pro Tables
 * Description: Modular Google Sheets manager with Global Styling and Freeze Pane support.
 * Version: 4.9.2
 */

if (!defined('ABSPATH')) exit;

define('PT_PATH', plugin_dir_path(__FILE__));

/* ==========================================================================
   PROTECTED LOGIC - DO NOT MODIFY THESE BLOCKS
   ========================================================================== */

// 1. USER DEFINED STYLE
add_action('admin_head', function() {
    echo '<style>#toplevel_page_protable .wp-menu-image img { padding: 9px !important; }</style>';
});

// 2. DATA FETCH LOGIC (LOCKED)
function get_protable_data($sheet_id, $range) {
    $api_key = protable_decrypt_key(get_option('protable_encrypted_api_key', ''));
    if (!$api_key) return 'API Key missing.';

    $transient_key = 'protable_' . md5($sheet_id . $range);
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;

    // LOCKED URL LINE (DO NOT MODIFY)
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}/values/" . rawurlencode($range) . "?key={$api_key}";

    $response = wp_remote_get($url, array('timeout' => 15));
    if (is_wp_error($response)) return 'Connection Error.';

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['values'])) {
        set_transient($transient_key, $body['values'], 3 * MINUTE_IN_SECONDS);
        return $body['values'];
    }
    return 'Data not found.';
}

function protable_encrypt_key($plain_text) {
    if (empty($plain_text)) return '';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($plain_text, 'aes-256-cbc', AUTH_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function protable_decrypt_key($encrypted_text) {
    if (empty($encrypted_text)) return '';
    $data = base64_decode($encrypted_text);
    $iv_len = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $iv_len);
    $encrypted = substr($data, $iv_len);
    return openssl_decrypt($encrypted, 'aes-256-cbc', AUTH_KEY, 0, $iv);
}

function pt_v($array, $key, $default = '') {
    return (isset($array[$key]) && $array[$key] !== '') ? $array[$key] : $default;
}
/* ==========================================================================
   END PROTECTED LOGIC
   ========================================================================== */

// Load the modular components
require_once PT_PATH . 'includes/admin.php';
require_once PT_PATH . 'includes/Settings.php';
require_once PT_PATH . 'includes/Defaults-Settings.php'; // ADD THIS LINE
require_once PT_PATH . 'includes/shortcode.php';
