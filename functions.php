<?php
if (!defined('ABSPATH')) exit;

// Safe value getter
if (!function_exists('ps_v')) {
    function ps_v($array, $key, $default = '') {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}

// Strict comparison helper
if (!function_exists('prosheets_values_match')) {
    function prosheets_values_match($a, $b) {
        if ($a === '' || $a === null) $a = '';
        if ($b === '' || $b === null) $b = '';
        return (string)$a === (string)$b;
    }
}

// API Key Encryption/Decryption
if (!function_exists('prosheets_encrypt_key')) {
    function prosheets_encrypt_key($key) {
        if (empty($key)) return '';
        return base64_encode($key);
    }
}

if (!function_exists('prosheets_decrypt_key')) {
    function prosheets_decrypt_key($encrypted) {
        if (empty($encrypted)) return '';
        $decoded = base64_decode($encrypted, true);
        return ($decoded === false) ? '' : $decoded;
    }
}

// Merge defaults with table-specific overrides (Fixed for #3)
if (!function_exists('prosheets_get_table_config')) {
    function prosheets_get_table_config($table_id = null) {
        $defaults = get_option('prosheets_defaults', array());
        if (empty($table_id)) return $defaults;
        
        $tables = get_option('prosheets_tables', array());
        $overrides = isset($tables[$table_id]) ? $tables[$table_id] : array();
        
        // Start with defaults as the absolute baseline
        $config = $defaults;
        
        // Only apply overrides if they are explicitly set and not empty
        foreach ($overrides as $key => $value) {
            if ($value !== '' && $value !== null && $value !== false) {
                $config[$key] = $value;
            }
        }
        
        return $config;
    }
}

// Google Sheets API Fetch
if (!function_exists('get_prosheets_data')) {
    function get_prosheets_data($sheet_id, $range, $cache_time = 3600) {
        $enc_key = get_option('prosheets_encrypted_api_key', '');
        if (empty($enc_key)) return 'Error: API Key not configured in Pro Sheets Settings.';

        $api_key = trim(prosheets_decrypt_key($enc_key));
        if (empty($api_key)) return 'Error: Could not decrypt API Key. Please re-save your key in API Settings.';

        $cache_key = 'prosheets_' . md5($sheet_id . $range);
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $url = esc_url_raw("https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}/values/{$range}?key={$api_key}");
        $response = wp_remote_get($url, array('timeout' => 30, 'sslverify' => true));

        if (is_wp_error($response)) return 'Connection Error: ' . $response->get_error_message();

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (isset($json['error'])) return 'API Error: ' . $json['error']['message'];
        if (!isset($json['values'])) return array();

        set_transient($cache_key, $json['values'], $cache_time);
        return $json['values'];
    }
}