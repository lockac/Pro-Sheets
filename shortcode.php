<?php
if (!defined('ABSPATH')) exit;

function ps_font_style($c, $prefix) {
    $css = '';
    if (!empty($c[$prefix.'_bold'])) $css .= 'font-weight:bold;'; else $css .= 'font-weight:normal;';
    if (!empty($c[$prefix.'_italic'])) $css .= 'font-style:italic;';
    if (!empty($c[$prefix.'_underline'])) $css .= 'text-decoration:underline;';
    return $css;
}

function ps_text_case($c, $prefix) {
    $case = isset($c[$prefix.'_case']) ? $c[$prefix.'_case'] : '';
    if ($case === 'lower') return 'text-transform:lowercase;';
    if ($case === 'upper') return 'text-transform:uppercase;';
    if ($case === 'proper') return 'text-transform:capitalize;';
    return '';
}

add_shortcode('prosheets', 'prosheets_shortcode');
function prosheets_shortcode($atts) {
    $a = shortcode_atts(array('id' => '', 'height' => '500'), $atts);
    $tables = get_option('prosheets_tables', array());
    if (!isset($tables[$a['id']])) return '';
    
    $c = prosheets_get_table_config($a['id']);
    
    // 1. Fix $h_count calculation (bypasses ps_v() type juggling)
    $h_en = isset($c['h_en']) && ($c['h_en'] == 1 || $c['h_en'] === '1' || $c['h_en'] === true);
    $h_count = $h_en ? (int)($c['h_rows'] ?? 1) : 0;
    
    $f_count = isset($c['f_en']) && $c['f_en'] ? (int)($c['f_rows'] ?? 0) : 0;
    $l_count = isset($c['l_en']) && $c['l_en'] ? (int)($c['l_cols'] ?? 0) : 0;
    $r_count = isset($c['r_en']) && $c['r_en'] ? (int)($c['r_cols'] ?? 0) : 0;
    
    // 2. Convert admin "Refresh Rate" to seconds
    $cache_value = isset($c['cache_time']) ? intval($c['cache_time']) : 1;
    $cache_unit = isset($c['cache_unit']) ? $c['cache_unit'] : 'hours';
    switch ($cache_unit) {
        case 'minutes': $cache_time = $cache_value * 60; break;
        case 'hours':   $cache_time = $cache_value * 3600; break;
        case 'days':    $cache_time = $cache_value * 86400; break;
        default:        $cache_time = 3600;
    }
    
    // 3. Admin testing bypass
    if (isset($_GET['prosheets_fresh']) && current_user_can('manage_options')) {
        delete_transient('prosheets_val_' . md5($c['sheet_id'] . $c['range']));
        delete_transient('prosheets_fmt_' . md5($c['sheet_id'] . $c['range']));
    }
    
    // 4. Fetch API data
    $api_response = get_prosheets_data($c['sheet_id'], $c['range'], $cache_time);
    if (isset($api_response['error'])) return '<p style="color:#dc3232; padding:10px; background:#ffe0e0; border:1px solid #dc3232;">' . esc_html($api_response['error']) . '</p>';
    
    // 5. Safe extraction
    $data       = $api_response['values'] ?? [];
    $raw_merges = $api_response['_raw_merges'] ?? []; // NEW: Raw API merges before filtering
    $merges     = $api_response['merges'] ?? [];
    $row_colors = $api_response['colors'] ?? [];
    $bounds     = $api_response['range_bounds'] ?? [];
    $col_widths = $api_response['col_widths'] ?? [];
    
    if (empty($data)) return '';

    // 🔍 ENHANCED DIAGNOSTIC (Admin only)
    if (isset($_GET['prosheets_debug_merge']) && current_user_can('manage_options')) {
        echo '<div style="background:#ffdd00;border:3px solid #c00;padding:12px;margin:15px 0;font-family:monospace;font-size:12px;line-height:1.4;">';
        echo '<strong>🔍 Merge Diagnostic</strong><br><br>';
        echo '1. Settings: h_en=' . var_export($h_en, true) . ' | h_rows=' . $c['h_rows'] . ' | $h_count=' . $h_count . '<br>';
        echo '2. Range Bounds: ' . json_encode($bounds) . '<br>';
        echo '3. RAW API Merges: ' . count($raw_merges) . (empty($raw_merges) ? ' ⚠️ Google returned empty array' : '') . '<br>';
        if (!empty($raw_merges)) echo '&nbsp;&nbsp;→ First raw merge: ' . json_encode($raw_merges[0]) . '<br>';
        echo '4. FILTERED Merges: ' . count($merges) . '<br>';
        echo '5. Parser will run? ' . ($h_count > 0 && !empty($merges) ? '✅ YES' : '❌ NO (h_count=' . $h_count . ', merges=' . count($merges) . ')') . '<br>';
        echo '</div>';
    }
    
    if (empty($data)) return '';
    
    
    $h_count = ps_v($c, 'h_en') ? intval(ps_v($c, 'h_rows', 1)) : 0;
    $f_count = ps_v($c, 'f_en') ? intval(ps_v($c, 'f_rows', 0)) : 0;
    $l_count = ps_v($c, 'l_en') ? intval(ps_v($c, 'l_cols', 0)) : 0;
    $r_count = ps_v($c, 'r_en') ? intval(ps_v($c, 'r_cols', 0)) : 0;
    $total = count($data);
    
    $border_style = ps_v($c,'t_b_en') ? ps_v($c,'t_b_thk',0).'px solid '.ps_v($c,'t_b_clr','#ddd') : 'none';
    $margin_bottom = (!empty($c['t_b_pad_b']) && trim($c['t_b_pad_b']) !== '') ? $c['t_b_pad_b'] : '0';
    $br = ps_v($c,'t_b_rad',0).'px';

    // 1. Base CSS
    $out = "<style>
    .ps-c-{$a['id']} { width: 100%; overflow: hidden; border: {$border_style}; border-radius: {$br}; box-shadow: ".ps_v($c,'t_b_shd','none')."; background: ".ps_v($c,'g_bg','#fff')."; margin-bottom: {$margin_bottom}; position: relative; }
    .ps-c-{$a['id']}-scroll { width: 100%; max-height: {$a['height']}px; overflow: auto; -webkit-overflow-scrolling: touch; border-radius: {$br}; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar { width: 12px; height: 12px; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-track { background: ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 6px; border: 3px solid ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-thumb:hover { background: #555; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-corner { background: ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll { scrollbar-width: thin; scrollbar-color: #888 ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: auto; font-family: sans-serif; font-size: ".ps_v($c,'g_font',14)."px; }
    .ps-c-{$a['id']}-scroll th, .ps-c-{$a['id']}-scroll td { padding: 10px; border: ".ps_v($c,'g_b_thk',1)."px solid ".ps_v($c,'g_b_clr','#ddd')."; white-space: pre-wrap; word-wrap: break-word; vertical-align: ".ps_v($c,'g_valign','top')."; color: ".ps_v($c,'g_txt','#333')."; text-align: ".ps_v($c,'g_align','left')."; ".ps_font_style($c, 'g')." }
    .ps-c-{$a['id']}-scroll tbody td { background: ".ps_v($c,'b_bg','#fff')."; font-size: ".ps_v($c,'b_font',14)."px; color: ".ps_v($c,'b_txt','#333')."; text-align: ".ps_v($c,'b_align','left')."; vertical-align: ".ps_v($c,'b_valign','top')."; border: ".ps_v($c,'b_b_thk',1)."px solid ".ps_v($c,'b_b_clr','#ddd')."; ".ps_font_style($c, 'b')." }";

    // 2. Sticky Panes CSS
    if ($h_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(n) { position: sticky; top: 0; z-index: 20; background: ".ps_v($c,'h_bg','#f9f9f9')." !important; color: ".ps_v($c,'h_txt','#333')." !important; text-align: ".ps_v($c,'h_align','center')." !important; vertical-align: ".ps_v($c,'h_valign','top')." !important; border: ".ps_v($c,'h_b_thk',1)."px solid ".ps_v($c,'h_b_clr','#ddd')." !important; font-size: ".ps_v($c,'h_font',14)."px !important; ".ps_font_style($c, 'h')." ".ps_text_case($c, 'h')." }";
    if ($f_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-child(n) { position: sticky; bottom: 0; z-index: 20; background: ".ps_v($c,'f_bg','#f9f9f9')." !important; color: ".ps_v($c,'f_txt','#333')." !important; text-align: ".ps_v($c,'f_align','center')." !important; vertical-align: ".ps_v($c,'f_valign','top')." !important; border: ".ps_v($c,'f_b_thk',1)."px solid ".ps_v($c,'f_b_clr','#ddd')." !important; font-size: ".ps_v($c,'f_font',14)."px !important; ".ps_font_style($c, 'f')." ".ps_text_case($c, 'f')." }";
    if ($l_count > 0) {
        $out .= ".ps-c-{$a['id']}-scroll tr *:nth-child(-n+{$l_count}) { position: sticky; left: 0; z-index: 10; background: ".ps_v($c,'l_bg','#f9f9f9')." !important; color: ".ps_v($c,'l_txt','#333')." !important; text-align: ".ps_v($c,'l_align','left')." !important; vertical-align: ".ps_v($c,'l_valign','top')." !important; border: ".ps_v($c,'l_b_thk',1)."px solid ".ps_v($c,'l_b_clr','#ddd')." !important; font-size: ".ps_v($c,'l_font',14)."px !important; ".ps_font_style($c, 'l')." ".ps_text_case($c, 'l')." }";
        $out .= ".ps-c-{$a['id']}-scroll tr *:nth-child(1) { width: 1%; white-space: nowrap; }";
    }    
    if ($r_count > 0) $out .= ".ps-c-{$a['id']}-scroll tr *:nth-last-child(-n+{$r_count}) { position: sticky; right: 0; z-index: 10; background: ".ps_v($c,'r_bg','#f9f9f9')." !important; color: ".ps_v($c,'r_txt','#333')." !important; text-align: ".ps_v($c,'r_align','left')." !important; vertical-align: ".ps_v($c,'r_valign','top')." !important; border: ".ps_v($c,'r_b_thk',1)."px solid ".ps_v($c,'r_b_clr','#ddd')." !important; font-size: ".ps_v($c,'r_font',14)."px !important; ".ps_font_style($c, 'r')." ".ps_text_case($c, 'r')." }";
    if ($h_count > 0 && $l_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(-n+{$l_count}) { z-index: 30 !important; }";
    if ($h_count > 0 && $r_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-last-child(-n+{$r_count}) { z-index: 30 !important; }";
    if ($f_count > 0 && $l_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-child(-n+{$l_count}) { z-index: 30 !important; }";
    if ($f_count > 0 && $r_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-last-child(-n+{$r_count}) { z-index: 30 !important; }";
    
    if ($l_count > 0 && !empty($c['l_align_to_col'])) {
        $l_align = ps_v($c, 'l_align', 'left');
        $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(-n+{$l_count}), .ps-c-{$a['id']}-scroll tfoot tr *:nth-child(-n+{$l_count}) { text-align: {$l_align} !important; }";
    }
    if ($r_count > 0 && !empty($c['r_align_to_col'])) {
        $r_align = ps_v($c, 'r_align', 'left');
        $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-last-child(-n+{$r_count}), .ps-c-{$a['id']}-scroll tfoot tr *:nth-last-child(-n+{$r_count}) { text-align: {$r_align} !important; }";
    }
    
    $out .= "</style>";

    // 3. HTML Output
       // 3. HTML Output
    $out .= "<div class='prosheets-container ps-c-{$a['id']}'><div class='ps-c-{$a['id']}-scroll'><table><thead>";
$out .= "<thead>";

// --- DIRECT MERGE PARSING (No abstraction, exact math) ---
$colspan_map = [];
$skip_cols = [];

if (!empty($merges) && $h_count > 0) {
    foreach ($merges as $m) {
        // Convert absolute sheet indices to relative range indices
        $rel_sr = (int)($m['startRowIndex'] ?? 0) - $bounds['start_row'];
        $rel_er = (int)($m['endRowIndex'] ?? 0) - $bounds['start_row'];
        $rel_sc = (int)($m['startColumnIndex'] ?? 0) - $bounds['start_col'];
        $rel_ec = (int)($m['endColumnIndex'] ?? 0) - $bounds['start_col'];
        
        // Only process merges that start inside header rows and span multiple columns
        if ($rel_sr >= 0 && $rel_sr < $h_count && $rel_ec > $rel_sc) {
            $span = $rel_ec - $rel_sc;
            $colspan_map[$rel_sc] = $span;
            for ($k = $rel_sc + 1; $k < $rel_ec; $k++) {
                $skip_cols[] = $k;
            }
        }
    }
    $skip_cols = array_unique($skip_cols);
    sort($skip_cols);
}

// 🔍 HTML DEBUG (Visible in View Source for admins)
if (current_user_can('manage_options')) {
$out .= "<!-- PS_DEBUG: merges=" . count($merges) . " | raw_merges=" . count($api_response['_raw_merges'] ?? []) . " | colspan_map=" . json_encode($colspan_map) . " -->";}

// --- GENERATE HEADER ROWS ---
for ($i = 0; $i < $h_count && isset($data[$i]); $i++) {
    $out .= "<tr>";
    $row = $data[$i];
    // Ensure loop covers data width OR merge width
    $max_cols = max(count($row), !empty($colspan_map) ? max(array_keys($colspan_map)) + 1 : 0);
    
    for ($col = 0; $col < $max_cols; $col++) {
        if (in_array($col, $skip_cols, true)) continue;
        
        $span = isset($colspan_map[$col]) ? (int)$colspan_map[$col] : 1;
        $cell = isset($row[$col]) ? $row[$col] : '';
        
        // Build header style (keeps your existing admin settings intact)
        $h_style = "text-align:" . ps_v($c, 'h_align', 'center') . ";";
        $h_style .= "vertical-align:" . ps_v($c, 'h_valign', 'top') . ";";
        $h_style .= "font-size:" . ps_v($c, 'h_font', 14) . "px;";
        $h_style .= "color:" . ps_v($c, 'h_txt', '#333') . ";";
        $h_style .= "background-color:" . ps_v($c, 'h_bg', '#f9f9f9') . ";";
        if (!empty($c['h_bold'])) $h_style .= "font-weight:bold;";
        if (!empty($c['h_italic'])) $h_style .= "font-style:italic;";
        if (!empty($c['h_underline'])) $h_style .= "text-decoration:underline;";
        
        $out .= '<th colspan="' . $span . '" style="' . $h_style . '">' . esc_html($cell) . '</th>';
    }
    $out .= "</tr>";
}
$out .= "</thead><tbody>";

    // Generate body rows with color propagation
    for($i=$h_count; $i<($total - $f_count); $i++) { 
        if (!isset($data[$i])) continue; 
        $out .= "<tr>";
        $row = $data[$i];
        $row_colors_arr = isset($row_colors[$i]) ? $row_colors[$i] : [];
        
        $row_bg = '';
        foreach ($row_colors_arr as $c) {
            if (empty($c)) continue;
            if ($c === 'rgb(0,0,0)' || $c === 'rgba(0,0,0,0)') continue;
            if (preg_match('/^rgba\(\d+,\s*\d+,\s*\d+,\s*[\d.]+\)$/', $c) || 
                preg_match('/^rgb\(\d+,\s*\d+,\s*\d+\)$/', $c)) {
                $row_bg = $c;
                break;
            }
        }
        if (empty($row_bg) && !empty($row_colors[$i])) {
            foreach ($row_colors[$i] as $fallback_color) {
                if (!empty($fallback_color) && $fallback_color !== 'rgb(0,0,0)') {
                    $row_bg = $fallback_color;
                    break;
                }
            }
        }

        foreach($row as $col => $cell) {
            $is_frozen = ($col < $l_count) || ($r_count > 0 && $col >= count($row) - $r_count);
            $style_attr = '';
            if (!$is_frozen && !empty($row_bg)) {
                $style_attr = ' style="background-color:' . esc_attr($row_bg) . ' !important;"';
            }
            $out .= '<td' . $style_attr . '>' . nl2br(esc_html($cell)) . '</td>';
        }
        $out .= "</tr>"; 
    }
    $out .= "</tbody><tfoot>";
    
    for($i=max($total - $f_count, $h_count); $i<$total; $i++) { 
        if (!isset($data[$i])) continue; 
        $out .= "<tr>"; 
        foreach($data[$i] as $cell) $out .= "<td>".nl2br(esc_html($cell))."</td>"; 
        $out .= "</tr>"; 
    }
    $out .= "</tfoot></table></div></div>";

    // 4. Hover Highlight CSS
    if (!empty($c['hi_en'])) {
        $hi_bg = trim(ps_v($c, 'hi_bg', '#ffff00'));
        $hi_txt = trim(ps_v($c, 'hi_txt', '#333333'));
        $opacity_percent = intval(ps_v($c, 'hi_opacity', 100));
        $hi_op = number_format($opacity_percent / 100, 2, '.', '');
        $hex = ltrim($hi_bg, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if (strlen($hex) === 6 && ctype_xdigit($hex)) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $out .= "<style>
            .ps-c-{$a['id']}-scroll tbody tr:hover td {
                box-shadow: inset 0 0 0 9999px rgba({$r}, {$g}, {$b}, {$hi_op}) !important;
                color: {$hi_txt} !important;
                " . ps_font_style($c, 'hi') . "
                transition: box-shadow 0.2s ease, color 0.2s ease;
            }
            </style>";
        }
    }

    return $out;
}
