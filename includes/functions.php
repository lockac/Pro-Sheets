<?php
if (!defined('ABSPATH')) exit;

// =========================================================
// HELPER FUNCTIONS (Keep these intact)
// =========================================================

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

// Helper: Convert column letter(s) to 0-based index (A=0, B=1, Z=26, AA=27)
if (!function_exists('prosheets_col_to_index')) {
    function prosheets_col_to_index($col) {
        $index = 0;
        $col = strtoupper(trim($col));
        for ($i = 0; $i < strlen($col); $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
}

// Helper: Parse A1 notation range to bounds
if (!function_exists('prosheets_parse_range')) {
    function prosheets_parse_range($range) {
        if (strpos($range, '!') !== false) {
            $range = explode('!', $range, 2)[1];
        }
        if (strpos($range, ':') === false) {
            $range = $range . ':' . $range;
        }
        list($start, $end) = explode(':', $range);
        preg_match('/([A-Z]+)(\d+)/i', $start, $s);
        preg_match('/([A-Z]+)(\d+)/i', $end, $e);
        return [
            'start_row' => (int)$s[2] - 1,
            'start_col' => prosheets_col_to_index($s[1]),
            'end_row' => (int)$e[2] - 1,
            'end_col' => prosheets_col_to_index($e[1])
        ];
    }
}

// =========================================================
// 🔄 MAIN DATA FETCH FUNCTION (3-Call Approach)
// Returns: values, merges, colors, column widths for range
// =========================================================
function get_prosheets_data($sheet_id, $range, $table_id = null) {
    $bypass = isset($_GET['prosheets_fresh']) && current_user_can('manage_options');
    
    // Pull cache time from admin settings
    $config = $table_id ? prosheets_get_table_config($table_id) : [];
    $cache_time = isset($config['cache_time']) ? intval($config['cache_time']) : 3600;
    
    $enc_key = get_option('prosheets_encrypted_api_key', '');
    if (empty($enc_key)) return ['error' => 'API Key not configured.'];
    $api_key = trim(prosheets_decrypt_key($enc_key));
    if (empty($api_key)) return ['error' => 'Invalid API Key.'];

    $cache_key = 'prosheets_unified_' . md5($sheet_id . $range);
    if (!$bypass) {
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;
    }

    $bounds = prosheets_parse_range($range);
    $expected_cols = ($bounds['end_col'] - $bounds['start_col']) + 1;
    $expected_rows = ($bounds['end_row'] - $bounds['start_row']) + 1;

    // =========================================================
    // CALL 1: CELL VALUES (spreadsheets.values.get) - ALWAYS WORKS
    // =========================================================
    $values_url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}/values/{$range}?key={$api_key}";
    $values_response = wp_remote_get($values_url, array('timeout' => 20, 'sslverify' => true));
    $values = [];
    
    if (!is_wp_error($values_response)) {
        $values_json = json_decode(wp_remote_retrieve_body($values_response), true);
        if (isset($values_json['values']) && is_array($values_json['values'])) {
            foreach ($values_json['values'] as $row) {
                $values[] = array_pad((array)$row, $expected_cols, '');
            }
        }
    }
    // Fill missing rows
    while (count($values) < $expected_rows) {
        $values[] = array_pad([], $expected_cols, '');
    }

    // =========================================================
    // CALL 2: MERGES (spreadsheets.get) - VERIFIED SLASH SYNTAX
    // =========================================================
    $merges_url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}?fields=sheets/merges&key={$api_key}";
    $merges_response = wp_remote_get($merges_url, array('timeout' => 20, 'sslverify' => true));
    $all_merges = [];
    
    if (!is_wp_error($merges_response)) {
        $merges_json = json_decode(wp_remote_retrieve_body($merges_response), true);
        if (isset($merges_json['sheets']) && is_array($merges_json['sheets'])) {
            foreach ($merges_json['sheets'] as $s) {
                if (isset($s['merges']) && is_array($s['merges'])) {
                    $all_merges = array_merge($all_merges, $s['merges']);
                }
            }
        }
    }
    
    // Filter merges to our range bounds
    $merges = [];
    foreach ($all_merges as $m) {
        $m_sr = (int)($m['startRowIndex'] ?? 0);
        $m_er = (int)($m['endRowIndex'] ?? 0); // exclusive
        $m_sc = (int)($m['startColumnIndex'] ?? 0);
        $m_ec = (int)($m['endColumnIndex'] ?? 0); // exclusive
        
        if ($m_sr <= $bounds['end_row'] && $m_er > $bounds['start_row'] &&
            $m_sc <= $bounds['end_col'] && $m_ec > $bounds['start_col']) {
            $merges[] = $m;
        }
    }

    // =========================================================
    // CALL 3: COLORS & COLUMN WIDTHS (spreadsheets.get + ranges=)
    // =========================================================
    $fmt_url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}?ranges=" . urlencode($range) . 
               "&fields=sheets/data/columnMetadata/pixelSize,sheets/data/rowData/values/effectiveFormat/backgroundColor&key={$api_key}";
    
    $fmt_response = wp_remote_get($fmt_url, array('timeout' => 20, 'sslverify' => true));
    $colors = [];
    $col_widths = [];
    $default_w = 100;
    
    if (!is_wp_error($fmt_response)) {
        $fmt_json = json_decode(wp_remote_retrieve_body($fmt_response), true);
        if (isset($fmt_json['sheets'][0]['data'][0])) {
            $fmt_data = $fmt_json['sheets'][0]['data'][0];
            
            // 1. Extract Colors
            if (isset($fmt_data['rowData'])) {
                foreach ($fmt_data['rowData'] as $row) {
                    $cells = isset($row['values']) ? $row['values'] : [];
                    $row_colors = [];
                    foreach ($cells as $cell) {
                        $bg = '';
                        if (isset($cell['effectiveFormat']['backgroundColor'])) {
                            $c = $cell['effectiveFormat']['backgroundColor'];
                            if (isset($c['red']) || isset($c['green']) || isset($c['blue'])) {
                                $r = intval(round(floatval($c['red'] ?? 0) * 255));
                                $g = intval(round(floatval($c['green'] ?? 0) * 255));
                                $b = intval(round(floatval($c['blue'] ?? 0) * 255));
                                $a = isset($c['alpha']) ? max(0.0, min(1.0, floatval($c['alpha']))) : 1.0;
                                $bg = ($a >= 1.0) ? sprintf('rgb(%d,%d,%d)', $r, $g, $b) : sprintf('rgba(%d,%d,%d,%.2f)', $r, $g, $b, $a);
                            }
                        }
                        $row_colors[] = $bg;
                    }
                    $colors[] = array_pad($row_colors, $expected_cols, '');
                }
            }
            
            // 2. Extract Widths - Ensure ALL columns get a width
            $extracted_widths = [];
            if (isset($fmt_data['columnMetadata'])) {
                foreach ($fmt_data['columnMetadata'] as $idx => $meta) {
                    $px = isset($meta['pixelSize']) ? intval($meta['pixelSize']) : $default_w;
                    $extracted_widths[$idx] = $px . 'px';
                }
            }
            
            // Fill in any missing column widths with default
            for ($i = 0; $i < $expected_cols; $i++) {
                if (!isset($extracted_widths[$i])) {
                    $extracted_widths[$i] = $default_w . 'px';
                }
            }
            
            // Only return exactly the number of columns we need
            $col_widths = array_values(array_slice($extracted_widths, 0, $expected_cols));
        }
    }
    
    // Pad colors array to match grid dimensions
    while (count($colors) < $expected_rows) {
        $colors[] = array_pad([], $expected_cols, '');
    }
    
    // ✅ STRICT FIX: Ensure col_widths exactly matches expected_cols (prevents phantom columns)
    $final_col_widths = [];
    for ($i = 0; $i < $expected_cols; $i++) {
        $final_col_widths[$i] = isset($col_widths[$i]) ? $col_widths[$i] : $default_w . 'px';
    }
    $col_widths = array_values($final_col_widths);
    
    // 🔍 DEBUG LOG: Verify counts match (check wp-content/debug.log)
    error_log("ProSheets Debug: ExpectedCols=$expected_cols | WidthsCount=" . count($col_widths) . " | ValuesCount=" . count($values));

    // =========================================================
    // RETURN COMBINED RESULT
    // =========================================================
    $result = [
        'values'       => $values,
        'merges'       => $merges,
        '_raw_merges'  => $all_merges,
        'colors'       => $colors,
        'col_widths'   => $col_widths,
        'range_bounds' => $bounds
    ];
    
    set_transient($cache_key, $result, $cache_time);
    return $result;
}

// =========================================================
// CONFIG HELPER: Merge defaults with table-specific overrides
// =========================================================
if (!function_exists('prosheets_get_table_config')) {
    function prosheets_get_table_config($table_id = null) {
        $defaults = get_option('prosheets_defaults', array());
        if (empty($table_id)) return $defaults;
        
        $tables = get_option('prosheets_tables', array());
        $overrides = isset($tables[$table_id]) ? $tables[$table_id] : array();
        
        $config = $defaults;
        foreach ($overrides as $key => $value) {
            if (strpos($key, '_en') !== false) {
                if (array_key_exists($key, $overrides)) $config[$key] = $value;
            } 
            elseif (strpos($key, '_rows') !== false || strpos($key, '_cols') !== false || strpos($key, '_thk') !== false || strpos($key, '_font') !== false || strpos($key, '_rad') !== false) {
                if ($value !== '' && $value !== null && is_numeric($value)) $config[$key] = $value;
            }
            else {
                if ($value !== '' && $value !== null && $value !== false) $config[$key] = $value;
            }
        }
        return $config;
    }
}